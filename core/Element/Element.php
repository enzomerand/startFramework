<?php

namespace Core\Element;

use Core\Database\Database;

class Element{
	
	protected $element;
	protected $db;
	
	public function __construct(Database $db){
		$this->db = $db;
		if(is_null($this->element)){
			$parts = explode('\\', get_class($this));
			$class_name = end($parts); //Récupère dernier élément du tableau
			$this->element = strtolower(str_replace('Element', '', $class_name . 's')); //On met en minuscule et on enlève le Element à la fin
		}
	}
	
	public function query($statement, $attr = null, $one = false){
		if($attr)
			return $this->db->prepare($statement, $attr, strrev(preg_replace(strrev("/Element/"),strrev('Entity'),strrev(str_replace('Element\\', 'Element\Entity\\', get_class($this))),1)), $one);
		else
			return $this->db->query($statement, strrev(preg_replace(strrev("/Element/"),strrev('Entity'),strrev(str_replace('Element\\', 'Element\Entity\\', get_class($this))),1)), $one);
	}
	
	//Pour le reste des query comme count par exemple, on utilise la méthode habituelle : $this->db->count
	
	public function all(){
		return $this->query('SELECT * FROM ' . PREFIX . $this->element);
	}
	
	public function find($id){
		return $this->query('SELECT * FROM ' . PREFIX . $this->element . ' WHERE id = ?', [$id], true);
	}
	
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
