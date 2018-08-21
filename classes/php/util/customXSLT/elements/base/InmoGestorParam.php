<?php

namespace php\util\customXSLT\elements\base;

use php\BaseObject;
use php\exceptions\Exception;

/**
 * Class InmoGestorParam
 *
 * Clase encargada de gestionar los parámetros del namespace InmoGestor 
 *
 * @package php
 * @subpackage util
 * @subpackage customXSLT
 * @subpackage elements
 * @subpackage base
 */
class InmoGestorParam extends BaseObject {

	/**
	 * @var string Nombre del parámetro
	 */
	protected $name;

	/**
	 * @var mixed Valor del parámetro.
	 */
	protected $value;

	/**
	 * @var string Método de transformación del parámetro.
	 */
	protected $transformationMethod;

	/**
	 * InmoGestorParam constructor.
	 *
	 * @throws \php\exceptions\Exception
	 */
	public function __construct() {
		$params = func_get_args();

		if (count($params) == 1 && $params[0] instanceof \DOMElement){
			// Constructor a través del DOMElement del param.
			$el = $params[0];

			if (!$el->getAttribute('type')){
				throw new Exception('InmoGestorParam \'type\' REQUIRED');
			}
			if (!$el->getAttribute('value')){
				throw new Exception('InmoGestorParam \'value\' REQUIRED');
			}

			$this->name = $el->getAttribute('type');
			$this->value = $el->getAttribute('value');

		}elseif (count($params) == 2){
			// Constructor pasando el $name y $value.
			$this->name = $params[0];
			$this->value = $params[1];
		}else{
			throw new Exception('\'InmoGestorParam Constructor\' not supported.');
		}

		$this->loadTransformationMethod();

	}

	/**
	 * Método encargado de cargar el modo de transformación del parámetro.
	 */
	protected function loadTransformationMethod(){
		switch ($this->name){
			case 'id':
				$this->transformationMethod = 'replace';
				break;

			case 'value':
				$this->transformationMethod = 'inject';
		}
	}

	/**
	 * Método encargado de lanzar el modo de transformación del parámetro.
	 *
	 * @param string $html codigo html que vamos a transformar.
	 */
	public function transform( &$html ){
		$this->{$this->transformationMethod}( $html );
	}

	/**
	 * Método encargado de transformar el modo "REPLACE".
	 *
	 * @param string $html codigo html que vamos a transformar.
	 */
	public function replace( &$html ){
		$html = str_replace('{InmoGestorParam::'.$this->name.'}', $this->value, $html);
	}

	/**
	 * Método encargado de transformar el modo "INJECT".
	 *
	 * @param string $html codigo html que vamos a transformar.
	 */
	public function inject( &$html ){
		$dom = new \DOMDocument('');
		$dom->loadXML($html);
		$xpath = new \DOMXPath($dom);

		foreach( $xpath->query("//*[@value]") as $res) {
			$nodo = $res->cloneNode(true);
			$nodo->removeAttribute("value");

			$xslAttribute = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform','xsl:attribute');
			$xslAttributeName = $dom->createAttribute('name');
			$xslAttributeName->value = "value";
			$xslAttribute->appendChild($xslAttributeName);

			$xslValueOf = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform','xsl:value-of');
			$xslValueOfSelect = $dom->createAttribute('select');
			$xslValueOfSelect->value = $this->value;
			$xslValueOf->appendChild($xslValueOfSelect);

			$xslAttribute->appendChild($xslValueOf);


			$nodo->appendChild($xslAttribute);
			$res->parentNode->replaceChild($nodo, $res);

			$html = $dom->saveXML();
		}
	}
}
