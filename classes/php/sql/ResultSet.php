<?php

namespace php\sql;

use php\exceptions\SQLException;

/**
 * Class ResultSet
 *
 * Clase abstracta de ResultSet.
 *
 * @package php
 * @subpackage sql
 */
abstract class ResultSet implements \Iterator {

	/**
	 * @var null|\php\sql\Connection La conexión que generó el ResultSet.
	 */
	protected $connection = null;

	/**
	 * @var null|\php\sql\PHPResource El identificador del recurso.
	 */
	protected $resource = null;

	/**
	 * @var null Modo de recuperación. (Fetch mode).
	 */
	protected $mode = null;

	/**
	 * @var array El array total de datos devueltos.
	 */
	protected $fields = array();

	/**
	 * @var array Tipos de los datos de SQL.
	 */
	protected $fieldsTypes;

	/**
	 * @var array Las tablas del ResultSet.
	 */
	protected $tables = array();

	/**
	 * @var int El número de tablas del ResultSet.
	 */
	protected $tabnum = 0;

	/**
	 * @var int Número de registros recuperados.
	 */
	protected $selectedRows = 0;

	/**
	 * @var int El puntero actual del iterador.
	 */
	protected $rownum = null;

	/**
	 * @var ResultSetRow El resultSetRow cargado.
	 */
	protected $row = null;

	/**
	 * ResultSet constructor.
	 *
	 * @param $connection $connection The Connection object
	 * @param $resource $resource The PHP Resource Identifier
	 */
	public function __construct($connection, $resource) {
		$this->connection = $connection;
		$this->resource = $resource;
		$this->rownum = 0;
		//$this->mode = $connection->getMode();
		$this->row = new ResultSetRow($connection, $this);
	}

	/**
	 * Devuelve el FetchMode del ResultSet.
	 *
	 * @return null FetchMode.
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * Método encargado de volver al primer elemento del Array.
	 *
	 * @return mixed Primer Elemento.
	 */
	public function rewind() {
		return $this->seek(0);
	}

	/**
	 * Método enccargado de recuperar el elemento actual del resultSet.
	 *
	 * @return ResultSet Este objeto.
	 */
	public function current() {
		if ($this->row->isEmpty()) {
			$this->loadRow();
		}

		return $this->row;
	}

	/**
	 * Método que recupera el número de Row actual.
	 *
	 * @return int El número de Row actual
	 */
	public function key() {
		return $this->rownum;
	}

	/**
	 * Método que comprueba si el rownum del bucle en el que estamos es válido.
	 *
	 * @return bool True si es válido, false en caso contrario.
	 */
	public function valid() {
		return $this->rownum < $this->selectedRows;
	}

	/**
	 * Devuelve el tipo del campo por clave (nombre).
	 *
	 * @return string Tipo del campo.
	 */
	public abstract function getFieldType($name);

	/**
	 * Método encargado de devolver el siguiente elemento del ResultSet.
	 *
	 * @return mixed Siguiente elemento del ResultSet.
	 */
	public function next() {
		// Workaround for allowing both foreach() and while() loops
		// This bit is only called the first time when using:
		// while( $rs->next() )
		if ($this->row->isEmpty() && $this->rownum == 0) {
			return $this->loadRow();
		}

		// This is called every other time.
		$this->rownum++;

		return $this->loadRow();
	}

	/**
	 * Método que devuelve el recurso.
	 *
	 * @returns php_resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Método que devuelve la conexión.
	 *
	 * @return null|\php\sql\Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Método que setea el fetchMode.
	 *
	 * @param $mode fetchMode.
	 *
	 * @throws \php\exceptions\SQLException
	 */
	public function setMode($mode) {
		if ($this->rownum > 0) {
			throw new SQLException("You can't change the ResultSet Fetch Mode in the middle of an Itereation (Current row: %d)", $this->rownum);
		}
		$this->mode = $mode;
	}

	/**
	 * Método que devuelve el número actual del row.
	 *
	 * @return int El actual número del row.
	 */
	public function rownum() {
		return $this->rownum;
	}

	/**
	 * Devuelve el row.
	 *
	 * @return array datos del row.¡
	 */
	public function row() {
		return $this->row;
	}

	/**
	 * Devuelve la lista de campos recuperados.
	 *
	 * @return array Lista de campos recuperados.
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Devuelve la cantidad de registros recuperados.
	 *
	 * @return int Cantidad de registros recuperados.
	 */
	public function selectedRows() {
		return $this->selectedRows;
	}

	/**
	 * Devuelve la cantidad de registros recuperados.
	 *
	 * @return int Cantidad de registros recuperados.
	 */
	public function length() {
		return $this->selectedRows();
	}

	/**
	 * Método encargado de comprobar si existe el campo pasado por parámetro.
	 *
	 * @param $table string tabla a consultar.
	 * @param null $field string campos a consultar.
	 *
	 * @return bool
	 */
	public function has($table, $field = null) {
		return $this->row->has($table, $field);
	}

	/**
	 * Método encargado de recuperar el valor del campo $field.
	 *
	 * @param $table string Nombre de la tabla.
	 * @param null $field string Campo a consultar.
	 *
	 * @return mixed Valor.
	 * @throws \php\exceptions\SQLException
	 */
	public function get($table, $field = null) {
		if ($this->row->isEmpty()) {
			throw new SQLException(sprintf('Can\'t get field "%s.%s" - ResultSet is empty', $table, $field));
		} else {
			return $this->row->get($table, $field);
		}
	}

	/**
	 * Método sobrecargado de ResultSetRow.
	 *
	 * Recupera el valor del get sin necesitar meter la tabla.
	 *
	 * @param $field string Campos a buscar.
	 *
	 * @return string Dato.
	 *
	 * @see ResultSetRow::get
	 */
	public function __get($field) {
		switch ($this->mode) {
			/*
						case Connection::MODE_MULTI:
							if ( $this->tabnum > 1 ) {
								return $this->row->fromTable( $field );
							} else {
								return $this->row->get( $this->tables[0], $field );
							}
							break;
			*/
			default:
				return $this->row->get($field);
		}
	}

	/**
	 * Método encargado de recuperar las tablas.
	 *
	 * @return array Tablas de la query.
	 */
	public function getTables() {
		return $this->tables;
	}

	/**
	 * Método encargado de buscar el registro en el numrow que pasamos por parámetro.
	 *
	 * @param int $rownum el número del resultado.
	 *
	 * @return bool Si lo encuentra devuelve true, si no false.
	 *
	 * @throws \php\exceptions\SQLException Al intentar buscar un registro mayor al número de registros total.
	 */
	public abstract function seek($rownum);

	/**
	 * Carga un registro de Datos al ResultSet.
	 *
	 * @throws SQLException En caso de que se produzca un error al generar el ResultSet.
	 */
	protected abstract function loadRow();

	/**
	 * Método para convertir el ResultSet a String.
	 */
	public function __toString() {
		$str = sprintf("%s [ID #%s]\n", get_class($this), $this->id());
		$str .= sprintf(" * Selected Rows: %d\n", $this->selectedRows);
		$str .= sprintf(" * Current Row #: %d\n", $this->rownum);
		$str .= sprintf(" * Tables in RS : %d\n", $this->tabnum);
		foreach ($this->tables as $table) {
			$str .= sprintf("  -> %s\n", $table);
		}
		if ($this->row && !$this->row->isEmpty()) {
			$str .= " * Fields:\n";
			foreach ($this->row->getValues() as $field => $value) {
				$str .= sprintf("  -> %-20s = %s\n", $field, $value);
				if (is_array($value)) {
					foreach ($value as $col => $val) {
						$str .= sprintf("    | => %-25s = %s\n", $col, $val);
					}
				}
			}
		}

		return $str;
	}

	/**
	 * Método que devuelve el primer registro que contiene el primer valor que encaja en $field.
	 *
	 * @param $field string campo donde buscaremos el valor.
	 * @param $value string valor a buscar.
	 *
	 * @return bool|\php\sql\ResultSet Devuelve el resulset.
	 */
	public function hasValue($field, $value) {
		foreach ($this as $obj) {
			if ($obj->$field == $value) return $obj;
		}

		return false;
	}

}
