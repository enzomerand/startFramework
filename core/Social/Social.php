<?php
/**
 * Social Class
 */

namespace Core\Social;

define('FACEBOOK_SDK_V4_SRC_DIR', __DIR__ . '/Facebook/');
require_once __DIR__ . '/Facebook/autoload.php';
require_once __DIR__ . '/Twitter/Codebird.php';

use Core\Config;
use Facebook\Facebook;
use Twitter\Codebird;
use Core\Social\SoundCloud\Services_Soundcloud;

/**
 * Cette classe permet d'initialiser et activer des APIs externes de réseaux sociaux
 *
 * @package startFramework\Core\Social
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 * @api
 * @todo    Terminer la classe et rendre les paramètres dynamiques via la bdd
 */
class Social {

	/**
	 * Configuration du site
	 *
	 * @var Config
	 */
	protected $config;

    /**
     * API Facebook
     *
     * @var Facebook
     */
	protected $fb;

    /**
     * ID de la page Facebook
     *
     * @var string|int
     */
    protected $fb_page_id;

    /**
     * fb_access_token de Facebook
     *
     * @var string
     */
    protected $fb_access_token;

    /**
     * API Twitter
     *
     * @var Codebird
     */
    protected $tw;

    /**
     * API Soundcloud
     *
     * @var Services_Soundcloud
     */
    protected $sc;

    /**
     * Initialisation de la classe
     */
	public function __construct(){
		$this->config = Config::getInstance(null);
	}

    /**
     * Paramétrage de l'API Facebook
     * @see Facebook
     */
	protected function setFacebook(){
		$this->fb_page_id = $this->config->get('fb_page_id');
		$this->fb_access_token = $this->config->get('fb_access_token');
		$this->fb = new Facebook([
		  'app_id' => $this->config->get('fb_app_id'),
		  'app_secret' => $this->config->get('fb_app_secret'),
		  'default_graph_version' => 'v2.5'
		]);
	}

    /**
     * Paramétrage de l'API Twitter
     * @see Codebir
     */
	protected function setTwitter(){
		Codebird::setConsumerKey($this->config->get('tw_consumer_key'), $this->config->get('tw_consumer_secret'));
		$this->tw = Codebird::getInstance();
		$this->tw->setToken($this->config->get('tw_access_token'), $this->config->get('tw_access_token_secret'));
	}

    /**
     * Paramétrage de l'API Google +
     */
	protected function setGPlus(){

	}

    /**
     * Paramétrage de l'API Soundcloud
     * @see Services_Soundcloud
     */
	protected function setSoundCloud(){
		$this->sc = new Services_Soundcloud(
			$this->config->get('sc_client_id'),
			$this->config->get('sc_client_secret'),
			$this->config->get('sc_redirect_url')
		);
	}
}
