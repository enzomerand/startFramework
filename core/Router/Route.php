<?php
/**
 * Route Class
 */

namespace Core\Router;

/**
 * Cette classe permet de mettre en place des liens propres et personnalisés et
 * assurer le bon fonctionnement du framework
 *
 * @package startFramework\Core\Router
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Route {

    /**
     * @var
     * @var
     * @var array
     * @var array
     */
    private $path,
            $callable,
            $matches = [],
            $params = [];

    /**
     * Initialisation de la classe
     * @param [type] $path     [description]
     * @param [type] $callable [description]
     */
    public function __construct($path, $callable){
        $this->path = trim($path, '/');
        $this->callable = $callable;
    }

    /**
     * [match description]
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    public function match($url){
		$url = trim($url, '/');
		$path = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], $this->path);
		$path = str_replace('/', '\/', $path);
		$regex = "#^$path$#i";
		if(!preg_match($regex, $url, $matches))
			return false;
		array_shift($matches);
		$this->matches = $matches;

		return true;
	}

    /**
     * [paramMatch description]
     * @param  [type] $match [description]
     * @return [type]        [description]
     */
	private function paramMatch($match){
		if(isset($this->params[$match[1]]))
			return '(' . $this->params[$match[1]] . ')';

		return '(.*)'; //([^/]+) (anciennement)
	}

    /**
     * [call description]
     * @return [type] [description]
     */
    public function call(){
		if(is_string($this->callable)){
			$params = explode('#', $this->callable);
			$controller = "App\\Controller\\" . $params[0] . "Controller";
			$controller = new $controller();
            if(empty($this->matches)) $this->matches = null;
            if(empty($params)) $params = null;

			return call_user_func_array([$controller, 'action'], [$params[1], $this->matches]);
		}else
			return call_user_func_array($this->callable, $this->matches);
	}

    /**
     * Permet d'envoyer des paramètres à la fonction
     *
     * @param  string $param Nom du paramètre
     * @param  string $regex Séléction du paramètre (via syntae regex)
     * @return void
     */
	public function with($param, $regex){
		$this->params[$param] = str_replace('(', '(?:', $regex);
		return $this;
	}

    /**
     * [getUrl description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
	public function getUrl($params){
		$path = $this->path;
		foreach($params as $k => $v){
			$path = str_replace(":$k", $v, $path);
		}
		return $path;
	}
}
