<?php

namespace Core\Entity;

class Entity {
	
	protected $page = PATH_USER;
	
	public function __construct(){
		$this->page = $_SERVER['REQUEST_URI'];
	}
	
	public function __get($key){
		$method = 'get' . ucfirst($key);
		$this->$key = $this->$method();
		return $this->$key;
	}
}
