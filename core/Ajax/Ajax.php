<?php

namespace Core\Ajax;

class Ajax {
    private $_data;

    public function __construct($data){
		echo json_encode($data);
    }
}

?>