<?php
/**
 * ErrorDb Class
 */

namespace Core\Error;

use Core\Database\Database;

/**
 * Cette classe permet de gérer et formater les erreurs via une base de donnée
 *
 * @package startFramework\Core\Error
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class ErrorDb implements ErrorInterface {

    /**
     * Dernier aractère de la chaîne de caractère de l'erreur
     *
     * @var string|null
     */
    private $end = '.';

    /**
     * Fichier où sont répertoriés les erreurs
     *
     * @var string
     */
    private $error_file = ROOT . "/app/errors.txt";

    /**
     * Nom de la table contenant les erreurs
     *
     * @var string
     */
    private $table = PREFIX . 'errors';

    /**
     * Nom de la colonne contenant les IDs des erreurs
     *
     * @var string
     */
    private $column_id = 'error_id';

    /**
     * Nom de la colonne contenant les erreurs
     *
     * @var string
     */
    private $column_text = 'error_text';

    /**
     * Permet d'effectuer des requêtes SQL sur la base de donnée
     *
     * @var Database
     */
    private $db;

    /**
     * Initialise la classe et connecte à la base de donnée
     *
     * @param Database $db API Database
     */
    public function __construct(Database $db){
        $this->db = $db;
    }

    public function getError($code, $vars = [], $end = 'default'){
        if($end == 'defaut')
            $end = $this->end;

        if(ctype_digit($code) || is_int($code)){
		    $error = $this->db->prepare("SELECT * FROM $this->table_prefix WHERE $this->column_id_prefix = ?", [$code], null, true);

            if(isset($vars[0]))
                $error = vsprintf($error, $vars);

	        return trim($error) . $end;
		}
    }

    public function echoError($code, $vars = [], $end = 'default'){
        echo $this->getError($code, $vars, $end);
    }

    public function setEnd($string){
        $this->end = $string;
    }

    public function setFile($string){
        $this->error_file = $string;
    }

    public function changeErrorText($code, $string){
        if(is_array($code) && is_array($string)){
            $replace = array_combine($code, $string);
            foreach($replace as $key => $value)
                $this->editFile($key, $value);

        }elseif((is_array($code) && !is_array($string)) || (is_array($string) && !is_array($code))){
            $code = is_array($code) ? $code[0] : $code;
            $string = is_array($string) ? $string[0] : $string;
            $this->editFile($code, $string);
        }else
            $this->editFile($code, $string);
    }

    private function editData($code, $error){
        if(ctype_digit($code) || is_int($code)){
            $error = $this->db->execute("UPDATE $this->table SET $this->column_text = ? WHERE $this->column_id = ?", [$error, $code]);
        }
    }

    /**
     * Permet de définir le nom de la table contenant les erreurs
     *
     * @param string $string
     */
    public function setTable($string){
        $this->table = $string;
    }

    /**
     * Permet de définir le nom de la colonne contenant les IDs des erreurs
     *
     * @param string $string
     */
    public function setColumnId($string){
        $this->column_id = $string;
    }

    /**
     * Permet de définir le nom de la colonne contenant les erreurs
     *
     * @param string $string
     */
    public function setColumnText($string){
        $this->column_text = $string;
    }
}

?>
