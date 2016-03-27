<?php

namespace Core\Social;

define('FACEBOOK_SDK_V4_SRC_DIR', __DIR__ . '/Facebook/');
require_once __DIR__ . '/Facebook/autoload.php';
require_once __DIR__ . '/Twitter/Codebird.php';

use Facebook\Facebook;
use Core\Config;
use Twitter\Codebird;
use SoundCloud\Services_Soundcloud;

class Social {
	
	private $config;
	
	protected $fb;
	protected $fb_page_id;
	protected $fb_access_token;
	
	protected $tw;
	
	protected $sc;
	
	public function __construct(){
		$this->config = Config::getInstance(null);
	}
	
	protected function setFacebook(){
		$this->fb_page_id = $this->config->get('fb_page_id');
		$this->fb_access_token = $this->config->get('fb_access_token');
		$this->fb = new Facebook([
		  'app_id' => $this->config->get('fb_app_id'),
		  'app_secret' => $this->config->get('fb_app_secret'),
		  'default_graph_version' => 'v2.5'
		]);
	}
	
	protected function setTwitter(){
		Codebird::setConsumerKey($this->config->get('tw_consumer_key'), $this->config->get('tw_consumer_secret'));
		$this->tw = Codebird::getInstance();
		$this->tw->setToken($this->config->get('tw_access_token'), $this->config->get('tw_access_token_secret'));
	}
	
	protected function setGPlus(){
		
	}
	
	protected function setSoundCloud(){
		$this->sc = new Services_Soundcloud(
			$this->config->get('sc_client_id'),
			$this->config->get('sc_client_secret'),
			$this->config->get('sc_redirect_url')
		);
	}
}