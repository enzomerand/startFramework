<?php
/**
 * WebsiteElement Class
 */

namespace App\Element;

use Core\Element\Element;


/**
 * Cette classe permet de récupérer à la volée des données de la base de donnée
 *
 * @package startFramework\App\Controller
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 *
 * @method string get$name()
 * @method string sget$name(array $p)
 * @method string set$name(array [string, bool])
 */
class WebsiteElement extends Element{

    /**
     * Nom de la table
     *
     * @see getParam()
     * @var string
     */
    public $setting_table_name = 'setting';

	/**
	 * Préfix des colonnes de la table
	 *
	 * @see getParam()
	 * @var string
	 */
	public $setting_column_prefix = 'setting_';

	/**
	 * Nom de la colonne contenant la valeur de la table
	 *
	 * @see getParam()
	 * @var string
	 */
	public $setting_value_name_column = 'value';

    /**
     * Permet de récupérer des paramètres du site, via une seule table.
     * L'usage de cette méthode magique est uniquement réservées à l'obtention
     * ou la définition de ces paramètres (issues de la bdd) mais il est
     * possible de récupérer d'autres données du site avec des requêtes précises
     * en créant votre propre fonction.
     *
     * @param  string $m
     * @param  array  $p
     * @return void
     */
	public function __call($m, $p) {

		$v = strtolower(substr($m,3));
		if (!strncasecmp($m,'get',3))
			return $this->$v;
		elseif (!strncasecmp($m,'set',3)){
            if(!empty($p[1]))
                $this->$v = $this->getParam($p[0], $p[1]);
            else
                $this->$v = $this->getParam($p[0]);
        }elseif (!strncasecmp($m,'sget',3)){
            if(!empty($p[1]))
                $this->$v = $this->getParam($p[0], $p[1]);
            else
                $this->$v = $this->getParam($p[0]);
            return $this->$v;
        }

    }

    /**
     * Permet de récupérer un paramètre dans une table de votre base de donnée,
     * basé sur une colonne avec un id ou un slug
     *
     * @param  int|string    $id            ID ou slug du paramètre
     * @param  bool          $return_value  Définit si la fonction retourne l'objet de la requête où directement sa valeur
     * @return string|object
     */
	public function getParam($id, $return_value = true){
		$data = $this->db->prepare("SELECT * FROM " . PREFIX . $this->setting_table_name . " WHERE " . $this->setting_column_prefix . ((ctype_digit($id)) ? 'id' : 'slug') . " = ?", [$id], null, true);
        if($return_value === true){
			$value_column = $this->setting_column_prefix . $this->setting_value_name_column;
		    return $data->$value_column;
		}else
		    return $data;
	}

}
