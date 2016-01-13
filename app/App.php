<?php

use Core\Config;
use Core\Database\Database;

class App{
	
	private $db_instance;
	private static $_instance;
	
	public static function getInstance(){
		if(is_null(self::$_instance))
			self::$_instance = new App();
		
		return self::$_instance;
	}
	
	final public static function load(){
	    //configurez vos paramètres et modifiez à votre guise
		error_reporting(E_ALL);
		ini_set("display_errors", 1);
		ini_set('default_charset', 'UTF-8');
		session_start();
		setlocale(LC_TIME, "fr_FR");
		define('PREFIX', 'app_');
		$https = (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") ? 'https://' : 'http://';
		define('HTTPS', $https);
		define('DOMAIN', $_SERVER['HTTP_HOST']);
		define('FULL_DOMAIN', HTTPS . DOMAIN . '/');
		define('CAPTCHA_PUBLIC', '');
		define('CAPTCHA_PRIVATE', '');
		
		define('PATH_ASSETS', FULL_DOMAIN . 'assets/');
		define('PATH_USER', '/user/');
		define('LINK_LOGIN', PATH_USER . 'login/');
		define('LINK_REGISTER', PATH_USER . 'register/');
		
		define('CURRENT_PATH', getcwd());
		
		require ROOT . '/app/Autoloader.php';
		require ROOT . '/core/Autoloader.php';
		require_once ROOT . '/core/ReCaptcha/autoload.php';
		require_once ROOT . '/core/PHPMailer/PHPMailerAutoload.php';
		App\Autoloader::register();
		Core\Autoloader::register();
	}
	
	public function getElement($name, $class_name = 'Element', $use_db = true){
		$class_name = '\\App\\' . $class_name . '\\' . ucfirst($name) . $class_name;
		if($use_db === false)
		    return new $class_name();
		else
			return new $class_name($this->getDb());
	}
	
	public function getDb(){
		$config = Config::getInstance(ROOT . '/app/config.php');
		if(is_null($this->db_instance))
			$this->db_instance = new Database($config->get('db_host'), $config->get('db_name'), $config->get('db_user'), $config->get('db_pass'));
		
		//var_dump($this->db_instance);
	    return $this->db_instance;
	}
}
