<?php
namespace php;

use php\exceptions\Exception;

/**
 * Class App
 *
 * Clase encargada de gestionar todas las llamadas de la aplicacion y ejecutar los metodos que se invocan a partir de
 * la URL
 *
 * @package php
 */
class App {

	/**
	 * Metodo principal desde el que se ejecutara la llamada.
	 *
	 *
	 * @param $request hashtable todos los datos de la URL.
	 */
	public static function run($request)
	{

		// Montamos el manejador de excepciones gordas.
		self::handleFatalErrors();

		// Primero pillamos el path y nos lo cepillamos del request.
		$path = $request['path'];
		unset ($request['path']);

		// Montamos un hashtable con todos los argumentos del request.
		//TODO:: Hay que ver bien en que partes de la app hacemos esto, alguna sobra.
		$args = new Hashtable($request);
		//$controller->setArgs($args);

		// Parseamos la URL para recuperar el controlador y la accion.
		list($controller, $action) = self::parsePath($path);

		//Comprobamos si la peticion requiere de login y cumple con las necesidades para aceptarla
		$decryptedToken = $controller->checkRequestInfo();

		if($_REQUEST['interface'] == 'app'){
			// Creamos el nodo base del modelo.
			$appNode = $controller->addMainNode();

			// Recuperamos el nombre de la vista.
			$returnName = $controller->$action($appNode, $args);
			$viewName = self::getViewName($controller, $action, $returnName);
			$viewName = preg_replace('/controller$/i','',$viewName);

			// Escupimos el resultado.
			echo $controller->getOutput($viewName, (($_REQUEST['interface'] == 'api') ? 1 : 0));

		
		}elseif($_REQUEST['interface'] == 'api'){
			//Creamos una clase en blanco, que es la que rellenaremos y convertiremos en json para escupir
			$stdClass = new \stdClass();
			//$stdClass->tokenInfo = $decryptedToken;
			$args->APIUserId = $decryptedToken->data->idUsuario;
			$controller->$action($stdClass, $args);

			// Escupimos el resultado.
			echo $controller->parseResponse( $controller->getOutput($stdClass, (($_REQUEST['interface'] == 'api') ? 1 : 0)) );
			
			
		}else{
			throw new Exception("Interface not found");
		}


		// Detectamos si tenemos que lanzar metodos adicionales dependiendo de la interfaz de entrada
		/*
		$additionalAction = $action.ucfirst($_REQUEST['interface']);
		if(method_exists($controller, $additionalAction)) {
			$controller->$additionalAction($appNode, $args);
		}
		*/

	}

	/**
	 * Metodo con el que parseamos la URL y recuperamos el controller y la accion.
	 *
	 * @param $path string Lo que hay a continuacion del interfaz de entrada
	 *
	 * @see: /app/$path o /api/$path
	 *
	 * @return array Devuelve un array con el controlador y la accion.
	 */
	private static function parsePath($path)
	{
		$parts = explode('/', $path);
		$last = array_pop($parts);
		$explodedLast = explode('.', $last);

		// Datos que nos interesan
		$namespace = join('\\', $parts);
		$controllerName = $explodedLast[0];
		$actionName = (isset($explodedLast[1])) ? $explodedLast[1] : 'default';

		// Buscamos donde esta el controller.
		$controller = self::getControllerByName($namespace, $controllerName);

		// Lo mismo con la acción.
		$action = self::getActionByName($controller, $actionName);

		return array($controller, $action);
	}

	/**
	 * Metodo que devuelve una instancia del controlador, mirando todos los directorios desde el mas "custom" hasta el
	 * mas generico.
	 *
	 * @todo: CRM-19 - PENDIENTE DE REFACTOR: ACEPTAR X NAMESPACES EN EL ORDEN ESTIPULADO EN EL APP-ROUTER.
	 *
	 * @param $ns string El namespace que recuperamos del path.
	 * @param $controllerName String Nombre del controlador que vamos a invocar.
	 *
	 * @return mixed Instancia del controlador que queremos invocar.
	 * @throws Exception Devolvemos una excepcion si no encontramos el controlador.
	 *
	 */
	private static function getControllerByName($ns, $controllerName)
	{
		//Transformamos el nombre del controller
		$controllerName = ucwords($controllerName)."Controller";

		// Montamos los Paths.
		$commonPath = "/controllers/" . str_replace("\\", "/", $ns) . "/" . $controllerName . ".php";

		$fullControllerPathCustom = DIR_CLASSES_CUSTOM . $commonPath;
		$fullControllerPathCRM = DIR_CLASSES_CRM . $commonPath;
		$fullControllerPathPHP = DIR_CLASSES_PHP . $commonPath;

		// Generamos el NS comun para crm y custom.
		$commonNS = "controllers";
		if ($ns && !empty($ns)) {
			$commonNS .= "\\" . $ns;
		}
		$commonNS .= "\\" . $controllerName;

		// Ahora montamos la parte propia de cada directorio de controladores.
		if (file_exists($fullControllerPathCustom)) {
			$fullControllerNameCustom = "custom\\" . $commonNS;
			$controller = new $fullControllerNameCustom;

			return $controller;
		} elseif (file_exists($fullControllerPathCRM)) {
			$fullControllerNameCRM = "crm\\" . $commonNS;
			$controller = new $fullControllerNameCRM;

			return $controller;
		} elseif (file_exists($fullControllerPathPHP)) {
			$fullControllerNamePHP = "php\\" . $commonNS;
			$controller = new $fullControllerNamePHP;

			return $controller;

		} else {
			throw new Exception("Controller Not Found");
		}
	}

	/**
	 * Método que devuelve la acción que vamos a ejecutar.
	 *
	 * @param $controller mixed Instancia del controlador que vamos a ejecutar.
	 * @param $action string Nombre de la acción que se va a ejecutar.
	 *
	 * @return string Nombre del método (acción) que se va a lanzar.
	 *
	 * @throws Exception Devuelve una excepción si no encuentra la acción.
	 */
	private static function getActionByName($controller, $action)
	{
		// Nombre de la accion.
		$fullAction = $action . "Action";

		// Levantamos un ReflectionClass
		$c = new \ReflectionClass($controller);

		// Comprobamos si el controller tiene la accion.
		if ($c->hasMethod($fullAction)) {
			$a = $c->getMethod($fullAction);
			// Comprobamos que los comentarios estan OK.
			if (self::checkMethodComments($a)) {
				return $fullAction;
			} else {
				throw new Exception("\"Method '\" . $fullAction . \"' in class '\" . $c->getName()
					. \"' without comments");
			}
		} else {
			throw new Exception("Method '" . $fullAction . "' in class '" . $c->getName() . "' not found.");
		}
	}

	/**
	 * Metodo con el que recuperamos el nombre de la vista. El orden que predomina es el siguiente:
	 *        1. Si el controlador es default => El nombre del controlador sera el de la vista.
	 *        2. Si no es default:
	 *                2.1. Si el controlador devuelve un string => Ese sera el nombre de la vista.
	 *                2.2. Si no => El nombre del controlador sera el nombre de la vista.
	 *
	 * @todo: CRM-19 - PENDIENTE DE REFACTOR: ACEPTAR X NAMESPACES EN EL ORDEN ESTIPULADO EN EL APP-ROUTER.
	 *
	 * @param $controller mixed Instancia del controlador que vamos a invocar.
	 * @param $action string Nombre de la acción de la que vamos a buscar la vista. Actualmente no se utiliza.
	 * @param $returnName string Retorno de la ejecucion del controlador
	 *
	 * @return mixed|string Devuelve el path de la vista.
	 */
	private static function getViewName($controller, $action, $returnName)
	{

		// ReflectionClass del controlador y nos quedamos solo con lo siguiente de ...\controllers\.
		$c = new \ReflectionClass($controller);
		$path = str_replace('\\', '/', str_replace('custom\controllers', '', str_replace('crm\controllers', '', str_replace('php\controllers', '', $c->getNamespaceName()))));

		$viewName = (($path == '') ? '' : $path . '/');

		// Pillamos el nombre del controller.
		if (strtoupper($viewName) == 'DEFAULT') {
			$viewName .= $c->getShortName();
		} else {
			if ($returnName) {
				$viewName .= $returnName;
			} else {
				$viewName .= $c->getShortName();
			}
		}

		return $viewName;
	}

	/**
	 * Metodo con el que extraemos los comentarios en un array y comprobamos si puede ejecutarse
	 * en el interface (app / api).
	 *
	 * @param $method \ReflectionMethod Metodo del que miraremos los comentarios.
	 *
	 * @return bool Si en los comentarios esta permitido el interfaz de la llamada -> true.
	 *
	 * @throws Exception Si no hay comentarios, o no esta la interfaz de la llamada definida, lanzamos excepcion.
	 */
	private static function checkMethodComments($method)
	{
		$comments = $method->getDocComment();
		if ($comments) {
			$commentLines = preg_split('/\r\n|\r|\n/', $comments);
			$commentLines = array_map(array(__CLASS__, 'cleanComments'), $commentLines);

			// Comprobamos si la interfaz esta disponible para ese metodo.
			if (!in_array('@' . $_REQUEST['interface'], $commentLines)) {
				throw new Exception('Method ' . $method->getName()
					. ' is not available through ' . $_REQUEST['interface'] . ' interface');
			}

			return true;
		} else {
			throw new Exception('Method ' . $method->getName() . ' without necessary doc.');
		}
	}

	/**
	 * Metodo que se encarga de limpiar los comentarios y dejar solo el texto.
	 *
	 * @param $comment string Comentario antes de limpiar.
	 *
	 * @return string comentario tras limpiarlo.
	 */
	private static function cleanComments($comment)
	{
		return trim(preg_replace('/(\/)?\**(\/)?/', '', $comment));
	}

	/**
	 * Metodo que se encarga de gestionar los errores que no pueden ser capturados automaticamente por PHP.
	 *
	 * @todo: CRM-20 - Tratar errores no capturables.
	 */
	private static function handleFatalErrors()
	{
		register_shutdown_function(
			function () {

				$error = error_get_last();
			//	var_dump($error);

				switch ($error['type']) {
					case E_ERROR:
					case E_USER_ERROR:
					case E_PARSE:
						echo "<pre><b>Error no capturable</b><br>";
						echo "ERRROR:" . $error['message']."<br>";
						break;

				}
			}
		);
	}

}
