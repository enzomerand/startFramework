<?php

namespace Core\Auth;

use Core\Config;
use \Njasm\Soundcloud\SoundcloudFacade;

class SocialAuth extends Auth{
	
	private $config;
	
	private $sc;
	
	public function setConfig(){
		$this->config = Config::getInstance(ROOT . '/app/config.php');
	}
	
	public function setSoundCloud(){
		$this->sc = new SoundcloudFacade($this->config->get('sc_client_id'), $this->config->get('sc_client_secret'), $this->config->get('sc_redirect_uri'));
	}
	
	public function getSoundCloudAuthUrl(){
		return $this->sc->getAuthUrl();;
	}
	
	public function setSoundCloudToken(){
		if(isset($_GET['code'])){
		    $this->sc->codeForToken($_GET['code']);
			return true;
		}
	}
	
	private function getSoundCloudData(){
		return $this->sc->get('/me')->request();
	}
	
	public function loginWithSoundCloud(){
		if($this->setSoundCloudToken() === true && isset($this->getSoundCloudData()->bodyObject()->id)){
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = \'soundcloud\'', [$this->getSoundCloudData()->bodyObject()->id]) == 1){
				$user = $this->db->prepare('SELECT social_login_user_id FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = \'soundcloud\'', [$this->getSoundCloudData()->bodyObject()->id], null, true);
				if($user){
					$this->db->execute("UPDATE " . PREFIX . "users_social_login SET social_login_raw = ?, social_login_token = ? WHERE social_login_app_id = ?", [serialize($this->getSoundCloudData()->bodyArray()), $this->sc->getAuthToken(), $this->getSoundCloudData()->bodyObject()->id]);
				    $this->createSession($user->social_login_user_id, true);
                    return true;					
				}
			}
		}
	}
	
	public function registerWithSoundCloud(){
		if($this->setSoundCloudToken() === true && isset($this->getSoundCloudData()->bodyObject()->id)){
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = soundcloud') == 0){
				$date = date("Y-m-d H:i:s", time());
				if($this->db->execute('INSERT INTO ' . PREFIX . 'users(user_date_create, user_account_activate, user_level) VALUES (?, 1, 2)', [$date]))
					if($this->db->execute('INSERT INTO ' . PREFIX . 'users_social_login(social_login_user_id, social_login_type, social_login_raw, social_login_token) VALUES(?, soundcloud, ?)', [$this->db->lastInsertId(), serialize($this->getSoundCloudData()->bodyArray()), $this->sc->getAuthToken()]))
						return true;
			}
		}
	}
	
	public function associateWithSoundCloud(){
		if($this->setSoundCloudToken() === true && isset($this->getSoundCloudData()->bodyObject()->id))
			if(defined('user_id'))
				if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'soundcloud']) == 0)
					if($this->db->execute('INSERT INTO ' . PREFIX . 'users_social_login(social_login_user_id, social_login_app_id, social_login_type, social_login_raw, social_login_token) VALUES(?, ?, ?, ?, ?)', [user_id, $this->getSoundCloudData()->bodyObject()->id, 'soundcloud', serialize($this->getSoundCloudData()->bodyArray()), $this->sc->getAuthToken()]))
						return true;
	}
	
	public function connectWithFacebook(){}
	
	public function connectWithTwitter(){}
	
	public function finishRegister(){
		//créer dossier + mettre email + envoyer mail avec clé
	}
}
