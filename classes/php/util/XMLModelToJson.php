<?php

namespace php\util;
use php\XMLModel;

/**
 * Class XMLModelToJson
 *
 * Clase creada para convertir el XML con atributos a un xml normalizado que al convertirlo en
 * JSON devuelva datos bien formateados, sin las etiquetas de @attributes
 *
 * @package php
 * @subpackage util
 */
class XMLModelToJson {

	/**
	 * Método que se encarga de normalizar un XMLModel
	 *
	 * @param $obj XMLModel
	 * @param $result $resultadoFinal
	 */
	private static function normalizeXMLModel($obj, &$result) {
		$data = $obj;
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$res = null;
				self::normalizeXMLModel($value, $res);
				if (($key == '@attributes') && ($key)) {
					$result = $res;
				} else {
					$result[$key] = $res;
				}
			}
		} else {
			$result = $data;
		}
	}

	/**
	 * Método que nos devuelve ya el array encodeado listo para mostrar por pantalla.
	 *
	 * @param $xml XML sin normalizar.
	 *
	 * @return string El JSON Normalizado.
	 */
	public static function normalize($xml){
		self::normalizeXMLModel($xml, $result);

		return json_encode($result);
	}
}