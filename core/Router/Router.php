<?php
/**
 * Router Class
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
class Router {

    /**
     * @var string
     * @var array
     * @var array
     */
    private $url,
            $routes = [],
            $namedRoutes = [];

    /**
     * [__construct description]
     * @param [type] $url [description]
     */
    public function __construct($url){
        $this->url = $url;
    }

    /**
     * Permet d'accéder à une page via une requête GET uniquement
     *
     * @param  string $path     Slug de la page (ex: /users/me/)
     * @param  string $callable Fonction à appeler
     * @param  [type] $name     [description]
     * @return void
     */
    public function get($path, $callable, $name = null){
        return $this->add($path, $callable, $name, 'GET');
    }

    /**
     * Permet d'accéder à une page via une requête POST uniquement
     *
     * @param  string $path     Slug de la page (ex: /users/me/)
     * @param  string $callable Fonction à appeler
     * @param  [type] $name     [description]
     * @return void
     */
    public function post($path, $callable, $name = null){
        return $this->add($path, $callable, $name, 'POST');
    }

    /**
     * Permet d'accéder à une page via une requête PUT uniquement
     *
     * @param  string $path     Slug de la page (ex: /users/me/)
     * @param  string $callable Fonction à appeler
     * @param  [type] $name     [description]
     * @return void
     */
    public function put($path, $callable, $name = null){
        return $this->add($path, $callable, $name, 'PUT');
    }

    /**
     * Permet d'accéder à une page via une requête DELETE uniquement
     *
     * @param  string $path     Slug de la page (ex: /users/me/)
     * @param  string $callable Fonction à appeler
     * @param  [type] $name     [description]
     * @return void
     */
    public function delete($path, $callable, $name = null){
        return $this->add($path, $callable, $name, 'DELETE');
    }

    private function add($path, $callable, $name, $method){
        $route = new Route($path, $callable);
        $this->routes[$method][] = $route;
        if(is_string($callable) && $name === null){
            $name = $callable;
        }
        if($name){
            $this->namedRoutes[$name] = $route;
        }
        return $route;
    }

    public function run(){
        if(!isset($this->routes[$_SERVER['REQUEST_METHOD']])){
            throw new RouterException('REQUEST_METHOD does not exist');
        }
        foreach($this->routes[$_SERVER['REQUEST_METHOD']] as $route){
            if($route->match($this->url)){
                return $route->call();
            }
        }
        //throw new RouterException('No matching routes');
		header('Location: ' . FULL_DOMAIN . '404/');
	    exit;
    }

    /**
     * [url description]
     *
     * @throws RouterException
     * @param  [type] $name   [description]
     * @param  [type] $params [description]
     * @return void
     */
    public function url($name, $params = []){
        if(!isset($this->namedRoutes[$name])){
            //throw new RouterException('No route matches this name');
			header('Location: ' . FULL_DOMAIN . '404/');
			exit;
        }
        return $this->namedRoutes[$name]->getUrl($params);
    }

}
