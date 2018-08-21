<?php

namespace php;
use php\i18n\BaseI18N;
use php\sql\MySQLResultSet;

/**
 * Class XMLModel
 *
 * Clase base que extiende de SimpleXMLElement, por si en un futuro queremos modificar algo.
 *
 * @package php
 */
class XMLModel extends \SimpleXMLElement {

	/**
	 * Método encargado de agregar en un nodo un RS completo.
	 * @param MySQLResultSet $rs ResultSet a agregar.
	 */
	public function addRS( MySQLResultSet $rs ){
		while ($rs->next()) {
			$item = $this->addChild('item');
			foreach ($rs->getFields() as $fields) {

				$item[$fields[1]] = utf8_encode($rs->get($fields[1]));
			}
		}
	}

	/**
	 * Método encargado de agregar al modelo un array con los datos.
	 * @param $array
	 */
	public function addArray( $array ){
		foreach( $array as $key => $val ){
			$this[$key] = $val;
		}
	}

	/**
	 * Método con el que agregamos al modelo todos los elementos
	 * de un i18n.
	 *
	 * @param BaseI18N $i18n
	 */
	public function addI18N( BaseI18N $i18n ){

		$elements = $i18n->getAll();

		foreach ( $elements as $value => $trad ){
			$item = $this->addChild('item');
			$item['value'] = $value;
			$item['trad'] = $trad;
		}
	}

	/**
	 * Método que agrega todos los datos de un ORM a este elemento XMLModel.
	 *
	 * @param $row
	 */
	public function appendORMAsAttributes($row){

		foreach ($row->getFields() as $dbName => $ormName){
			$this[$dbName] = utf8_encode($row->$ormName);
		}
	}
}