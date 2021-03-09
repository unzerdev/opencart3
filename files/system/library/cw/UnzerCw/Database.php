<?php 
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

/**
 * 
 * @author Thomas Hunziker
 * @deprecated
 */
final class UnzerCw_Database {
	
	private $db;
	
	private static $instance;

	private function __construct() {
		$this->db = UnzerCw_Util::getDatabaseObject();
	}
	
	/**
	 * @return UnzerCw_Database
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new UnzerCw_Database();
		}
		return self::$instance;
	}
	
	/**
	 * @param string $sql
	 * @return object handler
	 */
	public function query($sql) {
		$result = $this->db->query($sql);
		if ($result === true || count($result->rows) <= 0) {
			return null;
		}
		else {
			reset($result->rows);
			return $result;
		}
	}

	public function fetch($result) {
		if ($result === null) {
			return array();
		}
		
		$row = current($result->rows);
		next($result->rows);
		return $row;
	}
	
	public function fetchAll($result) {
		if ($result === null) {
			return array();
		}
		return $result->rows;
	}

	public function getInsertId() {
		return $this->db->getLastId();
	}

	public function countAffected() {
		return $this->db->countAffected();
	}
	
	public function strip($string) {
		return $this->escape(strip_tags($string));		
	}
	
	public function escape($string) {
		return $this->db->escape($string);
	}
	
	public function prepare($sql, $args = array()) {

		$cleanArgs = array();
		foreach($args as $arg) {
			$cleanArgs[] = $this->escape($arg);
		}

		return self::query(vsprintf($sql, $cleanArgs));
	}

	public function insert($tableName, $data) {
		$sql = 'INSERT INTO ' . $tableName . ' SET ';
		$sql .= implode(',', $this->getDataInsertItems($data));
		
		return $this->query($sql);
	}

	public function update($tableName, $data, $where) {

		if (is_array($where)) {
			$whereSql = '';
			foreach ($where as $key => $value) {
				$whereSql .= '`' . $key . '` = "' . $this->escape($value) . '" AND ';
			}
			
			$whereSql = substr($whereSql, 0, strlen($whereSql) - 4);
			$where = $whereSql;
		}
		$sql = 'UPDATE ' . $tableName . ' SET ';
		$sql .= implode(',', $this->getDataInsertItems($data));
		$sql .= ' WHERE ' . $where;
		
		return $this->query($sql);
	}
	
	private function getDataInsertItems($data) {
		$items = array();
		foreach ($data as $key => $value) {
			$items[] = ' `' . $key . '` = "' . $this->escape($value) . '" ';
		}
		return $items;
	}
	
}