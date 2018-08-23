<?php

namespace php;

use php\sql\DB;
use php\exceptions\Exception;
use php\util\customXSLT\InmoGestorBaseTransformer;

/**
 * Class Locale
 *
 * Clase encargada de generar los XSL compilados.
 *
 * @package php
 */
class Locale extends BaseObject {

	/**
	 * @var null Fichero XSL.
	 */
	private $xslFileRoot;

	/**
	 * Locale constructor.
	 *
	 * @param null $xslFileRoot
	 */
	public function __construct($xslFileRoot = null){
		if(!is_null($xslFileRoot)){
			$this->xslFileRoot = $xslFileRoot;
		}
	}

	/**
	 * Método encargado de compilar los imports del XSL.
	 *
	 * @param $dir
	 * @param $imports
	 *
	 * @throws \php\exceptions\Exception
	 */
	public function compileImports($dir, $imports){

		foreach ( $imports as $i ){
			$customPath = realpath(DIR_VIEWS. "/custom/" . $dir ."/" . $i['href']);
			$crmPath = realpath(DIR_VIEWS. "/crm/" . $dir ."/" . $i['href']);

			$a = pathinfo($i['href']);

			if ($customPath && !$crmPath){
				// Montamos el XML de custom.
				$xml = simplexml_load_file($customPath);
			}elseif(!$customPath && $crmPath){
				// Montamos el XML de crm.
				$xml = simplexml_load_file($crmPath);
			}elseif($crmPath && $customPath){
				// AQUI VIENE LA FIESTA.
			}else{
				throw new Exception("Import file not found.");
			}

			$this->translateXML($xml);
			$this->translateInmoGestorNS($xml);
			$dirComp = DIR_VIEWS . '/es_ES/' . $dir .'/'. $a['dirname'] . '/';
			$dirComp = realpath($dirComp);
			$dirComp = str_replace("./", "/", $dirComp );
			if (!is_dir($dirComp)){
				mkdir($dirComp,0755,true);
			}
			$xml->asXML($dirComp . '/'.$a['filename'].".".$a['extension']);
		}
	}

	/**
	 * Método encargado de compilar las vistas.
	 *
	 * @param $xslDocument
	 */
	public function compileView(&$xslDocument){

		if (strpos(strtoupper($this->xslFileRoot), strtoupper(DIR_VIEWS . "/custom/")) !== false ){
			$viewDirectoryAndName = substr( $this->xslFileRoot,strpos($this->xslFileRoot,DIR_VIEWS)+strlen(DIR_VIEWS)+strlen("/custom/"));
		}elseif (strpos(strtoupper($this->xslFileRoot), strtoupper(DIR_VIEWS . "/crm/")) !== false ){
                        $viewDirectoryAndName = substr( $this->xslFileRoot,strpos($this->xslFileRoot,DIR_VIEWS)+strlen(DIR_VIEWS)+strlen("/crm/"));
		}elseif (strpos(strtoupper($this->xslFileRoot), strtoupper(DIR_VIEWS )) !== false ){
                        $viewDirectoryAndName = substr( $this->xslFileRoot,strpos($this->xslFileRoot,DIR_VIEWS)+strlen(DIR_VIEWS));
                }

		$localesDirectory = DIR_VIEWS."es_ES/";
		$localesDirectoryTemplates = DIR_VIEWS."es_ES/templates/";

		if(!is_dir($localesDirectory)){
			mkdir($localesDirectory,0777,true);
			mkdir($localesDirectoryTemplates,0777,true);
		}
		$directories = explode("/", $viewDirectoryAndName);
		$filename = false;
		foreach($directories as $directory){
			if($directory!="" && strpos($directory, ".xsl")=== false){
				$localesDirectory.= $directory."/";
				if(!is_dir($localesDirectory)){
					mkdir($localesDirectory);
				}
			}elseif(strpos($directory, ".xsl")!== false){
				$filename = $directory;
			}
		}

		if(!REGENERATE_VIEWS && $filename && file_exists($localesDirectory.$filename)){
			$xslDocument = simplexml_load_file($localesDirectory.$filename);
		}else{
			$xslDocument = simplexml_load_file($this->xslFileRoot);
			$this->translateXML($xslDocument);
			$this->translateInmoGestorNS($xslDocument);
			$xslDocument->asXml($localesDirectory.$filename);
		}
		// Recuperamos el PATH.
		$dir = pathinfo($viewDirectoryAndName)['dirname'];
		$xslImports = $xslDocument->xpath( '//xsl:import' );
		$this->compileImports($dir,$xslImports);
		foreach ( $xslImports as $xsl ) {
			$xsl->attributes()->href = realpath($localesDirectory . $xsl->attributes()->href);
		}

	}

	/**
	 * Método encargado de modificar los valores del XSL de i18n:text.
	 *
	 * @param $xml
	 */
	private function translateXML( &$xml ){
		$domXML = new \DOMDocument();
		$domXML->loadXML($xml->asXML());
		$xpath =  new \DOMXPath ($domXML);
		$xpath->registerNamespace('i18n', 'http://apache.org/cocoon/i18n/2.1');

		$xslDomains = $xpath->query( '//i18n:domain' );
		$domains = array("global");
		foreach( $xslDomains as $domain ) {
			$domains[] = $domain->nodeValue;
		}

		$nodes = $xpath->query( '//i18n:text' );
		// TODO: DESCOMENTAR Y ARREGLAR
		$db = DB::getInstance();
		foreach( $nodes as $node ) {
			$txt = $this->searchTraduction ($db, $domains, $node->nodeValue);
			if(!$txt) $txt = $node->nodeValue;
			$node->parentNode->replaceChild( $domXML->createTextNode( $txt ), $node);
		}

		$xml = simplexml_import_dom($domXML);
	}

	/**
	 * Método que se encarga de parsear todos los elementos de inmogestor y los modifica.
	 *
	 * @todo: Refactor moviendo todo a estructura de clases en la que se definan los objetos existentes.
	 *
	 * @param $xml
	 */
	private function translateInmoGestorNS( &$xml ){
		$domXML = new \DOMDocument();
		$domXML->loadXML($xml->asXML());

		InmoGestorBaseTransformer::transform($domXML);

		$xml = simplexml_import_dom($domXML);
	}

	/**
	 * Metodo encargado de buscar una traduccion.
	 *
	 * @param $db
	 * @param $domains
	 * @param $text
	 *
	 * @return string
	 */
	private function searchTraduction($db, $domains, $text){
		//TODO:: Hay que eliminar la oblicacion de usar esta tabla, hay que abstraer la funcionalidad para poder decidir entre base de datos, fichero... o nada.
		$domainsCond = "";
		$domains = array_reverse($domains);
		foreach($domains as $domain){
			$domainsCond.=($domainsCond == ""?" and dominio in('$domain'":",'$domain'");
		}
		$domainsCond.=")";
		$db->query("select txt from trads_ES where clave = '$text' $domainsCond ");
                $rs = $db->getRs();
                $rs->next();

                return utf8_encode($rs->txt);
	}
}
?>
