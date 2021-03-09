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

require_once 'Customweb/Core/Language.php';

require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/AbstractOrderContext.php';


class UnzerCw_OrderContext extends UnzerCw_AbstractOrderContext
{
	private $shippingAddress = null;
	private $paymentAddress = null;
	private $customerData = array();
	private $productData = array();
	private $orderTotals = array();
	private $languageCode = null;
	private $currencyCode = null;
	private $orderTotal = null;
	private $orderInfo = array();
	
	public function __construct(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod, array $order_info, array $order_totals) {
		parent::__construct($paymentMethod);
	
		$this->orderInfo = $order_info;
		
		// Shippping data
		foreach ($this->orderInfo as $key => $value) {
			if (substr($key, 0, strlen('shipping_')) == 'shipping_') {
				if (!empty($value)) {
					$this->shippingAddress[substr($key, strlen('shipping_'))] = $value;
				}
			}
		}
		
		// Currency
		$this->currencyCode = $this->orderInfo['currency_code'];
		
		// Load customer data
		$db = UnzerCw_Database::getInstance();
		$customer_query = $db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$this->orderInfo['customer_id'] . "'");
		if ($customer_query !== null && count($customer_query->row) > 0) {
			$this->customerData = $customer_query->row;
		}
		
		// Product data
		$db = UnzerCw_Database::getInstance();
		$productResult = $db->query("SELECT `name`, `model`, `price`, `quantity`, `tax` + `price` AS 'total_with_tax', `price` as `total` FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = " . (int) $this->orderInfo['order_id']);
		if ($productResult !== null) {
			$this->productData = $productResult->rows;
		}
		
		// Update prices according to the currency exchange rate
		foreach ($this->productData as $key => $data) {
			$this->productData[$key]['total'] = UnzerCw_Util::convertTo($data['quantity'] * $data['total'], $this->getCurrencyCode());
			$this->productData[$key]['total_with_tax'] = UnzerCw_Util::convertTo($data['quantity'] * $data['total_with_tax'], $this->getCurrencyCode());
		}
		
	
		// Order Totals
		$this->orderTotals = $order_totals;
	
		// Langauge
		$this->languageCode = UnzerCw_Language::getLanguageCodeByLanguageId($this->orderInfo['language_id']);

		// Order total
		$this->orderTotal = UnzerCw_Util::convertTo($this->orderInfo['total'], $this->getCurrencyCode());
	
	}
	
	public function getOrderInfo() {
		return $this->orderInfo;
	}
	
	public function __sleep() {
		return array('customerData', 'productData', 'orderTotals', 'languageCode', 'currencyCode', 'orderTotal', 'orderInfo', 'paymentMethod', 'checkoutId');
	}
	
	protected function getInternalShippingAddress() {
		if ($this->shippingAddress == null) {
			$this->shippingAddress = array();
			foreach ($this->orderInfo as $key => $value) {
				if (substr($key, 0, strlen('shipping_')) == 'shipping_') {
					if (!empty($value)) {
						$this->shippingAddress[substr($key, strlen('shipping_'))] = $value;
					}
				}
			}
		}
		
		if (count($this->shippingAddress) <= 0) {
			return $this->getInternalPaymentAddress();
		}
		else {
			return $this->shippingAddress;
		}
	}
	
	protected function getInternalPaymentAddress() {
		if ($this->paymentAddress == null) {
			$this->paymentAddress = array();
			
			foreach ($this->orderInfo as $key => $value) {
				if (substr($key, 0, strlen('payment_')) == 'payment_') {
					$this->paymentAddress[substr($key, strlen('payment_'))] = $value;
				}
			}
		}
		return $this->paymentAddress;
	}
	
	protected function getCustomerData() {
		if (count($this->customerData) > 0) {
			return $this->customerData;
		}
		else {
			return $this->orderInfo;
		}
	}
	
	protected function getProductData() {
		return $this->productData;
	}
	
	protected function getTotalsData() {
		return $this->orderTotals;
	}
	
	public function getOrderAmountInDecimals() {
		return $this->orderTotal;
	}
	
	public function getCurrencyCode() {
		return $this->currencyCode;
	}
	
	public function getLanguage() {
		return new Customweb_Core_Language($this->languageCode);
	}
		
}