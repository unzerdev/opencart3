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

require_once 'Customweb/Util/System.php';
require_once 'Customweb/Util/Url.php';
require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
require_once 'Customweb/Payment/Authorization/IErrorMessage.php';
require_once 'Customweb/Payment/Authorization/PaymentPage/IAdapter.php';
require_once 'Customweb/Util/Html.php';
require_once 'Customweb/Core/Http/Response.php';
require_once 'Customweb/Core/Exception/CastException.php';
require_once 'Customweb/Payment/Endpoint/Dispatcher.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Entity/Transaction.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/Adapter/IframeAdapter.php';
require_once 'UnzerCw/Template.php';
require_once 'UnzerCw/PaymentMethod.php';
require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Adapter/WidgetAdapter.php';


class ControllerUnzerCwProcess extends UnzerCw_AbstractController
{
	private function redirectSetCookieIfRequired($action) {
		if($_SERVER['REQUEST_METHOD'] !== 'GET') {
			header_remove('set-cookie');
			header('Location: ' . UnzerCw_Util::getUrl('process', $action, array('cw_transaction_id' => $_REQUEST['cw_transaction_id']), true));
			die();
		}
	}

	public function notify() {
		header_remove('set-cookie');
		$dispatcher = new Customweb_Payment_Endpoint_Dispatcher(UnzerCw_Util::getEndpointAdapter(), UnzerCw_Util::getContainer(), array(
			0 => 'Customweb_Unzer',
 			1 => 'Customweb_Payment_Authorization',
 		));
		$response = new Customweb_Core_Http_Response($dispatcher->invokeControllerAction(UnzerCw_HttpRequest::getInstance(), 'process', 'index'));
		$response->send();
		die();
	}

	/**
	 * Ajax endpoint to handle updates on the checkout form (Alias selection etc.)
	 */
	public function payment_page_update() {

		$failedTransaction = null;
		$data = array();
		$data['checkout_form'] = $this->getCheckoutFormData($failedTransaction);
		$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath(UnzerCw_Template::PAYMENT_FORM_TEMPLATE), $data));
	}

	private function getCheckoutFormData($failedTransaction) {
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if(!is_array($order_info)) {
			die('Unable to load order information');
		}
		if ($failedTransaction !== null && $failedTransaction instanceof UnzerCw_Entity_Transaction) {
			$paymentMethodName = $failedTransaction->getPaymentMachineName();
		}
		else if (isset($order_info['payment_code'])) {
			$paymentMethodName = str_replace('unzercw_', '', $order_info['payment_code']);
		}
		else {
			die("Unable to determine the current selected payment method.");
		}

		$paymentMethod = UnzerCw_PaymentMethod::getPaymentMethod($paymentMethodName);
		$orderContext = $paymentMethod->newOrderContext($order_info, $this->registry);
		$adapter = $paymentMethod->getPaymentAdapterByOrderContext($orderContext);
		return $adapter->getCheckoutPageHtml($paymentMethod, $orderContext, $this->registry, $failedTransaction);
	}


	/**
	 * A page which shows only the payment form.
	 */
	public function payment() {

		$data = array();
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text'      => UnzerCw_Language::_("Payment"),
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'separator' => $this->language->get('text_separator')
		);

		// Translations:
		$data['heading_title'] = UnzerCw_Language::_("Payment");

		$failedTransaction = null;
		if (isset($_REQUEST['failed_transaction_id'])) {
			$failedTransaction = UnzerCw_Entity_Transaction::loadById($_REQUEST['failed_transaction_id']);
			/* @var $errorMessage Customweb_Payment_Authorization_IErrorMessage  */
			$errorMessage = current($failedTransaction->getTransactionObject()->getErrorMessages());
			if ($errorMessage instanceof Customweb_Payment_Authorization_IErrorMessage) {
				$data['error_warning'] = $errorMessage->getUserMessage();
			}
		}
		$data['checkout_form'] = $this->getCheckoutFormData($failedTransaction);

		$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath('template/unzercw/payment_page'), $data, array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		)));
	}

	public function iframe() {

		$transaction = $this->getTransactionFromRequest();

		$paymentAdapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($transaction->getTransactionObject()->getAuthorizationMethod());

		$adapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($paymentAdapter);

		if (!($adapter instanceof UnzerCw_Adapter_IframeAdapter)) {
			throw new Exception("Only supported for iframe authorization.");
		}
		// Translations:
		$data = array();
		$data['heading_title'] = UnzerCw_Language::_("Payment");
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text'      => UnzerCw_Language::_("Payment"),
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'separator' => $this->language->get('text_separator')
		);
		
		$adapter->prepareWithFormData(UnzerCw_Util::getFormData(), $transaction);
		$data['checkout_form'] = $adapter->getIframe();

		$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath('template/unzercw/payment_page'), $data, array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		)));
	}

	public function widget() {

		$transaction = $this->getTransactionFromRequest();

		$paymentAdapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($transaction->getTransactionObject()->getAuthorizationMethod());
		$adapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($paymentAdapter);

		if (!($adapter instanceof UnzerCw_Adapter_WidgetAdapter)) {
			throw new Exception("Only supported for widget authorization.");
		}

		// Translations:
		$data = array();
		$data['heading_title'] = UnzerCw_Language::_("Payment");
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text'      => UnzerCw_Language::_("Payment"),
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'separator' => $this->language->get('text_separator')
		);
		
		
		$adapter->prepareWithFormData(UnzerCw_Util::getFormData(), $transaction);
		$data['checkout_form'] = $adapter->getWidget();

		$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath('template/unzercw/payment_page'), $data, array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		)));
	}
	
	public function server_authorization() {
		$transaction = $this->getTransactionFromRequest();
		$paymentAdapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($transaction->getTransactionObject()->getAuthorizationMethod());
		
		if (!($paymentAdapter instanceof Customweb_Payment_Authorization_Server_IAdapter)) {
			throw new Customweb_Core_Exception_CastException('Customweb_Payment_Authorization_Server_IAdapter');
		}
		$transactionObject = $transaction->getTransactionObject();
		$response = new Customweb_Core_Http_Response($paymentAdapter->processAuthorization($transactionObject, UnzerCw_Util::getFormData()));
		
		UnzerCw_Util::getTransactionHandler()->persistTransactionObject($transactionObject);
		$response->send();
		die();
	}

	/**
	 * Force the browser to break out the iframe
	 */
	public function iframe_breakout() {
		header_remove('set-cookie');

		$transaction = $this->getTransactionFromRequest();

		$redirectionUrl = '';
		if ($transaction->getTransactionObject()->isAuthorizationFailed()) {
			$redirectionUrl = Customweb_Util_Url::appendParameters(
				$transaction->getTransactionObject()->getTransactionContext()->getFailedUrl(),
				$transaction->getTransactionObject()->getTransactionContext()->getCustomParameters()
			);
		}
		else {
			$redirectionUrl = Customweb_Util_Url::appendParameters(
				$transaction->getTransactionObject()->getTransactionContext()->getSuccessUrl(),
				$transaction->getTransactionObject()->getTransactionContext()->getCustomParameters()
			);
		}

		$data = array();
		$data['redirectionUrl'] = $redirectionUrl;
		$data['breadcrumbs'] = array();
		

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text'      => UnzerCw_Language::_("Payment"),
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'separator' => $this->language->get('text_separator')
		);
		
		
		$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath('template/unzercw/iframe_breakout'), $data, array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		)));
	}

	/**
	 * Redirect the customer to the payment page of the Unzer.
	 */
	public function redirection() {

		$transaction = $this->getTransactionFromRequest();

		$adapter = UnzerCw_Util::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($transaction->getTransactionObject()->getAuthorizationMethod());
		if (!($adapter instanceof Customweb_Payment_Authorization_PaymentPage_IAdapter)) {
			throw new Exception("Only supported for payment page authorization.");
		}
		$params = UnzerCw_Util::getFormData();

		$headerRedirection = $adapter->isHeaderRedirectionSupported($transaction->getTransactionObject(), $params);

		$data = array();
		if ($headerRedirection) {
			$url = $adapter->getRedirectionUrl($transaction->getTransactionObject(), $params);
			UnzerCw_Util::getEntityManager()->persist($transaction);
			header('Location: ' . $url);
			die();
		}
		else {
			$data['method_name'] = $transaction->getTransactionObject()->getPaymentMethod()->getPaymentMethodDisplayName();
			$data['form_target_url'] = $adapter->getFormActionUrl($transaction->getTransactionObject(), $params);
			$data['hidden_fields'] = Customweb_Util_Html::buildHiddenInputFields($adapter->getParameters($transaction->getTransactionObject(), $params));
			$data['button_continue'] = UnzerCw_Language::_("Continue");
			UnzerCw_Util::getEntityManager()->persist($transaction);

			$this->template = UnzerCw_Template::resolveTemplatePath('template/unzercw/redirect');

			$data['breadcrumbs'] = array();
			
			$data['breadcrumbs'][] = array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home'),
				'separator' => false
			);
			$data['breadcrumbs'][] = array(
				'text'      => UnzerCw_Language::_("Payment"),
				'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
				'separator' => $this->language->get('text_separator')
			);
				
			$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath('template/unzercw/redirect'), $data, array(
				'common/column_left',
				'common/column_right',
				'common/content_top',
				'common/content_bottom',
				'common/footer',
				'common/header'
			)));
		}
	}

	/**
	 * Handles failed payments
	 */
	public function failed() {
		$this->redirectSetCookieIfRequired('failed');

		$failedTransaction = $this->getTransactionFromRequest();
		if ($failedTransaction !== null 
				&& $failedTransaction->getCustomerId() !== null 
				&& $failedTransaction->getCustomerId() !== 0
				&& $failedTransaction->getCustomerId() !== '0'
				&& $failedTransaction->getCustomerId() !== $this->session->data['customer_id']) {
			die('Invalid customer.');
		}
		
		$errorMessage = current($failedTransaction->getTransactionObject()->getErrorMessages());
		if ($errorMessage instanceof Customweb_Payment_Authorization_IErrorMessage) {
			$this->session->data['error'] = $errorMessage->getUserMessage()->toString();
		}
		

		$url = UnzerCw_Util::getUrl('checkout', '', array('failed_transaction_id' => $failedTransaction->getTransactionId()), true, 'checkout');
		header('Location: ' . $url);
		die();

	}

	/**
	 * Handles successful payments
	 */
	public function success() {
		$this->redirectSetCookieIfRequired('success');
		
		$transaction = $this->getTransactionFromRequest();
		
		// We have to close the session here otherwise the transaction may not be updated by the notification
		// callback.
		session_write_close();

		$start = time();
		$maxExecutionTime = Customweb_Util_System::getMaxExecutionTime() - 5;
		if($maxExecutionTime > 30) {
			$maxExecutionTime = 30;
		}

		// Wait as long as the notification is done in the background
		while (true) {

			$transaction = UnzerCw_Entity_Transaction::loadById(intval($_REQUEST['cw_transaction_id']), false);

			$transactionObject = $transaction->getTransactionObject();

			$url = null;
			if ($transactionObject->isAuthorizationFailed()) {
				$url = UnzerCw_Util::getUrl('process', 'failed', array('cw_transaction_id' => $_REQUEST['cw_transaction_id']), true);
			}
			else if ($transactionObject->isAuthorized()) {
				$url = UnzerCw_Util::getUrl('success', '', array(), true, 'checkout');
			}

			if ($url !== null) {
				header('Location: ' . $url);
				die();
			}

			if (time() - $start > $maxExecutionTime) {
				$url = UnzerCw_Util::getUrl('process', 'timeout', array('cw_transaction_id' => $_REQUEST['cw_transaction_id']), true);
				header('Location: ' . $url);
				die();
			}
			else {
				// Wait 2 seconds for the next try.
				sleep(2);
			}

		}
	}
	
	/**
	 * Handles timeouts
	 */
	public function timeout() {
		$transaction = $this->getTransactionFromRequest();
		
		// Translations:
		$data = array();
		$data['heading_title'] = UnzerCw_Language::_("Payment");
		
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text'      => UnzerCw_Language::_("Payment"),
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'separator' => $this->language->get('text_separator')
		);
		
		$data['timeout_message'] = UnzerCw_Language::_('It seems as your order was successful. However we do not get any feedback from the payment processor. Please contact us to find out what is going on with your order.');
		$data['order_message'] = UnzerCw_Language::_('Please mention the following order id:');
		$data['order_id'] = $transaction->getOrderId();
		
		
		$this->response->setOutput($this->renderView(UnzerCw_Template::resolveTemplatePath('template/unzercw/timeout'), $data, array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		)));
		
	}
	
	private function getTransactionFromRequest() {

		if (!isset($_REQUEST['cw_transaction_id'])) {
			die("No transaction_id provided.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById(intval($_REQUEST['cw_transaction_id']));
		
		if ($transaction === null) {
			die("Invalid transaction id provided.");
		}
		
		return $transaction;
	}


}
