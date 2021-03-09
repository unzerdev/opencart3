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


require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/IPaymentMethodDefinition.php';
require_once 'UnzerCw/Template.php';
require_once 'UnzerCw/PaymentMethod.php';
require_once 'UnzerCw/AbstractController.php';


abstract class ControllerPaymentUnzerCwAbstract extends UnzerCw_AbstractController implements UnzerCw_IPaymentMethodDefinition
{
	
	public function index()
	{
		
		// Translations:
		$this->load->model('checkout/order');
		$orderId = $this->session->data['order_id'];
		if (!empty($orderId)) {
			$order_info = $this->model_checkout_order->getOrder($orderId);
			
			$failedTransaction = null;
			$paymentMethod = new UnzerCw_PaymentMethod($this);
			$orderContext = $paymentMethod->newOrderContext($order_info, $this->registry);
			$adapter = $paymentMethod->getPaymentAdapterByOrderContext($orderContext);
			
			$data = $adapter->getCheckoutPageHtml($paymentMethod, $orderContext, $this->registry, $failedTransaction);
			
			
			if (false) {
				$data = '<div style="border: 1px solid #ff0000; background: #ffcccc; font-weight: bold;">' . 
					UnzerCw_Language::_('We experienced a problem with your sellxed payment extension. For more information, please visit the configuration page of the plugin.') . 
				'</div>';
			}
			
			$vars = array();
			$vars['checkout_form'] = $data;
			return $this->renderView(UnzerCw_Template::resolveTemplatePath(UnzerCw_Template::PAYMENT_FORM_TEMPLATE), $vars);
		}
		else {
			return 'The order ID is not set in the session. This happens when the order could not be 
					created in the database. A common cause is a not completely executed OpenCart database schema migration.';
		}
	}
	
}

