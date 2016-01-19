<?php

namespace Core\Auth;

use Core\Database\Database;
use \DateTime;
use ReCaptcha\ReCaptcha;

class DBAuth{
	
	private $db;
	private $cookie_name = 'auth';
	private $reCaptcha;
	private $mail;
	private $token;
	private $active_account = true;
	private $name_site;
	
	public function __construct(Database $db){
		$this->db = $db;
		$this->reCaptcha = new ReCaptcha(CAPTCHA_PRIVATE);
		
		$this->mail = new \PHPMailer;
		$this->mail->setLanguage('fr', '../core/PHPMailer/language/');
		$this->mail->CharSet = 'utf-8';
		$this->mail->isHTML(true);
		
		$this->token = uniqid();
		
		if(defined(DOMAIN))
			$this->mail = 'no-reply@' . DOMAIN;
		else
			$this->mail = 'no-reply@' . $_SERVER['HTTP_HOST'];
	}
	
	public function setMail($value = null){
		//si vous souhaitez definir le mail via vore bdd, faite appel à cette fonction
		if(!is_null($value)) $this->mail = $value;
	}
	
	public function setNameSite($value){
		$this->name_site = $value;
	}
	
	public function setActiveAccountOption($value = true){
		if(is_bool($value))
		    $this->active_account == $value;
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
			if($user) {
				$continue = false;
				if($this->active_account === true){
					if($user->user_account_activate != 1)
						$error = 'Votre compte n\'est pas activé';
					else
						$continue = true;
				}
				if($continue === true){
					if($this->decode($user_password, $user->user_password) === true) {
						session_regenerate_id();
						$_SESSION[$this->cookie_name] = $this->serializeToken($user->user_id, $this->encode($this->token));
						if($remember_me === true)
							$this->createNewCookie($user->user_id);
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
		$user_id = (isset($_COOKIE[$this->cookie_name]) && $_COOKIE[$this->cookie_name] != NULL) ? $_COOKIE[$this->cookie_name] : $user_id = $_SESSION[$this->cookie_name];
		$_SESSION = array();
		session_unset();
		session_destroy();
		if(isset($_COOKIE[$this->cookie_name]))
			$this->deleteCookie();
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
													include("../app/Views/mails/create_account_with_validation.php");
													$body = ob_get_clean();
													
													$this->mail->setFrom($this->mail, $this->name_site);
													$this->mail->Subject = $this->name_site . ' - Validation de votre compte';
													$this->mail->addAddress($user_email);
												    $this->mail->Body = $body;
												}else {
													ob_start();
													include("../app/Views/mails/create_account.php");
													$body = ob_get_clean();
													
													$this->mail->setFrom($this->mail, $this->name_site);
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
								include("../app/Views/mails/reset_password.php");
								$body = ob_get_clean();
								
								$this->mail->setFrom($this->mail, $this->name_site);
								$this->mail->addAddress($user_email);

								$this->mail->Subject = $this->name_site . ' - Rénitialisation de votre mot de passe';
								$this->mail->Body = $body;
								
								if(!$this->mail->send())
									$error = "Erreur technique ! Insription impossible.<br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1. - " . $this->mail->ErrorInfo;
								else {
									$this->db->execute('UPDATE ' . PREFIX . 'users SET user_reset = ? WHERE user_email = ?', [$key[0], $user_email]);
									$error = true;
								}
							}else
								$error = "Erreur technique ! Insription impossible.<br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1.";
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
			$this->mail->setFrom($this->mail, $this->name_site);
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
			$cookie_content = $this->deserializeToken($_COOKIE[$this->cookie_name]);
		    $cookie = $this->db->prepare('SELECT id FROM ' . PREFIX . 'users_logged WHERE id = ? AND token = ?', [$cookie_content[0], $cookie_content[1]], null, true);
			if($cookie){
				$this->createNewCookie($cookie->id);
			    return true;
			}
		}else if(!empty($_SESSION[$this->cookie_name]))
			return true;
	}
	
	public function getUserId(){
		if(!empty($_COOKIE[$this->cookie_name])){
			$cookie = $this->deserializeToken($_COOKIE[$this->cookie_name]);
			
			return $cookie[0];
		}else
			return $this->deserializeToken($_SESSION[$this->cookie_name])[0];
	}
	
	public function setUserId(){
		if(!empty($_COOKIE[$this->cookie_name])){
			$cookie = $this->deserializeToken($_COOKIE[$this->cookie_name]);
			
			define('user_id', $cookie[0]);
		}else
			define('user_id', $this->deserializeToken($_SESSION[$this->cookie_name])[0]);
	}
	
	private function createNewCookie($id = null){
		if($id != null && ctype_digit($id)) {
			$token = $this->encode($this->token);
			setcookie($this->cookie_name, $this->serializeToken($id, $token), time() + 31536000, '/', DOMAIN, false, true); //expire in one year
			$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ?', [$id]);
			$this->db->execute('INSERT INTO ' . PREFIX . 'users_logged(id, token) VALUES(?, ?)', [$id, $token]);
		}
	}
	
	private function deleteCookie(){
		$cookie_content = $this->deserializeToken($_COOKIE[$this->cookie_name]);
		extract($cookie_content);
		unset($_COOKIE[$this->cookie_name]);
		setcookie($this->cookie_name, null, -1, '/', DOMAIN);
		
		$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE id = ?', [$id]);
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
