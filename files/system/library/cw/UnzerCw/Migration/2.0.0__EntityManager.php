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

require_once 'Customweb/Database/Migration/IScript.php';

class UnzerCw_Migration_2_0_0 implements Customweb_Database_Migration_IScript{

	public function execute(Customweb_Database_IDriver $driver) {
		
		try {
			$driver->query("RENAME TABLE unzercw_storage TO " . DB_PREFIX . "unzercw_storage")->execute();
		}
		catch(Exception $e) {
			// Ignore, it may be cause some error, when no db prefix is defined.
		}
		
		$driver->query("ALTER TABLE " . DB_PREFIX . "unzercw_transactions ENGINE = INNODB;")->execute();
		$driver->query("ALTER TABLE " . DB_PREFIX . "unzercw_customer_contexts ENGINE = INNODB;")->execute();
		
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `transaction_id`  `transactionId` BIGINT( 20 ) NOT NULL AUTO_INCREMENT;")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  transaction_number `transactionExternalId` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `order_id`  `orderId` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `alias_for_display`  `aliasForDisplay` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `alias_active`  `aliasActive` CHAR( 1 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `payment_method`  `paymentMachineName` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `transaction_object`  `transactionObject` LONGTEXT")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `authorization_type`  `authorizationType` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `customer_id`  `customerId` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `updated_on`  `updatedOn` DATETIME")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `created_on`  `createdOn` DATETIME")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `payment_id`  `paymentId` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` CHANGE  `updatable`  `updatable` CHAR( 1 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `executeUpdateOn`  DATETIME NULL DEFAULT NULL")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `authorizationAmount`  DECIMAL( 20, 5 ) NULL DEFAULT NULL")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `authorizationStatus` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `paid` CHAR( 1 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `currency` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `securityToken` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_transactions` ADD  `lastSetOrderStatusSettingKey` VARCHAR( 255 )")->execute();
		
		
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_customer_contexts` CHANGE  `customer_id`  `customerId` VARCHAR( 255 )")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_customer_contexts` CHANGE  `context_id` `contextId` BIGINT( 20 ) NOT NULL AUTO_INCREMENT")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_customer_contexts` CHANGE  `context_object`  `context_values` LONGTEXT")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_customer_contexts` DROP  `created_on`")->execute();
		$driver->query("ALTER TABLE  `" . DB_PREFIX . "unzercw_customer_contexts` DROP  `updated_on`")->execute();

	}

}