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

require_once 'Customweb/Core/Http/Client/Socket.php';
require_once 'Customweb/Payment/Entity/AbstractTransaction.php';
require_once 'Customweb/Core/Http/Request.php';
require_once 'Customweb/Core/Logger/Factory.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Store.php';


/**
 *
 * @Entity(tableName = 'unzercw_transactions')
 *
 */
class UnzerCw_Entity_Transaction extends Customweb_Payment_Entity_AbstractTransaction
{
	private $storeId = null;
	
	protected function updateOrderStatus(Customweb_Database_Entity_IManager $entityManager, $currentStatus, $orderStatusSettingKey) {
		$modelFile  = DIR_APPLICATION . 'model/checkout/order.php';
		if (file_exists($modelFile)) {
			$this->updateOrderStatusFromFrontend($currentStatus);
		}
		else {
			$this->updateOrderStatusFromBackend($currentStatus);
		}
	}
	
	protected function authorize(Customweb_Database_Entity_IManager $entityManager) {
		if (version_compare(VERSION, '2.0.0.0') >= 0) {
			// We set everything over the status. Hence we do not need here any action.
		}
		else {
			if ($this->getTransactionObject()->isAuthorized()) {
				$statusKey = $this->getTransactionObject()->getOrderStatusSettingKey();
				$statusId = $this->getTransactionObject()->getPaymentMethod()->getPaymentMethodConfigurationValue($statusKey);
				$registry = UnzerCw_Util::getRegistry();
				$registry->get('load')->model('checkout/order');
				$registry->get('model_checkout_order')->confirm($this->getOrderId(), $statusId);
			}
		}
	}
	
	public function onAfterLoad(Customweb_Database_Entity_IManager $entityManager) {
		parent::onAfterLoad($entityManager);
		UnzerCw_Store::forceStoreId($this->getStoreId());
	}

	protected function generateExternalTransactionId(Customweb_Database_Entity_IManager $entityManager) {
		return $this->generateExternalTransactionIdAlwaysAppend($entityManager);
	}
	
	/**
	 * Returns the store id used to start this transaction.
	 * 
	 * @throws Exception
	 */
	public function getStoreId() {
		if ($this->storeId === null) {
			$orderId = $this->getOrderId();
			if (!empty($orderId)) {
				$rs = UnzerCw_Util::getDriver()->query("SELECT store_id FROM `" . DB_PREFIX . 'order` WHERE order_id = >orderId')->setParameter('>orderId', $orderId);
				if (($row = $rs->fetch()) !== false) {
					return $row['store_id'];
				}
			}
		}
		
		return $this->storeId;
	}

	/**
	 * 
	 * @param int $storeId
	 * @return UnzerCw_Entity_Transaction
	 */
	public function setStoreId($storeId){
		$this->storeId = $storeId;
		return $this;
	}
	
	public static function getGridQuery() {
		return 'SELECT * FROM ' . DB_PREFIX . 'unzercw_transactions WHERE ${WHERE} ${ORDER_BY} ${LIMIT}';
	}
	
	/**
	 *
	 * @return UnzerCw_Entity_Transaction
	 */
	public static function loadById($id, $cache = true) {
		return UnzerCw_Util::getEntityManager()->fetch('UnzerCw_Entity_Transaction', $id, $cache);
	}
	
	/**
	 * 
	 * @param string $orderId
	 * @param boolean $loadFromCache
	 * @return UnzerCw_Entity_Transaction[]
	 */
	public static function getTransactionsByOrderId($orderId, $loadFromCache = true) {
		return UnzerCw_Util::getEntityManager()->searchByFilterName('UnzerCw_Entity_Transaction', 'loadByOrderId', array('>orderId' => $orderId), $loadFromCache);
	}
	
	private function updateOrderStatusFromBackend($currentStatus) {
		$registry = UnzerCw_Util::getRegistry();
		$data = array();
		$data['order_status_id'] = $currentStatus;
		$data['comment'] = '';
		$data['notify'] = false;
		if (version_compare(VERSION, '2.0.0.0') >= 0) {
			try{
				$client = new Customweb_Core_Http_Client_Socket();
				$request = new Customweb_Core_Http_Request();
				$request->setUrl(HTTPS_SERVER . 'index.php?route=api/order/history');
				$data['order_id'] = $this->getOrderId();
				$request->setBody(json_encode($data));
				if(!isset($registry->get('session')->data['api_id'])) {
					$registry->get('session')->data['api_id'] = 'temp';
				}
				$response = $client->send($request);
				$json = json_decode($response->getBody());
				if($registry->get('session')->data['api_id'] == 'temp') {
					unset($registry->get('session')->data['api_id']);
				}
				if(isset($json['error'])){
					throw new Exception($json['error']);
				}
				
			}
			catch(Exception $e) {
				// TODO use $this->logger once payment api is released
				Customweb_Core_Logger_Factory::getLogger(get_class($this))->logException($e);
			}
		}
		else {
			$registry->get('load')->model('sale/order');
			$registry->get('model_sale_order')->addOrderHistory($this->getOrderId(), $data);
		}
	}
	
	private function updateOrderStatusFromFrontend($currentStatus) {
		$registry = UnzerCw_Util::getRegistry();
		UnzerCw_Util::setMockCartProducts($this->getOrderId());
		$registry->get('load')->model('checkout/order');
		if (version_compare(VERSION, '2.0.0.0') >= 0) {
			$registry->get('model_checkout_order')->addOrderHistory($this->getOrderId(), $currentStatus);
		}
		else {
			$registry->get('model_checkout_order')->update($this->getOrderId(), $currentStatus);
		}
	}
	
}
