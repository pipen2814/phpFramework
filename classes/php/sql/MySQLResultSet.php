<?php

namespace php\sql;

use php\exceptions\SQLException;

/**
 * Class MySQLResultSet
 *
 * Clase encargada de gestionar un ResultSet de MySQL.
 *
 * @package php
 * @subpackage sql
 */
class MySQLResultSet extends ResultSet {
	
	/**
	 * Instantiates a new MySQLResultSet Object, doing some required initialization.
	 */
	/**
	 * MySQLResultSet constructor.
	 *
	 * @param \php\sql\Connection $connection Conexión del ResultSet.
	 * @param \php\sql\PHPResource $resource Variable donde tenemos los datos cargados
	 */
	public function __construct( $connection, $resource ) {
		parent::__construct( $connection, $resource );
		if (!$this->resource) return;
		if (!($this->resource instanceof \mysqli_result)) return;
		$this->selectedRows = mysqli_num_rows( $this->resource );
		$this->fieldsTypes = array();
		while( $col = mysqli_fetch_field( $this->resource ) ) {
			$table = ( $col->table ? $col->table : 0 );

			//do class resolving here...
			$this->fields[] = array( $table, $col->name );
			$this->fieldsTypes[ $col->name ] = $col->type; 
			if ( !in_array( $table, $this->tables ) ) $this->tables[] = $table;
		}
		$this->tabnum = sizeof( $this->tables );
	}

	/**
	 * Método encargado de destruir el ResultSet.
	 */
	public function __destruct(){
		$this->free();
		unset($this);
	}

	/**
	 * Método encargado de Liberar los datos del ResultSet.
	 */
	public function free(){
		@mysqli_free_result($this->resource);
	}

	/**
	 * Devuelve el tipo del campo por clave (nombre).
	 *
	 * @return string Tipo del campo.
	 */
	public function getFieldType( $name ) {
		return $this->fieldsTypes[ $name ];
	}

	/**
	 * Carga un registro de Datos al ResultSet.
	 *
	 * @throws SQLException En caso de que se produzca un error al generar el ResultSet.
	 */
	protected function loadRow() {
/*
		switch( $this->mode ) {

			case Connection::MODE_NUM:
				$values = mysql_fetch_array( $this->resource, MYSQL_NUM );
				break;
			case Connection::MODE_ASSOC:
				$values = mysql_fetch_array( $this->resource, MYSQL_ASSOC );
				break;
			case Connection::MODE_MULTI:
				$row = mysql_fetch_array( $this->resource, MYSQL_NUM );
				if ( !$row ) {
					$values = null;
					break;
				}
				$values = array();
				foreach( $row as $idx => $val ) {
					list( $tab, $col ) = $this->fields[$idx];
					$values[$tab][$col] = $val;
				}
				break;
		}
*/
		if (!$this->resource) return;
		$values = mysqli_fetch_array( $this->resource, MYSQLI_ASSOC );

		if ( !$values ) {
			$this->row->setValues( null );
/*
			if ( ( $mysql_errno = mysql_errno( $this->connection->getResource() ) ) > 0 ) {
				throw new SQLException( mysql_error( $this->connection->getResource() ), $mysql_errno );
			} else {
				return false;
			}
*/
return false;
		}
		$this->row->setValues( $values );
		return $this->row;
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
	public function seek( $rownum ) {
		if ( $this->selectedRows == 0 ) {
			return false;
		}
		if ( $rownum >= $this->selectedRows ) {
			throw new SQLException( sprintf( 'Error seeking data on row %d. Selected Rows: %d', $rownum, $this->selectedRows ) );
		}
		if ( !mysqli_data_seek( $this->resource, $rownum ) ) {
			return false;
		}

		$this->rownum = $rownum;
		$this->row->setValues( null );
		return true;
	}

	/**
	 * Método que se encarga de devolver el número de registros devueltos.
	 *
	 * @return int Número de registros devueltos.
	 */
	public function count(){
		return $this->selectedRows;
	}


}
