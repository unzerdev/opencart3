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


require_once 'UnzerCw/SessionOrderContext.php';
require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/IPaymentMethodDefinition.php';
require_once 'UnzerCw/PaymentMethod.php';
require_once 'UnzerCw/PaymentMethodWrapper.php';


abstract class ModelPaymentUnzerCwAbstract extends Model implements UnzerCw_IPaymentMethodDefinition
{
	/**
	 * @var UnzerCw_PaymentMethod
	 */
	private $paymentMethod;
	
	/**
	 * @var UnzerCw_SettingApi
	 */
	private $settingsApi;
	
	public function __construct($registry) {
		parent::__construct($registry);
		UnzerCw_Util::setRegistry($registry);
	}
	
	public function getMethod($address, $total)
	{
		
		$this->paymentMethod = new UnzerCw_PaymentMethod($this);
		$this->settingsApi = $this->paymentMethod->getSettingsApi();
		
		$method_data = array();
		if ($this->isEnabled($address, $total)) {
			
			$description = null;
			$descriptionData = $this->settingsApi->getValue('description');
			$langId = UnzerCw_Language::getCurrentLanguageId();
			if (!empty($descriptionData) && isset($descriptionData[$langId]) && !empty($descriptionData[$langId])) {
				$description = $descriptionData[$langId];
			}
			
			$method_data = array(
				'code'       => 'unzercw_' . strtolower($this->paymentMethod->getPaymentMethodName()),
				'title'      => $this->paymentMethod->getPaymentMethodDisplayName(),
				'sort_order' => $this->paymentMethod->getPaymentMethodConfigurationValue('sort_order'),
				'terms'      => '',
				'description' => $description,
			);
		}
		
		return $method_data;
	}
	
	protected function isEnabled($address, $total) {
		return $this->isActive() && $this->checkTotal($total) && $this->checkZones($address) && $this->checkCurrency() && $this->validate();
	}
	
	protected function validate() {
		$orderContext = new UnzerCw_SessionOrderContext(new UnzerCw_PaymentMethodWrapper($this->paymentMethod), $this->registry);
		$adapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByContext($orderContext);
		$customerContext =  UnzerCw_Util::getPaymentCustomerContext($orderContext->getCustomerId());
		try {
			$adapter->preValidate($orderContext, $customerContext);
			UnzerCw_Util::persistPaymentCustomerContext($customerContext);
			return true;
		}
		catch(UnzerCw_IncompleteDataException $e) {
			// We let the customer continue, when we get a exception because some data is missing. This is indicator that the checkout is somehow changed and hence
			// the validation was never really executed.
			UnzerCw_Util::persistPaymentCustomerContext($customerContext);
			UnzerCw_Util::log("Validation failed with: " . $e->getMessage(), 'error');
			return true;
		}
		catch(Exception $e) {
			UnzerCw_Util::persistPaymentCustomerContext($customerContext);
			UnzerCw_Util::log("Validation failed with: " . $e->getMessage(), 'error');
			return false;
		}
	}
	
	protected function checkTotal($total) {
		$minTotal = (float)$this->settingsApi->getValue('min_total');
		if ($minTotal > 0 && $minTotal > $total) {
			return false;
		}
		
		$maxTotal = (float)$this->settingsApi->getValue('max_total');
		if ($maxTotal > 0 && $maxTotal < $total) {
			return false;
		}
		
		return true;
	}
	
	protected function isActive() {
		if ($this->settingsApi->getValue('status') == 'enabled' || $this->settingsApi->getValue('status') === '1') {
			return true;
		}
		else {
			return false;
		}
	}
		
	protected function checkZones($address) {
		
		$allowedZones = $this->settingsApi->getValue('allowed_zones');
		if (count($allowedZones) <= 0) {
			return true;
		}
		
		foreach ($allowedZones as $zoneId) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone 
				WHERE 
					geo_zone_id = '" . (int)$zoneId . "' AND 
					country_id = '" . (int)$address['country_id'] . "' AND 
					(zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
			if ($query->num_rows) {
				return true;
			}
		}

		return false;		
	}
	
	protected function checkCurrency() {
		$allowedCurrencies = $this->settingsApi->getValue('active_currencies');
		$session = $this->registry->get('session');
		$currencyCode = $session->data['currency'];
		
		if (!is_array($allowedCurrencies) || count($allowedCurrencies) <= 0) {
			return true;
		}
		else if (in_array($currencyCode, $allowedCurrencies)) {
			return true;
		}
		else {
			return false;
		}
	}

}