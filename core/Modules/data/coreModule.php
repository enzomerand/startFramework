<?php

namespace Core\Modules\data;

use Core\Database\Database;

trait coreModule {
    
    protected $db;
    
    private $entity;
    
    public function getClassName($class){
        return str_replace('Module', '', str_replace('Core\Modules\\', '', $class));
    }
    
    public function setDB(Database $db, $entity = null){
        $this->db = $db;
        if($entity != null)
            $this->entity = $this->getClassName($entity);
    }

    public function query($statement, $attr = null, $one = false){
        $entity = 'Core\Modules\data\\' . $this->entity . 'Entity';
		if($attr)
			return $this->db->prepare($statement, $attr, $entity, $one);
		else
			return $this->db->query($statement, $entity, $one);
	}
}
