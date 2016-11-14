<?php
/**
 * Entity Class
 */

namespace Core\Entity;

/**
 * Cette classe permet d'afficher les valeurs de la base de donnée issues
 * d'une requête et/ou de formater ces valeurs à l'aide de fonctions.
 * Ainsi, on appelle directement les valeurs sur le fichier template.
 *
 * @package startFramework\Core\Entity
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Entity {

    /**
     * Contient le nom de la page actuelle
     *
     * @var string
     */
	protected $page = '/';

    /**
     * Assigne la valeur de la page actuelle
     *
     * @see globales $_SERVER
     */
	public function __construct(){
		$this->page = $_SERVER['REQUEST_URI'];
	}

    /**
     * Permet de récupérer une valeur de la bdd préalablemet instanciée
     * ou éxécuter une fonction de la classe enfant
     *
     * @param  string $function Nom de la méthode ou de la valeur de la base de donnée à appeler
     * @return void
     */
	public function __get($function){
		$method = 'get' . ucfirst(preg_replace_callback('/_([a-z]?)/', function($key) {return strtoupper($key[1]);}, $function));
		$this->$function = $this->$method();
		return $this->$function;
	}
}
