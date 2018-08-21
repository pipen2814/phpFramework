<?php

namespace php\util;

use php\util\paginator\PaginatorRowButton;
use php\XMLModel;
use php\Hastable;
use php\sql\ResultSet;
use php\exceptions\Exception;

/**
 * Objeto generador de datos paginados
 *
 * @package php
 * @subpackage util
 */
class Paginator { 

	const DEFAULT_NUM_PAG = 10;
	const DEFAULT_TAG = "span";

	private $data = null;
	private $model = null;

	private $fields = array();
	private $hiddenFields = array();

	private $modifiedTags = array();

	private $modifiedFields = array();

	private $rowButtons = array();

	/**
	 * Setea los valores y datos por defecto 
	 *
	 * @param XMLModel $model Modelo donde se cargaran los datos
	 * @param ResultSet $data Datos a cargar
	 */
	public function __construct( XMLModel $model, $data ) {
		if($data instanceof ResultSet) {
			$this->model = $model;
			$this->data = $data;
			$this->setFields();
		} else {
			throw new Exception(sprintf("Tipo %s no soportado",get_class($data)));	
		}
	}

	/**
	 * Añade campos a ocultar
	 *
	 * @param string|array $field Campo(s) a ocultar
	 */
	public function addHiddenFields( $field ) {
		if(is_array($field)) {
			$this->hiddenFields = array_merge($this->hiddenFields,$field);
		} else {
			$this->hiddenFields[] = $field;
		}
	}

	/**
	 * Método que agrega un botón a cada row.
	 *
	 * @param string $buttonName Nombre del botón.
	 * @param string $buttonCommonID Parte común del identificador de botón.
	 * @param string $buttonRowID Parte única del identificador del botón.
	 * @param string $onclick Lo que se ejecutará al pulsar el botón.
	 */
	public function addRowButton( $buttonName, $buttonCommonID, $buttonRowID, $onclick ){
		$prb = new PaginatorRowButton($buttonName,$buttonCommonID,$buttonRowID,$onclick);

		$this->rowButtons[] = $prb;

	}

	/**
	 * Añade las cabeceras de los datos
	 */
	private function setFields() {
		foreach($this->data->getFields() as $count => $field) {
			if(!in_array($field[1],$this->hiddenFields)) {
				$this->fields["item_$count"] = $field[1];
			}
		}
	}

	/**
	 * Añade las cabeceras de las columnas al modelo
	 */
	public function addFields() {
		$items = $this->model->addChild('items');

		$cont = 0;
		foreach($this->fields as $field) {
			if(!in_array($field,$this->hiddenFields)) {
				// TODO: Convertir el field a traduccion
				$items["item_".$cont++] = $field;
			}
		}

		// Agregamos fields de botones para que encaje la tabla
		foreach($this->rowButtons as $rb){
			$items["item_".$cont++] = "";
		}
			
	}

	/**
	 * Añade los datos al modelo
	 */
	private function addData() {

		$items = $this->model->items;

		$count = 0;
		$page = 0;
		while($row = $this->data->next()) {
			
			if(($count % static::DEFAULT_NUM_PAG) == 0) {
				$items = $this->model->items->addChild('page');
				$items['page'] = ++$page;
			}
			
			$item = $items->addChild('item');
			$cont = 0;
			foreach($this->fields as $field) {
				$valueAttr=false;
				if(!in_array($field,$this->hiddenFields)) {
					
					if(isset($this->modifiedFields[$field])) {

						$tag = (isset($this->modifiedFields[$field]['tag']))?$this->modifiedFields[$field]['tag']:$tag = static::DEFAULT_TAG;
						foreach($this->modifiedFields[$field] as $name => $value) {
							if($name != 'tag') {
								preg_match_all('/\$\w+/i', $value, $output);
								foreach(array_pop($output) as $reg) {
									$regS = str_replace('$','',$reg);
									if(!is_null($row->$regS)) {
										$value = str_replace($reg,$row->$regS,$value);
									}
								}

								if($name == 'value') {
									$val = $value;
								} else {
									if ($name == 'valueAttr'){
										$valueAttr=true;
										$name = 'value';
									}
									$tag .= " $name=\"$value\"";
								}
							}
						}
						//$dataField = "<$tag>".$val.utf8_encode($row->$field)."</$tag>";
						if ($valueAttr) {
							$dataField = "<$tag />";
						}else{
							$dataField = "<$tag>" . $val . $row->$field . "</$tag>";
						}
					} else {
						$dataField = $row->$field;

					}
					$item["item_".$cont++] = utf8_encode( $dataField ); 
				}
			}

			foreach($this->rowButtons as $rb){
				$rb->loadRow($row);
				$item["item_".$cont++] = $rb->getButton();
			}

			$count++;
		}
	}

	/**
	 * Modifica la etiqueta html y los atributos de un nodo
	 * Si el atributo es value, añade el valor antes del texto de la columna
	 *
	 * @param string $field Campo a modificar
	 * @param string $tag Etiqueta HTML por la que se va a modificar
	 * @param array $atr Atributos que se añadiran al tag
	 */
	public function modifiedField( $field, $tag = null, $atr = array() ) {

		$this->modifiedFields[$field] = $atr;
		if(!is_null($tag)) {
			$this->modifiedFields[$field]['tag'] = $tag;
		}
	}

	/**
	 * Guarda los datos paginados en el modelo
	 */
	public function run() {
		$this->addFields();
		$this->addData();
	}


}
