<?php

namespace php\sql;


use php\BaseObject;
use php\exceptions\Exception;
use php\Session;
use php\sql\stmt\PreparedStatement;

/**
 * Class ConnectionFactory
 *
 * Clase encargada de generar un PreparedStatement para ejecutar una Query.
 *
 * @package php
 * @subpackage sql
 */
class ConnectionFactory extends BaseObject {

	protected $db = null;

	/**
	 * ConnectionFactory constructor.
	 */
	public function __construct($master = false){
		if ($master){
			$this->db = DB::getInstance();
		}else{
			$hostname = Session::getSessionVar('hostnameOrganization');
			$database = "organization_".Session::getSessionVar('idOrganizacion');
			$this->db = DB::getInstance(false,$hostname,$database);
		}
	}

	/**
	 * Método encargado de montar la Query y devolver un PreparedStatement con ella y con la conexión
	 * listo para bindear todo.
	 *
	 * @param string $sql Query.
	 */
	public function prepare( $sql ){

		if (!$this->db){
			throw new Exception('Connection not initialized.');
		}

		return new PreparedStatement($this->db, $sql);

	}
}
