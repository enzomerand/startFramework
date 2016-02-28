<?php

namespace Core\Auth;

use Core\Database\Database;
use \DateTime;
use ReCaptcha\ReCaptcha;
use Core\FindBrowser\FindBrowser;

class DBAuth{
	
	private $db;
	private $cookie_name = 'auth';
	private $reCaptcha;
	private $mail;
	private $token;
	private $active_account = true;
	private $name_site;
	private $email;
	private $browser;
	
	public function __construct(Database $db){
		$this->db = $db;
		$this->reCaptcha = new ReCaptcha(CAPTCHA_PRIVATE);
		$this->browser = new FindBrowser();
		
		$this->mail = new \PHPMailer;
		$this->mail->setLanguage('fr', '../core/PHPMailer/language/');
		$this->mail->CharSet = 'utf-8';
		$this->mail->isHTML(true);
		
		$this->token = uniqid();
		
		$this->deleteSessions();
		
		if(defined(DOMAIN))
			$this->email = 'no-reply@' . DOMAIN;
		else
			$this->email = 'no-reply@' . $_SERVER['HTTP_HOST'];
	}
	
	public function setMail($value = null){
		//si vous souhaitez definir le mail via vore bdd, faite appel à cette fonction
		if(!is_null($value)) $this->email = $value;
	}
	
	public function setNameSite($value){
		$this->name_site = $value;
	}
	
	public function setActiveAccountOption($value = true){
		if(is_bool($value))
		    $this->active_account == $value;
	}
	
	final private function getIP(){
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if(filter_var($client, FILTER_VALIDATE_IP))
			$ip = $client;
		elseif(filter_var($forward, FILTER_VALIDATE_IP))
			$ip = $forward;
		else
			$ip = $remote;

		return $ip;
    }
	
	private function encode($value){
		$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
		$salt = sprintf("$2a$%02d$", 10) . $salt;
		$key = crypt($value, $salt);
		
		return $key;
	}
	
	private function verify($password, $existingHash) {
		$hash = crypt($password, $existingHash);
		if($hash === $existingHash)
			return true;
		else
			return false;
	}
	
	public function decode($value, $exist_value){
		if(hash_equals($exist_value, crypt($value, $exist_value)))
            return true;
	}
	
	private function genUserKey() {
		$chars = "abcdefghijklmnpqrstuvwxyABCDEFGHIJKLMNOPQRSTUVWXYZ123456";
		$key = "";
		for($i = 0;$i < 23;$i++) 
			$key .= substr($chars,rand()%(strlen($chars)),1);
		return [$key, $this->encode($key), str_shuffle($key)];
	}
	
	public function login($user_email, $user_password, $remember_me){
		if($user_email != NULL && $user_password != NULL) {
			$user = $this->db->prepare('SELECT user_id, user_password, user_account_activate FROM ' . PREFIX . 'users WHERE user_email = :user_email OR user_phone = :user_email', [':user_email' => $user_email], null, true);
			if($user){
				$continue = false;
				if($this->active_account === true){
					if($user->user_account_activate != 1)
						$error = 'Votre compte n\'est pas activé';
					else
						$continue = true;
				}
				if($continue === true){
					if($this->decode($user_password, $user->user_password) === true){
						if($remember_me === true){
							$this->createNewCookie($user->user_id);
						    //$this->setUserId(true);
					    }else
							$this->createNewSession($user->user_id);
						$error = true;
					}else
						$error = 'Identifiants incorrects';
				}
			}else
				$error = 'Identifiants incorrects';
		}else
			$error = 'Veuillez remplir les champs requis';
		
		return $error;
	}
	
	public function logout(){
		if(isset($_COOKIE[$this->cookie_name]))
			$this->delete('cookie');
		if(isset($_SESSION[$this->cookie_name]))
		    $this->delete('session');
	    session_unset();
		session_destroy();
	}
	
	public function register($user_email, $user_password, $repeat_password, $captcha, $user_phone = null){
		if($user_phone != null){
			if($this->db->count("SELECT user_id FROM " . PREFIX . "users WHERE user_phone = '{$user_phone}'") == 0)
				$phone_add = true;
			else
				$phone_add = false;
		}else
			$phone_add = true;
		
		if(!empty($user_email)) {
			$user_email = trim(strtolower($user_email));
			
			if(filter_var($user_email, FILTER_VALIDATE_EMAIL) == true){
				if($this->db->count("SELECT user_id FROM " . PREFIX . "users WHERE user_email = ?", [$user_email]) == 0) {
					if($phone_add === true){
						if(!empty($user_password)){
							if(isset($repeat_password) && $repeat_password == $user_password) {
								if(strlen($user_password) >= 8){
									$user_password = $this->encode($user_password);
									if(isset($captcha)) {
										$resp = $this->reCaptcha->verify($captcha, $_SERVER["REMOTE_ADDR"]);
										if($resp->isSuccess()){
											if(function_exists('mail')){
												
												if($this->active_account === true){
													$key = $this->genUserKey();
										            
													ob_start();
													include(ROOT . "/app/Views/templates/mails/create_account_with_validation.php");
													$body = ob_get_clean();
													
													$this->mail->setFrom($this->email, $this->name_site);
													$this->mail->Subject = $this->name_site . ' - Validation de votre compte';
													$this->mail->addAddress($user_email);
												    $this->mail->Body = $body;
												}else {
													ob_start();
													include(ROOT . "/app/Views/templates/mails/create_account.php");
													$body = ob_get_clean();
													
													$this->mail->setFrom($this->email, $this->name_site);
													$this->mail->Subject = $this->name_site . ' - Validation de votre compte';
													$this->mail->addAddress($user_email);
												    $this->mail->Body = $body;
													
													$key[2] == 1;
												}
												
										        if($this->mail->send()){
													$date = date("Y-m-d H:i:s", time());
													$this->db->execute('INSERT INTO ' . PREFIX . 'users(user_email, user_password, user_date_create, user_key, user_phone, user_account_activate, user_level) VALUES (?, ?, ?, ?, ?, ?, ?)', [$user_email, $user_password, $date, $key[1], $user_phone, $key[2], 1]);
												    $error = true;
												}else
													$error = "Erreur technique ! Insription impossible.<br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 3.";
											}else
												$error = "Erreur technique ! Insription impossible.<br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1.";
										}else
											$error = "CAPTCHA incorrect";
									}else
										$error = "CAPTCHA incorrect";
								}else
									$error = "Votre mot de passe doit faire au moins 8 caractères";
							}else
								$error = "Les mots de passe ne correspondent pas";
						}else
							$error = "Veuillez choisir un mot de passe";
					}else
						$error = "Le numéro de téléphone est déjà utilisé";
				}else
					$error = "L'adresse email est déjà utilisée";
			}else
				$error = "L'adresse email choisie est incorrecte";
		}else
			$error = "Veuillez saisir votre email";
		
		return $error;
	}
	
	public function forgot($user_email, $captcha){
		if(!empty($user_email)) {
			$user_email = trim(strtolower($user_email));
			
			if(filter_var($user_email, FILTER_VALIDATE_EMAIL) == true){
				$count = $this->db->count('SELECT * FROM ' . PREFIX . 'users WHERE user_email = ?', [$user_email]);
				if($count > 0){
					if(isset($captcha)){
						$resp = $this->reCaptcha->verify($captcha, $_SERVER["REMOTE_ADDR"]);
						if($resp->isSuccess()){
							if(function_exists('mail')){
								$key = $this->genUserKey();
						
								ob_start();
								include(ROOT . "/app/Views/templates/mails/reset_password.php");
								$body = ob_get_clean();
								
								$this->mail->setFrom($this->email, $this->name_site);
								$this->mail->addAddress($user_email);

								$this->mail->Subject = $this->name_site . ' - Rénitialisation de votre mot de passe';
								$this->mail->Body = $body;
								
								if(!$this->mail->send())
									$error = "Erreur technique ! <br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1. - " . $this->mail->ErrorInfo;
								else {
									$this->db->execute('UPDATE ' . PREFIX . 'users SET user_reset = ? WHERE user_email = ?', [$key[0], $user_email]);
									$error = true;
								}
							}else
								$error = "Erreur technique ! <br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1.";
						}else
							$error = "CAPTCHA incorrect";
					}else
						$error = "CAPTCHA incorrect";
				}else
					$error = "L'adresse email choisie n'existe pas";
			}else
				$error = "L'adresse email choisie est incorrecte";
		}else
			$error = "Veuillez saisir votre email";
		
		return $error;
	}
	
	public function active_account($key = null){
		if($this->active_account === true){
			if(!empty($key)){
				$key = explode('-', $key);
				$email = urldecode($key[0]);
				$key = !empty($key[1]) ? $key[1] : null;
				
				if($key != null){
					$data = $this->db->prepare("SELECT user_account_activate FROM " . PREFIX . "users WHERE user_email = ?", [$email], null, true);
					if($data->user_account_activate == $key){
						$this->db->execute("UPDATE " . PREFIX . "users SET user_account_activate = ? WHERE user_email = ?", [1, $email]);
						$error = true;
					}else
						$error = 'La clé n\'est pas valide.';
				}else
					$error = 'La clé n\'est pas valide.';
			}else
				$error = 'Veuillez saisir une clé.';
			
			return $error;
		}
	}
	
	public function resetKey($user_id){
		$key = $this->genUserKey();
		$date = date("Y-m-d H:i:s", strtotime("+20 days"));
		
		$user = $this->db->prepare("SELECT user_key, user_key_date, user_email FROM " . PREFIX . "users WHERE user_id = ?", [$user_id], null, true);
		if($user->user_key_date == null){
			$this->db->execute("UPDATE " . PREFIX . "users SET user_key = :user_key, user_key_date = :user_key_date WHERE user_id = :user_id", [':user_key' => $key[1], ':user_key_date' => $date, ':user_id' => $user_id]);
		    $error = true;
		}elseif($user->user_key_date <= date("Y-m-d H:i:s")){
			$this->db->execute("UPDATE " . PREFIX . "users SET user_key = :user_key, user_key_date = :user_key_date WHERE user_id = :user_id", [':user_key' => $key[1], ':user_key_date' => $date, ':user_id' => $user_id]);
			$error = true;
		}else {
			$user_key_date = date("Y-m-d H:i:s", strtotime($user->user_key_date));
			$current_date = date("Y-m-d H:i:s");
			$user_key_date = new DateTime($user_key_date);
			$current_date = new DateTime($current_date);
			$diff_date = $user_key_date->diff($current_date);
			$error = "Vous devez attendre encore {$diff_date->d} jours pour pouvoir réinitialiser votre clé de sécurité.";
		}
		
		if($error === true){
			ob_start();
			include(ROOT . "/app/Views/templates/mails/reset_key.php");
			$body = ob_get_clean();
								
			$this->mail->setFrom($this->email, $this->name_site);
			$this->mail->Subject = $this->name_site . ' - Réinitialisation de votre clé de sécurité';
			$this->mail->addAddress($user->user_email); 
            $this->mail->Body = $body;
			
			if(!$this->mail->send())
				$error = "Erreur technique ! Insription impossible.<br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1. - " . $this->mail->ErrorInfo;
		}
		
		return $error;
	}
	
	public function logged(){
		if(!empty($_COOKIE[$this->cookie_name])){
			$cookie = $this->deserializeToken($_COOKIE[$this->cookie_name]);
		    $connected = $this->db->prepare('SELECT id, token FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ? AND user_agent = ? AND ip = ?', [$cookie[0], $cookie[1], $_SERVER['HTTP_USER_AGENT'], $this->getIP()], null, true);
			if($connected){
				$this->createNewCookie($connected->id, $connected->token);
			    return true;
			}else
				return false;
		}else if(!empty($_SESSION[$this->cookie_name])){
			$session_content = $this->deserializeToken($_SESSION[$this->cookie_name]);
			$session = $this->db->prepare('SELECT id, token FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ? AND user_agent = ? AND ip = ? AND cookie = 0', [$session_content[0], $session_content[1], $_SERVER['HTTP_USER_AGENT'], $this->getIP()], null, true);
		    if($session){
				$this->createNewSession($session->id, $session->token);
				session_regenerate_id();
			    return true;
			}else 
				return false;
		}else if(!isset($_POST['user_email']) || !isset($_POST['user_password'])) {
			$this->logout();
			return false;
		}
	}
	
	public function getUserId(){
		if(!empty($_COOKIE[$this->cookie_name]))
			return $this->deserializeToken($_COOKIE[$this->cookie_name])[0];
		else if(!empty($_SESSION[$this->cookie_name]))
			return $this->deserializeToken($_SESSION[$this->cookie_name])[0];
	}
	
	public function setUserId(){
		if(isset($_COOKIE[$this->cookie_name]) || isset($_SESSION[$this->cookie_name]))
			define('user_id', $this->getUserId());
	}
	
	private function createNewCookie($id, $old_token = null){
		if($id != null && ctype_digit($id)){
			if($old_token)
				$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ? AND user_agent = ? AND ip = ?', [$id, $old_token, $_SERVER['HTTP_USER_AGENT'], $this->getIP()]);
			$token = $this->encode($this->token);
			setcookie($this->cookie_name, $this->serializeToken($id, $token), time() + 31536000, '/', DOMAIN, false, true);
			$this->db->execute('INSERT INTO ' . PREFIX . 'users_logged(id, token, user_agent, ip) VALUES(?, ?, ?, ?)', [$id, $token, $_SERVER['HTTP_USER_AGENT'], $this->getIP()]);
		}
	}
	
	private function createNewSession($id, $old_token = null){
		if($id != null && ctype_digit($id)){
		    if($old_token)
				$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ? AND user_agent = ? AND ip = ? AND cookie = 0', [$id, $old_token, $_SERVER['HTTP_USER_AGENT'], $this->getIP(), ]);
			$token = $this->encode($this->token);
			$_SESSION[$this->cookie_name] = $this->serializeToken($id, $token);
			$session_time = date("Y-m-d H:i:s", strtotime('+30 minutes', time()));
			$this->db->execute('INSERT INTO ' . PREFIX . 'users_logged(id, token, user_agent, ip, time, cookie) VALUES(?, ?, ?, ?, ?, 0)', [$id, $token, $_SERVER['HTTP_USER_AGENT'], $this->getIP(), $session_time]);
		}
	}
	
	public function delete($what, $params = []){
		switch($what){
			case 'cookie':
			    if(isset($params['delete_id']))
				    if($this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ? AND delete_id = ?', [user_id, $params['delete_id']]))
						return true;
				else{
					if($this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ? AND user_agent = ? AND ip = ?', [user_id,  $this->deserializeToken($_COOKIE[$this->cookie_name])[1], $_SERVER['HTTP_USER_AGENT'], $this->getIP()])){
						setcookie($this->cookie_name, null, -1, '/', DOMAIN);
						return true;
					}
				}
				break;
			case 'session':
			    if(isset($params['delete_id']))
					if($this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ? AND delete_id = ? AND cookie = 0', [user_id, $params['delete_id']]))
						return true;
				else{
					if($this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ? AND user_agent = ? AND ip = ? AND cookie = 0', [user_id,  $this->deserializeToken($_SESSION[$this->cookie_name])[1], $_SERVER['HTTP_USER_AGENT'], $this->getIP()])){
						$_SESSION = array();
						return true;
					}
				}
				break;
			case 'sessions':
			    $hello = true;
		}
	}
	
	private function deleteSessions(){
		// $current_date = date("Y-m-d H:i:s", time());
		// if($this->db->execute('DELETE FROM' . PREFIX . 'users_logged WHERE cookie = 0 AND (time = :time OR time > :time)', ['time' => $current_date]))
			// return true;
	}
	
	private function serializeToken($id, $token){
		$cookie = (($id * 7) / 4) . ':' . $token;
		
		return $cookie;
	}

	private function deserializeToken($token){
		$encoded_cookie = explode(':', $token);
		
	    $cookie[] = ($encoded_cookie[0] * 4) / 7;
		$cookie[] = $encoded_cookie[1];
		
		return $cookie;
	}
	
	public function update($user_email, $user_phone, $user_id){
		if(isset($user_email) && $user_email != null && filter_var($user_email, FILTER_VALIDATE_EMAIL) == true && isset($user_id) && $user_id != null){
			if(empty($user_phone))
				$count = $this->db->count('SELECT user_id FROM ' . PREFIX . 'users WHERE user_id NOT IN (?) AND user_email = ?', [$user_id, $user_email]);
			else
				$count = $this->db->count('SELECT user_id FROM ' . PREFIX . 'users WHERE user_id NOT IN (:user_id) AND (user_email = :user_email OR user_phone = :user_phone)', [':user_id' => $user_id, ':user_email' => $user_email, ':user_phone' => $user_phone]);
			
			if(ctype_digit($user_phone) || empty($user_phone)){
				if(strlen($user_phone) == 10 || empty($user_phone)){
					if($count == 0){
						$this->db->execute('UPDATE ' . PREFIX . 'users SET user_email = :user_email, user_phone = :user_phone WHERE user_id = :user_id', [':user_email' => $user_email, ':user_phone' => $user_phone, ':user_id' => $user_id], null, true);
						$error = true;
					}else
						$error = 'Le numéro de téléphone ou l\'adresse email existe déjà !';
				}else
					$error = 'Le numéro de téléphone doit faire au moins dix chiffres.';
		    }else
				$error = 'Le numéro de téléphone ne doit comporter que des chiffres.';
		}else
			$error = 'Veuillez remplir tous les champs requis';
		
		return $error;
	}
	
	public function deleteAccount($password, $secure_key, $user_id){
		if(isset($password) && $password != null && isset($secure_key) && $secure_key != null){
			$data = $this->db->prepare('SELECT user_id, user_key, user_password FROM ' . PREFIX . 'users WHERE user_id = ?', [$user_id], null, true);
			if($this->decode($password, $data->user_password) === true) {
				if($this->decode($secure_key, $data->user_key) === true){
					$this->db->execute('DELETE FROM ' . PREFIX . 'users WHERE user_id = ?', [$user_id]);
					$error = true;
				}else
					$error = "La clé de sécurité est invalide.";
			}else
				$error = "Mot de passe incorrect.";
		}else
			$error = 'Veuillez remplir tous les champs.';
		
		return $error;
	}
	
	public function changePassword($password, $new_password, $repeat_password, $secure_key, $user_id){
		if(isset($password) && $password != null && isset($new_password) && $new_password != null && isset($repeat_password) && $repeat_password != null && isset($secure_key) && $secure_key != null){
			if($repeat_password == $new_password) {
				$length = strlen($new_password);
				if($length >= 8){
					$data = $this->db->prepare('SELECT user_id, user_key, user_password FROM ' . PREFIX . 'users WHERE user_id = ?', [$user_id], null, true);
					if($this->decode($password, $data->user_password) === true) {
						if($this->decode($secure_key, $data->user_key) === true){
							$new_password = $this->encode($new_password);
							$this->db->execute('UPDATE ' . PREFIX . 'users SET user_password = ? WHERE user_id = ?', [$new_password, $user_id]);
							$error = true;
						}else
							$error = "La clé de sécurité est invalide.";
					}else
						$error = "Mot de passe actuel incorrect.";
				}else
					$error = "Votre mot de passe doit faire au moins 8 caractères.";
			} else
				$error = "Les mots de passe ne correspondent pas.";
		}else
			$error = 'Veuillez remplir tous les champs.';
		
		return $error;
	}
}
