<?php

namespace App\Controller;

use Core\Controller\Controller;
use App;

class AppController extends Controller{
	
	public $title = 'Name Site';
	public $page = '/';
	
	protected $template = 'default';
	protected $app;
	
	public function __construct(){
		$this->viewPath = ROOT . '/app/Views/main/';
		$this->app = App::getInstance();
		$this->page = $_SERVER['REQUEST_URI'];
		
		$this->loadElement('User', true);
		//indiquez les classes (elements) que vous utiliserez sur l'ensemble du site (pas l'admin par exemple), utilisable comme ceci (array) : $this->loadElement(['User', 'Data']); pour charger plusieurs classes
	}
	
	final protected function loadElement($element_name, $entity = false, $class_name = 'Element', $use_db = true){
		if(is_array($element_name)){
			$array = $element_name;
			foreach($array as $element)
			    $this->setElement($element, $entity, $class_name, $use_db);
	    }else
		    $this->setElement($element_name, $entity, $class_name, $use_db);
	}
	
	private function setElement($element_name, $entity = false, $class_name = 'Element', $use_db = true){
		$this->$element_name = $this->app->getElement($element_name, $entity, $class_name, $use_db);
	}
	
	public function set404(){
		header('Location: /404/');
		exit;
	}
	
	public function get404(){
		$this->render('404', compact(''));
	}
	
	protected function getAlert($text, $type = null){
		$type = ($type != null) ? ' alert-' . $type : null;
		return '<div class="alert' . $type . '">' . $text . '</div>';
	}
	
}
