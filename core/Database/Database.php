<?php
/**
 * Database Class
 */

namespace Core\Database;

use \PDO;
use \Exception;
use \PDOException;

/**
 * Cette classe permet d'initaliser et d'établir une connexion
 * à une base de donnée MySQL ainsi qu'éxécuter des requêtes.
 *
 * @package startFramework\Core\Database
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 * @api
 */
class Database{

	/**
	 * Objet pour effectuer les requêtes
	 *
	 * @var PDO
  	 */
    private $pdo;

    /**
     * État de l'activation globale des transactions
     *
     * @var bool
     */
	private $enable_transaction;

    /**
     * Stockage des paramètres de connexion à la base de donnée et initialisation
     *
     * @param string $db_host            L'hôte de la connexion à la base de donnée
  	 * @param string $db_name            Le nom de la base de donnée à utiliser
 	 * @param string $db_user            Le nom d'utilisateur de la connexion à la base de donnée
 	 * @param string $db_pass            Le mot de passe de la connexion à la base de donnée
 	 * @param bool   $enable_transaction L'activation/désactivation des transactions globales
     */
	public function __construct($db_host, $db_name, $db_user, $db_pass, $enable_transaction = false){
		$this->enable_transaction = $enable_transaction;
		if($this->pdo === null){
			try {
				$pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_pass);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES UTF8");
				$pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
				$this->pdo = $pdo;
			}catch(Exception $e) {
				echo 'Error db connection.';
				exit();
			}
		}
	}

	/**
	 * Permet d'afficher une erreur formatée
	 *
	 * @param PDOException $e Contient les données de l'erreur
	 */
	private function getError($e){
		echo '<b>Request Error</b><br><br>' . $e->getMessage() . '<br>Error ' . $e->getCode();
		exit();
	}

	/**
	 * Permet d'obtenir une erreur et annuler correctement une requête
	 *
	 * @param  PDOException $e           Contient les données de l'erreur
	 * @param  bool         $transaction Indique l'état de l'activation des transactions
	 * @return string                    Retourne une erreur formatée
	 */
	private function getPDOException($e, $transaction){
		if($transaction === true || $this->enable_transaction === true)
		$this->rollback();

		$this->getError($e);
	}

	/**
	 * Permet d'éxécuter une requête sans paramètres dynamiques
	 *
	 * @param  string       $statement   Requête contenant les paramères statiques
	 * @param  string       $class_name  Instancier les résultats dans une classe de type Entity
	 * @param  bool         $one         Indique si la requête retourne un résultat uniquement
	 * @param  bool         $transaction État de l'activation des transactions
	 * @return PDOException              Retourne le résultat de la requête
	 */
	public function query($statement, $class_name = null, $one = false, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true)
			$this->beginTransaction();

			$query = $this->pdo->query($statement);

			if($transaction === true || $this->enable_transaction === true)
			$this->commit();

			if($class_name === null)
			$query->setFetchMode(PDO::FETCH_OBJ);
			else
			$query->setFetchMode(PDO::FETCH_CLASS, $class_name);

			$data = ($one) ? $query->fetch() : $query->fetchAll();

			return $data;
		}catch(PDOException $e){
			$this->getPDOException($e, $transaction);
		}
	}

	/**
	 * Permet d'utiliser une requête
	 *
	 * @param  string  $statement   Requête contenant les paramères statiques
	 * @param  array   $values      Paramètres dynamiques
	 * @param  string  $class_name  Instancier les résultats dans une classe de type Entity
	 * @param  bool    $one         Indique si la requête retourne un résultat uniquement
	 * @param  bool    $transaction État de l'activation des transactions
	 * @return object               Retourne le résultat de la requête
	 */
	public function prepare($statement, $values, $class_name = null, $one = false, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true)
		    	$this->beginTransaction();

			$query = $this->pdo->prepare($statement);
			$query->execute($values);

			if($transaction === true || $this->enable_transaction === true)
		    	$this->commit();

			if($class_name === null)
		    	$query->setFetchMode(PDO::FETCH_OBJ);
			else
		    	$query->setFetchMode(PDO::FETCH_CLASS, $class_name);

			$data = ($one) ? $query->fetch() : $query->fetchAll();

			return $data;
		}catch(PDOException $e){
			$this->getPDOException($e, $transaction);
		}
	}

	/**
	 * Permet d'éxecuter une requête de type
	 * INSERT, UPDATE, DELETE avec ou sans paramètres dynamiques
	 *
	 * @param  string  $statement   Requête contenant les paramères statiques
	 * @param  array   $values      Paramètres dynamiques
	 * @param  bool    $transaction État de l'activation des transactions
	 * @return bool                 Retourne le succès de la requête
	 */
	public function execute($statement, $values = null, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true)
		    	$this->beginTransaction();

			if($values == null)
		    	$this->pdo->exec($statement);
			else {
				$query = $this->pdo->prepare($statement);
				$query->execute($values);
			}

			if($transaction === true || $this->enable_transaction === true)
			    $this->commit();

			return true;
		}catch(PDOException $e){
			$this->getPDOException($e, $transaction);
		}
	}

	/**
	 * Permet de retourner le nombre d'entrées
	 * d'une table de la base de donnée
	 *
	 * @param  string  $statement   Requête contenant les paramères statiques
	 * @param  array   $values      Paramètres dynamiques
	 * @param  bool    $transaction État de l'activation des transactions
	 * @return int                  Retourne le nombre de lignes
	 */
	public function count($statement, $values = null, $transaction = false){
		try {
			if($transaction === true || $this->enable_transaction === true)
		    	$this->beginTransaction();

			if($values == null)
			    $query = $this->pdo->query($statement);
			else{
				$query = $this->pdo->prepare($statement);
				$query->execute($values);
			}

			if($transaction === true || $this->enable_transaction === true)
		    	$this->commit();

			return $query->fetchColumn();
		}catch(PDOException $e){
			$this->getPDOException($e, $transaction);
		}
	}

	/**
	 * Permet de récupérer le dernier identifiant d'une ligne insérée
	 * dans la base de donnée issue de la dernière requête
	 *
	 * @return int L'identifiant de la dernière ligne insérée
	 */
	final public function lastInsertId(){
		return $this->pdo->lastInsertId();
	}

	/**
	 * Fonction utilisée automatiquement lors de l'activation des transactions
	 *
	 * @return void
	 */
	final private function beginTransaction(){
		return $this->pdo->beginTransaction();
	}

	/**
	 * Fonction utilisée automatiquement lors de l'activation des transactions
	 *
	 * @return void
	 */
	final private function commit(){
		return $this->pdo->commit();
	}

	/**
	 * Fonction utilisée automatiquement lors de l'activation des transactions
	 *
	 * @return void
	 */
	final private function rollback(){
		return $this->pdo->rollback();
	}

}
