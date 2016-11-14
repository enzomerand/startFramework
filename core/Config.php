<?php
/**
 * Config Class
 */

namespace Core;

/**
 * Cette classe permet d'initialiser et récupérer la configuration du site
 *
 * @package startFramework\Core
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Config {

	/**
	 * Contient les paramètres et leurs valeurs
	 * 
	 * @var array
	 */
	private $settings = [];

	/**
	 * Contient une unique instance Config
	 *
	 * @var Config
	 */
	private static $_instance;

	/**
	 * Charge le fichier de configuration
	 *
	 * @param string $file Location du fichier de configuration
	 */
	public function __construct($file){
		$this->settings = require($file);
	}

	/**
	 * Permet de créer et ré-utiliser une seule et unique instance
	 *
	 * @param  string $file Location du fichier de configuration
	 * @return Config
	 */
	public static function getInstance($file){
		if(is_null(self::$_instance))
			self::$_instance = new Config($file);

		return self::$_instance;
	}

	/**
	 * Permet de récupérer un paramètre et sa/ses valeur(s)
	 *
	 * @param  string $key Nom du paramètre
	 * @return string
	 */
	public function get($key){
		if(!isset($this->settings[$key]))
			return null;

		return $this->settings[$key];
	}
}
