<?php

namespace php\sql;

use php\exceptions\SQLException;

/**
 * Class ResultSetRow
 * 
 *
 * @package php
 * @subpackage sql
 */
class ResultSetRow {

	/**
	 * The owner ResultSet of this ResultSetRow (the one that produced it)
	 */
	private $rs = null;

	/**
	 * The underlying array holding the values for this row.
	 */
	private $values = null;

	/**
	 * The current table to retrieve fields from, using the overloaded __get() method.
	 */
	private $table = null;

	/**
	 * Instantiates a new ResultSetRow with the owner ResultSet
	 */
	public function __construct($conn, $rs = null) {
		$this->connection = $conn;
		//$this->rs = $rs;
	}

	/**
	 * Returns the connection originating this ResultSetRow
	 *
	 * @return Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Returns the ResultSet this Row belongs to
	 *
	 * @return ResultSet;
	 */
	public function getResultSet() {
		return $this->rs;
	}

	/**
	 * Sets the values for the current row.
	 */
	public function setValues($values) {
		$this->values = $values;
	}

	/**
	 * Returns the underlying values array.
	 *
	 * @returns array
	 */
	public function getValues() {
		return $this->values;
	}

	/**
	 * Returns wether this ResultSetRow has the given table.field
	 *
	 * @param string $table
	 * @param string $field
	 *
	 * @returns boolean
	 */
	public function has($table, $field = null) {
		if ($field != null) {
			return (array_key_exists($table, $this->values) && array_key_exists($field, $this->values[$table]));
		} else {
			return (array_key_exists($table, $this->values));
		}
	}

	/**
	 * Gets a field value by its table and field name.
	 *
	 * @param string $table The table name
	 * @param string $field The field name
	 *
	 * @returns mixed The field value.
	 */
	public function get($table, $field = null) {
		if ($field != null && !$this->has($table, $field)) {
			throw new SQLException(sprintf('Invalid field "%s.%s"', $table, $field));
		} elseif ($field == null) {
			return $this->values[$table];
		} else {
			return $this->values[$table][$field];
		}
	}

	/**
	 * Sets up the current table for getting field values through the overloaded __get() method.
	 *
	 * @param string $table
	 */
	public function fromTable($table) {
		$this->table = $table;

		return $this;
	}

	/**
	 * Returns wether this ResultSetRow is empty
	 *
	 * @returns boolean
	 */
	public function isEmpty() {
		return $this->values == null;
	}

	/**
	 * Overloaded Field Getter. For this getter to work, the table has to be set previously.
	 * This method is intended to be used proxied by the overloaded getter ResultSet::__get
	 *
	 * @param string $field The field name
	 *
	 * @returns mixed The value of the field for this row.
	 */
	public function __get($field) {
		if ($this->values == null) {
			throw new SQLException(sprintf('Can\'t get field "%s.%s" - ResultSetRow is empty', $this->table, $field));
		} else {
			if ($this->table) {
				return $this->get($this->table, $field);
			} else {
				return $this->get($field);
			}
		}
	}

	/**
	 * Returns a string representation of this ResultSetRow
	 *
	 * @return string
	 */
	public function __toString() {
		$str = sprintf("[%s (ID #%s)]\n", get_class($this), 'ID');
		$str .= " * Fields:\n";
		foreach ($this->values as $field => $value) {
			$str .= sprintf("  -> %-20s = %s\n", $field, $value);
			if (is_array($value)) {
				foreach ($value as $col => $val) {
					$str .= sprintf("    | => %-25s = %s\n", $col, $val);
				}
			}
		}

		return $str;
	}

}
