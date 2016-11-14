<?php
/**
 * Auth Class
 */

namespace Core\Auth;

use Core\Database\Database;
use Core\FindBrowser\FindBrowser;
use Core\Password\Password;
use Core\RandomLib;
use Core\Config;
use \DateTime;
use ReCaptcha\ReCaptcha;

/**
 * Auth
 * Cette classe permet de créer un système de connexion et d'inscription.
 *
 * @package startFramework\Core\Auth
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Auth{

    /**
     * Activer la vérification reCaptcha globale (hors inscription et fail login)
     *
     * @var bool
     */
    public $login_captcha = false;

    /**
     * Permet l'envoi de mails
     *
     * @var \PHPMailer
     */
    private $mail;

    /**
     * Email utilisé pour l'envoi des mails
     *
     * @var string
     */
    private $email;

    /**
     * Permet de générer des chaînes aléatoires
     *
     * @var object
     */
    private $generator;

    /**
     * Permet de crypter une chaîne de caractère
     *
     * @var Password
     */
    private $password;

    /**
     * Permet l'utilisation du reCaptcha
     *
     * @var ReCaptcha
     */
    private $reCaptcha;

    /**
     * Contient les informations du naviguateur actuel
     *
     * @var FindBrowser
     */
    private $browser;

    /**
     * Sous-domaine(s) où sont créés les cookies/sessions
     *
     * @var array
     */
    private $subdomains = ['', 'manage.', 'label.'];

    /**
     * Permet d'effectuer des requêtes sur la bdd
     *
     * @var Database
     */
    protected $db;

    /**
     * Contient la configuration du site
     *
     * @var Config
     */
    protected $config;

    /**
     * Nom du cookie et de la session
     *
     * @var string
     */
	const COOKIE_NAME = 'auth';

    /**
     * Initialisation des plugins et définition des paramètres
     *
     * @param Database $db Permet d'effectuer des requêtes sur la bdd
     */
	public function __construct(Database $db){
		$this->db = $db;
        $this->config = Config::getInstance(ROOT . '/app/config.php');

		$factory         = new RandomLib\Factory;
        $this->generator = $factory->getGenerator(new \Core\SecurityLib\Strength(\Core\SecurityLib\Strength::MEDIUM));
        $this->generator = $factory->getMediumStrengthGenerator();
        $this->password  = new Password();

        $this->reCaptcha = new ReCaptcha(CAPTCHA_PRIVATE);
        $this->browser   = new FindBrowser();

		$this->deleteSession('sessions');

		$this->mail = new \PHPMailer;
		$this->mail->setLanguage('fr', '../core/PHPMailer/language/');
		$this->mail->CharSet = 'utf-8';
		$this->mail->isHTML(true);

		if(defined(SUBDOMAIN)) $this->email = 'no-reply@' . SUBDOMAIN;
		else $this->email = 'no-reply@' . $_SERVER['HTTP_HOST'];
	}

    /**
     * Vérifie si le site utilise le protocol HTTPS
     *
     * @return bool
     */
	private function isSecure(){
		if(isset($_SERVER['HTTPS']) == 'on')
            return 1;
		else
            return 0;
	}

    /**
     * Vérifie qu'une chaîne est sérialisée
     *
     * @param  string $str Chaîne de caractère à vérifier
     * @return bool
     */
	private function isSerialized($str) {
		return ($str == serialize(false) || unserialize($str) !== false);
	}

    /**
     * Permet de récupérer l'IP de l'utilisateur actuel
     *
     * @return string
     */
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

    /**
     * Permet de définir l'id de l'utilisateur globalement
     *
     * @param int|null $user_id Permet de choisir manuellement l'id à définir
     */
	public function setUserId($user_id = null){
		if($user_id != null && ctype_digit($user_id))
			define('user_id', $user_id);
		elseif(isset($_COOKIE[self::COOKIE_NAME]) || isset($_SESSION[self::COOKIE_NAME]))
			define('user_id', $this->getUserId());
	}

    /**
     * Permet de récupérer l'id dynamique de l'utilisateur (via les cookies ou la session)
     *
     * @see    constant user_id
     * @return int
     */
	public function getUserId(){
		if(!empty($_COOKIE[self::COOKIE_NAME]))
			return unserialize($_COOKIE[self::COOKIE_NAME])[0];
		else if(!empty($_SESSION[self::COOKIE_NAME]))
			return unserialize($_SESSION[self::COOKIE_NAME])[0];
	}

    /**
     * Permet de généer un token aléatoire
     *
     * @param  int   $length Nombre de caractères à générer
     * @return array         Contient le token en clair et crypté
     */
	private function generateToken($length = 21){
		$token = bin2hex(random_bytes($length));

		return [$token, $this->encrypt($token)];
	}

    /**
     * Permet de générer une clé, uniquement composée de lettres
     *
     * @param  int    $length Nombre de caractères à générer
     * @return string         Retourne la clé en clair, cryptée et une variante
     */
	private function generateKey($length = 16){
		$key = $this->generator->generateString($length, 'abcdefghijklmnpqrstuvwxyABCDEFGHIJKLMNOPQRSTUVWXYZ123456');

		return [$key, $this->encrypt($key), str_shuffle($key)];
	}

    /**
     * Permet de crypter une chaîne de caractère, nottament pour les mdp
     *
     * @param  string $string Chaîne de caractère à crypter
     * @return string
     */
	private function encrypt($string){
		return $this->password->password_hash($string, PASSWORD_BCRYPT);
	}

    /**
     * Permet de décrypter une chaîne de caractères
     *
     * @param  string  $string  Chaîne de caractère en clair
     * @param  string  $_string Chaîne de caractère cryptée
     * @return bool
     */
	public function decrypt($string, $_string){
		if($this->password->password_verify($string, $_string))
			return true;
	}

    /**
     * Permet de créer une session ou un cookie pour la connexion
     *
     * @param  int  $user_id Id de l'utilisateur
     * @param  bool $cookie  Créer un cookie au lieu d'une session
     */
	protected function createSession($user_id, $cookie = false){
		$token = $this->generateToken();
		$selector = $this->generateToken(5)[0];
		$data = $this->getIP() . '::' . $_SERVER['HTTP_USER_AGENT'];
		$expire = time() + 31536000;

		if($cookie === true){
			setcookie(self::COOKIE_NAME, serialize([$user_id, $selector, $token[0], $data]), $expire, '/', '.' . SUBDOMAIN, $this->isSecure(), 1);
            $session_time = date("Y-m-d H:i:s", $expire);
		}else{
  		    $_SESSION[self::COOKIE_NAME] = serialize([$user_id, $selector, $token[0], $data]);
			$session_time = date("Y-m-d H:i:s", strtotime('+30 minutes', time()));
  	    }

		$this->db->execute('INSERT INTO ' . PREFIX . 'users_logged(users_logged_selector, users_logged_token, users_logged_user_id, users_logged_expires, users_logged_data) VALUES(?, ?, ?, ?, ?)', [$selector, $token[1], $user_id, $session_time, $data], true); // Utilise les transactions, moteur InnoDB requis pour la table
	}

    /**
     * Permet d'actualiser la session en cours pour rester connecté(e)
     *
     * @param  string $selector Identifiant de coordonance entre la base de donnée et en local
     * @param  bool   $cookie   Actualise le cookie au lieu de la session
     */
	private function updateSession($selector, $cookie = false){
		$token = $this->generateToken();
		$data = $this->getIP() . '::' . $_SERVER['HTTP_USER_AGENT'];
		$expire = time() + 31536000;

		$get_session = $this->db->prepare('SELECT users_logged_expires FROM ' . PREFIX . 'users_logged WHERE users_logged_selector = ?', [$selector], null, true, true); // Utilise les transactions, moteur InnoDB requis pour la table

        if($cookie === true){
			setcookie(self::COOKIE_NAME, serialize([user_id, $selector, $token[0], $data]), strtotime($get_session->users_logged_expires), '/', '.' . SUBDOMAIN, $this->isSecure(), 1);
            $session_time = date("Y-m-d H:i:s", $expire);
		}else{
  		    $_SESSION[self::COOKIE_NAME] = serialize([user_id, $selector, $token[0], $data]);
			$session_time = date("Y-m-d H:i:s", strtotime('+30 minutes', time()));
     	}

		$this->db->execute('UPDATE ' . PREFIX . 'users_logged SET users_logged_token = ?, users_logged_data = ? WHERE users_logged_selector = ?', [$token[1], $data, $selector], true); // Utilise les transactions, moteur InnoDB requis pour la table
	}

    /**
     * Permet de réupérer la session locale actuelle (avec vérification de la coordonance avec la bdd)
     *
     * @return object
     */
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

    /**
     * Permet de récupérer la session actuelle
     *
     * @return string Identifiant de coordonance entre la base de donnée et en local
     */
	public function getCurrentSession(){
		if(!empty($_COOKIE[self::COOKIE_NAME]) && $this->isSerialized($_COOKIE[self::COOKIE_NAME]))
			return unserialize($_COOKIE[self::COOKIE_NAME])[1];
		else if(!empty($_SESSION[self::COOKIE_NAME]))
			return unserialize($_SESSION[self::COOKIE_NAME])[1];
	}

    /**
     * Permet de supprimer une session ou un cookie
     *
     * @param  string      $what     Choix de l'élément à supprimer ('session', 'cookie', 'sessions')
     * @param  string|null $selector Identifiant de coordonance entre la base de donnée et en local
     * @return bool                  Statut du succès
     */
	public function deleteSession($what = 'session', $selector = null){
		if($selector != null){
			if($selector == $this->getCurrentSession())
				return false;
			else{
				if($this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE users_logged_selector = ?', [$selector], true))
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
						setcookie(self::COOKIE_NAME, null, -1, '/', '.' . SUBDOMAIN);
					}
					break;
				case 'session':
					if(isset($_SESSION[self::COOKIE_NAME])){
						if($this->isSerialized($_SESSION[self::COOKIE_NAME]))
							if(isset(unserialize($_SESSION[self::COOKIE_NAME])[1]))
								$this->db->execute('DELETE FROM ' . PREFIX . 'users_logged WHERE users_logged_selector = ?', [unserialize($_SESSION[self::COOKIE_NAME])[1]], true);
						session_destroy();
						session_unset();
						$_SESSION = array();
					}else {
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

    /**
     * Permet de vérifier qu'un utilisateur est connecté
     *
     * @return bool Statut du succès
     */
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

    /**
     * Permet de connecter un utilisateur
     *
     * @param  string     $user_email    L'email saisi par l'utilisateur
     * @param  string     $user_password Le mot de passe saisi par l'utilisateur
     * @param  bool       $remember_me   Enregistre la session dans un cookie
     * @param  bool|mixed $captcha       Contient les données du captcha
     * @return int                       Retourne le code de l'erreur
     */
	public function login($user_email, $user_password, $remember_me, $captcha = false){
		$this->tryToLogin();

		if($captcha !== false){
			$resp = $this->reCaptcha->verify($captcha, $this->getIP());
			if(!$resp->isSuccess())
                return 14;
		}

		if($user_email != NULL && $user_password != NULL){
			$user = $this->db->prepare('SELECT user_id, user_password, user_keys, user_folder FROM ' . PREFIX . 'users WHERE user_email = :user_email OR user_phone = :user_email', [':user_email' => $user_email], null, true);
			if($user){
					if($this->decrypt($user_password, $user->user_password) === true){
						if(unserialize($user->user_keys)[1] === false){
							//TODO: Ajouter un blocage, pour ne pas envoyer pleins de mails (mettre sur false l'user en verifiant date)
							if(function_exists('mail')){
								$activation_key = unserialize($user->user_keys)[1];
								$folder = $user->user_folder;

								ob_start();
								include(ROOT . "/app/Views/mails/reminder_create_account.php");
								$body = ob_get_clean();

								$this->mail->setFrom($this->email, WEBSITE_NAME);
								$this->mail->Subject = WEBSITE_NAME . ' - Rappel de validation de votre compte';
								$this->mail->addAddress($user_email);
								$this->mail->Body = $body;

								if(!$this->mail->send()) return 32;
							}else return 32;
							return 3;
						}elseif(unserialize($user->user_keys)[1] === 'blocked') return 34;
						elseif(unserialize($user->user_keys)[1] !== true) return 4;
						else {
							$this->db->execute("UPDATE " . PREFIX . "users SET user_password = ? WHERE user_id = ?", [$this->encrypt($user_password), $user->user_id]);
							$this->createSession($user->user_id, $remember_me);
							$this->tryToLogin(true);

							return true;
						}
					}else return 1;
			}else return 1;
		}else return 2;
	}

    /**
     * Permet de limiter la fraude à la connexion en limitant les tentatives
     *
     * @param bool $logged Indique si l'utliateur est connecté
     */
	public function tryToLogin($logged = false){
	    if($logged === true)
			$this->db->execute('DELETE FROM ' . PREFIX . 'users_attempts WHERE attempt_ip = ?', [$this->getIP()]);
		else
			$this->db->execute('INSERT INTO ' . PREFIX . 'users_attempts(attempt_ip) VALUES(?)', [$this->getIP()]);
	}

    /**
     * Permet de limiter la fraude à la connexion en limitant les tentatives et
     * en activant le captcha au bout de 4 tentatives échouées
     */
	public function checkAttemptsLogin(){
		$valid_time = date("Y-m-d H:i:s", strtotime('-15 minutes', time()));
		$this->db->execute('DELETE FROM ' . PREFIX . 'users_attempts WHERE attempt_ip = ? AND attempt_time < ?', [$this->getIP(), $valid_time]);

		if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_attempts WHERE attempt_ip = ?', [$this->getIP()]) > 4)
			$this->login_captcha = true;
	}

    /**
     * Permet de déconnecter l'utilisateur
     * @todo La déconnexion avec les sessions ne fonctionne pas
     */
	public function logout(){
		if(isset($_COOKIE[self::COOKIE_NAME]))
			$this->deleteSession('cookie');
		$this->deleteSession(); // On force la déconnexion pour les session php
        //TODO: La déconnexion avec les sessions ne fonctionne pas
	}

    /**
     * Permet d'inscrire l'utilisateur
     *
     * @param  string      $user_email      L'email saisi par l'utilisateur
     * @param  string      $user_password   Le mot de passe saisi par l'utilisateur
     * @param  string|null $repeat_password Le second mot de passe saisi par l'utilisateur
     * @param  mixed       $captcha         Le code captcha
     * @param  string|null $user_phone      Le numéro de téléphone saisi par l'utilisateur
     * @param  string|null $code            Le code saisi par l'utilisateur
     * @return int                          Retourne le code de l'erreur
     */
	public function register($user_email, $user_password, $repeat_password, $captcha, $user_phone = null, $code = null){

		$phone_add = true;
		if($user_phone != null)
			if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "users WHERE user_phone = ?", [$user_phone]) > 0)
				$phone_add = false;

		if($code != null)
			if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "codes WHERE code = ? AND (code_validity < ? OR code_validity IS NULL) AND (code_used <= code_max_use OR code_max_use IS NULL)", [$user_code, date('Y-m-d')]) <= 0)
				$code = false;

        if(!empty($user_email)){
            $user_email = trim(strtolower($user_email));
            if(filter_var($user_email, FILTER_VALIDATE_EMAIL) == true){
                if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "users WHERE user_email = ?", [$user_email]) != 0)
                    return 7;
            }else return 6;
        }else return 5;

        if(!empty($user_phone)){
            $user_phone = trim(str_replace(' ', '', $user_phone));
            if(ctype_digit($user_phone) && strlen($user_phone) <= 10){
                if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "users WHERE user_phone = ?", [$user_phone]) > 0)
                    return 8;
            }else return 9;
        }

        if(!empty($code))
		    if($this->db->count("SELECT COUNT(*) FROM " . PREFIX . "codes WHERE code = ? AND (code_validity < ? OR code_validity IS NULL) AND (code_used <= code_max_use OR code_max_use IS NULL)", [$user_code, date('Y-m-d')]) <= 0)
		    	return 10;

        if(!empty($user_password)){
            if(isset($repeat_password) && $repeat_password == $user_password){
                if(strlen($user_password) >= 8){
                    $user_password = $this->encrypt($user_password);
                }else return 13;
            }else return 12;
        }else return 11;

	    if(isset($captcha)){
	    	$resp = $this->reCaptcha->verify($captcha, $this->getIP());
	    	if(!$resp->isSuccess()) return 14;
    	}else return 14;

		//SECOND PART
		$folder = uniqid();
		if(file_exists(ROOT . '/public/assets/files/users/' . $folder)) return 15;
		else {
			if(function_exists('mail')){
				$activation_key = $this->generateKey();

				ob_start();
				include(ROOT . "/app/Views/mails/create_account_with_validation.php");
				$body = ob_get_clean();

				$this->mail->setFrom($this->email, WEBSITE_NAME);
				$this->mail->Subject = WEBSITE_NAME . ' - Validation de votre compte';
				$this->mail->addAddress($user_email);
				$this->mail->Body = $body;

				if($this->mail->send()){
					$date = date("Y-m-d H:i:s", time());
					$keys = serialize([$activation_key[2], $activation_key[0], null]); //@TODO: Schema : [user_key, key_activation, key_password]
					mkdir(ROOT . '/public/assets/files/users/' . $folder, 0777, true);
					$this->db->execute('INSERT INTO ' . PREFIX . 'users(user_email, user_password, user_created, user_keys, user_phone, user_level, user_folder) VALUES (?, ?, ?, ?, ?, 1, ?)', [$user_email, $user_password, $date, $keys, $user_phone, $folder]);
					$this->setUserId($this->db->lastInsertId());

					if(!empty($code)){
						$code_data = $this->db->prepare('SELECT * FROM ' . PREFIX . 'codes WHERE code = ?', [$code], null, true);
						$this->setContentCode($code_data->code_content_slug);
					}

					return true;
			  }else return 16;
			}else return 16;
		}
	}

    /**
     * Permet d'appliuer une action relative à un code d'inscription
     *
     * @param string $code Slug du code saisit par l'utilisateur (déjà validé)
     */
	private function setContentCode($code){
		switch($code){
			case 'set_volunteer':
				$this->db->execute('UPDATE ' . PREFIX . 'codes SET code_used = code_used + 1 WHERE code = ?', [$code]);
				$this->db->execute("UPDATE " . PREFIX . "users SET user_level = 9 WHERE user_id = ?", [user_id]);
				$level_name = $this->db->prepare("SELECT user_id, user_level, level_id, level_name FROM " . PREFIX . "users INNER JOIN " . PREFIX . "users_level ON user_level = level_id WHERE user_id = ?", [user_id], null, true);
				$level_name = $level_name->level_name;

				ob_start();
				include(ROOT . "/app/Views/mails/change-level.php");
				$body = ob_get_clean();

				$this->mail->setFrom($this->email, WEBSITE_NAME);
				$this->mail->Subject = WEBSITE_NAME . ' - Vous êtes désormais un ' . $level_name . ' sur ' . WEBSITE_NAME . ' !';
				$this->mail->addAddress($user_email);
				$this->mail->Body = $body;

				$this->mail->send();
					break;
		}
	}

    /**
     * Permet de réinitialiser un mot de passe
     *
     * @todo Changer les retours d'erreur par leurs codes d'erreur
     * @param  string $user_email Email saisi par l'utilisateur
     * @param  mixed  $captcha    Captcha
     * @return string             Retourne l'erreur
     */
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
								include(ROOT . "/app/Views/mails/reset_password.php");
								$body = ob_get_clean();

								$this->mail->setFrom($this->email, WEBSITE_NAME);
								$this->mail->addAddress($user_email);

								$this->mail->Subject = WEBSITE_NAME . ' - Rénitialisation de votre mot de passe';
								$this->mail->Body = $body;

								$date = date("Y-m-d H:i:s", strtotime('+8 hours', time()));
								$data = serialize(['date' => $date, 'key' => $key[2]]);

								if(!$this->mail->send())
									$error = "Erreur technique ! <br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1. - " . $this->mail->ErrorInfo;
								else {
									$this->db->execute('UPDATE ' . PREFIX . 'users SET user_reset = ? WHERE user_email = ?', [$data, $user_email]);
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
        //TODO: Changer les retours d'erreur par leurs codes d'erreur
	}

    /**
     * Permet de finaliser la ccréation d'un compte en le validant
     *
     * @param  string $key Clé d'activation (envoyée par mail)
     * @return int         Retourne le code d'erreur
     */
	public function activeAccount($key = null){
			if(!empty($key)){
				$key = explode('-', $key);
				$identifier = urldecode($key[0]);
				$key = !empty($key[1]) ? $key[1] : null;

				if($key != null){
					$data = $this->db->prepare("SELECT user_keys FROM " . PREFIX . "users WHERE user_folder = ?", [$identifier], null, true);
					$keys = unserialize($data->user_keys);
					if($keys[1] == $key){
						$keys[1] = true;
						$keys = serialize([$keys]);
						$this->db->execute("UPDATE " . PREFIX . "users SET users_key = ? WHERE user_folder = ?", [$identifier, $email]);
						return true;
					}else return 18;
				}else return 18;
			}else return 17;
	}

    /**
     * Permet de réinitialiser la clé du compte
     *
     * @todo Refaire la fonction resetKey
     * @param  int   $user_id Id de l'utilisateur
     * @param  bool  $admin   Indique si l'utilisateur est un administrateur
     * @param  bool  $force   Permet de forcer la réinitialisation
     * @return mixed
     */
	public function resetKey($user_id, $admin = false, $force = false){
        //TODO: Refaire la fonction resetKey
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
			include(ROOT . "/app/Views/mails/reset_key.php");
			$body = ob_get_clean();

			$this->mail->setFrom($this->email, WEBSITE_NAME);
			$this->mail->Subject = WEBSITE_NAME . ' - Réinitialisation de votre clé de sécurité';
			$this->mail->addAddress($user->user_email);
            $this->mail->Body = $body;

			if(!$this->mail->send())
				$error = "Erreur technique ! Insription impossible.<br />Contactez le support ({$this->mail}) en indiquant le code d'erreur suivant : 1. - " . $this->mail->ErrorInfo;
		}

		return $error;
	}

    /**
     * Permet de mettre à jour les informations du compte
     *
     * @param  string $user_email Email de l'utilisateur
     * @param  string $user_phone Téléphone de l'utilisateur
     * @return int                Retourne le code de l'erreur
     */
	public function update($user_email, $user_phone){
		if(isset($user_email) && $user_email != null && filter_var($user_email, FILTER_VALIDATE_EMAIL) == true){
			$count = empty($user_phone) ? $this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users WHERE user_id NOT IN (?) AND user_email = ?', [user_id, $user_email]) : $this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users WHERE user_id NOT IN (?) AND (user_email = ? OR user_phone = ?)', [user_id, $user_email, $user_phone]);

			if(ctype_digit($user_phone) || empty($user_phone)){
				if(strlen($user_phone) == 10 || empty($user_phone)){
					if($count == 0){
						$this->db->execute('UPDATE ' . PREFIX . 'users SET user_email = :user_email, user_phone = :user_phone WHERE user_id = :user_id', [':user_email' => $user_email, ':user_phone' => $user_phone, ':user_id' => user_id], null, true);
						return true;
					}else return 26;
				}else return 25;
		  }else return 24;
		}else return 23;
	}

    /**
     * Permet de modifier le mot de passe du compte
     *
     * @param  string $password        Mot de passe actuel
     * @param  string $new_password    Nouveau mot de passe
     * @param  string $repeat_password Nouveau mot de passe (seconde variable)
     * @param  string $secure_key      Clé du compte
     * @return int                     Retourne le code de l'erreur
     */
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
                            $this->deleteSession('sessions'); //TODO: vérifier que ça fonctionne
							$error = true;
						}else return 31;
					}else return 30;
				}else return 29;
			} else return 28;
		}else return 27;
	}

    /**
     * Permet de réinitaliser le mot de passe d'un compte
     *
     * @todo Voir pourquoi user_reset sur null
     * @todo Changer les retours d'erreur par leurs codes d'erreur
     * @param  string $key  Clé du compte
     * @param  array  $post Données du formualire (user_email, user_password, user_password_)
     * @return mixed
     */
	public function resetPassword($key, $post){
		$post = (object) $post;
		if(!empty($post->user_email) && !empty($post->user_password) && !empty($post->user_password_)){
			$data = $this->db->prepare('SELECT user_reset FROM ' . PREFIX . 'users WHERE user_email = ?', [$post->user_email], null, true);
			if($data && $data->user_reset != null){
				$data = (object) unserialize($data->user_reset);
				if(isset($data->date) && $data->date > date("Y-m-d H:i:s", time())){
					if(isset($data->key) && $data->key == $key){
						if($post->user_password == $post->user_password_){
							$new_password = $this->encrypt($post->user_password);
							$this->db->execute('UPDATE ' . PREFIX . 'users SET user_reset = NULL, user_password = ? WHERE user_email = ?', [$new_password, $post->user_email]);
							$error = true;
						}else
							$error = 'Les mots de passe ne correspondent pas';
					}else
						$error = 'Le lien n\'existe pas';
				}else {
					$error = 'Votre lien a expiré';
					$this->db->execute('UPDATE ' . PREFIX . 'users SET user_reset = NULL WHERE user_email = ?', [$post->user_email]); //TODO: voir pourquoi user_reset sur null
				}
			}else
				$error = 'Veuillez vérifier votre email et réessayer';
		}else
			$error = 'Veuillez remplir tous les champs';

		return $error;
        //TODO: Changer les retours d'erreur par leurs codes d'erreur
	}
}
