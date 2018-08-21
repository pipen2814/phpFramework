<?php
/**
 * Created by IntelliJ IDEA.
 * User: lmmartin
 * Date: 19/1/17
 * Time: 23:34
 */

namespace php\util;


use php\BaseObject;

/**
 * Class Request
 * @package php
 * @subpackage util
 */
class Request extends BaseObject {

	/**
	 * Método que utilizamos para redirigir las llamadas de la aplicación.
	 *
	 * @param $url Url a donde iremos.
	 */
	public static function redirect( $url ){
		header('Location:'.$url);
	}
}