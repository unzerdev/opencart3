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


require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/SettingApi.php';


final class UnzerCw_OrderStatus {
	
	private static $orderStatuses = null;
	
	private function __construct() {
		
	}
	
	public static function installOrderStatuses() {
		$statuses = array();
		$statuses['uncertain'] = array(
			'title' => array(
				'de' => 'Zahlung unsicher (Unzer)',
				'en' => 'Payment uncertain (Unzer)',
			),
		);
		$statuses['cancelled'] = array(
			'title' => array(
				'de' => 'Zahlung abgebrochen (Unzer)',
				'en' => 'Payment cancelled (Unzer)',
			),
		);
		$statuses['pending'] = array(
			'title' => array(
				'de' => 'Bevorstehende Zahlung (Unzer)',
				'en' => 'Pending Payment (Unzer)',
			),
		);
		
		foreach ($statuses as $statusKey => $status) {
			
			$db = UnzerCw_Database::getInstance();
			
			$configKey = self::getStatusConfigKey($statusKey);
			$id = self::getStatusIdByKey($statusKey);
			if ($id === null) {
				$row = $db->fetch($db->query("SELECT max(order_status_id) as order_status_id FROM " . DB_PREFIX . "order_status"));
				$statusId = $row['order_status_id'] + 1;
				foreach (UnzerCw_Util::getLanguages() as $lang) {
				
					if (isset($status['title'][$lang['code']])) {
						$title = $status['title'][$lang['code']];
					}
					else {
						$title = $status['title']['en'];
					}
					$db->insert(DB_PREFIX . 'order_status', array(
						'order_status_id' => $statusId, 
						'language_id' => $lang['language_id'], 
						'name' => $title
					));
				}
				UnzerCw_SettingApi::writeSetting('0', 'unzercw_order_status', $configKey, $statusId);
			}
		}

		
	}
	
	private static function getStatusConfigKey($statusKey) {
		return 'status_id_' . strtolower($statusKey);
	}
	
	public static function getStatusIdByKey($key) {
		if ($key == 'authorized') {
			return 1;
		}
		$configKey = self::getStatusConfigKey($key);
		
		try {
			return UnzerCw_SettingApi::readSetting('0', 'unzercw_order_status', $configKey);
		}
		catch(UnzerCw_Exception_SettingNotFoundException $e) {
			return null;
		}
	}
	
	public static function getOrderStatuses() {
		
		if (self::$orderStatuses === null) {
			self::$orderStatuses = array ();
			
			$result = UnzerCw_Database::getInstance()->prepare(
				"SELECT order_status_id, name FROM " . DB_PREFIX . "order_status WHERE language_id = '%s' order by name",
				array( UnzerCw_Language::getCurrentLanguageId())
			);
			
			foreach (UnzerCw_Database::getInstance()->fetchAll($result) as $row) {
				self::$orderStatuses[$row['order_status_id']] = $row['name'];
			}
		}
		
		return self::$orderStatuses;
	}
	
	
}