<?php

namespace Core\Auth;

use Core\Database\Database;
use \DateTime;
use ReCaptcha\ReCaptcha;
use Core\FindBrowser\FindBrowser;
use Core\Password\Password;
use Core\RandomLib;

class Auth{
	
	private $db;
	private $mail;
	private $active_account = true;
	private $name_site;
	private $email;
	
	private $factory;
	private $password;
	private $reCaptcha;
	private $browser;
	private $generator;
	
	const COOKIE_NAME = 'auth';
	
	public function __construct(Database $db){
		$this->db = $db;
		$this->password = new Password();
		$this->reCaptcha = new ReCaptcha(CAPTCHA_PRIVATE);
		$this->browser = new FindBrowser();
		$this->factory = new RandomLib\Factory;
        $this->generator = $this->factory->getGenerator(new \Core\SecurityLib\Strength(\Core\SecurityLib\Strength::MEDIUM));
		
		$this->deleteSession('sessions');
		
		$this->mail = new \PHPMailer;
		$this->mail->setLanguage('fr', '../core/PHPMailer/language/');
		$this->mail->CharSet = 'utf-8';
		$this->mail->isHTML(true);
		
		if(defined(DOMAIN))
			$this->email = 'no-reply@' . DOMAIN;
		else
			$this->email = 'no-reply@' . $_SERVER['HTTP_HOST'];
	}
	
	private function isSerialized($str) {
		return ($str == serialize(false) || @unserialize($str) !== false);
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
	
	public function setUserId(){
		if(isset($_COOKIE[self::COOKIE_NAME]) || isset($_SESSION[self::COOKIE_NAME]))
			define('user_id', $this->getUserId());
	}
	
	public function getUserId(){
		if(!empty($_COOKIE[self::COOKIE_NAME]))
			return unserialize($_COOKIE[self::COOKIE_NAME])[0];
		else if(!empty($_SESSION[self::COOKIE_NAME]))
			return unserialize($_SESSION[self::COOKIE_NAME])[0];
	}
	
	private function generateToken($length = 21){
		$this->generator = $this->factory->getMediumStrengthGenerator();
		$token = bin2hex(random_bytes($length)); //$this->generator->generate(21);
		return [$token, $this->encrypt($token)];
	}
	
	private function generateKey($length = 16){
		$this->generator = $this->factory->getMediumStrengthGenerator();
		$key = $this->generator->generateString($length, 'abcdefghijklmnpqrstuvwxyABCDEFGHIJKLMNOPQRSTUVWXYZ123456');
		return [$key, $this->encrypt($key), str_shuffle($key)];
	}
	
	private function encrypt($string){
		return $this->password->password_hash($string, PASSWORD_BCRYPT);
	}
	
	public function decrypt($string, $_string){
		if($this->password->password_verify($string, $_string))
			return true;
	}
	
	private function createSession($user_id, $cookie = false){
		$token = $this->generateToken();
		$selector = $this->generateToken(5)[0];
		$data = $this->getIP() . '::' . $_SERVER['HTTP_USER_AGENT'];
		$expire = time() + 31536000;
		
		if($cookie === true){
			setcookie(self::COOKIE_NAME, serialize([$user_id, $selector, $token[0], $data]), $expire, '/', DOMAIN, 1, 1);
			$session_time = date("Y-m-d H:i:s", $expire);
		}else{
			$_SESSION[self::COOKIE_NAME] = serialize([$user_id, $selector, $token[0], $data]);
			$session_time = date("Y-m-d H:i:s", strtotime('+30 minutes', time()));
		}
		
		$this->db->execute('INSERT INTO ' . PREFIX . 'users_logged(users_logged_selector, users_logged_token, users_logged_user_id, users_logged_expires, users_logged_data) VALUES(?, ?, ?, ?, ?)', [$selector, $token[1], $user_id, $session_time, $data]);
	}
	
	private function updateSession($selector, $cookie = false){
		$token = $this->generateToken();
		$data = $this->getIP() . '::' . $_SERVER['HTTP_USER_AGENT'];
		
		$get_session = $this->db->prepare('SELECT users_logged_expires FROM ' . PREFIX . 'users_logged WHERE users_logged_selector = ?', [$selector], null, true);
		
		if($cookie === true)
			setcookie(self::COOKIE_NAME, serialize([user_id, $selector, $token[0], $data]), strtotime($get_session->users_logged_expires), '/', DOMAIN, 1, 1);
		else{
			$_SESSION[self::COOKIE_NAME] = serialize([user_id, $selector, $token[0], $data]);
			session_regenerate_id();
		}
		
		$this->db->execute('UPDATE ' . PREFIX . 'users_logged SET users_logged_token = ?, users_logged_data = ? WHERE users_logged_selector = ?', [$token[1], $data, $selector]);
	}
	
	public function getSession(){
		if(!empty($_COOKIE[self::COOKIE_NAME]) && $this->isSerialized($_COOKIE[self::COOKIE_NAME])){
			$cookie_content = unserialize($_COOKIE[self::COOKIE_NAME]);
			if(isset($cookie_content[0]) && isset($cookie_content[1]) && isset($cookie_content[2])){
				$cookie = $this->db->prepare('SELECT * FROM ' . PREFIX . 'users_logged WHERE users_logged_user_id = ? AND users_logged_selector = ?', [$cookie_content[0], $cookie_content[1]], null, true);
				if($cookie)
					if($this->decrypt($cookie_content[2], $cookie->users_logged_token))
						return $cookie_content;
			}
		}else if(!empty($_SESSION[self::COOKIE_NAME])){
			$session_content = unserialize($_SESSION[self::COOKIE_NAME]);
			if(isset($session_content[0]) && isset($session_content[1]) && isset($session_content[2])){
				$session = $this->db->prepare('SELECT * FROM ' . PREFIX . 'users_logged WHERE users_logged_user_id = ? AND users_logged_selector = ?', [$session_content[0], $session_content[1]], null, true);
				if($session)
					if($this->decrypt($session_content[2], $session->users_logged_token))
						return $session_content;
			}
		}
	}
	
	public function getCurrentSession(){
		if(!empty($_COOKIE[self::COOKIE_NAME]) && $this->isSerialized($_COOKIE[self::COOKIE_NAME]))
			return unserialize($_COOKIE[self::COOKIE_NAME])[1];
		else if(!empty($_SESSION[self::COOKIE_NAME]))
			return unserialize($_SESSION[self::COOKIE_NAME])[1];
	}
	
	public function deleteSession($what = 'session', $selector = null){
		if($selector != null){
			if($selector == $this->getCurrentSession())
				return false;
			else{
				if($this->db->execute('DELETE FROM ' .PREFIX . 'users_logged WHERE users_logged_selector = ?', [$selector]))
					return true;
				else
					return false;
			}
		}else {
			switch($what){
				case 'cookie':
					if(isset($_COOKIE[self::COOKIE_NAME])){
						if($this->isSerialized($_COOKIE[self::COOKIE_NAME]))
							if(isset(unserialize($_COOKIE[self::COOKIE_NAME])[1]))
								$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE users_logged_selector = ?', [unserialize($_COOKIE[self::COOKIE_NAME])[1]]);

						unset($_COOKIE[self::COOKIE_NAME]);
						setcookie(self::COOKIE_NAME, null, -1, '/', DOMAIN);
					}
					break;
				case 'session':
					if(isset($_SESSION[self::COOKIE_NAME])){
						if($this->isSerialized($_SESSION[self::COOKIE_NAME]))
							if(isset(unserialize($_SESSION[self::COOKIE_NAME])[1]))
								$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE users_logged_selector = ?', [unserialize($_SESSION[self::COOKIE_NAME])[1]]);
						session_destroy();
						session_unset();
						$_SESSION = array();
					}
					break;
				case 'sessions':
					$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE users_logged_expires < ?', [date("Y-m-d H:i:s", time())]);
			}
		}
	}
	
	private function serializeSession($user_id, $selector, $token, $data){
		return $user_id . '::' . $selector . '::' . $token . '::' . $data;
	}
	
	private function unserializeSession($session){
		return explode('::', $session);
	}
	
	public function logged(){
		if(!empty($_COOKIE[self::COOKIE_NAME]) && $this->isSerialized($_COOKIE[self::COOKIE_NAME])){
			$cookie_content = unserialize($_COOKIE[self::COOKIE_NAME]);
			if(isset($cookie_content[0]) && isset($cookie_content[1]) && isset($cookie_content[2])){
				$cookie = $this->db->prepare('SELECT * FROM ' . PREFIX . 'users_logged WHERE users_logged_user_id = ? AND users_logged_selector = ?', [$cookie_content[0], $cookie_content[1]], null, true);
				if($cookie){
					if($this->decrypt($cookie_content[2], $cookie->users_logged_token)){
						$this->setUserId($cookie->users_logged_user_id);
						$this->updateSession($cookie->users_logged_selector, true);
						return true;
					}else {
						$this->logout();
					    return false;
					}
				}else {
					$this->logout();
					return false;
				}
			}else {
				$this->logout();
				return false;
			}
		}else if(!empty($_SESSION[self::COOKIE_NAME])){
			$session_content = unserialize($_SESSION[self::COOKIE_NAME]);
			if(isset($session_content[0]) && isset($session_content[1]) && isset($session_content[2])){
				$session = $this->db->prepare('SELECT * FROM ' . PREFIX . 'users_logged WHERE users_logged_user_id = ? AND users_logged_selector = ?', [$session_content[0], $session_content[1]], null, true);
				if($session){
					if($this->decrypt($session_content[2], $session->users_logged_token)){
						$this->setUserId($session->users_logged_user_id);
						$this->updateSession($session->users_logged_selector);
						return true;
					}
				}else {
					$this->logout();
					return false;
				}
			}else {
				$this->logout();
				return false;
			}
		}else if(!isset($_POST['user_email']) || !isset($_POST['user_password'])) {
			$this->logout();
			return false;
		}
	}
	
	public function login($user_email, $user_password, $remember_me){
		if($user_email != NULL && $user_password != NULL){
			$user = $this->db->prepare('SELECT user_id, user_password, user_key_password, user_account_activate FROM ' . PREFIX . 'users WHERE user_email = :user_email OR user_phone = :user_email', [':user_email' => $user_email], null, true);
			if($user){
				if($this->active_account === true){
					if($user->user_account_activate == 3)
						$error = 'Votre compte est bloqué. Contactez le support pour plus d\'informations.';
					elseif($user->user_account_activate != 1)
					    $error = 'Votre compte n\'est pas activé';
					else{
						if($this->decrypt($user_password, $user->user_password) === true){
							$this->db->execute("UPDATE " . PREFIX . "users SET user_password = ? WHERE user_id = ?", [$this->encrypt($user_password), $user->user_id]);
							$this->createSession($user->user_id, $remember_me);
							$error = true;
						}else
							$error = 'Identifiants incorrects';
					}
				}
			}else
				$error = 'Identifiants incorrects';
		}else
			$error = 'Veuillez remplir les champs requis';
		
		return $error;
	}
	
	public function logout(){
		if(isset($_COOKIE[self::COOKIE_NAME]))
			$this->deleteSession('cookie');
		if(isset($_SESSION[self::COOKIE_NAME]))
		    $this->deleteSession();
	}
	
	public function register($user_email, $user_password, $repeat_password, $captcha, $user_phone = null){
		$phone_add = true;
		if($user_phone != null)
			if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "users WHERE user_phone = ?", [$user_phone]) > 0)
				$phone_add = false;
		
		if(!empty($user_email)) {
			$user_email = trim(strtolower($user_email));
			
			if(filter_var($user_email, FILTER_VALIDATE_EMAIL) == true){
				if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "users WHERE user_email = ?", [$user_email]) == 0) {
					if($phone_add === true){
						if(!empty($user_password)){
							if(isset($repeat_password) && $repeat_password == $user_password) {
								if(strlen($user_password) >= 8){
									$user_password = $this->encrypt($user_password);
									if(isset($captcha)) {
										$resp = $this->reCaptcha->verify($captcha, $_SERVER["REMOTE_ADDR"]);
										if($resp->isSuccess()){
											if(function_exists('mail')){
												if($this->active_account === true){
													$activation_key = $this->generateKey();
													$template = '_with_validation';
												}
												
												ob_start();
												include(ROOT . "/app/Views/templates/mails/create_account{$template}.php");
												$body = ob_get_clean();
												
												$this->mail->setFrom($this->email, $this->name_site);
												$this->mail->Subject = $this->name_site . ' - Validation de votre compte';
												$this->mail->addAddress($user_email);
												$this->mail->Body = $body;
												
										        if($this->mail->send()){
													$date = date("Y-m-d H:i:s", time());
													$this->db->execute('INSERT INTO ' . PREFIX . 'users(user_email, user_password, user_date_create, user_key, user_phone, user_account_activate, user_level) VALUES (?, ?, ?, ?, ?, ?, 1)', [$user_email, $user_password, $date, $activation_key[1], $user_phone, $activation_key[2]]);
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
				if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users WHERE user_email = ?', [$user_email]) > 0){
					if(isset($captcha)){
						$resp = $this->reCaptcha->verify($captcha, $_SERVER["REMOTE_ADDR"]);
						if($resp->isSuccess()){
							if(function_exists('mail')){
								$key = $this->generateKey(10);
						
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
									$this->db->execute('UPDATE ' . PREFIX . 'users SET user_reset = ? WHERE user_email = ?', [$key[2], $user_email]);
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
	
	public function activeAccount($key = null){
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
		}else
			$error = 'Erreur ! l\'activation des comptes n\'est pas disponible ! Contactez le support.';
		
		return $error;
	}
	
	public function resetKey($user_id, $admin = false, $force = false){
		$key = $this->generateKey();
		$date = date("Y-m-d H:i:s", strtotime("+20 days"));
		
		$user = $this->db->prepare("SELECT user_key, user_key_date, user_email FROM " . PREFIX . "users WHERE user_id = ?", [$user_id], null, true);
		if($user->user_key_date == null || $user->user_key_date <= date("Y-m-d H:i:s") || $force === true){
			$this->db->execute("UPDATE " . PREFIX . "users SET user_key = :user_key, user_key_date = :user_key_date WHERE user_id = :user_id", [':user_key' => $key[1], ':user_key_date' => $date, ':user_id' => $user_id]);
			$error = true;
		}else {
			$user_key_date = date("Y-m-d H:i:s", strtotime($user->user_key_date));
			$current_date = date("Y-m-d H:i:s");
			$user_key_date = new DateTime($user_key_date);
			$current_date = new DateTime($current_date);
			$diff_date = $user_key_date->diff($current_date);
			$error = "Vous devez attendre encore {$diff_date->d} jours pour pouvoir réinitialiser " . ($admin === true ? 'la' : 'votre') . " clé de sécurité.";
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
	
	public function update($user_email, $user_phone){
		if(isset($user_email) && $user_email != null && filter_var($user_email, FILTER_VALIDATE_EMAIL) == true){
			if(empty($user_phone))
				$count = $this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users WHERE user_id NOT IN (?) AND user_email = ?', [user_id, $user_email]);
			else
				$count = $this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users WHERE user_id NOT IN (?) AND (user_email = ? OR user_phone = ?)', [user_id, $user_email, $user_phone]);
			
			if(ctype_digit($user_phone) || empty($user_phone)){
				if(strlen($user_phone) == 10 || empty($user_phone)){
					if($count == 0){
						$this->db->execute('UPDATE ' . PREFIX . 'users SET user_email = :user_email, user_phone = :user_phone WHERE user_id = :user_id', [':user_email' => $user_email, ':user_phone' => $user_phone, ':user_id' => user_id], null, true);
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
	
	public function changePassword($password, $new_password, $repeat_password, $secure_key){
		if(isset($password) && $password != null && isset($new_password) && $new_password != null && isset($repeat_password) && $repeat_password != null && isset($secure_key) && $secure_key != null){
			if($repeat_password == $new_password) {
				$length = strlen($new_password);
				if($length >= 8){
					$data = $this->db->prepare('SELECT user_id, user_key, user_password FROM ' . PREFIX . 'users WHERE user_id = ?', [user_id], null, true);
					if($this->decrypt($password, $data->user_password) === true) {
						if($this->decrypt($secure_key, $data->user_key) === true){
							$new_password = $this->encrypt($new_password);
							$this->db->execute('UPDATE ' . PREFIX . 'users SET user_password = ? WHERE user_id = ?', [$new_password, user_id]);
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
