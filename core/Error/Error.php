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
     * Fichier où sont répertoriés les erreurs
     *
     * @var string
     */
    private $error_file = ROOT . "/app/errors.txt";

    public function getError($code, $vars = [], $end = 'default'){
        if($end == 'defaut')
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
