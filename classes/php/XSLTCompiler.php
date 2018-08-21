<?php

namespace php;

use php\exceptions\Exception;
use php\util\XMLModelToJson;


/**
 * Class XSLTCompiler
 *
 * Compilador de los XSLs
 *
 * @package php
 */
class XSLTCompiler extends BaseObject {

	protected $xslFileRoot;
	protected $xmlFileRoot;

	protected $processor;
	protected $xslDocument;
	protected $xmlDocument;
	protected $compiled;

	/**
	 * XSLTCompiler constructor.
	 */
	public function __construct(){
		$this->compiled = false;
		$this->xmlDocument = new XMLModel("<a></a>");//\DOMDocument('1.0');
		$this->xslDocument = new XMLModel("<a></a>");//DOMDocument('1.0');
	}

	/**
	 * Devuelve los datos del fichero XSL.
	 *
	 * @return mixed
	 */
	public function getXslFileRoot(){
		return $this->xslFileRoot;
	}

	/**
	 * Setea los datos del fichero XSL.
	 *
	 * @param $value
	 */
	public function setXslFileRoot($value){
		$this->xslFileRoot = $value;
	}

	/**
	 * Devuelve los datos del XML.
	 *
	 * @return mixed
	 */
	public function getXmlFileRoot(){
		return $this->xmlFileRoot;
	}

	/**
	 * Setea los datos del XML.
	 *
	 * @param $value
	 */
	public function setXmlFileRoot($value){
		$this->xmlFileRoot = $value;
	}

	/**
	 * @param \php\XMLModel $xml
	 */
	public function setXmlDom(XMLModel $xml){
		$this->xmlDocument = $xml;
	}

	/**
	 * Método con el que compilamos el XML desde el documento.
	 *
	 * @throws \php\exceptions\Exception
	 */
	public function compileFromFile(){
		if(!is_file($this->xslFileRoot) || !is_file($this->xmlFileRoot)){
			throw new Exception("El fichero del modelo no esta definido");
		}
		$this->xmlDocument->load($this->xmlFileRoot);
		return $this->compile();
	}

	/**
	 * Método que se encarga de compilar la vista.
	 */
	public function compile(){
		try{
			if(!$this->xmlDocument instanceof XMLModel){
				throw new Exception("El modelo no es una instancia de XMLModel");
			}
			if(isset($_REQUEST['view'])){
				if ( $_REQUEST['view'] == 'xml'){
					header ("Content-Type:text/xml");
					echo $this->xmlDocument->asXML();
					die;
				}
				if ( $_REQUEST['view'] == 'json'){
					header ("Content-Type:application/json");

					echo XMLModelToJson::normalize($this->xmlDocument);
					die;
				}
			}
			if(!file_exists($this->xslFileRoot)){
				echo "El fichero {$this->xslFileRoot} no existe.";
				exit;
			}

			$locale = new Locale($this->xslFileRoot);
			$locale->compileView($this->xslDocument);

			$this->processor = new \XSLTProcessor();
			$this->processor->importStylesheet($this->xslDocument);
			$this->compiled = true;
		}catch(Exception $e){
			echo "No se puede compilar la vista. Error:\n{$e->getMessage()}";
			die;
		}
	}

	/**
	 * Método que devuelve la salida del modelo / vista.
	 * @param null $isApi
	 *
	 * @return string
	 */
	public function getOutput( $isApi = null ){
		if ($isApi){
			//header ("Content-Type:application/json");
			$output = XMLModelToJson::normalize($this->xmlDocument);
			return (is_null($output) || $output == 'null'?'':$output);
		}
		if(!$this->compiled){
			$this->compile();
		}
			return $this->processor->transformToXML($this->xmlDocument);
	}
}
?>
