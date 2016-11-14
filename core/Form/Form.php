<?php
/**
 * Form Class
 */

namespace Core\Form;

use ReCaptcha\ReCaptcha;

/**
 * Cette classe permet d'afficher des éléments correctement formatés d'un
 * formulaire
 *
 * @package startFramework\Core\Form
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 */
class Form {

	/**
	 * Contient les données envoyées du formulaire
	 *
	 * @var array
	 */
	private $data;

    /**
     * Permet d'utiliser la librairie  Google reCaptcha
     *
     * @var ReCaptcha
     */
	private $reCaptcha;

	/**
	 * Type de balise qui entoure les éléménts
	 *
	 * @var string
	 */
	protected $surround = 'p';

	/**
	 * Initialise la construction du formulaire
	 *
	 * @param array $data Contient les valeurs des données envoyées
	 */
	public function __construct($data){
		$this->data = $data;
		$this->reCaptcha = new ReCaptcha(CAPTCHA_PRIVATE);
	}

	/**
	 * Met en forme l'élément voulue et l'entoure de balise(s)
	 *
	 * @param  string      $html     Contient la base de l'élément
	 * @param  string      $label    Affichage d'un label avec l'élément
	 * @param  bool|string $class    Afficher une classe CSS personnalisée
	 * @param  bool        $surround Afficher ou non les balises HTML qui entoure l'élément voulu
	 * @return string                Affichage de l'élément formaté
	 */
	protected function surround($html, $label, $class = false, $surround = false){
		$class = ($class) ? 'class="' . $class . '"' : null;
		if($surround == true)
		    return "<{$this->surround}{$class}>{$label}{$html}</{$this->surround}>";
		else
			return $label . $html;
	}

	/**
	 * Permet de retrouver la valeur envoyée avec le formulaire, nottament en cas d'échec
	 *
	 * @param  string      $index Élément associé à la valeur envoyée
	 * @return string|null
	 */
	protected function getValue($index){
		return isset($this->data[$index]) ? $this->data[$index] : null;
	}

	/**
	 * Créé un élément de formulaire de type "input"
	 *
	 * @param  string $name    Nom de l'élément
	 * @param  string $label   Label (facultatif)
	 * @param  array  $options Autres paramètres
	 * @return string
	 */
	public function input($name = null, $label = null, $options = []){
		$required = !empty($options['required']) ? ' required=""' : null;
		$disabled = !empty($options['disabled']) ? ' disabled' : null;
		$value = ($options['value'] == true || empty($options['value'])) ? ' value="' . $this->getValue($name) . '"' : (($options['value'] === false) ? null : ' value="' . $options['value'] . '"');
		$type = !empty($options['type']) ? 'type="' . $options['type'] . '"' : 'type="text"';
		$placeholder = isset($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$id = isset($options['id']) ? " id=\"{$options['id']}\"" : null;
		$label = (!empty($label)) ? "<label>{$label}</label>" : null;
		$class = !empty($options['class']) ? " class=\"{$options['class']}\"" : null;
		$surround = !empty($options['surround']) ? $options['surround'] : false;

		return $this->surround('<input ' . $type . ' name="' . $name . '" ' . $value . $placeholder . $id . $disabled . $required . $class . ' />', $label, false, $surround);
	}

	/**
	 * Créé un élément de formulaire de type "textarea"
	 *
	 * @param  string $name    Nom de l'élément
	 * @param  string $label   Label (facultatif)
	 * @param  array  $options Autres paramètres
	 * @return string
	 */
	public function textarea($name = null, $label = null, $options = []){
		$label = !empty($label) ? "<label>{$label}</label>" : null;
		$name_b = !empty($name) ? 'name="' . $name . '"' : null;
		$placeholder = !empty($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$value = ($options['value'] == true || empty($options['value'])) ? $this->getValue($name) : (($options['value'] === false) ? null : $options['value']);
		$rows = !empty($options['rows']) ? 'rows="' . $options['rows'] . '"' : null;
		$class = !empty($options['class']) ? "class=\"{$options['class']}\"" : null;

		return $label . "<textarea spellcheck=\"true\" {$name_b} {$placeholder} {$rows} {$class}>{$value}</textarea>";
	}

	/**
	 * Créé un code CAPTCHA
	 *
	 * @param  string $public_key Clé publique de l'aPI Google reCaptcha
	 * @return string             Retourne le code captcha
	 */
	public function captcha($public_key = null){
		if(defined('CAPTCHA_PUBLIC'))
			return '<div class="g-recaptcha" data-sitekey="' . CAPTCHA_PUBLIC . '"></div>';
		elseif($public_key != null)
			return '<div class="g-recaptcha" data-sitekey="' . $public_key . '"></div>';
	}

	/**
	 * Créé un élément de formulaire de type "checkbox"
	 *
	 * @param  string     $name    Nom de l'élément
	 * @param  string|int $value   Valeur de l'élément
	 * @param  string     $text    Texte de description de l'élément
	 * @param  bool       $checked État de l'élément
	 * @return string
	 */
	public function checkbox($name = null, $value = 1, $text = 'Se souvenir de moi', $checked = null){
		return '<input type="checkbox" name="' . $name . '" value="' . $value . '"' . (($checked) ? ' ' . $checked : null) . '> ' . $text;
	}
}
