<?php

namespace App\Controller;

use Core\Controller\Controller;
use App;
use Core\Auth\SocialAuth;
use Core\Sitemap\Sitemap;
use Core\Sitemap\SitemapIndex;

class AppController extends Controller{
	
	public $title = 'MTFO Music';
	public $desc;
	public $page = '/';
	public $logo_color = 'white';
	
	protected $template = 'default';
	protected $app;
	protected $auth;
	protected $logged;
    protected $modules = [];
	
	public function __construct(){
		$this->viewPath = ROOT . '/app/Views/main/';
		$this->app = App::getInstance();
		$this->auth = new SocialAuth($this->app->getDb());
		$this->logged = $this->auth->logged() ? true : false;
		$this->page = $_SERVER['REQUEST_URI'];
        
        if($this->logged === true)
            $this->auth->setUserId();
		
		$this->logo_color = $this->page == '/' ? 'white' : 'black';
		
		$this->loadElement(['Website', 'Data']);
		$this->updateSitemap();
		
		$this->Website->setTitle('name_site');
		$this->title = $this->Website->getTitle();
		$this->Website->setDesc('desc_site');
		$this->desc = $this->Website->getDesc();
		$this->auth->setNameSite(WEBSITE_NAME);
        
        $this->loadModules();
	}
	
	final protected function loadElement($element_name, $entity = false, $class_name = 'Element', $use_db = true){
		if(is_array($element_name)){
			$array = $element_name;
			foreach($array as $element)
			    $this->setElement($element, $entity, $class_name, $use_db);
	    }else
		    $this->setElement($element_name, $entity, $class_name, $use_db);
	}
    
    final private function loadModules(){
        $dir = '../core/Modules/';
        $dir_files = scandir($dir);
        foreach($dir_files as $file){
            if(is_file($dir . $file)){
                $module_load = 'Core\Modules\\' . str_replace('.php', '', $file);
                $module = str_replace('.php', '', $file);
                $this->$module = new $module_load();
                if(isset($this->$module->module_for_controller) && $this->$module->module_for_controller != null){
                    $module_for = $module . '_controller';
                    $this->modules[$module_for] = [];
					array_push($this->modules[$module_for], $module, $this->$module->module_for_controller);
                    if(isset($this->$module->module_require_db))
                        array_push($this->modules[$module_for], $this->$module->module_require_db);
                    $this->$module = null;
				}else
                    if(isset($this->$module->module_require_db) && $this->$module->module_require_db == true){
                        $this->$module = null;
                        $this->$module = new $module($this->app->getDb());
                    }
            }
        }
        
    }
    
    final protected function loadModule($module){
        $module_name = $module[0];
        $module_loc = '\Core\Modules\\' . $module_name;
        if(isset($module[2]) && $module[2] === true)
            $this->$module_name = new $module_loc($this->app->getDb());
        else
            $this->$module_name = new $module_loc();
    }
    
    protected function setModule($class = null){
        if($class != null){
            $module = str_replace('Controller', 'Module_controller', str_replace('App\Controller\User\\', '', $class));
            if(array_key_exists($module, $this->modules))
                $this->loadModule($this->modules[$module]);
        }
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
	
	final private function updateSitemap(){
		$artistsSitemap = new Sitemap(CURRENT_PATH . '/sitemap-artists.xml');
		$artists = $this->Data->getArtists(true, false)["data"];
		foreach($artists as $artist)
		$artistsSitemap->addItem(FULL_DOMAIN . "artists/{$artist->permalink}", $artist->_date, Sitemap::MONTHLY, 0.6);
		$artistsSitemap->write();
		$artistsSitemapUrls = $artistsSitemap->getSitemapUrls(FULL_DOMAIN);
		
		$artistsLabelSitemap = new Sitemap(CURRENT_PATH . '/sitemap-artists-label.xml');
		$artists = $this->Data->getArtists(true, false, 'ARTIST.label = 1')["data"];
		foreach($artists as $artist)
		$artistsLabelSitemap->addItem(FULL_DOMAIN . "artists/{$artist->permalink}", $artist->_date, Sitemap::MONTHLY, 0.6);
		$artistsLabelSitemap->write();
		$artistsLabelSitemapUrls = $artistsLabelSitemap->getSitemapUrls(FULL_DOMAIN);
		
		$pagesSitemap = new Sitemap(CURRENT_PATH . '/sitemap-home.xml');
		foreach($this->Website->getPages('sidebar_footer')["data"] as $page)
		$pagesSitemap->addItem(FULL_DOMAIN . $page->page_slug . '/');
		foreach($this->Website->getPages('sidebar_site')["data"] as $page)
		$pagesSitemap->addItem(FULL_DOMAIN . $page->page_slug . '/');
        $pagesSitemap->write();
		$pagesSitemapUrls = $pagesSitemap->getSitemapUrls(FULL_DOMAIN);
		
		$indexSitemap = new SitemapIndex(CURRENT_PATH . '/sitemap.xml');
		foreach ($pagesSitemapUrls as $url)
			$indexSitemap->addSitemap($url);
		foreach ($artistsSitemapUrls as $url)
			$indexSitemap->addSitemap($url);
		foreach ($artistsLabelSitemapUrls as $url)
			$indexSitemap->addSitemap($url);
		$indexSitemap->write();
	}
	
}
