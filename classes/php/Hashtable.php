<?php

namespace php;

/**
 * Class Hashtable
 *
 * Clase Hashtable base de la que tira toda la aplicación.
 *
 * @package php
 */
class Hashtable extends BaseObject implements \Iterator {

	/**
	 * @var array|null El array de datos.
	 */
	private $array = array();

	/**
	 * Hashtable constructor.
	 *
	 * @param null $values
	 */
	public function __construct( $values=null ) {
		if ( is_array( $values ) ) {
			$this->array = $values;
		}
	}

	/**
	 * Getter de Array.
	 *
	 * @return array un Array.
	 */
	public function getArray(){
		return $this->array;
	}

	/**
	 * Agrega un nuevo valor al hashtable.
	 *
	 * @deprecated
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	public function put( $key, $val ) {
		$this->array[$key] = $val;
	}

	/**
	 * Borra un valor del hashtable.
	 *
	 * @param string key
	 */
	public function remove( $key ) {
		if (isset($this->array[$key])) {
			unset($this->array[$key]);
		}
	}
	
	/**
	 * Comprueba si el hashtable tiene esa clave.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function has( $key ) {
		return array_key_exists( $key, $this->array );
	}
	
	/**
	 * Devuelve el valor de un hashtable.
	 *
	 * @deprecated
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( $key ) {
		return ( $this->has($key) ? $this->array[$key] : null );
	}

	/**
	 * Magic Getter. No hace falta usar el get.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic Setter. No hace falta usar el put.
	 *
	 * @param string $key
	 * @param mixed $val
	 */ 
	public function __set( $key, $val ) {
		$this->put( $key, $val );
	}
	
	/**
	 * Devuelve el array del Hashtable.
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->array;
	}
	
	/**
	 * Limpia todos los datos del Hashtable.
	 */
	public function clear() {
		$this->array = array();
	}

	/**
	 * Devuelve la longitud del hashtable.
	 * 
	 * @return int
	 */
	public function length() {
		return sizeof( $this->array );
	}

	/**
	 * Devuelve las claves del array.
	 *
	 * @return array
	 */
	public function getKeys() {
		return array_keys( $this->array );
	}

	/**
	* Resetea la posición del cursor.
	*/
	public function rewind() {
		reset($this->array);
	}

	/**
	 * Devuelve la cantidad de elementos del array.
	 *
	 * @return int
	 */
	public function count() {
		return count($this->array);
	}

	/**
	 * Devuelve el elemento actual del puntero.
	 *
	 * @return mixed
	 */
	public function current() {
		return  current($this->array);
	}

	/**
	 * Devuelve el siguiente elemento del array.
	 *
	 * @return mixed
	 */
	public function next() {
		return  next($this->array);
	}
	
	/**
	* Devuelve la clave actual.
	*/
	public function key() {
		return key($this->array);
	}

	/**
	 * Comprueba si el iterador es válido.
	 *
	 * @return bool
	 */
	public function valid() {
		return ($this->current() !== false);
	}

	/**
	 * Devuelve una instancia del hashtable a partir de unos argumentos
	 *
	 * @return \php\Hashtable
	 */
	public static function getFromArgs(){

		$args = func_get_args();

		if(!is_null($args) && is_array($args))
			return new Hashtable($args);

		return new Hashtable();
	}

	/**
	 * Devuelve una representación en string del Hashtable.
	 */
	public function __toString() {
		$str = sprintf( "[%s (ID #%s)] (%d elements)\n", get_called_class(),1, $this->length() );
		$str.= "Values:\n";
		foreach( $this->array as $field => $value ) {
			$str.= sprintf( "  -> %-25s = %s\n", $field, $value );
		}
		return $str."\n";
	}
	
}
