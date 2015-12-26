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
		
		$this->loadModel('User');
		//indiquez les classes (elements) que vous utiliserez sur l'ensemble du site (pas l'admin par exemple)
	}
	
	protected function loadModel($model_name, $class_name = 'Element', $use_db = true){
		$this->$model_name = $this->app->getElement($model_name, $class_name, $use_db);
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
