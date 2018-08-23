<?php

namespace php;

use php\XSLTCompiler;
use php\Hashtable;
use php\exceptions\Exception;
use php\managers\Tokenizer;

/**
 * Class Controller
 *
 * Clase base de Controladores.
 *
 * @package php
 */
class Controller extends BaseObject {

	protected $xslDocumentName;
	protected $xslDocumentRoot;
	protected $model;
	protected $args;
	protected $compiler;

	/**
	 * Controller constructor.
	 */
	public function __construct(){
		if(!$this->isAPI()){
			$this->compiler = new XSLTCompiler();
			$this->model = new XMLModel("<app></app>");
			$this->compiler->setXmlDom($this->model);
			$this->xslDocumentRoot = DIR_VIEWS;
		}
	}

	/**
	 * Getter del compilador.
	 *
	 * @return \php\XSLTCompiler
	 */
	public function getCompiler(){
		return $this->compiler;
	}

	/**
	 * Setter de los argumentos.
	 *
	 * @param $args
	 */
	public function setArgs( $args ){
		$this->args = $args;
	}

	/**
	 * Metodo que se encarga de codificar un valor a utf8.
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public function encode($value){
		return utf8_encode($value);
	}

	/**
	 * Metodo que se encarga de decodificar de utf8 un valor.
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public function decode($value){
		return utf8_decode($value);
	}

	/**
	 * Setter del nombre del XSL.
	 *
	 * @param $value
	 */
	protected function setXslDocumentName($value){
		$this->xslDocumentName = $value;
	}

	/**
	 * Getter del nombre del XSL.
	 *
	 * @return mixed
	 */
	protected function getXslDocumentName(){
		return $this->xslDocumentName;
	}

	/**
	 * Setter de la raiz del XSL.
	 *
	 * @param $value
	 */
	protected function setXslDocumentRoot($value){
		$this->xslDocumentRoot = $value;
	}

	/**
	 * Getter de la raiz del XSL.
	 *
	 * @return mixed
	 */
	protected function getXslDocumentRoot(){
		return $this->xslDocumentRoot;
	}

	/**
	 * Metodo que setea la informacion del usuario.
	 *
	 * @return \php\XMLModel
	 */
	public function checkRequestInfo(){
		if($_REQUEST['interface'] == 'api'){
			header('Content-Type: application/json');
			header("access-control-allow-origin: *");
			header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
			header("Allow: GET, POST, OPTIONS, PUT, DELETE");

			if($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
				die();
			}

			if(!is_null($_REQUEST['apiTK'])){
				list($validToken, $decryptedToken) = Tokenizer::checkValidToken($_REQUEST['apiTK']);
				if(!$validToken){
					echo '{"status": "KO", "message": "Not valid token found for request, check your session before continue"}';
					die;
				}else{
					//El token es valido y dejamos pasar la peticion. Si hay que hacer algo comun a todas las peticiones API, hacerlo aqui.
					return $decryptedToken;
				}
			}else{	//Tenemos una llamada al API sin token, o es algo especifico que lo permita o es error
			
				//TODO::Hacer una clase APIByPass que gestione las peticiones que pasan sin API. De momento escrito a mano que pasa solo el login.
				if($_REQUEST['path'] != 'login.loginAPI'){
					echo '{"status": "KO", "message": "Not valid token found for request, try to include a valid one"}';
					die;
				}
			}
		}else{ //Llamada por app normal
			if($this->checkAppCredentials()){
			//TODO: Agregar variable a config para determinar si se entra por login o no. En PHP no debemos obligar a login.
			Session::startSession();
			$appNode = $this->addMainNode();
			if(!is_null(Session::getSessionVar('idUsuario')) && $_REQUEST['interface'] == 'app') {
				$userRS = UserManager::getUserById(Session::getSessionVar('idUsuario'));
				if ($userRS->next()){
					//$this->generateJWT($appNode, $userRS);
				}
				userModel::addCompleteUserInfomation($this->model, $appNode);
			}elseif(is_null(Session::getSessionVar('idUsuario')) && strpos($_REQUEST["path"], "login")=== false){
				header("Location: /app/login.logout");
			}
			return $appNode;
			}else{
				//No hacemos que se compruebe nada, se continua la ejecucion normal. Configuracion por defecto para PHP
			}
		}
	}

	protected function checkAppCredentials(){
		return false;
	}

	/**
	 * Metodo que agrega los datos del nodo principal.
	 *
	 * @return \php\XMLModel
	 */
	public function addMainNode(){
		$app = $this->model;
		return $app;
	}

	/**
	 * Metodo que devuelve la salida del HTML.
	 *
	 * @param null $viewName
	 * @param null $isApi
	 *
	 * @return string
	 * @throws \php\exceptions\Exception
	 */
	public function getOutput( $viewName = null, $isApi = null ){
		if ( $isApi ){ 
			return json_encode($viewName);
		}

		if ( file_exists($this->xslDocumentRoot . "/custom/" . $viewName . ".xsl") ){
			$this->compiler->setXslFileRoot($this->xslDocumentRoot."/custom/".$viewName.".xsl");

			return $this->compiler->getOutput();
		}elseif ( file_exists($this->xslDocumentRoot . "/crm/" . $viewName . ".xsl") ){
			$this->compiler->setXslFileRoot($this->xslDocumentRoot."/crm/".$viewName.".xsl");

			return $this->compiler->getOutput();
		}elseif ( file_exists($this->xslDocumentRoot . $viewName . ".xsl") ){
			$this->compiler->setXslFileRoot($this->xslDocumentRoot.$viewName.".xsl");

			return $this->compiler->getOutput();
		
		}else{
			if ($this->args->view == 'json' || $this->args->view == 'xml'){
				return $this->compiler->getOutput();
			}else {
				throw new Exception("View not found");
			}
		}
	}

	/**
	 * Metodo que agrega un resultset al dom
	 *
	 * @deprecated
	 *
	 * @param $nodeName
	 * @param $node
	 * @param $rs
	 * @param bool $utf8Encode
	 */
	public function addResultSet($nodeName, $node, $rs, $utf8Encode = true){
		$parentNode = $this->model->createElement($nodeName);
		$node->appendChild($parentNode);
		while($result = $rs->next()){
			$item = $this->model->createElement("item");
			if(is_array($result->getValues()))foreach($result->getValues() as $key => $value){
				if($utf8Encode){
					$item->setAttribute($key, utf8_encode($value));
				}else{
					$item->setAttribute($key, $value);
				}
			}
			$parentNode->appendChild($item);
		}
	}


	/**
	 * Metodo que agrega los datos del request al modelo.
	 *
	 * @param $parentNode
	 */
	protected function addRequestNode($parentNode){
		$requestNode = $parentNode->addChild("request");
		if(!is_null($_REQUEST)) {
			foreach($_REQUEST as $index => $value){
				$requestNode[$index] = $value;
			}
		}
	}

	/**
	 * Crea un nodo de mensajes en el modelo
	 *
	 * @param XMLModel $appNode Modelo
	 * @param string $type Tipo de mensaje
	 * @param strin $msg Mensaje a mostrar
	 * @return void
	 */
	private function addMessage($parentNode,$type,$msg) {

		if(!isset($parentNode->messages)) {
			$node = $parentNode->addChild("messages");
		} else {
			$node = $parentNode->messages;
		}

		$msgNode = $node->addChild("message");
		$msgNode["type"] = $type;
		$msgNode["message"] = $msg;
	}
	
	/**
	 * Crea un mensajes de tipo Success
	 *
	 * @param XMLModel $appNode Modelo
	 * @param strin $txt Mensaje a mostrar
	 * @return void
	 */
	protected function addSuccess($parentNode, $txt) {
		$this->addMessage($parentNode, "success", $txt);
	}

	/**
	 * Crea un mensajes de tipo Info
	 *
	 * @param XMLModel $appNode Modelo
	 * @param strin $txt Mensaje a mostrar
	 * @return void
	 */
	protected function addInfo($parentNode, $txt) {
		$this->addMessage($parentNode, "info", $txt);
	}

	/**
	 * Crea un mensajes de tipo Warning
	 *
	 * @param XMLModel $appNode Modelo
	 * @param strin $txt Mensaje a mostrar
	 * @return void
	 */
	protected function addWarning($parentNode, $txt) {
		$this->addMessage($parentNode, "warning", $txt);
	}

	/**
	 * Crea un mensajes de tipo Error
	 *
	 * @param XMLModel $appNode Modelo
	 * @param strin $txt Mensaje a mostrar
	 * @return void
	 */
	protected function addError($parentNode, $txt) {
		$this->addMessage($parentNode, "error", $txt);
	}

	/**
	 * Metodo que comprueba si es API o no.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function isAPI(){
		if (strtolower($_REQUEST['interface']) == 'api')
			return true;
		else
			return false;
	}

	/**
	 * Metodo que hackea la respuesta para peticion API entre dominios.
	 *
	 * @param response$
	 *
	 * @return string
	 */

	public function parseResponse($response){
		if(!is_null($_REQUEST['callback'])){
			return $_REQUEST['callback'] . '(' . "$response" . ')';
		}else{
			return $response;
		}
	}
}
?>
