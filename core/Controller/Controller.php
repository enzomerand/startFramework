<?php

namespace Core\Controller;

use Core\Auth\DBAuth;
use App;

abstract class Controller{
	
	protected $viewPath;
	protected $template;
	
	private function setTemplate($end = null){
		if($end == 'backend' || $end == 'frontend')
		    return $end;
	    elseif(in_array('admin', explode('/', $this->viewPath)) || in_array('user', explode('/', $this->viewPath)))
			return 'backend';
		else
			return 'frontend';
	}
	
	protected function render($view, $variables = [], $end = null){
		ob_start();
		extract($variables);
		require($this->viewPath . $view . '.php');
		$content = ob_get_clean();
	    require(ROOT . "/app/Views/templates/{$this->template}-{$this->setTemplate($end)}.php");
	}
	
	public function redirect($location = '/', $page = 'index', $get = null){
		header("Location: {$location}" . (($get != null) ? '?' . (($get == 1) ? 'do' : $get) . '=' . $page : ''));
	}
	
}
