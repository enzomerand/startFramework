<?php
/**
 * SocialAuth Class
 */

namespace Core\Auth;

use \Njasm\Soundcloud\SoundcloudFacade;
use Core\Social\Deezer\Deezer;
use Core\Social\Spotify;

/**
 * Cette classe permet de créer un système de connexion et d'inscription via
 * des APIs externes.
 *
 * @package startFramework\Core\Auth
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 * @see     Auth
 */
class SocialAuth extends Auth{

    /**
     * @var string
     */
	private $token;

    /**
     * @var
     * @var
     * @var
     * @var
     */
	public $sc,
	       $dz,
	       $sp,
	       $sp_api;

    /**
     * [setSocialAuth description]
     * @param [type] $services [description]
     */
	public function setSocialAuth($services){
		if(is_array($services)){
			foreach($services as $service){
			    $service = 'set' . $service;
				$this->$service();
			}
		}
	}

	/* -- SOUNDCLOUD -- */

	public function setSoundCloud(){
		$this->sc = new SoundcloudFacade($this->config->get('sc_client_id'), $this->config->get('sc_client_secret'), $this->config->get('sc_redirect_uri'));
	}

	public function getSoundCloudAuthUrl(){
		return $this->sc->getAuthUrl();
	}

	public function setSoundCloudToken(){
		if(isset($_GET['code'])){
		    $this->sc->codeForToken($_GET['code']);
			return true;
		}
	}

	public function refreshSoundCloudToken(){
		var_dump($this->sc->refreshAccessToken());
	}

	private function getSoundCloudToken(){
		if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = \'soundcloud\'', [user_id]) == 1){
			$user = $this->db->prepare('SELECT * FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = \'soundcloud\'', [user_id], null, true);
		    if($user)
				return $user->social_login_token;
		}
	}

	public function getSoundCloudData($data = true){
		if($data === true){
			$this->sc->setAccessToken($this->getSoundCloudToken());
			return $this->sc->get('/me')->request();
		}elseif($data === false){
			return $this->sc->get('/me')->request();
		}else {
			$this->sc->setAccessToken($this->getSoundCloudToken());
			return $this->sc->get($data)->request();
		}
	}

	public function putSoundCloudData($uri, $data = null){
		$this->sc->setAccessToken($this->getSoundCloudToken());
		return $this->sc->put($uri, $data)->request();
	}

	public function loginWithSoundCloud(){
		if($this->setSoundCloudToken() === true && isset($this->getSoundCloudData(false)->bodyObject()->id)){
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = \'soundcloud\'', [$this->getSoundCloudData(false)->bodyObject()->id]) == 1){
				$user = $this->db->prepare('SELECT social_login_user_id FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = \'soundcloud\'', [$this->getSoundCloudData(false)->bodyObject()->id], null, true);
				if($user){
					$this->db->execute("UPDATE " . PREFIX . "users_social_login SET social_login_raw = ?, social_login_token = ? WHERE social_login_app_id = ?", [serialize($this->getSoundCloudData(false)->bodyArray()), $this->sc->getAuthToken(), $this->getSoundCloudData(false)->bodyObject()->id]);
				    $this->createSession($user->social_login_user_id, true);
                    return true;
				}
			}else {
				//register
				$date = date("Y-m-d H:i:s", time());
				if($this->db->execute('INSERT INTO ' . PREFIX . 'users(user_date_create, user_account_activate, user_level) VALUES (?, 1, 2)', [$date])){
					$user_id = $this->db->lastInsertId();
					if($this->db->execute('INSERT INTO ' . PREFIX . 'users_social_login(social_login_user_id, social_login_app_id, social_login_type, social_login_raw, social_login_token) VALUES(?, ?, \'soundcloud\', ?, ?)', [$this->db->lastInsertId(), $this->getSoundCloudData(false)->bodyObject()->id, serialize($this->getSoundCloudData(false)->bodyArray()), $this->sc->getAuthToken()])){
						$this->createSession($user_id, true);
						return true;
					}
				}
			}
		}
	}

	public function associateWithSoundcloud(){
		if($this->setSoundCloudToken() === true && isset($this->getSoundCloudData(false)->bodyObject()->id)){
			if(defined('user_id'))
				if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'soundcloud']) == 0)
					if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getSoundCloudData(false)->bodyObject()->id, 'soundcloud']) == 0)
				        if($this->db->execute('INSERT INTO ' . PREFIX . 'users_social_login(social_login_user_id, social_login_app_id, social_login_type, social_login_raw, social_login_token) VALUES(?, ?, ?, ?, ?)', [user_id, $this->getSoundCloudData(false)->bodyObject()->id, 'soundcloud', serialize($this->getSoundCloudData(false)->bodyArray()), $this->sc->getAuthToken()]))
						    return true;
		}
	}

	public function dissociateWithSoundcloud(){
		if(defined('user_id'))
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'soundcloud']) > 0)
				if($this->db->execute('DELETE FROM ' . PREFIX . 'users_social_login WHERE social_login_type = ? AND social_login_user_id = ?', ['soundcloud', user_id]))
					return true;
	}

	/* -- SPOTIFY -- */

	public function setSpotify(){
		$this->sp = new Spotify\Session($this->config->get('sp_client_id'), $this->config->get('sp_client_secret'), $this->config->get('sp_redirect_uri'));
	}

	public function getSpotifyAuthUrl(){
		return $this->sp->getAuthorizeUrl(['scope' => ['user-read-private', 'user-read-email']]);
	}

	public function setSpotifyToken($token = null){
		$this->sp_api = new Spotify\SpotifyWebAPI();
		if(isset($_GET['code'])){
		    $this->sp->requestAccessToken($_GET['code']);
            $this->sp_api->setAccessToken($this->sp->getAccessToken());
			return true;
		}elseif($token != null){
			$this->sp_api->setAccessToken($token);
			return true;
		}
	}

	public function refreshSpotifyToken(){
		$this->sp->refreshAccessToken($this->sp->getRefreshToken());
        if($this->setSpotifyToken($this->sp->getAccessToken()));
			return true;
	}

	private function getSpotifyData(){
		return $this->sp_api->me();
	}

	public function loginWithSpotify(){
		if($this->setSpotifyToken() === true && isset($this->getSpotifyData()->id)){
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getSpotifyData()->id, 'spotify']) == 1){
				$user = $this->db->prepare('SELECT social_login_user_id FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getSpotifyData()->id, 'spotify'], null, true);
				if($user){
					$this->db->execute("UPDATE " . PREFIX . "users_social_login SET social_login_raw = ?, social_login_token = ? WHERE social_login_app_id = ?", [serialize($this->getSpotifyData()), $this->sp->getRefreshToken(), $this->getSpotifyData()->id]);
				    $this->createSession($user->social_login_user_id, true);
                    return true;
				}
			}
		}
	}
	public function associateWithSpotify(){
		if($this->setSpotifyToken() === true && isset($this->getSpotifyData()->id)){
			if(defined('user_id'))
				if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'spotify']) == 0)
					if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getSpotifyData()->id, 'spotify']) == 0)
						if($this->db->execute('INSERT INTO ' . PREFIX . 'users_social_login(social_login_user_id, social_login_app_id, social_login_type, social_login_raw, social_login_token) VALUES(?, ?, ?, ?, ?)', [user_id, $this->getSpotifyData()->id, 'spotify', serialize($this->getSpotifyData()), $this->sp->getRefreshToken()]))
						    return true;
		}
	}

	public function dissociateWithSpotify(){
		if(defined('user_id'))
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'spotify']) > 0)
				if($this->db->execute('DELETE FROM ' . PREFIX . 'users_social_login WHERE social_login_type = ? AND social_login_user_id = ?', ['spotify', user_id]))
					return true;
	}

	/* -- DEEZER -- */

	public function setDeezer(){
		$this->dz = new Deezer(['app_id' => $this->config->get('dz_app_id'), 'app_secret' => $this->config->get('dz_secret_key'), 'my_url' => $this->config->get('dz_redirect_uri')]);
	}

	public function getDeezerAuthUrl(){
		if(!isset($_SESSION['dz']) || $_SESSION['dz'] == null)
		    $_SESSION['dz'] = md5(uniqid(rand(), TRUE));
		return "https://connect.deezer.com/oauth/auth.php?app_id=" . $this->dz->config['app_id'] . "&redirect_uri=" . urlencode($this->dz->config['my_url']) . "&perms=basic_access,email,offline_access&state=" . $_SESSION['dz'];
	}

	public function setDeezerToken(){
		if(isset($_GET['code']) && isset($_REQUEST['state']) && !empty($_SESSION['dz'])){
			if($_REQUEST['state'] == $_SESSION['dz']){
	            $response  = file_get_contents("https://connect.deezer.com/oauth/access_token.php?app_id=" . $this->dz->config['app_id'] . "&secret=" . $this->dz->config['app_secret'] . "&code=" . $_GET['code']);
				$params = null;
	            parse_str($response, $params);
				$this->dz->setToken($params['access_token']);
			    return true;
			}
		}
	}

	private function getDeezerData(){
		return $this->dz->getUser();
	}

	public function loginWithDeezer(){
		if($this->setDeezerToken() === true && isset($this->getDeezerData()->id)){
			unset($_SESSION['dz']);
			$_SESSION['dz'] = null;
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getDeezerData()->id, 'deezer']) == 1){
				$user = $this->db->prepare('SELECT social_login_user_id FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getDeezerData()->id, 'deezer'], null, true);
				if($user){
					$this->db->execute("UPDATE " . PREFIX . "users_social_login SET social_login_raw = ?, social_login_token = ? WHERE social_login_app_id = ?", [serialize($this->getDeezerData()), $this->dz->getToken(), $this->getDeezerData()->id]);
				    $this->createSession($user->social_login_user_id, true);
                    return true;
				}
			}
		}
	}

	public function associateWithDeezer(){
		if($this->setDeezerToken() === true && isset($this->getDeezerData()->id)){
			if(defined('user_id'))
				if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'deezer']) == 0)
					if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_app_id = ? AND social_login_type = ?', [$this->getDeezerData()->id, 'deezer']) == 0)
						if($this->db->execute('INSERT INTO ' . PREFIX . 'users_social_login(social_login_user_id, social_login_app_id, social_login_type, social_login_raw, social_login_token) VALUES(?, ?, ?, ?, ?)', [user_id, $this->getDeezerData()->id, 'deezer', serialize($this->getDeezerData()), $this->dz->getToken()]))
					    	return true;
		}
	}

	public function dissociateWithDeezer(){
		if(defined('user_id'))
			if($this->db->count('SELECT COUNT(*) FROM ' . PREFIX . 'users_social_login WHERE social_login_user_id = ? AND social_login_type = ?', [user_id, 'deezer']) > 0)
				if($this->db->execute('DELETE FROM ' . PREFIX . 'users_social_login WHERE social_login_type = ? AND social_login_user_id = ?', ['deezer', user_id]))
					return true;
	}
}
