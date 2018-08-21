<?php

namespace php\managers;

use php\managers\Tokenizer;
use php\sql\DB;

/**
 * Class UserManager
 *
 * @package php
 * @subpackage managers
 */
class UserManager extends BaseManager{

	/*
	* Buscamos el usuario en base de datos. Si existe, creamos un token con su info y lo devolvemos. Si no se encuentra, devuelve false.
	*/
	public function loginAPI($user, $clave){

		$db = DB::getInstance();
		$clave= md5($clave);
		$db->query("select u.usuario, u.id_usuario, u.nombre, u.apellidos 
				from usuarios u
				where activo = 1 and u.usuario = '$user' and u.clave = '$clave'");
		$rs = $db->getRs();

		$userData = false;
		$token = false;
		if($reg = $rs->next()) {
			$userData = $this->createInfoForToken($reg);
			$token = Tokenizer::createToken($userData);
		}
		return array($userData, $token);

	}

	/*
	* Comprobamos si el token es correcto y no ha caducado, en cuyo caso lo devolvemos renovado.
	*/
	public function checkAPIToken($token){

		list($validToken, $decryptedToken) = Tokenizer::checkValidToken($token);
		$userData = false;
		$token = false;
		if($validToken) {
			$userData = $this->createInfoForToken($reg);
			$token = Tokenizer::createToken($decryptedToken->data);
		}
		return array($userData, $token);

	}



	/*
	* Creamos una clase y le damos como atributo los datos recogidos del login. Devolvemos la clase creada.
	*/
	protected function createInfoForToken($register){
		
		$user = new \stdClass();
		$user->usuario = $register->usuario;
		$user->idUsuario = $register->id_usuario;
		$user->nombre = $register->nombre;
		$user->apellidos = $register->apellidos;
		return $user;
	}
}
