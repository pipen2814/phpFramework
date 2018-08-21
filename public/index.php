<?php

use php\App;
use php\exceptions\Exception;

if (!$_REQUEST['path']){
	header('Location: /app/login');
}

try{
	App::run($_REQUEST);

}catch(Exception $e){

	echo "Error: " . $e->getMessage() . PHP_EOL;

	// TODO: Si es DEV por pantalla, Si es PROD: a Fichero para que nadie pueda ver nuestros PATHS,
	echo '<pre>' .$e->getTraceAsString();

}
