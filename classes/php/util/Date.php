<?php

namespace php\util;

use php\BaseObject;
use php\XMLModel;

/**
 * Class Date
 *
 * Clase encargada de gestionar todas las fechas desde PHP
 *
 * @package php
 * @subpackage util
 */
class Date extends BaseObject {

	/**
	 * Formato de año 4 dígitos.
	 */
	const FORMAT_FULL_YEAR = 'Y';
	/**
	 * Formato de mes 01-12
	 */
	const FORMAT_NUMBER_MONTH = 'm';
	/**
	 * Formato de día 01-31
	 */
	const FORMAT_DAY = 'd';
	/**
	 * Formato de Horas: 00-23
	 */
	const FORMAT_HOUR = 'H';
	/**
	 * Formato de minutos 00-59
	 */
	const FORMAT_MINUTES = 'i';
	/**
	 * Formato de segundos 00-59
	 */
	const FORMAT_SECONDS = 's';

	/**
	 * Formato fecha SQL: 2017-01-01
	 */
	const FORMAT_SQL_DATE = 'Y-m-d';
	/**
	 * Formato fechaHora SQL: 2016-01-01 23:59:59
	 */
	const FORMAT_SQL_DATETIME = 'Y-m-d H:i:s';
	/**
	 * Formato fechaHora de <inmogestor:calendar> 28-02-2017 10:10
	 */
	const FORMAT_INMOGESTOR_CALENDAR = 'd-m-Y H:i';


	/**
	 * @var int|null Timestamp de la hora de cargamos en el objeto.
	 */
	protected $timeStamp = null;

	/**
	 * Date constructor.
	 *
	 * @param null|string $date Fecha con la que inicializaremos el objeto.
	 */
	public function __construct( $date = null ) {
		if (is_null($date)){
			$this->timeStamp = time();
		}else{
			$this->timeStamp = strtotime($date);
		}
	}

	/**
	 * Método encargado de formatear la fecha al formato que queramos.
	 *
	 * @param string $format Cadena de formato.
	 *
	 * @return string Resultado del formateo.
	 */
	public function formatDate($format){

		return date($format, $this->timeStamp);
	}

	/**
	 * Método que carga en el modelo pasado todos los datos de la hora como atributos.
	 *
	 * @param XMLModel $model
	 */
	public function insertIntoModel( XMLModel $model ){
		$model['time_stamp'] = $this->timeStamp;

		$model['year'] = $this->formatDate(self::FORMAT_FULL_YEAR);
		$model['month'] = $this->formatDate(self::FORMAT_NUMBER_MONTH);
		$model['js_month'] = ((int)$model['month']) -1;
		$model['day'] = $this->formatDate(self::FORMAT_DAY);

		$model['hours'] = $this->formatDate(self::FORMAT_HOUR);
		$model['minutes'] = $this->formatDate(self::FORMAT_MINUTES);
		$model['seconds'] = $this->formatDate(self::FORMAT_SECONDS);
	}
}
