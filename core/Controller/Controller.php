<?php

namespace Core\Controller;

use Core\Auth\DBAuth;
use App;

abstract class Controller{
	
	protected $viewPath;
	protected $template;
	
	private function setTemplatePath(){
	    //si on se trouve dans le repertoire admin
		if(in_array('admin', explode('/', $this->viewPath)))
			return  ROOT . '/app/Views/user/';
		else
			return $this->viewPath;
	}
	
	protected function render($view, $variables = []){
		ob_start();
		extract($variables);
		require($this->viewPath . $view . '.php');
		$content = ob_get_clean();
		require($this->setTemplatePath() . 'templates/' . $this->template . '.php');
	}
	
	public function redirect($location = '/', $page = 'index', $get = null){
		header("Location: {$location}" . (($get != null) ? '?' . (($get == 1) ? 'do' : $get) . '=' . $page : ''));
	}
	
}
