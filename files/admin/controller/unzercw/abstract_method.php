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
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/IPaymentMethodDefinition.php';
require_once 'UnzerCw/Entity/Transaction.php';
require_once 'UnzerCw/PaymentMethod.php';
require_once 'UnzerCw/AbstractController.php';


abstract class ControllerPaymentUnzerCwAbstract extends UnzerCw_AbstractController implements UnzerCw_IPaymentMethodDefinition
{
	protected function getModuleBasePath() {
		return 'payment/unzercw';
	}
	
	protected function getModuleParentPath() {
		return 'extension/payment';
	}
	
	
	public function index() {
		$data = array();
		$this->load->model('unzercw/setting');
		$paymentMethod = new UnzerCw_PaymentMethod($this);

		// Store the configuration
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_unzercw_setting->saveSettings($paymentMethod->getSettingsApi(), $this->request->post);
			$data['success'] = UnzerCw_Language::_("Save was successful.");
		}

		$this->document->addStyle('view/stylesheet/unzercw.css');
		$this->document->addScript('view/javascript/unzercw.js');


		$heading = UnzerCw_Language::_("Configurations for !method (Unzer)", array('!method' => $paymentMethod->getPaymentMethodDisplayName()));
		$this->document->setTitle($heading);

		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => UnzerCw_Language::_('Modules'),
			'href'      => $this->url->link($this->getModuleParentPath(), 'type=payment&user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'      => $heading,
			'href'      => $this->url->link($this->getModuleBasePath() . '_' . strtolower($paymentMethod->getPaymentMethodName()), 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
		);


		$data['more_link'] = $this->url->link($this->getModuleBasePath() . '/form_overview', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['heading_title'] = $heading;
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');


		$data['action'] = $this->url->link($this->getModuleBasePath() . '_' . strtolower($paymentMethod->getPaymentMethodName()), 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['cancel'] = $this->url->link($this->getModuleParentPath(), 'type=payment&user_token=' . $this->session->data['user_token'], 'SSL');

		$data['tabs'] = $this->model_unzercw_setting->renderStoreTabs($this->url->link($this->getModuleBasePath() . '_'  . strtolower($paymentMethod->getPaymentMethodName()), 'user_token=' . $this->session->data['user_token'], 'SSL'));
		$data['form'] = $this->model_unzercw_setting->render($paymentMethod->getSettingsApi());
		$data['text_edit'] = $heading;

		if (version_compare(VERSION, '2.0.0.0') >= 0) {
			$this->document->addScript('view/javascript/bootstrap-tab.min.js');
		}
		
		$this->response->setOutput($this->renderView('unzercw/settings', $data, array(
			'common/header',
			'common/footer',
		)));
	}

	public function install() {
		UnzerCw_Util::migrate();
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/unzercw')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	public function orderAction() {
		$orderId = $this->request->get['order_id'];

		$transactions = UnzerCw_Entity_Transaction::getTransactionsByOrderId($orderId);

		$data = array();
		$data['transactions'] = $transactions;
		$this->response->setOutput($this->renderView('unzercw/order_form', $data));
	}

}