<?php

namespace php\util\customXSLT\elements\base;

use php\BaseObject;

/**
 * Class InmoGestorXSLTElement
 *
 * Clase base de elementos XSLT custom.
 *
 * @package php
 * @subpackage util
 * @subpackage customXSLT
 * @subpackage elements
 * @subpackage base
 */
class InmoGestorXSLTElement extends BaseObject {

	/**
	 * @var string Nombre del namespace del elemento
	 */
	protected $ns = "inmogestor";

	/**
	 * @var string Url del namespace del elemento.
	 */
	protected $nsUri = "http://inmo-gestor.com";

	/**
	 * @var string Tag del elemento del namespace.
	 */
	protected $tag;

	/**
	 * @var string Código html del elemento del namespace
	 */
	protected $html;

	/**
	 * @var string Código html que se transformará para mantener el original intacto.
	 */
	protected $transformed;

	/**
	 * @var array Parámetros que necesitaremos aplicar.
	 */
	protected $params = array();

	/**
	 * @param $xml Método que se encarga de buscar el elemento en el xml que le pasamos como parametro
	 * y ejecutar las transformaciones necesarias si lo encuentra.
	 */
	public function transform( &$xml ){

		$xpath =  new \DOMXPath ($xml);
		$xpath->registerNamespace($this->ns, $this->nsUri);

		// Recuperamos los nodos del tag.
		$xslElements = $xpath->query('//'.$this->tag);

		// Para cada nodo
		foreach ( $xslElements as $el ){

			$this->transformed = $this->html;

			$this->params = array();

			// Nos traemos todos los parámetros del elemento:
			foreach($el->getElementsByTagNameNS($this->nsUri,'param') as $param) {
				$this->params[] = new InmoGestorParam($param);
			}

			// Ahora que tenemos todos los params, tenemos que mergear params y html.
			$this->mergeData();

			$dom = new \DOMDocument('');
			$dom->loadXML($this->transformed);
			$node = $dom->getElementsByTagName('div')->item(0);

			$el->parentNode->replaceChild($xml->importNode($node, true), $el);

		}
	}

	/**
	 * Método encargado de transformar los parámetros.
	 */
	public function mergeData(){
		foreach ( $this->params as $p ){
			$p->transform( $this->transformed );
		}
	}

}
