<?php

namespace Core\Database;

use \PDO;
use \Exception;
use \PDOException;

class Database{
	
	private $db_host;
	private $db_name;
	private $db_user;
	private $db_pass;
	private $enable_transaction = false;
	private $pdo;
	
	public function __construct($db_host, $db_name, $db_user, $db_pass, $enable_transaction = false){
		$this->db_host = $db_host;
		$this->db_name = $db_name;
		$this->db_user = $db_user;
		$this->db_pass = $db_pass;
		$this->enable_transaction = $enable_transaction;
	}
	
	final private function getPDO(){
		if($this->pdo === null){
			try {
				$pdo = new PDO("mysql:host={$this->db_host};dbname={$this->db_name};charset=utf8", $this->db_user, $this->db_pass);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES UTF8");
				$pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
				$this->pdo = $pdo;
			}catch(Exception $e) {
				echo 'Error db connection.';
				exit();
			}
		}
		
		return $this->pdo;
	}
	
	private function getError($e){
		echo '<b>Request Error</b><br><br>' . $e->getMessage() . '<br>Error ' . $e->getCode();
		exit();
	}
	
	private function getPDOException($e, $transaction){
		if($transaction === true || $this->enable_transaction === true) $pdo->rollback();
		$this->getError($e);
	}
	
	public function query($statement, $class_name = null, $one = false, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true) $this->beginTransaction();
			
			$query = $this->getPDO()->query($statement);
			
			if($transaction === true || $this->enable_transaction === true) $this->commit();
			
			if($class_name === null)
			$query->setFetchMode(PDO::FETCH_OBJ);
			else
			$query->setFetchMode(PDO::FETCH_CLASS, $class_name);
			
			$data = ($one) ? $query->fetch() : $query->fetchAll();
			
			return $data;
		}catch(PDOException $e){ $this->getPDOException($e, $transaction); }
	}
	
	public function prepare($statement, $values, $class_name = null, $one = false, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true) $this->beginTransaction();
			
			$query = $this->getPDO()->prepare($statement);
			$query->execute($values);
			
			if($transaction === true || $this->enable_transaction === true) $this->commit();
			
			if($class_name === null)
			$query->setFetchMode(PDO::FETCH_OBJ);
			else
			$query->setFetchMode(PDO::FETCH_CLASS, $class_name);
			
			$data = ($one) ? $query->fetch() : $query->fetchAll();
			
			return $data;
		}catch(PDOException $e){ $this->getPDOException($e, $transaction); }
	}
	
	public function execute($statement, $values = null, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true) $this->beginTransaction();
			
			if($values == null)
			$this->getPDO()->exec($statement);
			else {
				$query = $this->getPDO()->prepare($statement);
				$query->execute($values);
			}
			
			if($transaction === true || $this->enable_transaction === true) $this->commit();
			
			return true;
		}catch(PDOException $e){ $this->getPDOException($e, $transaction); }
	}
	
	public function count($statement, $values = null, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true) $this->beginTransaction();
			
			if($values == null)
			$query = $this->getPDO()->query($statement);
			else{
				$query = $this->getPDO()->prepare($statement);
				$query->execute($values);
			}
			
			if($transaction === true || $this->enable_transaction === true) $this->commit();
			
			return $query->fetchColumn();
		}catch(PDOException $e){ $this->getPDOException($e, $transaction); }
	}
	
	public function lastInsertId(){
		return $this->getPDO()->lastInsertId();
	}
	
	public function beginTransaction(){
		return $this->getPDO()->beginTransaction();
	}
	
	public function commit(){
		return $this->getPDO()->commit();
	}
	
	public function rollback(){
		return $this->getPDO()->rollback();
	}
	
}
