<?php

namespace App\Controller;

use Core\Controller\Controller;

class MainController extends AppController{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$this->render('index', compact(''));
	}

}
