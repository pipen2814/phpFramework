<?php

namespace php\sql\stmt;

use php\BaseObject;
use php\sql\DB;

/**
 * Class PreparedStatement
 *
 * Clase encargada de gestionar queries.
 *
 * @package php
 * @subpackage sql
 * @subpackage stmt
 */
class PreparedStatement extends BaseObject  {

	/**
	 * @var DB $db Conexion a la base de datos
	 */
	protected $db = null;

	/**
	 * @var string $query Query que se va a ejecutar.
	 */
	protected $query = null;

	/**
	 * @var array $binds Binds de la query.
	 */
	protected $binds = array();

	/**
	 * PreparedStatement constructor.
	 */
	public function __construct( $db, $query ) {
		$this->db = $db;
		$this->query = $query;
	}

	/**
	 * Método encargado de bindear datos a la query.
	 *
	 * @param string $str Cadena a bindear.
	 * @param mixed $val Valor a bindear.
	 */
	public function bind( $str, $val ){
		$this->binds[$str] = $val;
	}

	/**
	 * Método encargado de bindear un array
	 *
	 * TODO: Cambiar para que haga merge
	 *
	 * @param array $binds Array de binds.
	 */
	public function binds( array $binds ){
		$this->binds = $binds;
	}

	/**
	 * Método encargado de transformar la query con binds.
	 *
	 * @return string Query
	 */
	private function buildQuery(){

		$query = $this->query;
		foreach ( $this->binds as $bind => $bindValue ){
			$query = str_replace($bind, "'".$bindValue."'", $query);
		}

		return $query;
	}

	/**
	 * Método encargado de devolver la query bindeada.
	 *
	 * @return string Query bindeada.
	 */
	public function getQuery(){
		$query = $this->buildQuery();

		return $query;
	}

	/**
	 * Método encargado de ejecutar la query.
	 *
	 * @return MySQLResultSet Resultado de la query.
	 */
	public function execute(){
		$query = $this->buildQuery();

		$this->db->query($query);

		return $this->db->getRs();
	}
}