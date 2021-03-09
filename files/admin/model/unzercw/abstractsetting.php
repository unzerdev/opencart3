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

require_once DIR_SYSTEM . '/library/cw/UnzerCw/init.php';

require_once 'Customweb/I18n/Translation.php';

require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Store.php';


abstract class ModelUnzerCwAbstractSetting extends Model
{
	protected $settingsDefinitions = array();
	
	/**
	 * @var UnzerCw_SettingApi
	 */
	protected $api;
	
	public function saveSettings(UnzerCw_SettingApi $api, $data) {
		
		$this->api = $api;
		
		// Store default values:
		$storeIds = array(UnzerCw_Store::DEFAULT_STORE_ID);
		foreach ($this->getStoresIncludingDefaultStore() as $store) { 
			$storeIds[] = $store['store_id'];
		}
		
		foreach ($storeIds as $storeId) {
			foreach ($this->api->getSpaceSettingDefintion() as $key => $item) {
				$useDefault = isset($data['use_default'][$key][$storeId]) && $data['use_default'][$key][$storeId] == 'true';
				if ($useDefault == false) {
					$type = strtolower($item['type']);
					if ($type == 'file') {
						$fileKey = $key . '___' . $storeId;
						if (isset($data['reset'][$key][$storeId]) && $data['reset'][$key][$storeId] == 'reset') {
							$this->api->removeValue($key, $storeId);
						}
						else if (isset($_FILES[$fileKey]) && !empty($_FILES[$fileKey]['name'])) {
							$fileExtension = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
							$fileName = self::getUploadFileName($this->api->getSpace(), $key, $storeId, $fileExtension);
							if (!is_writable(UnzerCw_Util::getFileUploadDir())) {
								die(Customweb_I18n_Translation::__("For uploading files the path '@path' must be writable.", array('@path' => UnzerCw_Util::getFileUploadDir())));
							}
							move_uploaded_file($_FILES[$fileKey]['tmp_name'], UnzerCw_Util::getFileUploadDir() . '/' . $fileName);
							$this->api->setValue($key, $storeId, $fileName);
						}
					}
					else if (isset($data[$key][$storeId])) {
						$this->api->setValue($key, $storeId, $data[$key][$storeId]);
					}
					else if ($type == 'multiselect' || $type == 'currencyselect' || $type == 'orderstatusmultiselect'){
						$this->api->setValue($key, $storeId, array());
					}
					
				}
				else if ($useDefault) {
					$this->api->removeValue($key, $storeId);
				}
			}
		}
	}
	
	public function render(UnzerCw_SettingApi $api) {
		$this->api = $api;
		$output = '';
		foreach ($this->getStoresIncludingDefaultStore() as $store) {
			$output .= $this->renderStoreSettings($store['store_id']);
		}
	
		return $output;
	}

	public function getStoresIncludingDefaultStore() {
		$url = HTTP_CATALOG;
		$ssl = null;
		if (defined('HTTPS_CATALOG')) {
			$ssl = HTTPS_CATALOG;
		}
	
		$stores = array(array(
			'store_id' => '0',
			'name' => UnzerCw_Language::_('Default Store'),
			'url' => $url,
			'ssl' => $ssl,
		));
		return array_merge($stores, $this->getStores());
	}
	
	protected function getStores() {
		$store_data = $this->cache->get('store');
	
		if (!$store_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store ORDER BY name");
	
			$store_data = $query->rows;
	
			$this->cache->set('store', $store_data);
		}
	
		return $store_data;
	}

	protected static function getUploadFileName($space, $settingKey, $storeId, $fileExtension) {
		return 's_' . $storeId . '__' . strtolower($space) . '__' . strtolower($settingKey) . '.' . $fileExtension;
	}
	
	protected function renderCurrencySelect(array $item, $storeId) {
		$currencies = array();
	
		$currencyActive = UnzerCw_Util::getCurrencies();
		foreach ($currencyActive as $currency) {
			if (in_array($currency['code'], $item['allowedCurrencies'])) {
				$currencies[$currency['code']] = $currency['code'];
			}
		}
	
		$item['options'] = $currencies;
		return $this->renderMultiselect($item, $storeId);
	}
	
	protected function renderStoreSettings($storeId) {
		
		$output = $this->renderPreSettingForm($storeId);
		
		foreach ($this->api->getSpaceSettingDefintion() as $key => $item) {
			$item['key'] = $key;
			$value = $this->api->getValue($key, $storeId);
			$item['value'] = $value;
			switch(strtolower($item['type'])) {
				case 'select':
					$controlOutput = $this->renderSelect($item, $storeId);
					break;
				case 'textfield':
					$controlOutput = $this->renderTextfield($item, $storeId);
					break;
				case 'multiselect':
					$controlOutput = $this->renderMultiselect($item, $storeId);
					break;
				case 'password':
					$controlOutput = $this->renderPassword($item, $storeId);
					break;
				case 'multilangfield':
					$controlOutput = $this->renderMultiLangfield($item, $storeId);
					break;
				case 'textarea':
					$controlOutput = $this->renderTextarea($item, $storeId);
					break;
				case 'orderstatusselect':
					$controlOutput = $this->renderOrderStatusSelect($item, $storeId);
					break;
				case 'orderstatusmultiselect':
					$controlOutput = $this->renderOrderStatusMultiselect($item, $storeId);
					break;
				case 'file':
					$controlOutput = $this->renderFile($item, $storeId);
					break;
				case 'currencyselect':
					$controlOutput = $this->renderCurrencySelect($item, $storeId);
					break;
			}
			$output .= $this->renderElement($item, $controlOutput, $storeId);
		}
		
		$output .= $this->renderPostSettingForm($storeId);
		return $output;
	}
	
	abstract protected function renderMultiselect(array $item, $storeId);
	
	abstract protected function renderPreSettingForm($storeId);
	
	abstract protected function renderPostSettingForm($storeId);
	
	abstract protected function renderSelect(array $item, $storeId);
	
	abstract protected function renderTextfield(array $item, $storeId);
	
	abstract protected function renderPassword(array $item, $storeId);
	
	abstract protected function renderMultiLangfield(array $item, $storeId);
	
	abstract protected function renderTextarea(array $item, $storeId);
	
	abstract protected function renderOrderStatusSelect(array $item, $storeId);
	
	abstract protected function renderOrderStatusMultiselect(array $item, $storeId);
	
	abstract protected function renderFile(array $item, $storeId);
	
	abstract protected function renderElement($item, $controlHtml, $storeId);
	
	abstract public function renderStoreTabs($baseurl);
	
	protected function getControlId(array $item, $storeId) {
		return 'setting-' . $item['key'] . '-' . $storeId;
	}
}