<?php
/**
 * AppController Class
 */

namespace App\Controller;

use Core\Controller\Controller;
use App;
use Core\Auth\SocialAuth;
# use Core\Sitemap\Sitemap;
# use Core\Sitemap\SitemapIndex;

/**
 * Cette classe permet d'éxécuter des librairies et de définir des paramètres dynamiques
 *
 * @package startFramework\App\Controller
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class AppController extends Controller{

	/**
	 * Contient le nom du site
	 *
	 * @deprecated Vous pouvez utiliser directement les paramètres dynamiues
	 *             via la base de donnée ou définir votre propre variable statique (au sein de cette classe)
	 * @see Website (classe) Pour utiliser via la base de donnée
	 * @var string
	 */
	public $title = 'Default website name';

	/**
	 * Permet de définir la page actuelle
	 *
	 * @see __construct()
	 * @var string
	 */
	public $page = '/';

    /**
     * Nom de la page (template) à utiliser
     *
     * @var string
     */
	protected $template = 'default';

	/**
	 * L'instance de la classe App
	 *
	 * @var App
	 */
	protected $app;

	/**
	 * Instance de la classe Auth/SocialAuth
	 *
	 * @var SocialAuth
	 */
	protected $auth;

	/**
	 * Indique si l'utilisateur est connecté
	 *
	 * @var bool
	 */
	protected $logged;

    /**
     * Charge les librairies et définit quelques paramètres statiques ou dynamiques
     */
	public function __construct(){
		$this->viewPath = ROOT . '/app/Views/main/';
		$this->app = App::getInstance();

        $this->loadElement(['Website', 'Data']);
		$this->title = $this->Website->sgetTitle('name_site');
		$this->desc = $this->Website->sgetDesc('desc_site');
		define('WEBSITE_NAME', $this->title);

		$this->auth = new SocialAuth($this->app->getDb());
		$this->logged = $this->auth->logged() ? true : false;
		$this->page = $_SERVER['REQUEST_URI'];

        if($this->logged === true)
            $this->auth->setUserId();
	}

    /**
     * Permet d'initaliser le chargement des éléments
     *
     * @param string|array $element_name Contient le(s) élément(s) à charger
     * @param bool         $entity       Définit si l'élément doit se créer et s'associer une entité
     * @param string       $class_name   Suffix de l'élément
     * @param bool         $use_db       Indique l'utilisation de la base de donnée
     */
	final protected function loadElement($element_name, $entity = false, $class_name = 'Element', $use_db = true){
		if(is_array($element_name)){
			$array = $element_name;
			foreach($array as $element)
			    $this->setElement($element, $entity, $class_name, $use_db);
	    }else
		    $this->setElement($element_name, $entity, $class_name, $use_db);
	}

    /**
     * Permet de définir une variable qui contiendra une nstance d'un élément à charger
     *
     * @see   App->getElement()
     * @param string $element_name Nom de l'élément
     * @param bool   $entity       Définit si l'élément doit se créer et s'associer une entité
     * @param string $class_name   Suffix de l'élément
     * @param bool   $use_db       Indique l'utilisation de la base de donnée
     */
	private function setElement($element_name, $entity = false, $class_name = 'Element', $use_db = true){
		$this->$element_name = $this->app->getElement($element_name, $entity, $class_name, $use_db);
	}

    /**
     * Permet de rediriger vers la page 404
     *
     * @param  string $page_name
     */
	public function set404($page_name = '404'){
		header("Location: /$page_name/");
		exit;
	}

    /**
     * Permet d'afficher la page 404
     *
     * @param  string $template
     * @return void
     */
	public function get404($template = '404'){
		$this->render($template, compact(''));
	}

    /**
     * Permet de retourner une simple alerte formatée
     *
     * @example Cette fonction est à titre d'example
     * @param  string $text Texte à afficher dans l'alerte
     * @param  string $type Extension de classe (CSS)
     * @return string
     */
	protected function getAlert($text, $type = null){
		$type = ($type != null) ? ' alert-' . $type : null;
		return '<div class="alert' . $type . '">' . $text . '</div>';
	}

}
