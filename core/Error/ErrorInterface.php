<?php
/**
 * ErrorInterface Interface
 */

namespace Core\Error;

/**
 * Cette interface permet de gérer la gestion des erreurs
 *
 * @package startFramework\Core\Error
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
interface ErrorInterface {

    /**
     * Permet de retourner une erreur formatée
     *
     * @example Erreur en brute dans le fichier (à la ligne 25 pour l'exemple) :
     *              Bonjour %s, vous êtes disponible pendant %d minutes
     *
     *         On peut appeler la fonction suivante :
     *             <code>
     *                 $this->Error->getError(25, ['Jean', 5], '!');
     *             </code>
     *
     *         Erreur en brute dans le fichier (à la ligne 26 pour l'exemple) :
     *              Impossible d'accéder à la page
     *
     *         On peut appeler la fonction suivante :
     *             <code>
     *                 $this->Error->getError(26);
     *             </code>
     *
     * @see    sprintf()
     * @param  int    $code   Code de l'erreur
     * @param  array  $vars   Variables dynamiques à passer
     * @param  string $end    Dernier caractère de la chaîne
     * @return string
     */
    public function getError($code, $vars = [], $end = 'default');

    /**
     * Permet d'afficher une erreur au lieu de la retourner
     *
     * @see    getError()
     * @param  int    $code   Code de l'erreur
     * @param  array  $vars   Variables dynamiques à passer
     * @param  string $end    Dernier caractère de la chaîne
     */
    public function echoError($code, $vars = [], $end = 'default');

    /**
     * Permet de définir le dernier caractère de la chaîne de caractère de l'erreur
     *
     * @param string $string
     */
    public function setEnd($string);

    /**
     * Permet de définir la location du fihier contenant les erreurs
     *
     * @param string $string
     */
    public function setFile($string);

    /**
     * Permet de modifier une erreur dynamiquement
     *
     * @param  int|array    $code   Code(s) de(s) l'erreur(s) à modifier
     * @param  string|array $string Texte(s) de remplacement
     * @return void
     */
    public function changeErrorText($code, $string);

    /**
     * Permet de modifier le fichier erreur
     *
     * @param  int    $code   Code de l'erreur à modifier
     * @param  string $error Texte de remplacement
     */
    public function editData($code, $error);
}
