<?php

namespace php\managers;

use php\Hashtable;
use Firebase\JWT\JWT;

/**
 * Class Tokenizer
 *
 * @package php
 * @subpackage managers
 */
class Tokenizer extends BaseManager{

	public static function createToken($data = null){
		$time = time();
		$token = array(
			"iat" => $time,
			"exp" => $time + (int)TOKEN_EXPIRATION,
			"data" => $data 
		);
		$jwt = JWT::encode($token, TOKEN_PASSWORD);
		return $jwt;
	}

	public static function decryptToken($token){
		try{
			return JWT::decode($token, TOKEN_PASSWORD, array('HS256'));
		}catch(\Exception $e){
			return false;
		}
	}

	public static function checkValidToken($token){
		$decryptedToken = static::decryptToken($token);
		$time = time();
		if ($decryptedToken && $decryptedToken->iat <= $time && $decryptedToken->exp >= $time){
			return array(true, $decryptedToken);
		}else{
			return array(false,false);
		}
	}
}
