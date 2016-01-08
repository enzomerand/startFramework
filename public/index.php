<?php
    define('ROOT', dirname(__DIR__));
	require ROOT . '/app/App.php';
	App::load();
	
	$router = new \Core\Router\Router($_GET['url']);
	
	/*
	    Indiquez vos pages pour le router
	    Ex: $router->get('/404/', "App#display404");
	        $router->get('/posts/:slug/', "Page#getPost")->with('slug', '([a-z0-9-]+)');
	        $router->post('/:page/', "Page#getPage")->with('page', '([a-z0-9-]+)');
	        Pour accepter tout type de cractères, utiliser le regex (.*)
	*/
	
	$router->get('/', "Home#home");
	
	$router->get('/:page/', "Page#getPage");
	
	$router->run();
?>
