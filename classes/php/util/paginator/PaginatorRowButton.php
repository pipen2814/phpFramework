<?php

namespace php\util\paginator;
use php\sql\ResultSetRow;

/**
 * Class PaginatorRowButton
 *
 * Clase encargada de almacenar los datos del botón del paginador.
 *
 * @package php\util\paginator
 */
class PaginatorRowButton extends PaginatorButton {

	/**
	 * @var null|string Parte única del identificador para saber diferenciar entre cada columna.
	 */
	protected $buttonRowId = null;

	/**
	 * @var null|string Valor del id a partir de buttonRowId.
	 */
	protected $buttonRowValueId = null;

	/**
	 * PaginatorRowButton constructor.
	 *
	 * @param string $name Nombre del botón.
	 * @param string $commonId Parte común del identificador del botón.
	 * @param string $buttonRowId Parte única del identificador para saber diferenciar entre cada columna.
	 * @param string $onclick Lo que se ejecutará en el onclick.
	 */
	public function __construct($name, $commonId, $buttonRowId, $onclick) {
		parent::__construct($name, $commonId, $onclick);

		$this->buttonRowId = $buttonRowId;
	}

	/**
	 * Recuperamos el identificador que diferencia entre cada columna.
	 *
	 * @return null|string El identificador.
	 */
	public function getButtonRowId(){
		return $this->buttonRowId;
	}

	/**
	 * Recuperamos el valor del identificador que diferencia entre cada columna.
	 *
	 * @return null|string El valor del identificador cargado con el loadRow.
	 */
	public function getButtonRowValueId(){
		return $this->buttonRowValueId;
	}


	/**
	 * Método que carga los datos del id variable a partir del row.
	 *
	 * @todo: Importante, tenemos que cambiar este método cuando queramos permitir buttonRowId's de n campos. (n > 1).
	 * @param ResultSetRow $row Registro que estamos pintando.
	 */
	public function loadRow($row){
		$this->buttonRowValueId = $row->get($this->buttonRowId);
	}

	/**
	 * Método con el que formatearemos el id agregando el variable de cada row.
	 * @return string
	 */
	public function formatId() {
		return $this->getCommonId()."_".$this->getButtonRowValueId();
	}

}