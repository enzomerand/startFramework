<?php
/**
 * Element Class
 */

namespace Core\Element;

use Core\Database\Database;

/**
 * Cette classe permet d'éxécuter des requêtes propres à un élément, une ou plusieurs pages
 *
 * @package startFramework\Core\Element
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Element{

    /**
     * Nom de l'élément (classe enfant)
     *
     * @var string
     */
	protected $element;

	/**
	 * Permet l'éxécution de requêtes SQL
	 *
	 * @var Database
	 */
	protected $db;

    /**
     * Définit si l'élément doit avoir une entité (paramètre global)
     *
     * @var bool
     */
	private $entity = true;

    /**
     * Initialisation de la classe
     *
     * @param string   $entity Définit l'utilisation d'une entité pour l'élément enfant
     * @param Database $db     Permet l'éxécution de requêtes SQL
     */
	public function __construct($entity, Database $db){
		$this->db = $db;
		if(is_null($this->element)){
			$parts = explode('\\', get_class($this));
			$class_name = end($parts); //Récupère dernier élément du tableau
			$this->element = strtolower(str_replace('Element', '', $class_name . 's')); //On met en minuscule et on enlève le Element à la fin
		}
		$this->entity = $entity;
	}

    /**
     * Simplification et adapttion de l'utilisation des requêtes pour l'élément
     *
     * @param  string      $statement Base de la requête (avec paramètres statiques)
     * @param  array|null  $attr      Paramètres dynamiques
     * @param  bool        $one       Définit si la requête retourne un unique résultat
     * @return object
     */
	public function query($statement, $attr = null, $one = false){
		$entity = ($this->entity === true) ? strrev(preg_replace(strrev("/Element/"),strrev('Entity'),strrev(str_replace('Element\\', 'Element\Entity\\', get_class($this))),1)) : null;
		if($attr)
			return $this->db->prepare($statement, $attr, $entity, $one);
		else
			return $this->db->query($statement, $entity, $one);
	}

    /**
     * Simplifie l'utilisation de la requête de type COUNT
     *
     * @param  string      $statement Base de la requête (avec paramètres statiques)
     * @param  array|null  $attr      Paramètres dynamiques
     * @return int
     */
	public function count($statement, $attr = null){
		return $this->db->count($statement);
	}

    /**
     * Permet de retourner toutes les lignes de la bdd propres au nom de
     * l'élément (la classe enfant)
     *
     * @example "Le nom de la classe élément enfant s'appelle UsersElement,
     *           cette fonction retournera toutes les lignes de la table 'users'"
     *
     * @return mixed
     */
	public function getAll(){
		return $this->query('SELECT * FROM ' . PREFIX . $this->element);
	}

    /**
     * Permet de retourner une ligne de la bdd propre au nom de l'élément
     * (la classe enfant)
     *
     * @see    getAll()
     *
     * @param  int   $id ID de la ligne à retourner
     * @return mixed
     */
	public function get($id){
		return $this->query('SELECT * FROM ' . PREFIX . $this->element . ' WHERE id = ?', [$id], true);
	}

    /**
     * Permet de formater une chaîne au format adapté pour une url
     *
     * @param  string $string Chaîne de caractère à formater
     * @return string
     */
	public function strclean($string){
		$chars = array(
			'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', '@' => 'a',
			'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', '€' => 'e',
			'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
			'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Ö' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
			'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'µ' => 'u',
			'Œ' => 'oe', 'œ' => 'oe',
			'$' => 's');

		$string = strtr($string, $chars);
		$string = preg_replace('#[^A-Za-z0-9]+#', '-', $string);
		$string = trim($string, '-');
		$string = strtolower($string);

		return $string;
    }
}
