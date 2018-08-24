<?php

namespace php\i18n;

use php\BaseObject;
use php\sql\DB;

/**
 * Class BaseI18N
 *
 * Clase base de I18Ns
 *
 * @package php
 * @subpackage i18n
 */
class BaseI18N extends BaseObject {

	public static $key;

	/**
	 * Método que devuelve la traducción de un elemento del i18n.
	 * @param $key
	 *
	 * @return string
	 */
	public static function getTrad($key){

		$db = DB::getInstance();
		$key = static::$key.".$key";
		$db->query("select txt from crm_master.trads_ES where dominio = 'i18n_trads' and clave = '{$key}'");
		$rs = $db->getRs();
		
		if($reg = $rs->next()){
			return $reg->txt;
		}else{
			return $key;
		}
	}

	/**
	 * Método que devuelve todos los elementos del i18n con sus traducciones.
	 *
	 * @return array
	 */
	public function getAll(){

		$refClass = new \ReflectionClass($this);

		$constants = $refClass->getConstants();

		$trads = array();

		foreach( $constants as $c ){
			$trads[$c] = static::getTrad($c);
		}

		return $trads;
	}
}
?>
