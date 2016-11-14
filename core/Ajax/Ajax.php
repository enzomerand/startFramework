<?php
/**
 * Ajax Class
 */

namespace Core\Ajax;

/**
 * Ajax
 * Cette classe permet de gérer les requêtes Ajax
 *
 * @package startFramework\Core\Ajax
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 * @todo    Vérifier l'utilité de cette classe
 */
class Ajax {

    /**
     * @var string Contient les données de la requête
     */
    private $_data;

    /**
     * @param string $data Contient les données de la requête
     */
    public function __construct($data){
		echo json_encode($data);
    }
}

?>
