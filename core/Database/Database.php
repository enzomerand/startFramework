<?php

namespace Core\Database;

use \PDO;

class Database{
	
	private $db_host;
	private $db_name;
	private $db_user;
	private $db_pass;
	private $pdo;
	
	public function __construct($db_host, $db_name, $db_user, $db_pass){
		$this->db_host = $db_host;
		$this->db_name = $db_name;
		$this->db_user = $db_user;
		$this->db_pass = $db_pass;
	}
	
	final private function getPDO(){
		if($this->pdo === null){
			$pdo = new PDO("mysql:host={$this->db_host};dbname={$this->db_name}", $this->db_user, $this->db_pass);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
			$this->pdo = $pdo;
		}
		
		return $this->pdo;
	}
	
	public function query($statement, $class_name = null, $one = false){
		$query = $this->getPDO()->query($statement);
		if($class_name === null)
			$query->setFetchMode(PDO::FETCH_OBJ);
		else
			$query->setFetchMode(PDO::FETCH_CLASS, $class_name);
		
		$data = ($one) ? $query->fetch() : $query->fetchAll();
		
		return $data;
	}
	
	public function prepare($statement, $values, $class_name = null, $one = false){
		$query = $this->getPDO()->prepare($statement);
		$query->execute($values);
		if($class_name === null)
			$query->setFetchMode(PDO::FETCH_OBJ);
		else
			$query->setFetchMode(PDO::FETCH_CLASS, $class_name);
		
		$data = ($one) ? $query->fetch() : $query->fetchAll();
		
		return $data;
	}
	
	public function execute($statement, $values){
		$query = $this->getPDO()->prepare($statement);
		$query->execute($values);
		
		return true;
	}
	
	public function count($statement, $values = null){
		if($values == null)
		    $query = $this->getPDO()->query($statement);
		else{
		    $query = $this->getPDO()->prepare($statement);
			$query->execute($values);	
		}
		
		return $query->fetchColumn();
	}
	
	public function lastInsertId(){
        return $this->getPDO()->lastInsertId();
    }
	
}
