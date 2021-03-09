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

require_once 'Customweb/Payment/Authorization/OrderContext/AbstractDeprecated.php';
require_once 'Customweb/Payment/Authorization/DefaultInvoiceItem.php';
require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
require_once 'Customweb/Payment/Authorization/IOrderContext.php';
require_once 'Customweb/Core/Util/Rand.php';
require_once 'Customweb/Util/Invoice.php';

require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/IncompleteDataException.php';


/**
 * This order context is created from the session. This order context should not be persisted.
 * 
 * @author Thomas Hunziker
 *
 */
abstract class UnzerCw_AbstractOrderContext extends Customweb_Payment_Authorization_OrderContext_AbstractDeprecated implements Customweb_Payment_Authorization_IOrderContext
{
	/**
	 * @var Customweb_Payment_Authorization_IPaymentMethod
	 */
	protected $paymentMethod = null;
	
	protected $checkoutId = null;
	
	public function __construct(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
		$this->paymentMethod = $paymentMethod;
		
		if (!isset($_SESSION['unzercw_checkout_id'])) {
			$_SESSION['unzercw_checkout_id'] = array();
		}
		if (!isset($_SESSION['unzercw_checkout_id'][$paymentMethod->getPaymentMethodName()])) {
			$_SESSION['unzercw_checkout_id'][$paymentMethod->getPaymentMethodName()] = Customweb_Core_Util_Rand::getUuid();
		}
		$this->checkoutId = $_SESSION['unzercw_checkout_id'][$paymentMethod->getPaymentMethodName()];
	}
	
	abstract protected function getInternalShippingAddress();
	
	abstract protected function getInternalPaymentAddress();
	
	abstract protected function getCustomerData();
	
	abstract protected function getProductData();
	
	abstract protected function getTotalsData();
	
	public function getCheckoutId() {
		return $this->checkoutId;
	}
	
	public function getCustomerId() {
		$data = $this->getCustomerData();
		if (isset($data['customer_id'])) {
			return $data['customer_id'];
		}
		else {
			return null;
		}
	}
	
	public function isNewCustomer() {
		return 'unknown';
	}
	
	public function getCustomerRegistrationDate() {
		$data = $this->getCustomerData();
		if (isset($data['date_added'])) {
			return new DateTime($data['date_added']);
		}
		else {
			return null;
		}
	}
	
	public function getInvoiceItems() {
		$items = array();
		
		$products = $this->getProductData();
		
		foreach ($products as $product) {
			$sku = $product['name'];
			if (!empty($product['model'])) {
				$sku = $product['model'];
			}
			
			$totalWithoutTax = $product['total'];
			$totalWithTax = $product['total_with_tax'];
			if ($totalWithoutTax > 0 || $totalWithoutTax < 0) {
				$taxRate = round(($totalWithTax / $totalWithoutTax - 1) * 100, 4);
			}
			else {
				$taxRate = 0;
			}
			
			$item = new Customweb_Payment_Authorization_DefaultInvoiceItem($sku, $product['name'], $taxRate, $totalWithTax, $product['quantity']);
			$items[] = $item;
		}
		
		$totals = $this->getTotalsData();
		foreach ($totals as $data) {
			$key = $data['code'];
			if ($key != 'sub_total' && $key != 'total' && $key != 'tax') {
				$type = Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE;
				$totalWithoutTax = $data['value'];
				$taxRate = $data['tax_rate'];
				$totalWithTax = $totalWithoutTax * (100 + $taxRate) / 100;
				$sku = $key;
				$name = $data['title'];
				
				if ($data['value'] < 0) {
					$type = Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT;
					$totalWithTax = abs($totalWithTax);
					$totalWithoutTax = abs($totalWithoutTax);
				}
				
				if ($key == 'shipping') {
					$type = Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING;
				}
				
				$item = new Customweb_Payment_Authorization_DefaultInvoiceItem($sku, $name, $taxRate, $totalWithTax, 1, $type);
				$items[] = $item;
			}
		}
		
		return Customweb_Util_Invoice::cleanupLineItems($items, $this->getOrderAmountInDecimals(), $this->getCurrencyCode());
	}
	
	
	public function getShippingMethod() {
		$totals = $this->getTotalsData();
		if (isset($totals['shipping']['title'])) {
			return $totals['shipping']['title'];
		}
		else {
			return UnzerCw_Language::_('No Shipping');
		}
	}
	
	public function getPaymentMethod() {
		return $this->paymentMethod;
	}
	
	
	public function getCustomerEMailAddress() {
		$data = $this->getCustomerData();
		if (empty($data['email'])) {
			return null;
		}
		else {
			return $data['email'];
		}
	}
	
	public function getBillingEMailAddress() {
		return $this->getCustomerEMailAddress();
	}
	
	public function getBillingGender() {
		if ($this->getBillingCompanyName() !== null) {
			return 'company';
		}
		else {
			return null;
		}
	}
	
	public function getBillingSalutation() {
		return null;
	}
	
	public function getBillingFirstName() {
		$data = $this->getInternalPaymentAddress();
		if (empty($data['firstname'])) {
			return null;
		}
		else {
			return $data['firstname'];
		}
	}
	
	public function getBillingLastName() {
		$data = $this->getInternalPaymentAddress();
		if (empty($data['lastname'])) {
			return null;
		}
		else {
			return $data['lastname'];
		}
	}
	
	public function getBillingStreet() {
		$data = $this->getInternalPaymentAddress();
		if (empty($data['address_1'])) {
			return null;
		}
		else {
			return trim($data['address_1'] . ' ' . $data['address_2']);
		}
	}
	
	public function getBillingCity() {
		$data = $this->getInternalPaymentAddress();
		if (empty($data['city'])) {
			return null;
		}
		else {
			return $data['city'];
		}
	}
	
	public function getBillingPostCode() {
		$data = $this->getInternalPaymentAddress();
		if (empty($data['postcode'])) {
			return null;
		}
		else {
			return $data['postcode'];
		}
	}
	
	public function getBillingState() {
		$data = $this->getInternalPaymentAddress();
		if (!isset($data['zone_code'])) {
			return null;
		}
		return $data['zone_code'];
	}
	
	public function getBillingCountryIsoCode() {
		$data = $this->getInternalPaymentAddress();
		if (!isset($data['iso_code_2'])) {
			throw new UnzerCw_IncompleteDataException("No billing country code was set.");
		}
		return $data['iso_code_2'];
	}
	
	public function getBillingPhoneNumber() {
		if ($this->equalsCustomerNameBillingName()) {
			$data = $this->getCustomerData();
			if (isset($data['telephone'])) {
				return $data['telephone'];
			}
			else {
				return null;
			}
		}
		else {
			return null;
		}
	}
	
	public function getBillingMobilePhoneNumber() {
		return null;
	}
	
	public function getBillingDateOfBirth() {
		return null;
	}
	
	public function getBillingCommercialRegisterNumber() {
		$data = $this->getInternalPaymentAddress();
		if (isset($data['company_id']) && !empty($data['company_id'])) {
			return $data['company_id'];
		}
		else {
			return null;
		}
	}
	
	public function getBillingSalesTaxNumber() {
		return null;
	}
	
	public function getBillingSocialSecurityNumber() {
		return null;
	}
	
	public function getBillingCompanyName() {
		$data = $this->getInternalPaymentAddress();
		if (isset($data['company']) && !empty($data['company'])) {
			return $data['company'];
		}
		else {
			return null;
		}
	}
	
	public function getShippingEMailAddress() {
		return $this->getCustomerEMailAddress();
	}
	
	public function getShippingGender() {
		if ($this->getShippingCompanyName() !== null) {
			return 'company';
		}
		else {
			return null;
		}
	}
	
	public function getShippingSalutation() {
		return null;
	}
	
	
	public function getShippingFirstName() {
		$data = $this->getInternalShippingAddress();
		if(isset($data['firstname'])) {
			return $data['firstname'];
		}
		else {
			return $this->getBillingFirstName();
		}
	}
	
	public function getShippingLastName() {
		$data = $this->getInternalShippingAddress();
		if(isset($data['lastname'])) {
			return $data['lastname'];
		}
		else {
			return $this->getBillingLastName();
		}
	}
	
	public function getShippingStreet() {
		$data = $this->getInternalShippingAddress();
		if(isset($data['address_2'])) {
			return trim($data['address_1'] . ' ' . $data['address_2']);
		}
		else {
			if(isset($data['address_1'])) {
				return trim($data['address_1']);
			}
			else {
				return $this->getBillingStreet();
			}
		}
	}
	
	public function getShippingCity() {
		$data = $this->getInternalShippingAddress();
		if(isset($data['city'])) {
			return $data['city'];
		}
		else {
			return $this->getBillingCity();
		}
	}
	
	public function getShippingPostCode() {
		$data = $this->getInternalShippingAddress();
		if(isset($data['postcode'])) {
			return $data['postcode'];
		}
		else {
			return $this->getBillingPostCode();
		}
	}
	
	public function getShippingState() {
		$data = $this->getInternalShippingAddress();
		if (isset($data['zone_code'])) {
			return $data['zone_code'];
		}
		else {
			return $this->getBillingState();
		}
		
	}
	
	public function getShippingCountryIsoCode() {
		$data = $this->getInternalShippingAddress();
		if (isset($data['iso_code_2'])) {
			return $data['iso_code_2'];
		}
		else {
			return $this->getBillingCountryIsoCode();
		}
		
	}
	
	public function getShippingPhoneNumber() {
		if ($this->equalsCustomerNameShippingName()) {
			$data = $this->getCustomerData();
			if (isset($data['telephone'])) {
				return $data['telephone'];
			}
			else {
				return null;
			}
		}
		else {
			return null;
		}
	}
	
	public function getShippingMobilePhoneNumber() {
		return null;
	}
	
	public function getShippingDateOfBirth() {
		return null;
	}
	
	public function getShippingCompanyName() {
		$data = $this->getInternalShippingAddress();
		if (isset($data['company']) && !empty($data['company'])) {
			return $data['company'];
		}
		else {
			return null;
		}
	}
	
	public function getShippingCommercialRegisterNumber() {
		$data = $this->getInternalShippingAddress();
		if (isset($data['company_id']) && !empty($data['company_id'])) {
			return $data['company_id'];
		}
		else {
			return null;
		}
	}
	
	public function getShippingSalesTaxNumber() {
		return null;
	}
	
	public function getShippingSocialSecurityNumber() {
		return null;
	}
	
	public function getOrderParameters() {
		return array();
	}
	
	protected function equalsCustomerNameBillingName() {
		return $this->getBillingFirstName() == $this->getCustomerFirstName() &&
		$this->getBillingLastName() == $this->getCustomerLastName();
	}
	
	protected function equalsCustomerNameShippingName() {
		return $this->getShippingFirstName() == $this->getCustomerFirstName() &&
		$this->getShippingLastName() == $this->getCustomerLastName();
	}

	private function getCustomerFirstName() {
		$data = $this->getCustomerData();
		if (empty($data['firstname'])) {
			return null;
		}
		else {
			return $data['firstname'];
		}
	}

	private function getCustomerLastName() {
		$data = $this->getCustomerData();
		if (empty($data['firstname'])) {
			return null;
		}
		else {
			return $data['firstname'];
		}
	}
	
	
}