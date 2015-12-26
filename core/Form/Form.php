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
	
	protected function surround($html, $label, $class = false){
		$class = ($class) ? 'class="' . $class . '"' : null;
		
		return "<{$this->surround}{$class}>{$label}{$html}</{$this->surround}>";
	}
	
	protected function getValue($index){
		return isset($this->data[$index]) ? $this->data[$index] : null;
	}
	
	public function input($name = null, $label = null, $options = []){
		$required = !empty($options['required']) ? ' required=""' : null;
		$disabled = !empty($options['disabled']) ? ' disabled' : null;
		$value = empty($options['value']) ? ' value="' . $this->getValue($name) . '"' : (($options['value'] === false) ? null : ' value="' . $options['value'] . '"');
		$type = !empty($options['type']) ? 'type="' . $options['type'] . '"' : 'type="text"';
		$placeholder = isset($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$id = isset($options['id']) ? " id=\"{$options['id']}\"" : null;
		$label = (!empty($label)) ? "<label>{$label}</label>" : null;
		return $this->surround('<input ' . $type . ' name="' . $name . '" ' . $value . $placeholder . $id . $disabled . $required . ' />', $label);
	}
	
	public function textarea($name = null, $label = null, $options = []){
		$label = !empty($label) ? "<label>{$label}</label>" : null;
		$name_b = !empty($name) ? 'name="' . $name . '"' : null;
		$placeholder = !empty($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$value = empty($options['value']) ? $this->getValue($name) : (($options['value'] === false) ? null : $options['value']);
		$rows = !empty($options['rows']) ? 'rows="' . $options['rows'] . '"' : null;
		
		return $label . "<textarea spellcheck=\"true\" {$name_b} {$placeholder} {$rows}>{$value}</textarea>";
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
