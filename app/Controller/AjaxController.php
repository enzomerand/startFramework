<?php

namespace App\Controller;

use Core\Controller\Controller;
use Core\Ajax\Ajax;

class AjaxController {
	
	private $_data;
	
	public function call($request){
		if(isset(explode('::', $request)[0]) && isset(explode('::', $request)[1])){
			$data = explode('::', $request)[1];
		    $call = explode('::', $request)[0];
			if(method_exists(get_class($this), $call)){
				$this->_data = $this->$call($data);
				$this->display();
			}else
				echo json_encode('error');
		}elseif(isset(explode('::', $request)[0]){
		    $call = explode('::', $request)[0];
			if(method_exists(get_class($this), $call)){
				$this->_data = $this->$call();
				$this->display();
			}else
				echo json_encode('error');
		}
			echo json_encode('error');
	}
	
	public function display(){
		return new Ajax($this->_data);
	}
	
	private function preview_image(){
		$data['file'] = $_FILES;
        $data['text'] = $_POST;
		return $data;
	}
	
	private function test($text){
		return $text;
	}

}