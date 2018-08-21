<?php

namespace php\util\paginator;


use php\BaseObject;

/**
 * Class PaginatorButton
 *
 * Clase genérica de botón del paginador.
 *
 * @package php
 * @subpackage util
 * @subpackage paginator
 */
class PaginatorButton extends BaseObject {

	const CLASSES = 'btn btn-crm btn-block';
	/**
	 * @var null|string Nombre del botón.
	 */
	protected $name = null;

	/**
	 * @var null|string Parte común del identificador del botón
	 */
	protected $commonId = null;

	/**
	 * @var null|string Lo que se ejecutará en el onclick
	 */
	protected $onclick = null;

	/**
	 * PaginatorButton constructor.
	 *
	 * @param string $name Nombre del botón.
	 * @param string $commonId Parte común del identificador del botón.
	 * @param string $onclick Lo que se ejecutará en el onclick.
	 */
	public function __construct($name, $commonId, $onclick) {
		$this->name = $name;
		$this->commonId = $commonId;
		$this->onclick = $onclick;
	}

	/**
	 * Devuelve el nombre del botón.
	 *
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * Devuelve el identificador común del botón.
	 *
	 * @return string
	 */
	public function getCommonId(){
		return $this->commonId;
	}

	/**
	 * Devuelve el onclick del botón.
	 *
	 * @return string
	 */
	public function getOnclick(){
		return $this->onclick;
	}

	/**
	 * Método que devuelve las clases con las que daremos estilos a ese botón.
	 *
	 * @return string String con las clases para ese botón.
	 */
	protected function formatClasses(){
		return self::CLASSES;
	}

	/**
	 * Método que se encarga de devolver el Identificador del input
	 *
	 * @return string Identificador del Input.
	 */
	protected function formatId(){
		return $this->getCommonId();
	}

	/**
	 * Método que se encarga de devolver el name del input.
	 *
	 * @return string name del input.
	 */
	protected function formatName(){
		return $this->formatId();
	}

	/**
	 * Método que devuelve el value del input.
	 *
	 * @return string Value del input.
	 */
	protected function formatValue(){
		return $this->getName();
	}

	/**
	 * Método que se encarga de devolver el onlick que generaremos.
	 *
	 * @return string onclick del botón.
	 */
	protected function formatOnclick(){
		return $this->getOnclick();
	}
	/**
	 * Método que se encarga de parsear los datos y generar un elemento de HTML.
	 *
	 * @return string el input del botón.
	 */
	public function getButton(){

		return sprintf(PaginatorTemplates::BUTTON_TEMPLATE,
			$this->formatClasses(),
			$this->formatId(),
			$this->formatName(),
			$this->formatValue(),
			$this->formatOnclick());
	}
}