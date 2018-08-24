<?php

namespace php\orm;

use php\exceptions\SQLException;
use php\sql\ConnectionFactory;
use php\sql\DB;
use php\session;
use php\Hashtable;
use php\Exception;
use php\sql\MySQLResultSet;
use php\sql\ResultSetRow;

/**
 *
 * Class BaseORM
 *
 * Define el funcionamineto de basico de los ORM
 *
 * @package crm
 * @subpackage orm
 */
class BaseORM extends Hashtable {

	protected $db = null;
	protected $conn = null;
	protected $tableName = null;
	protected $id = array();
	protected $fields = array();
	protected $isNew = false;

	protected $host = "crmmaster";

	/**
	 * Crea o usa una instancia del objeto
	 * 
	 * @param bool $forceNewInstance
	 * @param string $hostname Nombre del host de base de datos al que conectarse
	 * @param string $database Base de datos en la que buscar
	 * @param string $password Contraseña para la conexión
	 * @param string $username Usuario para la conexión
	 *
	 * @return void
	 */
	public function __construct($forceNewInstance = false, $hostname = MASTER_DATABASE_SERVER, $database = MASTER_DATABASE_NAME,  $password = MASTER_DATABASE_PWD, $username = MASTER_DATABASE_USER){
		$this->isNew = true;
		if($this->host == "crmmaster"){
			$this->db = DB::getInstance($forceNewInstance, MASTER_DATABASE_SERVER, MASTER_DATABASE_NAME, MASTER_DATABASE_PWD, MASTER_DATABASE_USER);
			$this->conn = new ConnectionFactory('MASTER');
		}elseif($this->host == "organization"){
			$database = "organization_".session::getSessionVar('idOrganizacion');
			$hostname = session::getSessionVar('hostnameOrganization');
			$this->db = DB::getInstance($forceNewInstance, $hostname, $database, ORGANIZATION_DATABASE_PWD, ORGANIZATION_DATABASE_USER);
			$this->conn = new ConnectionFactory();
		}else{
			throw new \Exception("No se ha definido la variable host en BaseORM");
		}
	}

	/**
	 * Getter del atributo tableName.
	 *
	 * @return array Fields del ORM.
	 */
	public function getTableName(){
		return $this->tableName;
	}


	/**
	 * Getter del atributo fields del ORM.
	 *
	 * @return array Fields del ORM.
	 */
	public function getFields(){
		return $this->fields;
	}

	/**
	 * Cambia la conexion a BD
	 *
	 * @param $forceNewInstance = false determina si tiene que crear una nueva instancia de bd o reusarla
	 * @param $hostname = MASTER_DATABASE_SERVER Host de BD
	 * @param $database = MASTER_DATABASE_NAME Nombre de BD
	 * @param $password = MASTER_DATABASE_PWD Contraseña de usuario de BD
	 * @param $usarname = MASTER_DATABASE_USER Usuario de BD
	 */
	public function setHostConnection($forceNewInstance = false, $hostname = MASTER_DATABASE_SERVER, $database = MASTER_DATABASE_NAME, $password = MASTER_DATABASE_PWD, $username = MASTER_DATABASE_USER){
		$this->db = DB::getInstance($forceNewInstance, $hostname, $database, $password, $username);
	}

	/**
	 * Obtiene los registros de la tabla mediante sus claves primarias
	 *
	 * @param $args Claves primarias de la tabla
	 *
	 * @return $rs Resultado de la busqueda
	 */
	public function getByPK(){
		$args = func_get_args();

		if(count($this->id) != count($args)) {
			throw new Exception("El número de parametros no es el esperado");
		}

		$hydratedObject = $this->getById($args);
		$firstPos = $this->fields[$this->id[0]];
		$firstPKField = $this->$firstPos;

		return (is_null($firstPKField))?null:$hydratedObject;
	}

	/**
	 * Obtiene resultados mediante el id de fila
	 *
	 * @param $id Id de la fila
	 *
	 * @return $r Fila de BD
	 */
	public function getById($id){
		$condition = "";
		$count=0;
		if(is_array($id)){
			foreach($this->id as $index){
				$condition.=($condition==""?" $index = ":" and $index = ");
				$condition.=(is_numeric($id[$count])?" ".$id[$count]:"'".preg_replace("/'/","\'",$id[$count])."'");
				$count++;
			}
		}else{
			$condition = " ".$this->id[0]." = ";	
			$condition.=(is_numeric($id)?$id:"'".preg_replace("/'/","\'",$id)."'");
		}
		$this->db->ExecQuery("select * from ".$this->tableName." where $condition");
		return $this->hydrate($this->db->next());
	}

	/**
	 * Hidrata un objeto con los resultados de base de datos
	 *
	 * @param $rs = false ResultSet para rellenar
	 *
	 * @return $r Objeto con los datos del ResultSet
	 */
	public function hydrate($rs = false){
		if ($rs instanceof MySQLResultSet){
			$this->hydrate($rs->current()->getValues());
		}elseif( $rs instanceof ResultSetRow){
			$this->hydrate($rs->getValues());
		}elseif(is_array($rs)){
			$this->isNew = false;
			foreach($rs as $field => $value){
				if(in_array($field,array_keys($this->fields)) && isset($this->fields[$field])){
					$varName = $this->fields[$field];
					$this->$varName = $value;
				}
			}
		}else{
			$this->isNew = true;
			foreach($this->fields as $field => $value){
				$this->$field = null;
			}
		}
		return $this;
	}
	
	/**
	 * Guarda el objeto en base de datos
	 *
	 * @todo detectar si tiene que hacer insert o replace mediante parametro
	 */
	public function save(){
		$sentence = (($this->isNew)?"insert":"replace")." into {$this->tableName} ";
		$fields = "";
		$values = "";
		foreach(array_keys($this->fields) as $fieldName){

			$value = $this->get($this->fields[$fieldName]);
			if(!is_null($value)){
				if($fields!='') $fields.=",";
				$fields.=$fieldName;
				if($values!='') $values.=",";
				if(!is_numeric($value)) $addQuotes = true;
				else $addQuotes = false;
				$values.=($addQuotes)?"'".$value."'":$value;
			}
		}

		$sentence.=" ($fields) values ($values)";

		$lastId = $this->db->ExecQuery($sentence, true);
		if (sizeof($this->id) == 1){
			$this->{$this->fields[$this->id[0]]} = $lastId;
		}
	}

	/**
	 * Elimina un registro mediante su id
	 *
	 * @param $id ID del registro
	 */
	public function deleteById(){
		$params = func_get_args();

		if (count($params) != count($this->id)){
			throw new SQLException('deleteById needs same parameters that PK fields');
		}

		if (count($this->id) == 0 ){
			throw new SQLException('deleteById can only be used with PK fields in ORM');
		}

		$whereClause = [];
		for($i = 0; $i < count($this->id); $i++){
			$whereClause[] = " ".$this->id[$i] ." = '".$params[$i]."' ";
		}

		$this->db->ExecQuery("delete from ".$this->tableName." where ".join("and", $whereClause).";");
	}

	/**
	 * Devuelve el siguiente resultado o falso si ya no hay mas
	 *
	 * @return $rs|false Siguiente resultado de busqueda o falso
	 */
	public function next(){
		if($record = $this->db->next()){
			return $record;
		}else{
			return false;
		}
	}

	/**
	 * Realiza una busqueda mediante filtros pasados por parametros
	 *
	 * @param $filters Hashtable con los filtros
	 *
	 * @return $rs ResultSet de la busqueda
	 */
	public function getElementsByFilters(Hashtable $filters){
		$conditions="";
		foreach($filters as $filterName => $filterValue){
			if(!is_numeric($filterValue)) $addQuotes = true;
			else $addQuotes = false;
			if($conditions != "") $conditions.=" and ";
			$conditions.=($addQuotes)?" $filterName = '$filterValue' ":" $filterName = $filterValue "; 
		}
		$query = "select * from ".$this->tableName." where ".$conditions;
		$this->db->query($query);
		return $this->db->getRs();
	}

	/**
	 * Devuelve todos los registros de una tabla
	 *
	 * @return ResultSet 
	 */

	public function getAll(){
		$this->db->ExecQuery("select * from ".$this->tableName);
		return $this->db->getRS();
	}

	/**
	 * Recupera los datos que coincidan (%like%) 
	 *
	 * @param string $field Campo sobre el que buscar
	 * @param string $search Palabra a buscar
	 * @return ResultSet Coincidencias
	 */
	public function searchLikeField($field, $search) {
		$search = "'%$search%'";
		$query = sprintf("select * from %s where %s like %s",$this->tableName,$field,$search);
		$this->db->query($query);
		return $this->db->getRs();
	}

	/**
	 * Método pra no mostrar los datos innecesarios del orm.
	 *
	 * @return array Elementos a mostrar.
	 */
	public function __debugInfo() {
		return $this->getArray();
	}

	/**
	 * Método mágico para devolver en string lo que nos interesa del objeto.
	 *
	 * @return string Lo que nos interesa del objeto (datos).
	 */
	public function __toString() {
		$str = sprintf( "[%s ()] \n", get_called_class());
		$str.= "Values:\n";
		foreach( $this->getArray() as $field => $value ) {
			$str.= sprintf( "  -> %-25s = %s\n", $field, $value );
		}
		return $str."\n";
	}
}

?>
