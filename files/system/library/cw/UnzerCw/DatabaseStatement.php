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

require_once 'Customweb/Database/Driver/AbstractStatement.php';

require_once 'UnzerCw/Error.php';


class UnzerCw_DatabaseStatement extends Customweb_Database_Driver_AbstractStatement {
	
	
	private $result;
	
	public function getInsertId() {
		$this->executeQuery();
		return $this->getDb()->getLastId();
	}
	
	public function getRowCount() {
		$this->executeQuery();
		if ($this->result === null) {
			return 0;
		}
		else if(isset($this->result->num_rows)) {
			return $this->result->num_rows;
		}
		else {
			return $this->getDb()->countAffected();
		}
	}
	
	public function fetch() {
		$this->executeQuery();
		if ($this->result === null) {
			return array();
		}
		
		$row = current($this->result->rows);
		next($this->result->rows);
		return $row;
	}
	
	final protected function executeQuery() {
		if (!$this->isQueryExecuted()) {
			UnzerCw_Error::startErrorHandling();
			$this->result = $this->getDb()->query($this->prepareQuery());
			UnzerCw_Error::endErrorHandling();
			$this->setQueryExecuted();
		}
	}

	protected function getResult(){
		return $this->result;
	}
	
	/**
	 * @return DB
	 */
	protected function getDb() {
		return $this->getDriver()->getDb();
	}
	
}