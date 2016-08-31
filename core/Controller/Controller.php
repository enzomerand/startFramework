<?php

namespace Core\Controller;

use App;

abstract class Controller{

	protected $viewPath;
	protected $template;

	private function setFace($face = 'backend'){
		if($face == 'backend' || $face == 'frontend') return $face;
		else return 'backend';
	}

	protected function render($view, $variables = [], $face = null){
		ob_start();
		extract($variables);
		require($this->viewPath . $view . '.php');
		$content = ob_get_clean();
		require(ROOT . "/app/Views/templates/{$this->setFace($face)}/{$this->template}.php");
	}

	public function redirect($location = '/', $params = null){
		$params = ($params != null) ? '?' . $params : null;
		header("Location: {$location}{$params}");
		exit;
	}

	public function action($class, $params = null){
		if(method_exists($class)){
			$restriction_name = strtolower(preg_replace('/\B([A-Z])/', '_$1', $class));
			$this->isRestricted($class);
			$this->$class($params);
		}
	}

}
