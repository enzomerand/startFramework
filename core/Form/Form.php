<?php

namespace Core\Form;

class Form {
	private $data;
	private $reCaptcha;
	
	protected $surround = 'p';
	
	public function __construct($data){
		$this->data = $data;
		$this->reCaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_PRIVATE);
	}
	
	protected function surround($html, $label, $class = false, $surround = false){
		$class = ($class) ? 'class="' . $class . '"' : null;
		if($surround == true)
		    return "<{$this->surround}{$class}>{$label}{$html}</{$this->surround}>";
		else
			return $label . $html;
	}
	
	protected function getValue($index){
		return isset($this->data[$index]) ? $this->data[$index] : null;
	}
	
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
	
	public function textarea($name = null, $label = null, $options = []){
		$label = !empty($label) ? "<label>{$label}</label>" : null;
		$name_b = !empty($name) ? 'name="' . $name . '"' : null;
		$placeholder = !empty($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$value = ($options['value'] == true || empty($options['value'])) ? $this->getValue($name) : (($options['value'] === false) ? null : $options['value']);
		$rows = !empty($options['rows']) ? 'rows="' . $options['rows'] . '"' : null;
		$class = !empty($options['class']) ? "class=\"{$options['class']}\"" : null;
		
		return $label . "<textarea spellcheck=\"true\" {$name_b} {$placeholder} {$rows} {$class}>{$value}</textarea>";
	}
	
	public function captcha($public_key = null){
		if(defined('CAPTCHA_PUBLIC'))
			return '<div class="g-recaptcha" data-sitekey="' . CAPTCHA_PUBLIC . '"></div>';
		elseif($public_key != null)
			return '<div class="g-recaptcha" data-sitekey="' . $public_key . '"></div>';
	}
	
	public function checkbox($name = null, $value = 1, $text = 'Se souvenir de moi', $checked = null){
		return '<input type="checkbox" name="' . $name . '" value="' . $value . '"' . (($checked) ? ' ' . $checked : null) . '> ' . $text;
	}
}
