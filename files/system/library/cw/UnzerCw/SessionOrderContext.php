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
require_once 'UnzerCw/AbstractOrderContext.php';


/**
 * This order context is created from the session. This order context should not be persisted.
 *
 * @author Thomas Hunziker
 *
 */
class UnzerCw_SessionOrderContext extends UnzerCw_AbstractOrderContext
{
	private $shippingAddress = array();
	private $paymentAddress = array();
	private $customerData = array();
	private $productData = array();
	private $orderTotals = array();
	private $languageCode = null;
	private $currencyCode = null;
	private $orderTotal = null;


	public function __construct(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod, $registry) {
		parent::__construct($paymentMethod);

		$registry->get('load')->model('account/address');
		$addressModel = $registry->get('model_account_address');
		$session = $registry->get('session');
		$customer = $registry->get('customer');
		$cart = $registry->get('cart');
		$config = $registry->get('config');

		// Shipping Address
		$shipping = array();
		if ($customer->isLogged() && isset($session->data['shipping_address_id'])) {
			$shipping = $addressModel->getAddress($session->data['shipping_address_id']);
		}
		if (isset($session->data['shipping_address']) && is_array($session->data['shipping_address'])) {
			$shipping = UnzerCw_Util::mergeArray($shipping, $session->data['shipping_address']);
		}
		if (isset($session->data['shipping']['shipping_address']) && is_array($session->data['shipping']['shipping_address'])) {
			$shipping = UnzerCw_Util::mergeArray($shipping, $session->data['shipping']['shipping_address']);

		}
		if (isset($session->data['guest']['shipping']) && is_array($session->data['guest']['shipping'])) {
			$shipping = UnzerCw_Util::mergeArray($shipping, $session->data['guest']['shipping']);
		}
		$this->shippingAddress = $shipping;

		// Payment Address
		$payment = array();
		if ($customer->isLogged() && isset($session->data['payment_address_id'])) {
			$payment = $addressModel->getAddress($session->data['payment_address_id']);
		}
		if (isset($session->data['guest']['payment']) && is_array($session->data['guest']['payment'])) {
			$payment = UnzerCw_Util::mergeArray($payment, $session->data['guest']['payment']);

		}
		if (isset($session->data['payment_address']) && is_array($session->data['payment_address'])) {
			$payment = UnzerCw_Util::mergeArray($payment, $session->data['payment_address']);
		}
		$this->paymentAddress = $payment;


		// Load customer data
		$customer_query = $registry->get('db')->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer->getId() . "'");
		if ($customer_query->num_rows) {
			$this->customerData = $customer_query->row;
		}
		else if(isset($session->data['guest'])) {
			$this->customerData =  $session->data['guest'];
		}

		// Product data
		$products = $cart->getProducts();
		foreach ($products as $key => $product) {
			$products[$key]['total_with_tax'] = $cart->tax->calculate($product['price'], $product['tax_class_id'], $config->get('config_tax')) * $product['quantity'];
		}
		$this->productData = $products;

		// Order Totals
		$this->orderTotals = UnzerCw_Util::getOrderTotals($registry);

		// Langauge
		$this->languageCode = UnzerCw_Language::getCurrentLanguageCode();

		// Currency
		$this->currencyCode = $session->data['currency'];

		// Order total
		$this->orderTotal = $cart->getTotal();

	}

	protected function getInternalShippingAddress() {
		return $this->shippingAddress;
	}

	protected function getInternalPaymentAddress() {
		return $this->paymentAddress;
	}

	protected function getCustomerData() {
		return $this->customerData;
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