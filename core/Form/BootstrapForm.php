<?php

namespace Core\Form;

class BootstrapForm extends Form{
	
	protected function surround($html, $label, $input_group = null, $help_text = null){
		$html = !empty($input_group) ? '<div class="input-group"><span class="input-group-addon"><i class="fa fa-fw fa-' . $input_group . '"></i></span>' . $html . '</div>' : $html;
		return "<{$this->surround} class=\"form-group\">{$label}{$html}{$help_text}</{$this->surround}>";
	}
	
	public function input($name = null, $label = null, $options = []){
		$label = !empty($label) ? "<label>{$label}</label>" : null;
		$type = !empty($options['type']) ? 'type="' . $options['type'] . '"' : 'type="text"';
		$name_b = !empty($name) ? 'name="' . $name . '"' : null;
		$placeholder = !empty($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$id = !empty($options['id']) ? " id=\"{$options['id']}\"" : null;
		$required = !empty($options['required']) ? ' required=""' : null;
		$help_text = !empty($options['help-text']) ? "<small class=\"text-muted\">{$options['help-text']}</small>" : null;
		$disabled = !empty($options['disabled']) ? ' disabled' : null;
		$value = empty($options['value']) ? 'value="' . $this->getValue($name) . '"' : (($options['value'] === false) ? null : 'value="' . $options['value'] . '"');
		$input_group = !empty($options['input_group']) ? $options['input_group'] : null;
		
		return $this->surround("<input class=\"form-control\" {$type} {$name_b} {$value} {$placeholder} {$id} {$required} {$disabled} />", $label, $input_group, $help_text);
	}
	
	public function textarea($name = null, $label = null, $options = []){
		$label = !empty($label) ? "<label>{$label}</label>" : null;
		$name_b = !empty($name) ? 'name="' . $name . '"' : null;
		$placeholder = !empty($options['placeholder']) ? " placeholder=\"{$options['placeholder']}\"" : null;
		$value = empty($options['value']) ? $this->getValue($name) : (($options['value'] === false) ? null : $options['value']);
		$rows = !empty($options['rows']) ? 'rows="' . $options['rows'] . '"' : null;
		
		return $label . "<textarea class=\"form-control\" {$name_b} {$placeholder} {$rows}>{$value}</textarea>";
	}
	
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
	
	public function file($name, $label = null, $options = []){
		$help_text = !empty($options['help-text']) ? "<small class=\"text-muted\">{$options['help-text']}</small>" : null;
		return $this->surround('<input type="file" id="file" name="' . $name . '">', $label, null, $help_text);
	}
	
	public function select($name, $options = []){
		$opt = '';
		foreach($options as $option => $value)
		    $opt .= '<option value="' . $option . '">' . $value . '</option>';
		
		return '<select class="c-select" name="' . $name . '">' . $opt . '</select>';
	}
}
