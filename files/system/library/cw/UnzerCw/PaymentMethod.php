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


require_once 'Customweb/Payment/Authorization/IPaymentMethod.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Entity/Transaction.php';
require_once 'UnzerCw/PaymentMethod.php';
require_once 'UnzerCw/TransactionContext.php';
require_once 'UnzerCw/PaymentMethodWrapper.php';
require_once 'UnzerCw/Store.php';
require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/DefaultPaymentMethodDefinition.php';
require_once 'UnzerCw/SettingApi.php';
require_once 'UnzerCw/OrderContext.php';


final class UnzerCw_PaymentMethod implements Customweb_Payment_Authorization_IPaymentMethod{

	/**
	 * @var UnzerCw_IPaymentMethodDefinition
	 */
	private $paymentMethodDefinitions;

	/**
	 * @var UnzerCw_SettingApi
	 */
	private $settingsApi;

	private static $completePaymentMethodDefinitions = array(
		'creditcard' => array(
			'machineName' => 'CreditCard',
 			'frontendName' => 'Credit / Debit Card',
 			'backendName' => 'Unzer: Credit / Debit Card',
 		),
 		'secureinvoice' => array(
			'machineName' => 'SecureInvoice',
 			'frontendName' => 'Secure Invoice',
 			'backendName' => 'Unzer: Secure Invoice',
 		),
 		'openinvoice' => array(
			'machineName' => 'OpenInvoice',
 			'frontendName' => 'Invoice',
 			'backendName' => 'Unzer: Invoice',
 		),
 		'securesepa' => array(
			'machineName' => 'SecureSepa',
 			'frontendName' => 'Secure SEPA',
 			'backendName' => 'Unzer: Secure SEPA',
 		),
 		'directdebitssepa' => array(
			'machineName' => 'DirectDebitsSepa',
 			'frontendName' => 'Sepa Direct Debits',
 			'backendName' => 'Unzer: Sepa Direct Debits',
 		),
 		'bcmc' => array(
			'machineName' => 'Bcmc',
 			'frontendName' => 'Bancontact',
 			'backendName' => 'Unzer: Bancontact',
 		),
 		'wechatpay' => array(
			'machineName' => 'WeChatPay',
 			'frontendName' => 'WeChat Pay',
 			'backendName' => 'Unzer: WeChat Pay',
 		),
 		'alipay' => array(
			'machineName' => 'Alipay',
 			'frontendName' => 'Alipay',
 			'backendName' => 'Unzer: Alipay',
 		),
 		'prepayment' => array(
			'machineName' => 'Prepayment',
 			'frontendName' => 'Prepayment',
 			'backendName' => 'Unzer: Prepayment',
 		),
 		'eps' => array(
			'machineName' => 'Eps',
 			'frontendName' => 'EPS',
 			'backendName' => 'Unzer: EPS',
 		),
 		'ideal' => array(
			'machineName' => 'IDeal',
 			'frontendName' => 'iDEAL',
 			'backendName' => 'Unzer: iDEAL',
 		),
 		'przelewy24' => array(
			'machineName' => 'Przelewy24',
 			'frontendName' => 'Przelewy24',
 			'backendName' => 'Unzer: Przelewy24',
 		),
 		'giropay' => array(
			'machineName' => 'Giropay',
 			'frontendName' => 'giropay',
 			'backendName' => 'Unzer: giropay',
 		),
 		'sofortueberweisung' => array(
			'machineName' => 'Sofortueberweisung',
 			'frontendName' => 'SOFORT',
 			'backendName' => 'Unzer: SOFORT',
 		),
 		'paypal' => array(
			'machineName' => 'PayPal',
 			'frontendName' => 'PayPal',
 			'backendName' => 'Unzer: PayPal',
 		),
 		'unzerbanktransfer' => array(
			'machineName' => 'UnzerBankTransfer',
 			'frontendName' => 'Unzer Bank Transfer',
 			'backendName' => 'Unzer: Unzer Bank Transfer',
 		),
 		'unzerinstallment' => array(
			'machineName' => 'UnzerInstallment',
 			'frontendName' => 'Unzer Instalment',
 			'backendName' => 'Unzer: Unzer Instalment',
 		),
 	);

	public function __construct(UnzerCw_IPaymentMethodDefinition $defintions) {
		$this->paymentMethodDefinitions = $defintions;
		$this->settingsApi = new UnzerCw_SettingApi('payment_unzercw_' . $this->paymentMethodDefinitions->getMachineName());
	}

	public static function getPaymentMethod($paymentMethodMachineName) {
		$paymentMethodMachineName = strtolower($paymentMethodMachineName);

		if (isset(self::$completePaymentMethodDefinitions[$paymentMethodMachineName])) {
			$def = self::$completePaymentMethodDefinitions[$paymentMethodMachineName];
			return new UnzerCw_PaymentMethod(new UnzerCw_DefaultPaymentMethodDefinition($def['machineName'], $def['backendName'], $def['frontendName']));
		}
		else {
			throw new Exception("No payment method found with name '" . $paymentMethodMachineName . "'.");
		}
	}

	/**
	 * @return UnzerCw_SettingApi
	 */
	public function getSettingsApi() {
		return $this->settingsApi;
	}

	/**
	 * (non-PHPdoc)
	 * @see Customweb_Payment_Authorization_IPaymentMethod::getPaymentMethodName()
	 */
	public function getPaymentMethodName() {
		return strtolower($this->paymentMethodDefinitions->getMachineName());
	}

	public function getPaymentMethodDisplayName() {
		$title = $this->getSettingsApi()->getValue('title');
		$langId = UnzerCw_Language::getCurrentLanguageId();
		if (!empty($title) && isset($title[$langId]) && !empty($title[$langId])) {
			return $title[$langId];
		}
		else {
			return $this->paymentMethodDefinitions->getFrontendName();
		}
	}

	public function getPaymentMethodConfigurationValue($key, $languageCode = null) {

		if ($languageCode === null) {
			return $this->getSettingsApi()->getValue($key);
		}
		else {
			$languageId = null;
			$languageCode = (string)$languageCode;
			foreach (UnzerCw_Util::getLanguages() as $language) {
				if ($language['code'] == $languageCode) {
					$languageId = $language['language_id'];
					break;
				}
			}

			if ($languageId === null) {
				throw new Exception("Could not find language with language code '" . $languageCode . "'.");
			}

			return $this->getSettingsApi()->getValue($key, null, $languageId);
		}
	}

	public function existsPaymentMethodConfigurationValue($key, $languageCode = null) {
		return $this->getSettingsApi()->isSettingPresent($key);
	}

	public function getBackendPaymentMethodName() {
		return $this->paymentMethodDefinitions->getBackendName();
	}

	/**
	 * @param Customweb_Payment_Authorization_IOrderContext $context
	 * @return UnzerCw_Adapter_IAdapter
	 */
	public function getPaymentAdapterByOrderContext(Customweb_Payment_Authorization_IOrderContext $context) {
		$paymentAdapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByContext($context);
		return UnzerCw_Util::getShopAdapterByPaymentAdapter($paymentAdapter);

	}

	/**
	 * @param UnzerCw_Entity_Transaction $transaction
	 * @return UnzerCw_Adapter_IAdapter
	 */
	public function getPaymentAdapterByTransaction(UnzerCw_Entity_Transaction $transaction) {
		$paymentAdapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationType());
		return UnzerCw_Util::getShopAdapterByPaymentAdapter($paymentAdapter);
	}


	/**
	 * @return UnzerCw_Entity_Transaction
	 */
	public function newTransaction(UnzerCw_OrderContext $orderContext, $aliasTransactionId = null, $failedTransactionObject = null) {
		$transaction = new UnzerCw_Entity_Transaction();

		$orderInfo = $orderContext->getOrderInfo();
		$transaction->setOrderId($orderInfo['order_id'])->setCustomerId($orderInfo['customer_id']);
		$transaction->setStoreId(UnzerCw_Store::getStoreId());
		UnzerCw_Util::getEntityManager()->persist($transaction);

		$transactionContext = new UnzerCw_TransactionContext($transaction, $orderContext, $aliasTransactionId);
		$transactionObject = $this->getPaymentAdapterByOrderContext($orderContext)->getInterfaceAdapter()->createTransaction($transactionContext, $failedTransactionObject);
		
		unset($_SESSION['unzercw_checkout_id'][$orderContext->getPaymentMethod()->getPaymentMethodName()]);
		
		$transaction->setTransactionObject($transactionObject);
		UnzerCw_Util::getEntityManager()->persist($transaction);

		return $transaction;
	}

	public function newOrderContext($orderInfo, $registry) {
		$order_totals = UnzerCw_Util::getOrderTotals($registry);
		return new UnzerCw_OrderContext(new UnzerCw_PaymentMethodWrapper($this), $orderInfo, $order_totals);
	}
}