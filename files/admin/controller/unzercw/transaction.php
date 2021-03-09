<?php
/**
 *  * You are allowed to use this API in your web application.
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
require_once 'Customweb/Core/Util/Xml.php';
require_once 'Customweb/Core/Util/Html.php';

require_once 'Customweb/Payment/Authorization/DefaultInvoiceItem.php';
require_once 'Customweb/Grid/Column.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICapture.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICancel.php';
require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
require_once 'Customweb/Grid/DataAdapter/DriverAdapter.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/IRefund.php';
require_once 'Customweb/Grid/Loader.php';

require_once 'UnzerCw/Grid/TransactionActionColumn.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Entity/Transaction.php';
require_once 'UnzerCw/Grid/Renderer.php';
require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Grid/TransactionStatusColumn.php';

class ControllerUnzerCwTransaction extends UnzerCw_AbstractController {

	public function index(){
		$this->getList();
	}

	public function view($data = array()){
		if (!isset($_GET['transaction_id'])) {
			die("No transaction id given.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById($_GET['transaction_id']);
		
		if ($transaction === null) {
			die('Could not load transaction.');
		}
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false 
		);
		
		$data['breadcrumbs'][] = array(
			'text' => UnzerCw_Language::_('Unzer Transactions'),
			'href' => $this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: ' 
		);
		$this->document->addStyle('view/stylesheet/unzercw.css');
		
		$relatedTransactions = array();
		if ($transaction->getOrderId() > 0) {
			$relatedTransactions = UnzerCw_Entity_Transaction::getTransactionsByOrderId($transaction->getOrderId());
		}
		
		$this->document->setTitle(UnzerCw_Language::_('Unzer View Transaction'));
		$data['heading_title'] = UnzerCw_Language::_('Unzer View Transaction');
		
		$data['transaction'] = $transaction;
		$data['cancel'] = $this->url->link('unzercw/transaction/cancel', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		$data['capture'] = $this->url->link('unzercw/transaction/capture', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		$data['refund'] = $this->url->link('unzercw/transaction/refund', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		$data['relatedTransactions'] = $relatedTransactions;
		$data['dateFormat'] = 'Y-m-d H:i:s';
		$data['url'] = $this->url;
		$data['user_token'] = $this->session->data['user_token'];
		
		if (!isset($data['success'])) {
			$data['success'] = '';
		}
		
		$this->response->setOutput(
				$this->renderView('unzercw/transaction/view', $data, array(
					'common/header',
					'common/footer' 
				)));
	}

	public function refund(){
		if (!isset($_GET['transaction_id'])) {
			die("No transaction id given.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById($_GET['transaction_id']);
		
		if ($transaction === null) {
			die('Could not load transaction.');
		}
		$data = array();
		if (isset($_POST['quantity'])) {
			
			$refundLineItems = array();
			$lineItems = $transaction->getTransactionObject()->getNonRefundedLineItems();
			foreach ($_POST['quantity'] as $index => $quantity) {
				if (isset($_POST['price_including'][$index]) && floatval($_POST['price_including'][$index]) != 0) {
					$originalItem = $lineItems[$index];
					if ($originalItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$priceModifier = -1;
					}
					else {
						$priceModifier = 1;
					}
					$refundLineItems[$index] = new Customweb_Payment_Authorization_DefaultInvoiceItem($originalItem->getSku(), 
							$originalItem->getName(), $originalItem->getTaxRate(), $priceModifier * (floatval($_POST['price_including'][$index])), 
							$quantity, $originalItem->getType());
				}
			}
			if (count($refundLineItems) > 0) {
				$adapter = UnzerCw_Util::getContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_IRefund');
				
				if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_IRefund)) {
					throw new Exception("Adapter has to be of instance 'Customweb_Payment_BackendOperation_Adapter_Service_IRefund'.");
				}
				
				$close = false;
				if (isset($_POST['close']) && $_POST['close'] == 'on') {
					$close = true;
				}
				try {
					$adapter->partialRefund($transaction->getTransactionObject(), $refundLineItems, $close);
					UnzerCw_Util::getEntityManager()->persist($transaction);
					$data['success'] = UnzerCw_Language::_('Refund was successful.');
					$this->view();
					return;
				}
				catch (Exception $e) {
					$data['error_warning'] = $e->getMessage();
				}
			}
		}
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false 
		);
		
		$data['breadcrumbs'][] = array(
			'text' => UnzerCw_Language::_('Unzer Transactions'),
			'href' => $this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: ' 
		);
		$this->document->addStyle('view/stylesheet/unzercw.css');
		$this->document->addScript('view/javascript/unzercw_line_item_grid.js');
		
		$this->document->setTitle(UnzerCw_Language::_('Unzer View Transaction'));
		$data['heading_title'] = UnzerCw_Language::_('Unzer View Transaction');
		
		$data['transaction'] = $transaction;
		$data['back'] = $this->url->link('unzercw/transaction/view', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		$data['refundConfirmUrl'] = $this->url->link('unzercw/transaction/refund', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		
		if (!isset($data['success'])) {
			$data['success'] = '';
		}
		
		$this->response->setOutput(
				$this->renderView('unzercw/transaction/refund', $data, array(
					'common/header',
					'common/footer' 
				)));
	}

	public function capture(){
		if (!isset($_GET['transaction_id'])) {
			die("No transaction id given.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById($_GET['transaction_id']);
		
		if ($transaction === null) {
			die('Could not load transaction.');
		}
		
		$data = array();
		if (isset($_POST['quantity'])) {
			
			$captureLineItems = array();
			$lineItems = $transaction->getTransactionObject()->getUncapturedLineItems();
			foreach ($_POST['quantity'] as $index => $quantity) {
				if (isset($_POST['price_including'][$index]) && floatval($_POST['price_including'][$index]) != 0) {
					$originalItem = $lineItems[$index];
					if ($originalItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$priceModifier = -1;
					}
					else {
						$priceModifier = 1;
					}
					$captureLineItems[$index] = new Customweb_Payment_Authorization_DefaultInvoiceItem($originalItem->getSku(), 
							$originalItem->getName(), $originalItem->getTaxRate(), $priceModifier * (floatval($_POST['price_including'][$index])), 
							$quantity, $originalItem->getType());
				}
			}
			if (count($captureLineItems) > 0) {
				$adapter = UnzerCw_Util::getContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_ICapture');
				
				if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_ICapture)) {
					throw new Exception("Adapter has to be of instance 'Customweb_Payment_BackendOperation_Adapter_Service_ICapture'.");
				}
				
				$close = false;
				if (isset($_POST['close']) && $_POST['close'] == 'on') {
					$close = true;
				}
				try {
					$adapter->partialCapture($transaction->getTransactionObject(), $captureLineItems, $close);
					UnzerCw_Util::getEntityManager()->persist($transaction);
					$data['success'] = UnzerCw_Language::_('Capture was successful.');
					$this->view();
					return;
				}
				catch (Exception $e) {
					$data['error_warning'] = $e->getMessage();
				}
			}
		}
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false 
		);
		
		$data['breadcrumbs'][] = array(
			'text' => UnzerCw_Language::_('Unzer Transactions'),
			'href' => $this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: ' 
		);
		$this->document->addStyle('view/stylesheet/unzercw.css');
		$this->document->addScript('view/javascript/unzercw_line_item_grid.js');
		
		$this->document->setTitle(UnzerCw_Language::_('Unzer View Transaction'));
		$data['heading_title'] = UnzerCw_Language::_('Unzer View Transaction');
		
		$data['transaction'] = $transaction;
		$data['back'] = $this->url->link('unzercw/transaction/view', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		$data['captureConfirmUrl'] = $this->url->link('unzercw/transaction/capture', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		
		if (!isset($data['success'])) {
			$data['success'] = '';
		}
		
		$this->response->setOutput(
				$this->renderView('unzercw/transaction/capture', $data, array(
					'common/header',
					'common/footer' 
				)));
	}

	public function cancel(){
		if (!isset($_GET['transaction_id'])) {
			die("No transaction id given.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById($_GET['transaction_id']);
		
		if ($transaction === null) {
			die('Could not load transaction.');
		}
		
		$data = array();
		$adapter = UnzerCw_Util::getContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_ICancel');
		if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_ICancel)) {
			throw new Exception("Adapter has to be of instance 'Customweb_Payment_BackendOperation_Adapter_Service_ICancel'.");
		}
		
		try {
			$adapter->cancel($transaction->getTransactionObject());
			UnzerCw_Util::getEntityManager()->persist($transaction);
			$data['success'] = UnzerCw_Language::_('Cancel was successful.');
		}
		catch (Exception $e) {
			UnzerCw_Util::getEntityManager()->persist($transaction);
			$data['error_warning'] = $e->getMessage();
		}
		$this->view($data);
	}

	public function view_capture(){
		if (!isset($_GET['transaction_id'])) {
			die("No transaction id given.");
		}
		
		if (!isset($_GET['capture_id'])) {
			die("No capture id given.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById($_GET['transaction_id']);
		
		if ($transaction === null) {
			die('Could not load transaction.');
		}
		
		$capture = null;
		foreach ($transaction->getTransactionObject()->getCaptures() as $item) {
			if ($item->getCaptureId() == $_GET['capture_id']) {
				$capture = $item;
				break;
			}
		}
		
		if ($capture == null) {
			die('No capture found with the given id.');
		}
		
		$data = array();
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false 
		);
		
		$data['breadcrumbs'][] = array(
			'text' => UnzerCw_Language::_('Unzer Transactions'),
			'href' => $this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: ' 
		);
		$this->document->addStyle('view/stylesheet/unzercw.css');
		
		$this->document->setTitle(UnzerCw_Language::_('Unzer View Capture'));
		$data['heading_title'] = UnzerCw_Language::_('Unzer View Capture');
		$data['dateFormat'] = 'Y-m-d H:i:s';
		
		$data['transaction'] = $transaction;
		$data['capture'] = $capture;
		$data['back'] = $this->url->link('unzercw/transaction/view', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		
		if (!isset($data['success'])) {
			$data['success'] = '';
		}
		
		$this->response->setOutput(
				$this->renderView('unzercw/transaction/capture_view', $data, array(
					'common/header',
					'common/footer' 
				)));
	}

	public function view_refund(){
		if (!isset($_GET['transaction_id'])) {
			die("No transaction id given.");
		}
		
		if (!isset($_GET['refund_id'])) {
			die("No refund id given.");
		}
		
		$transaction = UnzerCw_Entity_Transaction::loadById($_GET['transaction_id']);
		
		if ($transaction === null) {
			die('Could not load transaction.');
		}
		
		$refund = null;
		foreach ($transaction->getTransactionObject()->getRefunds() as $item) {
			if ($item->getRefundId() == $_GET['refund_id']) {
				$refund = $item;
				break;
			}
		}
		
		if ($refund == null) {
			die('No refund found with the given id.');
		}
		
		$data = array();
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false 
		);
		
		$data['breadcrumbs'][] = array(
			'text' => UnzerCw_Language::_('Unzer Transactions'),
			'href' => $this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: ' 
		);
		$this->document->addStyle('view/stylesheet/unzercw.css');
		
		$this->document->setTitle(UnzerCw_Language::_('Unzer View Refund'));
		$data['heading_title'] = UnzerCw_Language::_('Unzer View Refund');
		$data['dateFormat'] = 'Y-m-d H:i:s';
		
		$data['transaction'] = $transaction;
		$data['refund'] = $refund;
		$data['back'] = $this->url->link('unzercw/transaction/view', 
				'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $_GET['transaction_id'], 'SSL');
		
		if (!isset($data['success'])) {
			$data['success'] = '';
		}
		
		$this->response->setOutput(
				$this->renderView('unzercw/transaction/refund_view', $data, array(
					'common/header',
					'common/footer' 
				)));
	}

	protected function getList(){
		$data = array();
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false 
		);
		
		$data['breadcrumbs'][] = array(
			'text' => UnzerCw_Language::_('Unzer Transactions'),
			'href' => $this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: ' 
		);
		
		$this->document->setTitle(UnzerCw_Language::_('Unzer Transactions'));
		$this->document->addStyle('view/stylesheet/unzercw_grid.css');
		
		$data['heading_title'] = UnzerCw_Language::_('Unzer Transactions');
		
		$adapter = new Customweb_Grid_DataAdapter_DriverAdapter(UnzerCw_Entity_Transaction::getGridQuery(), 
				UnzerCw_Util::getDriver());
		$loader = new Customweb_Grid_Loader();
		$loader->setDataAdapter($adapter);
		$loader->setRequestData($_GET);
		$loader->addColumn(new Customweb_Grid_Column('transactionId', '#'))->addColumn(
				new Customweb_Grid_Column('transactionExternalId', UnzerCw_Language::_('Transaction Number')))->addColumn(
				new Customweb_Grid_Column('orderId', UnzerCw_Language::_('Order ID')))->addColumn(
				new Customweb_Grid_Column('paymentMachineName', UnzerCw_Language::_('Payment Method')))->addColumn(
				new UnzerCw_Grid_TransactionStatusColumn('authorizationStatus', UnzerCw_Language::_('Authorization Status')))->addColumn(
				new Customweb_Grid_Column('createdOn', UnzerCw_Language::_('Created On'), 'DESC'))->addColumn(
				new UnzerCw_Grid_TransactionActionColumn('actions', UnzerCw_Language::_('Actions')));
		
		$renderer = new UnzerCw_Grid_Renderer($loader, 
				$this->url->link('unzercw/transaction', 'user_token=' . $this->session->data['user_token'], 'SSL'));
		$renderer->setGridId('transaction-grid');
		$data['grid'] = $renderer->render();
		
		if (!isset($data['success'])) {
			$data['success'] = '';
		}
		
		$this->response->setOutput(
				$this->renderView('unzercw/transaction/list', $data, array(
					'common/header',
					'common/footer' 
				)));
	}
}