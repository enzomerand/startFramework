<?php
/**
 * Controller Class
 */

namespace Core\Controller;

use App;

/**
 * Cette classe permet d'afficher une page et d'être héritée pour effectuer
 * des actions
 *
 * @package startFramework\Core\Controller
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
abstract class Controller{

    /**
     * Définit quel dossier utiliser
     *
     * @var string
     *
     */
    protected $viewPath;

    /**
     * Contient le template à utiliser
     *
     * @var string
     */
	protected $template;

    /**
     * Définit la vue utilisée (backend ou frontend)
     *
     * @see   render()
     * @param string $face
     */
	private function setFace($face = 'backend'){
		if($face == 'backend' || $face == 'frontend') return $face;
		else return 'backend';
	}

    /**
     * Permet l'affichage d'une page
     *
     * @param  string      $view      Définit la page à afficher
     * @param  array       $variables Variables à passer pour les utiliser dans la page à afficher, à envoyer avec la fonction compact()
     * @param  string|null $face      Définit la vue utilisée (backend ou frontend)
     */
	protected function render($view, $variables = [], $face = null){
		ob_start();
		extract($variables);
		require($this->viewPath . $view . '.php');
		$content = ob_get_clean();
		require(ROOT . "/app/Views/templates/{$this->setFace($face)}/{$this->template}.php");
	}

    /**
     * Permet d'effectuer une redirection simplement
     *
     * @param  string     $location Lien/page cible
     * @param  array|null $params   Paramètres optionnels (de type GET)
     */
	public function redirect($location = '/', $params = null){
		$get = null;
        if($params != null){
            foreach ($params as $key => $value)
    			$get .= '&' . $key . '=' . $value;
        }
		$get = ($get != null) ? '?' . ltrim($get, '&') : null;
		header("Location: {$location}{$get}");
		exit;
	}

	/**
	 * Permet d'éxécuter une méthode en vérifiant si l'utilisateur
	 * à les droits au sein du Controller (étendue à cette classe)
	 *
	 * @see    isRestricted()
	 * @param  string     $function      Nom de la fonction à utiliser
	 * @param  array|null $params        Paramètres optionnels pour la fonction
	 */
	public function action($function, $params = null){
		if(method_exists(get_class($this), $function)){
			$restriction_name = strtolower(preg_replace('/\B([A-Z])/', '_$1', $function));
			if(method_exists(get_class($this), 'isRestricted')) $this->isRestricted($function);
			$this->$function($params);
		}
	}

}
