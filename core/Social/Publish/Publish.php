<?php

namespace Core\Social\Publish;

use Facebook\Facebook;
use Twitter\Codebird;
use Core\Social\SoundCloud\Services_Soundcloud;

class Publish extends \Core\Social\SocialUser{
	
	use SoundCloudPublish, TwitterPublish, FacebookPublish;
	
	private $module;
	private $modules;
	
	public function __construct($modules){
		/* if($modules == null){
			$dir = '../core/Social/Publish/';
			$dir_files = scandir($dir);
			$files = null;
			foreach($dir_files as $file)
			    if(is_file($dir . $file) && $file != 'Publish.php')
					$files .= $file;
			$this->modules = explode('.', str_replace('Publish.php', '.', $files));
			array_pop($this->modules);
		}else */
	    $this->modules = $modules;
		
		foreach($this->modules as $module => $values)
		    $this->setModule($module);
	}
	
	public function call($function, $params = []){
		$service = strtolower(substr($function, -2));
		if($this->$service != false || $this->$service != null){
			/* if(method_exists('SoundCloudPublish', $function)){
				$this->$function($params);
			}else
				var_dump('fonction ' . $function . ' existe pas'); */
			$this->$function($params);
	    }else
			var_dump($service . ' non configuré'); //A modifier
	}
	
	private function setModule($module){
		if(array_key_exists($module, $this->modules)){
			$setter = 'set' . $module;
			$this->$setter();
		}
	}
	
	protected function setFacebook(){
		if(in_array(str_replace('set', '', __FUNCTION__), $this->modules)){
			foreach($this->modules as $module => $keys){
				$this->fb = new Facebook([
				   'app_id' => $key['APP_ID'],
				   'app_secret' => $key['APP_SECRET'],
				   'default_graph_version' => 'v2.5'
				]);
			}
		}
	}
	
	protected function setSoundCloud(){
		if(array_key_exists(str_replace('set', '', __FUNCTION__), $this->modules)){
			foreach($this->modules as $module => $key){
				$this->sc = new Services_Soundcloud(
					$key['CLIENT_ID'],
					$key['CLIENT_SECRET'],
					$key['REDIRECT_URL']
				);
			}
		}
	}
}