<?php

namespace App\Entity;

use \Core\Entity\Entity;

class UserEntity extends Entity{
	
	public function setNavbar($url = "#", $icon = null, $title = null){
		$active = ($this->page == $url) ? ' active' : null;
		$icon = ($icon != null) ? '<i class="fa fa-' . $icon . '"></i> ' : null;
		if($title != null)
			echo '<li class="nav-item' . $active . '"><a class="nav-link" href="' . $url . '">' . $icon . $title . '</a></li>';
	}
	
	public function getNavbar(){
		switch($this->user_level):
			case 1: //pour le rang 1, exemple
			    $this->setNavbar(PATH_USER . 'hello/', 'users', 'Hello');
				$this->setNavbar(PATH_USER . 'goodby/', 'file-image-o', 'Good By');
			break;
			//etc
		endswitch;
	}
}
