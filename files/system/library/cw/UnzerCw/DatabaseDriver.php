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

require_once 'Customweb/Database/Driver/AbstractDriver.php';
require_once 'Customweb/Database/IDriver.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/DatabaseStatement.php';


/**
 * 
 * @author Thomas Hunziker
 *
 */
class UnzerCw_DatabaseDriver extends Customweb_Database_Driver_AbstractDriver implements Customweb_Database_IDriver {
	
	/**
	 * @var DB
	 */
	private $db;
	private $autoCommitActive = 0;

	/**
	 * The resource link is the connection link to the database.
	 *
	 * @param resource $resourceLink        	
	 */
	public function __construct(){
		$this->db = UnzerCw_Util::getDatabaseObject();
	}

	public function beginTransaction(){
		$result = $this->query("SELECT @@autocommit as auto")->fetch();
		if (!empty($result)) {
			$this->autoCommitActive = $result["auto"];
		}
		else{
			throw new Exception('Could not start DB transaction, as the autocommit state can not be read.');
		}
		$this->query("SET autocommit = 0;")->execute();
		$this->query("START TRANSACTION;")->execute();
		$this->setTransactionRunning(true);
		
	}

	public function commit(){
		$this->query("COMMIT;")->execute();
		$this->query(sprintf("SET autocommit = %d;", $this->autoCommitActive))->execute();
		$this->setTransactionRunning(false);
		
	}

	public function rollBack(){
		$this->query("ROLLBACK;")->execute();
		$this->query(sprintf("SET autocommit = %d;", $this->autoCommitActive))->execute();
		$this->setTransactionRunning(false);
	}

	public function query($query){
		$statement = new UnzerCw_DatabaseStatement($query, $this);
		return $statement;
	}

	public function quote($string){
		return '"' . $this->db->escape($string) . '"';
	}

	/**
	 * @return DB
	 */
	public function getDb(){
		return $this->db;
	}
		
}
