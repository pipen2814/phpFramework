<?php

namespace php;


/**
 * Class Session
 *
 * Clase de Sesión.
 *
 * @package php
 */
class Session extends BaseObject {

	/**
	 * Inicia una sesión de.
	 */
	public static function startSession(){
		if(!isset($_SESSION)){
			session_start();
		}
	}

	/**
	 * @param $idUsuario Agrega el idUsuario a la sesión.
	 */
	public static function setUserSession($idUsuario){
		if(isset($_SESSION)){
			self::startSession();
		}
		self::setSessionVar('idUsuario', $idUsuario);
	}

	/**
	 * Setea la organización a la sesión.
	 *
	 * @param $idOrganizacion
	 */
	public static function setOrganizationSession($idOrganizacion){
		if(isset($_SESSION)){
			self::startSession();
		}
		self::setSessionVar('idOrganizacion', $idOrganizacion);
	}

	/**
	 * Devuelve el dato de la sesión.
	 *
	 * @param $varName
	 *
	 * @return null
	 */
	public static function getSessionVar($varName){
		if(!isset($_SESSION)){
			self::startSession();
			return null;
		}else{
			if(isset($_SESSION[$varName])){
				return $_SESSION[$varName];
			}else{
				return null;
			}
		}
	}

	/**
	 * Almacena un dato en la sesión.
	 *
	 * @param $name
	 * @param $value
	 */
	public static function setSessionVar($name, $value){
		if(!isset($_SESSION)){
			self::startSession();
		}
		$_SESSION[$name] = $value;
	}

	/**
	 * Destruye una sesión.
	 */
	public static function destroy(){
		if(isset($_SESSION)){
			session_destroy();
		}
	}

	/**
	 * Recupera el id de sesión.
	 *
	 * @return null|string
	 */
	public static function getSessionId(){
		if(isset($_SESSION)){
			return session_id();
		}else{
			return null;
		}
	}
}
?>
