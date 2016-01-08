<?php

namespace App\Controller;

use Core\Controller\Controller;

class PageController extends AppController{
	
	private $pages = ['Exemple title' => 'slug_page'];
	
	public function getPage($slug){
	    $file = "../public/assets/files/{$slug}.html";
		if(!file_exists($file)):
			$this->set404();
		else:
		    $title = array_search($slug, $this->pages);
			$this->title = $title;
			$this->render('page', compact('file'));
		endif;
	}	
}
