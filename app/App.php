<?php
/**
 * App Class
 */

use Core\Config;
use Core\Database\Database;

/**
 * Cette classe permet de définir les paramètres techniques principaux du site web et
 * charger les librairies
 *
 * @package startFramework
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class App{

	/**
	 * Instance de la classe Database
	 *
	 * @var Database
	 */
	private $db_instance;

	/**
	 * Instance de la classe App
	 *
	 * @var App
	 */
	private static $_instance;

	/**
	 * Permet de créer une unique instance de la classe et la réutiliser
	 *
	 * @return void|App
	 */
	public static function getInstance(){
		if(is_null(self::$_instance))
			self::$_instance = new App();

		return self::$_instance;
	}

	/**
	 * Permet de définir les paramètres principaux et d'enregistrer les librairies
	 */
	final public static function load(){
	    # Configurez vos paramètres et modifiez à votre guise

		//error_reporting(E_ALL);
		//ini_set("display_errors", 1);
		//ini_set('default_charset', 'UTF-8');

		# Variables requises au bon fonctionnement #
		session_start();
		setlocale(LC_TIME, "fr_FR"); // Modifiable
		define('PREFIX', 'app_');    // Modifiable
		$https = (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") ? 'https://' : 'http://';
		define('HTTPS', $https);
		define('DOMAIN', $_SERVER['HTTP_HOST']);
		define('FULL_DOMAIN', HTTPS . DOMAIN . '/');
		define('FULL_DOMAIN_NS', HTTPS . DOMAIN); // NS = No Slash = Pas de "/" à la fin de l'url
		if(count(explode('.', DOMAIN)) == 3) // Si c'est un sous-domaine actuellement
		    define('SUBDOMAIN', explode('.', DOMAIN)[1]);
		# Fin des variables requises au bon fonctionnement #

        // Les variables ci-dessous peuvent être définies dynamiquement
		define('PATH_ASSETS', FULL_DOMAIN . 'assets/');
		define('PATH_USER', '/user/');
		define('LINK_LOGIN', PATH_USER . 'login/');
		define('LINK_REGISTER', PATH_USER . 'register/');

		define('CURRENT_PATH', getcwd());

        // Chargement des librairies, vous pouvez ajouter vos propres autoloaders
		require ROOT . '/app/Autoloader.php';
		require ROOT . '/core/Autoloader.php';
		require_once ROOT . '/core/ReCaptcha/autoload.php';
		require_once ROOT . '/core/PHPMailer/PHPMailerAutoload.php';
		App\Autoloader::register();
		Core\Autoloader::register();
	}

	/**
	 * Créer un élément et le charge
	 *
	 * @example <code>$name . $classname</code> et <code>use App\$class_name\$name . $class_name</code>
	 * @param   string $name       Nom de l'élément
	 * @param   string $class_name Suffix de l'élément
	 * @param   bool   $use_db     Indique l'utilisation de la base de donnée
	 * @return  object             Retourne la classe de l'élément
	 */
	public function getElement($name, $class_name = 'Element', $use_db = true){
		$class_name = '\\App\\' . $class_name . '\\' . ucfirst($name) . $class_name;
		if($use_db === false)
		    return new $class_name();
		else
			return new $class_name($this->getDb());
	}

	/**
	 * Permet de créer une unique instance de la classe Database et la réutiliser
	 *
	 * @see    Database
	 * @see    Config
	 * @return Database
	 */
	public function getDb(){
		$config = Config::getInstance(ROOT . '/app/config.php');
		if(is_null($this->db_instance))
			$this->db_instance = new Database($config->get('db_host'), $config->get('db_name'), $config->get('db_user'), $config->get('db_pass'));

	    return $this->db_instance;
	}
}
