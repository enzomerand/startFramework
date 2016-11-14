<?php
/**
 * BootstrapForm Class
 */

namespace Core\Form;

/**
 * Cette classe permet d'afficher des éléments correctement formatés d'un
 * formulaire adapté pour Bootstrap
 *
 * @package startFramework\Core\Form
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 * @see     Form
 */
class BootstrapForm extends Form{

    /**
     * Met en forme l'élément voulue et l'entoure de balise(s)
	 *
	 * @param  string      $html           Contient la base de l'élément
	 * @param  string      $label          Affichage d'un label avec l'élément
	 * @param  string|null $input_group    Permet d'afficher sous forme de "group", avec un icône avant
	 * @param  string|null $help_text      Permet d'afficher un texte d'aide en dessous de l'élément
	 * @param  bool        $surround       Afficher ou non les balises HTML qui entoure l'élément voulu
	 * @param  string|null $surround_class Afficher une classe CSS personnalisée sur les balises HTML qui entoure l'élément
     * @return string
     */
	protected function surround($html, $label, $input_group = null, $help_text = null, $surround = false, $surround_class = null){
		$html = !empty($input_group) ? '<div class="input-group"><span class="input-group-addon"><i class="fa fa-fw fa-' . $input_group . '"></i></span>' . $html . '</div>' : $html;
		if($surround == false)
			return $label . $html . $help_text;
		else
	    	return "<{$this->surround} class=\"form-group{$surround_class}\">{$label}{$html}{$help_text}</{$this->surround}>";
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
		$label = !empty($label) ? "<label>{$label}</label>" : null;
		$type = !empty($options['type']) ? 'type="' . $options['type'] . '"' : 'type="text"';
		$name_b = !empty($name) ? 'name="' . $name . '"' : null;
		$placeholder = !empty($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$id = !empty($options['id']) ? " id=\"{$options['id']}\"" : null;
		$required = !empty($options['required']) ? ' required=""' : null;
		$help_text = !empty($options['help-text']) ? "<small class=\"text-muted\">{$options['help-text']}</small>" : null;
		$disabled = !empty($options['disabled']) ? ' disabled' : null;
		$value = isset($options['value']) ? ($options['value'] === false ? '' : 'value="' . $options['value'] . '"') : 'value="' . $this->getValue($name) . '"';
		$input_group = !empty($options['input_group']) ? $options['input_group'] : null;
		$class = !empty($options['class']) ? ' ' . $options['class'] : null;
		$surround_class = !empty($options['surround-class']) ? ' ' . $options['surround-class'] : null;
		$surround = !empty($options['surround']) ? $options['surround'] : false;

		return $this->surround("<input class=\"form-control{$class}\" {$type} {$name_b} {$value} {$placeholder} {$id} {$required} {$disabled} />", $label, $input_group, $help_text, $surround, $surround_class);
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
		$value = empty($options['value']) ? $this->getValue($name) : (($options['value'] === false) ? null : $options['value']);
		$rows = !empty($options['rows']) ? 'rows="' . $options['rows'] . '"' : 'rows="3"';
		$help_text = !empty($options['help-text']) ? "<small class=\"text-muted\">{$options['help-text']}</small>" : null;
		$surround = !empty($options['surround']) ? $options['surround'] : false;

		return $this->surround("<textarea class=\"form-control\" {$name_b} {$placeholder} {$rows}>{$value}</textarea>", $label, null, $help_text, $surround);
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
		return '
		<p>
			<label class="c-input c-checkbox">
				<input type="checkbox" name="' . $name . '" value="' . $value . '"' . (($checked) ? ' checked="checked"' : null) . '>
				<span class="c-indicator"></span>
				' . $text . '
			</label>
		</p>
		';
	}

	/**
	 * Créé un élément de formulaire de type "input>file"
	 *
	 * @param  string $name    Nom de l'élément
	 * @param  string $label   Label (facultatif)
	 * @param  array  $options Autres paramètres
	 * @return string
	 */
	public function file($name, $label = null, $options = []){
		$help_text = !empty($options['help-text']) ? "<small class=\"text-muted\">{$options['help-text']}</small>" : null;
		return $this->surround('<input type="file" id="file" name="' . $name . '">', $label, null, $help_text);
	}

	/**
	 * Créé un élément de formulaire de type "select"
	 *
	 * @param  string $name    Nom de l'élément
	 * @param  array  $options Autres paramètres
	 * @return string
	 */
	public function select($name, $options = []){
		$opt = '';
		foreach($options as $option => $value)
		    $opt .= '<option value="' . $option . '">' . $value . '</option>';

		return '<select class="c-select" name="' . $name . '">' . $opt . '</select>';
	}
}
