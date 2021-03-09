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
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/OrderStatus.php';

class UnzerCw_Migration_1_0_0 implements Customweb_Database_Migration_IScript{

	public function execute(Customweb_Database_IDriver $driver) {

		try {
			$driver->query("ALTER TABLE  `" . DB_PREFIX . "setting` ADD  `group` VARCHAR( 32 )")->execute();
		}
		catch(Exception $e) {
			// Ignore, it may be cause some error, when group already exists.
		}
		
		$driver->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "unzercw_customer_contexts (
			`context_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`customer_id` bigint(20) NOT NULL,
			`context_object` text default '',
			`updated_on` datetime NOT NULL,
			`created_on` datetime NOT NULL,
			PRIMARY KEY  (`context_id`),
			UNIQUE (`customer_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;")->execute();
		
		$driver->query("CREATE TABLE IF NOT EXISTS `unzercw_storage` (
			`keyId` bigint(20) NOT NULL AUTO_INCREMENT,
			`keyName` varchar(165) DEFAULT NULL,
			`keySpace` varchar(165) DEFAULT NULL,
			`keyValue` longtext,
			PRIMARY KEY (`keyId`),
			UNIQUE KEY `keyName_keySpace` (`keyName`,`keySpace`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1")->execute();
		
		$driver->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "unzercw_transactions (
			`transaction_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`transaction_number` varchar(255) default NULL,
			`order_id` bigint(20) default NULL,
			`alias_for_display` varchar(255) default NULL,
			`alias_active` char(1) default 'y',
			`payment_method` varchar(255) NOT NULL,
			`payment_method_definitions` text default '',
			`transaction_object` mediumtext default '',
			`authorization_type` varchar(255) NOT NULL,
			`customer_id` varchar(255) default NULL,
			`updated_on` datetime NOT NULL,
			`created_on` datetime NOT NULL,
			`payment_id` varchar(255) NOT NULL,
			`updatable` char(1) default 'n',
			PRIMARY KEY  (`transaction_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;")->execute();

		// Increase extension code size
		UnzerCw_Database::getInstance()->query("ALTER TABLE " . DB_PREFIX . "extension CHANGE `code` `code` VARCHAR( 64 )");
		
		UnzerCw_OrderStatus::installOrderStatuses();
		
	}

}