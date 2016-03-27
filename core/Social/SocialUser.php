<?php

namespace Core\Social;

use Core\Social\SoundCloud\Services_Soundcloud;

class SocialUser extends Social{
	
	private $config;
	private $user_data;
	
	protected $fb;
	
	protected $tw;
	
	protected $sc;
	
	protected function setFacebook(){
		
	}
	
	protected function setTwitter(){
		
	}
	
	protected function setGPlus(){
		
	}
	
	protected function setSoundCloud(){
		$this->sc = new Services_Soundcloud(
			'945f47c436c29321b09fca87c89c59ff',
			'4072f13882af3f1c98ce983e553ecc34',
			'http://mtfo.fr/manage/login.php?do=soundcloud'
		);
	}
}