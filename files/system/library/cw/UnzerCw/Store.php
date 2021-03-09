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

require_once 'Customweb/Core/String.php';
require_once 'Customweb/Core/Util/Error.php';

require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Store.php';


final class UnzerCw_Store {
	
	const DEFAULT_STORE_ID = '0';
	
	private static $contextStoreId = null;
	private static $forcedStoreId = null;
	private static $storeNames = null;
	private static $storeConfigs = array();
	private static $baseUrls = array();
	
	private function __construct() {
		
	}
		
	public static function getStoreId() {
		if (self::$forcedStoreId !== null) {
			return self::$forcedStoreId;
		}
		else if (self::$contextStoreId === null) {
			if (isset($GLOBALS['config']) && $GLOBALS['config']->has('config_store_id')) {
				self::$contextStoreId = (int)$GLOBALS['config']->get('config_store_id');
			}
			else{
				self::$contextStoreId = (int)self::DEFAULT_STORE_ID;
				try{
					$registry = UnzerCw_Util::getRegistry();
					$config = $registry->get('config');
					if($config->has('config_store_id')){
						self::$contextStoreId = (int)$config->get('config_store_id');
					}
				}
				catch(Exception $e){}
			}
			
		}
		
		return self::$contextStoreId;
	}
	
	public static function forceStoreId($storeId) {
		self::$forcedStoreId = (int)$storeId;
	}
	
	public static function resetStoreId() {
		self::$forcedStoreId = null;
	}
	
	public static function getStoreName($storeId = null) {
		if ($storeId === null) {
			$storeId = self::getStoreId();
		}
		
		$stores = self::getStores();
		if (isset($stores[$storeId])) {
			return $stores[$storeId];
		}
		else {
			throw new Exception(Customweb_Core_String::_("No store name found for store id '@storeId'.")->format(array('@storeId' => $storeId)));
		}
	}
	
	/**
	 * Returns a list of stores active
	 * 
	 * @return object
	 */
	public static function getStores() {
		if (self::$storeNames === null) {
			self::$storeNames[self::DEFAULT_STORE_ID] = UnzerCw_Language::_("Default Store");
			$rs = UnzerCw_Util::getDriver()->query("SELECT * FROM " . DB_PREFIX . "store ORDER BY name");
			while (($row = $rs->fetch()) !== false) {
				self::$storeNames[$row['store_id']] = $row['name'];
			}
		}
		
		return self::$storeNames;
	}

	/**
	 * Returns the base store URL. 
	 * 
	 * @param string $storeId
	 * @return string
	 */
	public static function getStoreBaseUrl($storeId = null) {
		if ($storeId === null) {
			$storeId = UnzerCw_Store::getStoreId();
		}
	
		$configs = self::getStoreConfigs($storeId);
		
		if (isset($configs['config_use_ssl']) || (isset($configs['config_secure']) && $configs['config_secure'])) {
			self::$baseUrls[$storeId] = $configs['config_ssl'];
		}	
		else {
			self::$baseUrls[$storeId] = $configs['config_url'];
		}
		return rtrim(self::$baseUrls[$storeId], '/');
	}
	
	/**
	 * Returns a map of configurations for given store. If no store is given the 
	 * current store is used.
	 * 
	 * @param string $storeId
	 * @return multitype:
	 */
	public static function getStoreConfigs($storeId = null) {
		if ($storeId === null) {
			$storeId = self::getStoreId();
		}
		
		if (!isset(self::$storeConfigs[$storeId])) {
			self::$storeConfigs[$storeId] = array(
				'config_url' => defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER,
				'config_ssl' => defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTPS_SERVER,
			);
			
			$query = UnzerCw_Util::getDriver()->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' OR store_id = '" . (int)$storeId . "' ORDER BY store_id ASC");
			while (($setting = $query->fetch()) !== false) {
				if (!isset($setting['serialized']) || $setting['serialized'] != 1) {
					self::$storeConfigs[$storeId][$setting['key']] = $setting['value'];
				}
				else {
					Customweb_Core_Util_Error::deactivateErrorMessages();
					self::$storeConfigs[$storeId][$setting['key']] = unserialize($setting['value']);
					Customweb_Core_Util_Error::activateErrorMessages();
				}
			}
		}
	
		return self::$storeConfigs[$storeId];
	}
	
	
	
}