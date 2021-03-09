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

require_once 'Customweb/Payment/IConfigurationAdapter.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/SettingApi.php';
require_once 'UnzerCw/OrderStatus.php';
require_once 'UnzerCw/Store.php';


/**
 * 
 * @author Thomas Hunziker
 * @Bean
 */
class UnzerCw_Configuration implements Customweb_Payment_IConfigurationAdapter{
	
	/**
	 * @var UnzerCw_SettingApi
	 */
	private static $config = null;
	
	/**
	 * @return UnzerCw_SettingApi
	 */
	private static function getSetting() {
		if (self::$config === null) {
			self::$config = new UnzerCw_SettingApi('module_unzercw');
		}
		return self::$config;
	}
	
	public function getConfigurationValue($key, $languageCode = null) {
		return self::getSetting()->getValue($key, null, $languageCode);
	}
	
	public function existsConfiguration($key, $languageCode = null) {
		return self::getSetting()->isSettingPresent($key, null, $languageCode);
	}
	
	public function getLanguages($currentStore = false) {
		$langs = array();
		foreach (UnzerCw_Util::getLanguages() as $language) {
			$langs[$language['code']] = $language['name'];
		}
		return $langs;
	}

	public function getStoreHierarchy() {
		$storeId = UnzerCw_Store::getStoreId();
		$hierarchy = array(
			UnzerCw_Store::DEFAULT_STORE_ID => UnzerCw_Store::getStoreName(UnzerCw_Store::DEFAULT_STORE_ID),
		);
		if ($storeId === UnzerCw_Store::DEFAULT_STORE_ID) {
			return $hierarchy;
		}
		$hierarchy[$storeId] = UnzerCw_Store::getStoreName($storeId);
		return $hierarchy;
	}

	public function useDefaultValue(Customweb_Form_IElement $element, array $formData) {
		$controlName = implode('_', $element->getControl()->getControlNameAsArray());
		return (isset($formData['default'][$controlName]) && $formData['default'][$controlName] == 'default');
	}

	public function getOrderStatus() {
		return UnzerCw_OrderStatus::getOrderStatuses();
	}
	
	public static function getLoggingLevel(){
		return self::getSetting()->getValue('log_level');
		
	}

}