<?php
/**
 * Error Class
 */

namespace Core\Error;

/**
 * Cette classe permet de gérer et formater les erreurs
 *
 * @package startFramework\Core\Error
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Error implements ErrorInterface {

    /**
     * Dernier aractère de la chaîne de caractère de l'erreur
     *
     * @var string|null
     */
    private $end = '.';

    /**
     * Fichier où sont répertoriés les erreurs, indiquer le fichier par défaut
     * Si vous changez le nom du dossier contenant les erreurs, changez également le nom à la ligne 72:141
     *
     * @var string
     */
    private $error_file = ROOT . "/app/errors/errors.txt";

    /**
     * Permet de récupérer une erreur
     *
     * @example <code>
     *              // Error contient l'instance de la classe Error pour l'exemple
     *              // $this->Error->get$wantError();
     *              $this->Error->getAuthError(); -> Le fichier chargé sera 'errors-auth.txt'
     *          </code>
     *          Si on souhaite un retour d'erreur par défaut, on appelle directement la fonction getError()
     *          Pour afficher directement une erreur, on utilise la fonction echoError().
     *
     * @param  void  $method
     * @param  array $params
     * @return void
     */
    public function __call($method, $params){
        $this->error_file = str_replace('.txt', '-' . str_replace('echo', '', str_replace('error', '', str_replace('get', '', strtolower($method)))) . '.txt', $this->error_file);
        switch($params){
            case isset($params[2]):
                if (!strncasecmp($method, 'echo', 4))
                    $this->echoError($params[0], $params[1], $params[2]);
                else
                    return $this->getError($params[0], $params[1], $params[2]);
                break;
            case isset($params[1]):
                if (!strncasecmp($method, 'echo', 4)) //timed out ?
                    $this->echoError($params[0], $params[1]);
                else
                    return $this->getError($params[0], $params[1]);
                break;
            case isset($params[0]):
                if (!strncasecmp($method, 'echo', 4))
                    $this->echoError($params[0]);
                else
                    return $this->getError($params[0]);
                break;
        }
    }

    public function getError($code, $vars = [], $end = 'default'){
        if(!file_exists($this->error_file)){
            $file_name = isset(explode('-', $this->error_file)[1]) ? str_replace('.txt', '', explode('-', $this->error_file)[1]) : explode('errors/', strstr($this->error_file, '.txt', true))[1];
            return "Erreur ! Le fichier d'erreur \"$file_name\" est introuvable.";
        }

        if($end == 'default')
            $end = $this->end;

        if(ctype_digit($code) || is_int($code)){
		    $file = fopen($this->error_file, 'r');
            $i = 0;

            while ($i < $code){
                $error = fgets($file);
                ++$i;
            }
            fclose($file);

            if(isset($vars[0]))
                $error = vsprintf($error, $vars);

	        return trim($error) . ' ' . $end;
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

    public function editData($code, $error){
        if(ctype_digit($code) || is_int($code)){
            $file = fopen($this->error_file, 'r+');
            $i = 0;

            while ($i < $code){
                fgets($file);
                if($i == $code)
                    fputs($file, $error);

                ++$i;
            }

            fclose($file);
        }
    }
}

?>
