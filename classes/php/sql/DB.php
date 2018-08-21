<?php

namespace php\sql;

use php\BaseObject;
use php\exceptions\Exception;

/**
 * Class DB
 *
 * Clase encargada de gestionar las conexiones a base de datos.
 *
 * @package php
 * @subpackage sql
 */
class DB {

	/**
	 * @var string Nombre del host de la base de datos.
	 */
	private $hostname;

	/**
	 * @var string Nombre de la base de datos.
	 */
	private $database;

	/**
	 * @var string Usuario de la base de datos.
	 */
	private $username;

	/**
	 * @var string Pass de la base de datos.
	 */
	private $password;

	/**
	 * @var mysqli_connection Instancia de la conexión a la base de datos.
	 */
	private $connection;

	/**
	 * @var bool Atributo encargado para mostrar o no las queries que se lanzan.
	 */
	private $showTrace;

	/**
	 * @var array Instancias de las conexiones a la base de datos.
	 */
	private static $instances = array();

	/**
	 * DB constructor.
	 *
	 * @param $hostname string Host de la base de datos.
	 * @param $database string Nombre de la base de datos.
	 * @param $password string Pass de la base de datos.
	 * @param $username string Usuario de la base de datos.
	 */
	public function __construct($hostname, $database, $password, $username) {
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		$this->showTrace = false;
	}

	/**
	 * Método encargado de devolver la instancia de la conexión o generarla si es necesario.
	 *
	 * @param bool $forceNewInstance Encargado de forzar la instancia a la base de datos.
	 * @param $hostname string Nombre del host de la base de datos.
	 * @param $database string Nombre de la base de datos.
	 * @param $password string Password de la base de datos.
	 * @param $username string Usuario de la base de datos.
	 *
	 * @return mixed|\php\sql\DB Instancia de la conexión.
	 */
	public static function getInstance($forceNewInstance = false, $hostname = MASTER_DATABASE_SERVER, $database = MASTER_DATABASE_NAME, $password = MASTER_DATABASE_PWD, $username = MASTER_DATABASE_USER) {
		if ($forceNewInstance) return new self($hostname, $database, $password, $username);
		else {
			if (!in_array($hostname, array_keys(self::$instances))) { //&& !self::$instances[$hostname] instanceof self){
				self::$instances[$hostname] = new self($hostname, $database, $password, $username);
			}
		}

		//Si el host es el mismo pero la base de datos es distinta, seleccionamos la nueva base de datos.
		if (self::$instances[$hostname]->getDatabase() != $database) {
			self::$instances[$hostname]->setDatabase($database);
			self::$instances[$hostname]->conectar();
			mysqli_select_db(self::$instances[$hostname]->getConnection(), $database);
		}

		return self::$instances[$hostname];
	}

	/**
	 * Metodo encargado de generar una conexion al host.
	 *
	 * @return \mysqli|\php\sql\mysqli_connection Devuelve un objeto de conexion instanciando los datos cargados.
	 */
	public function conectar() {
		$this->connection = mysqli_connect($this->hostname, $this->username, $this->password, $this->database) or die("Error al conectar a la BBDD");
		//mysqli_set_charset($this->connection , 'utf8' );
		mysqli_select_db($this->connection, $this->database);

		return $this->connection;
	}

	/**
	 * Método encargado de recuperar el objeto de conexión.
	 *
	 * @return \php\sql\mysqli_connection Objeto de conexión.
	 */
	private function getConnection() {
		return $this->connection;
	}

	/**
	 * Método encargado de setear la base de datos.
	 *
	 * @param $database string Nombre de la base de datos.
	 */
	public function setDatabase($database) {
		$this->database = $database;
	}

	/**
	 * Método encargado de setear el usuario de la base de datos.
	 *
	 * @param $username string Usuario de la base de datos.
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Método encargado de setear la pass de la base de datos.
	 *
	 * @param $password string Pass de la base de datos.
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Método encargado de setear el hostname de la base de datos.
	 *
	 * @param $hostname Host de la base de datos.
	 */
	public function setHostname($hostname) {
		$this->hostname = $hostname;
	}

	/**
	 * Método encargado de activar/desactivar la traza de queries.
	 *
	 * @param $bool true - activado // false - desactivado
	 */
	public function showTrace($bool) {
		$this->showTrace = $bool;
	}

	/**
	 * Método encargado de devolver el nombre de la base de datos.
	 *
	 * @return string Nombre de la base de datos.
	 */
	public function getDatabase() {
		return $this->database;
	}

	/**
	 * Método encargado de devolver el usuario de la base de datos.
	 *
	 * @return string Usuario de la base de datos.
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Método encargado de devolver la pass de la base de datos.
	 *
	 * @return string Password de la base de datos.
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Método encargado de devolver el host de la base de datos.
	 *
	 * @return string Host de la base de datos.
	 */
	public function getHostname() {
		return $this->hostname;
	}

	/**
	 * Método encargado de ejecutar la query que se le pasa como parámetro.
	 *
	 * @param $query string Query que se va a ejecutar.
	 *
	 * @return mixed Array de resultados.
	 */
	public function query($query) {
		return $this->execQuery($query);
	}

	/**
	 * Método encargado de ejecutar una query, pintarla por pantalla si hace falta y devolver los resultados.
	 *
	 * @param $query string Query a Ejecutar.
	 * @param null $returnLastId Si queremos devolver el id del lastInsert.
	 *
	 * @return mixed Array de resultados.
	 */
	public function execQuery($query, $returnLastId = null) {
		if ((isset($_GET['tracequery']) && $_GET['tracequery']) || $this->showTrace) {
			echo "$query<br>";
		}
		if (!$this->connection) {
			$this->conectar();
		}
		try {
			$this->arrayResult = $this->connection->query($query);
			if (!$this->arrayResult) {
				throw new Exception("MySQL Error: " . $this->connection->error);
			}

			if ($returnLastId) {
				return $this->connection->insert_id;
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}

		return $this->arrayResult;

	}

	/**
	 * Método que devuelve un MySQLResultSet con los datos que devolvió la query ejecutada anteriormente.
	 *
	 * @return \php\sql\MySQLResultSet Resultado de la query ejecutada anteriormente.
	 */
	public function getRs() {
		$rs = new MySQLResultSet($this->connection, $this->arrayResult);

		return $rs;
	}

	/**
	 * Método encargado de recorrer el puntero de resultados del array de resultados.
	 *
	 * @return array|null Array de resultados si todavía quedan. Si no quedan, null.
	 */
	public function next() {
		$contenido = mysqli_fetch_array($this->arrayResult);

		return $contenido;
	}

	/**
	 * Método encargado de devolver la cuenta de registros de la query ejecutada.
	 *
	 * @return int Count de los registros devueltos.
	 */
	public function count() {
		return mysqli_num_rows($this->connection, $this->arrayResult);
	}

	/**
	 * Método encargado de devolver el último ID insertado.
	 *
	 * @return int|string Último id insertado.
	 */
	public function getLastId() {
		return mysqli_insert_id();
	}
}
