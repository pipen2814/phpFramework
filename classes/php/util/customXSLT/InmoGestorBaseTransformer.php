<?php

namespace php\util\customXSLT;


use php\BaseObject;

/**
 * Class InmoGestorBaseTransformer
 *
 * Clase encargada de realizar las transformaciones del NS InmoGestor.
 *
 * @package php
 * @subpackage util
 * @subpackage customXSLT
 */
class InmoGestorBaseTransformer extends BaseObject  {

	/**
	 * @var array Lista de elementos a transformar.
	 *
	 * Lo creamos en forma de array para que podamos tener total control sobre
	 * esto, en vez de pillar dinamicamente del directorio de elementos.
	 */
	protected static $transformationElements = [
		"InmoGestorCalendar"
	];

	/**
	 * Método principal encargado de transformar el XML pasado como parámetro (por referencia).
	 *
	 * @param \DOMDocument $xml XML pasado por Referencia.
	 */
	public static function transform( \DOMDocument &$xml ){

		// Checkeamos y transformamos cada elemento definido.
		foreach ( self::$transformationElements as $te ){
			$elementClassReflection = new \ReflectionClass('php\\util\\customXSLT\\elements\\' . $te);
			$elementClass = $elementClassReflection->newInstanceWithoutConstructor();

			$elementClass->transform($xml);
		}


	}

}
