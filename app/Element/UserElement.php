<?php

namespace App\Element;

use Core\Element\Element;

class UserElement extends Element {
	
	private $page = PATH_USER;
	
	public $isAdmin = false;
	public $isArtist = false;
	
	public function setLevel(){
		switch($this->getLevel()->user_level){
			case 5: $this->isAdmin = true;
			    break;
			default : $this->isArtist = true;
		}
	}
	
	public function getLevel(){
		return $this->query("
		    SELECT user_id, user_level, level_id, level_name
			FROM " . PREFIX . "users
			INNER JOIN " . PREFIX . "users_level
			    ON user_level = level_id
			WHERE  user_id = ?
		", [user_id], true);
	}
	
	public function findUser($id = null){
		unset($id); //on laisse le id en paramètre pour compatibilité avec la classe parente
		return $this->query("SELECT user_id, user_email, user_date_create, user_key, user_phone, user_account_activate, user_level FROM " . PREFIX . "users WHERE user_id = ?", [user_id], true);
	}
	
}
