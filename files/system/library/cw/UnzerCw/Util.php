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

require_once 'Customweb/DependencyInjection/Container/Default.php';
require_once 'Customweb/Asset/Resolver/Composite.php';
require_once 'Customweb/Util/Html.php';
require_once 'Customweb/Cache/Backend/Memory.php';
require_once 'Customweb/Asset/Resolver/Simple.php';
require_once 'Customweb/Core/Url.php';
require_once 'Customweb/Payment/Authorization/DefaultPaymentCustomerContext.php';
require_once 'Customweb/DependencyInjection/Bean/Provider/Annotation.php';
require_once 'Customweb/Database/Migration/Manager.php';
require_once 'Customweb/DependencyInjection/Bean/Provider/Editable.php';
require_once 'Customweb/Core/Util/Class.php';
require_once 'Customweb/Payment/Authorization/IAdapterFactory.php';

require_once 'UnzerCw/EntityManager.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/Adapter/IAdapter.php';
require_once 'UnzerCw/Store.php';
require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/DatabaseDriver.php';
require_once 'UnzerCw/Entity/PaymentCustomerContext.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/Template.php';


final class UnzerCw_Util {

	private static $baseModule = null;
	private static $encodingSetting = null;
	private static $countries = null;
	private static $currencies = null;
	private static $languages = null;
	private static $zones = null;
	private static $container = null;
	private static $entityManager = null;
	private static $driver = null;
	private static $paymentCustomerContexts = array();
	private static $baseUrls = array();
	private static $fileUploadDir = null;
	private static $endpointAdapter = null;
	private static $resolver = null;
	private static $registry = null;
	private static $mockedCartProducts = array();
	
	private function __construct() {}

	public static function getUrl($controller, $action = '', array $params = array(), $sslActive = true, $space = 'unzercw') {

		$registry = self::getRegistry();
		$ssl = '';
		if ($sslActive) {
			$ssl = 'SSL';
		}
		$parameters = Customweb_Core_Url::parseArrayToString($params);
		
		$config = UnzerCw_Store::getStoreConfigs();
		if (isset($config['config_use_ssl'])) {
			$url = new Url($config['config_url'], $config['config_use_ssl'] ? $config['config_ssl'] : $config['config_url']);
		}
		else {
			$url = new Url($config['config_url'], $config['config_secure'] ? $config['config_ssl'] : $config['config_url']);
		}
		
		if (class_exists('MijoShop')) {
			$url->addRewrite(MijoShop::get('router'));
		}
		
		if (empty($action)) {
			$url = str_replace('&amp;', '&', $url->link($space . '/' . $controller, $parameters, $ssl));
		}
		else {
			$url = str_replace('&amp;', '&', $url->link($space . '/' . $controller . '/' . $action, $parameters, $ssl));
		}
		

		// Add option and Itemid to the URL in case they are set (Joomla). 
		if (isset($_GET['option']) && isset($_GET['Itemid'])) {
			$p = array('option' => $_GET['option'], 'Itemid' => $_GET['Itemid']);
			$url = str_replace('index.php?route=', 'index.php?' . Customweb_Core_Url::parseArrayToString($p) . '&route=', $url);
		}
		
		return $url;
	}
	
	public static function getRegistry() {
		if (isset($GLOBALS['registry']) && $GLOBALS['registry'] instanceof Registry) {
			// deprecated as of 2.3.x, setRegistry call required
			return $GLOBALS['registry'];
		}
		else if (self::$registry !== null) {
			return self::$registry;
		}
		else {
			return CwRegistryHolder::getRegistry();
		}
	}
	
	public static function setRegistry($registry) {
		if ($registry !== null && $registry instanceof Registry) {
			CwRegistryHolder::setRegistry($registry);
			self::$registry = $registry;
		}
		else {
			throw new Exception("The registry could not be set.");
		}
	}
	
	public static function getDatabaseObject() {
		if (isset($GLOBALS['db'])) {
			return $GLOBALS['db'];
		}
		else {
			$db = self::getRegistry()->get('db');
			if (isset($db)) {
				return $db;
			}
		}
		throw new Exception("Could not find database object in global space.");
	}
	
	public static function getFileUploadDir() {
		if (self::$fileUploadDir === null) {
			$base = dirname(DIR_SYSTEM) . '/';
			self::$fileUploadDir = $base . 'upload/unzercw/';
		}
		
		return self::$fileUploadDir;
	}

	public static function isAliasManagerActive(Customweb_Payment_Authorization_IOrderContext $orderContext) {
		$paymentMethod = $orderContext->getPaymentMethod();
		$customerId = trim($orderContext->getCustomerId());
		if ($paymentMethod->existsPaymentMethodConfigurationValue('alias_manager') && strtolower($paymentMethod->getPaymentMethodConfigurationValue('alias_manager')) == 'active'
			&& !empty($customerId)) {
			return true;
		}
		else {
			return false;
		}
	}

	public static function getOrderTotals($registry) {

		// Load total extensions
		$registry->get('load')->model('setting/extension');
		$totalExtensions = $registry->get('model_setting_extension')->getExtensions('total');
		
		$orderedKeys = array();
		foreach ($totalExtensions as $key => $value) {
			$orderedKeys[$key] = $registry->get('config')->get('total_'.$value['code'] . '_sort_order');
		}
		array_multisort($orderedKeys, SORT_ASC, $totalExtensions);

		// Calculate the current totals
		$resolvedData = self::buildOrderTotalData($registry, $totalExtensions);
		
		
		$taxAmounts = $resolvedData['taxAmounts'];
		$totalData = $resolvedData['totalData'];

		// Calculate the tax rates (aggregated per position)
		foreach ($totalData as $id => $data) {
			$key = $data['code'];
			$taxRate = 0;

			$totalData[$id]['value'] = self::convertTo(
				$totalData[$id]['value'],
				UnzerCw_Util::getRegistry()->get('session')->data['currency']
			);

			if (isset ($taxAmounts[$key]) && $taxAmounts[$key] > 0) {
				$taxAmounts[$key] = self::convertTo(
					$taxAmounts[$key],
					UnzerCw_Util::getRegistry()->get('session')->data['currency']
				);
				$taxRate = round(abs($taxAmounts[$key] / $totalData[$id]['value'] * 100), 4);
			}
			$totalData[$id]['tax_rate'] = $taxRate;

		}

		return $totalData;
	}

	private static function buildOrderTotalData($registry, $totalExtensions) {
		$totalAmount = 0;
		$totalTaxes = array();
		$totalTotals = array();
		$total = array(
			'total' => &$totalAmount,
			'taxes' => &$totalTaxes,
			'totals' => &$totalTotals,
		);
		$taxAmounts = array();
		$previousTotalTax = 0;
		foreach ($totalExtensions as $extension) {
			if ($registry->get('config')->get('total_'.$extension['code'] . '_status')) {
				$extensionModel = 'extension/total/' . $extension['code'];
				
				$registry->get('load')->model($extensionModel);
				$registry->get('model_' . str_replace("/", '_', $extensionModel))->getTotal($total);
				
				$totalTax = 0;
				foreach ($total['taxes'] as $value) {
					$totalTax += $value;
				}
				$taxAmounts[$extension['code']] = $totalTax - $previousTotalTax;
				$previousTotalTax = $totalTax;
			}
		}
		return array(
			'taxAmounts' => $taxAmounts,
			'totalData' => $total['totals'],
		);
		
	}
	
	public static function convertTo($amount, $currency) {
		return self::getRegistry()->get('currency')->getValue($currency) * $amount;
	}

	public static function getCurrencies() {
		if (self::$currencies === null) {
			self::$currencies = array ();
			$db = UnzerCw_Database::getInstance();
			$query = $db->query("SELECT * FROM " . DB_PREFIX . "currency ORDER BY title ASC");
			while ($row = $db->fetch($query)) {
				self::$currencies[$row['currency_id']] = $row;
			}
		}

		return self::$currencies;
	}

	/**
	 * @return Customweb_DependencyInjection_Container_Default
	 */
	public static function getContainer() {
		if (self::$container === null) {

			$packages = array(
			0 => 'Customweb_Unzer',
 			1 => 'Customweb_Payment_Authorization',
 		);
			$packages[] = 'UnzerCw_';
			$packages[] = 'Customweb_Payment_Alias_Handler';
			$packages[] = 'Customweb_Payment_Alias';
			$packages[] = 'Customweb_Payment_Update';
			$packages[] = 'Customweb_Payment_TransactionHandler';
			$packages[] = 'UnzerCw_LayoutRenderer';
			$packages[] = 'UnzerCw_EndpointAdapter';
			$packages[] = 'Customweb_Payment_SettingHandler';
			$packages[] = 'Customweb_Storage_Backend_Database';
			$packages[] = 'UnzerCw_TemplateRenderer';
				
			$provider = new Customweb_DependencyInjection_Bean_Provider_Editable(new Customweb_DependencyInjection_Bean_Provider_Annotation(
					$packages
			));

			$provider
				->addObject(self::getEntityManager())
				->addObject(UnzerCw_HttpRequest::getInstance())
				->addObject(self::getDriver())
				->addObject(self::getAssetResolver())
				->add('databaseTransactionClassName', 'UnzerCw_Entity_Transaction')
				->add('storageDatabaseEntityClassName', 'UnzerCw_Entity_Storage');
				;

			self::$container = new Customweb_DependencyInjection_Container_Default($provider);
		}

		return self::$container;
	}

	/**
	 * @return Customweb_Database_Entity_Manager
	 */
	public static function getEntityManager() {
		if (self::$entityManager === null) {
			$cache = new Customweb_Cache_Backend_Memory();
			self::$entityManager = new UnzerCw_EntityManager(self::getDriver(), $cache);
		}
		return self::$entityManager;
	}
	
	/**
	 *
	 * @return Customweb_Payment_ITransactionHandler
	 */
	public static function getTransactionHandler(){
		$container = self::getContainer();
		$handler = $container->getBean('Customweb_Payment_ITransactionHandler');
		return $handler;
	}
	
	/**
	 * @return Customweb_Storage_IBackend
	 */
	public static function getStorageAdapter() {
		return self::getContainer()->getBean('Customweb_Storage_IBackend');
	}

	/**
	 * @return Customweb_Database_Driver_PDO_Driver
	 */
	public static function getDriver() {
		if (self::$driver === null) {
			self::$driver = new UnzerCw_DatabaseDriver();
		}
		return self::$driver;
	}

	/**
	 * @throws Exception
	 * @return Customweb_Payment_Authorization_IAdapterFactory
	 */
	public static function getAuthorizationAdapterFactory() {
		$factory = self::getContainer()->getBean('Customweb_Payment_Authorization_IAdapterFactory');

		if (!($factory instanceof Customweb_Payment_Authorization_IAdapterFactory)) {
			throw new Exception("The payment api has to provide a class which implements 'Customweb_Payment_Authorization_IAdapterFactory' as a bean.");
		}

		return $factory;
	}

	/**
	 * @param Customweb_Payment_Authorization_IAdapter $paymentAdapter
	 * @throws Exception
	 * @return UnzerCw_Adapter_IAdapter
	 */
	public static function getShopAdapterByPaymentAdapter(Customweb_Payment_Authorization_IAdapter $paymentAdapter) {
		$reflection = new ReflectionClass($paymentAdapter);
		$adapters = self::getContainer()->getBeansByType('UnzerCw_Adapter_IAdapter');
		foreach ($adapters as $adapter) {
			if ($adapter instanceof UnzerCw_Adapter_IAdapter) {
				$inferfaceName = $adapter->getPaymentAdapterInterfaceName();
				try {
					Customweb_Core_Util_Class::loadLibraryClassByName($inferfaceName);
					if ($reflection->implementsInterface($inferfaceName)) {
						$adapter->setInterfaceAdapter($paymentAdapter);
						return $adapter;
					}
				}
				catch(Customweb_Core_Exception_ClassNotFoundException $e) {
					// Ignore
				}
			}
		}

		throw new Exception("Could not resolve to Shop adapter.");
	}

	public static function getZones() {
		if (self::$zones === null) {
			self::$zones = array ();
			$db = UnzerCw_Database::getInstance();
			$query = $db->query("SELECT * FROM " . DB_PREFIX . "geo_zone ORDER BY name ASC");
			while ($row = $db->fetch($query)) {
				self::$zones[$row['geo_zone_id']] = $row;
			}
		}

		return self::$zones;
	}

	public static function getLanguages() {
		return UnzerCw_Language::getLanguages();
	}

	public static function log($message, $type) {
		$message = '[' . strtoupper($type) . '] ' . $message;
		self::getRegistry()->get('log')->write($message);
	}
	
	public static function migrate() {

		$migration = new Customweb_Database_Migration_Manager(UnzerCw_Util::getDriver(), dirname(__FILE__) . '/Migration/', DB_PREFIX.'unzercw_schema_version');
		$migration->migrate();
		
		$registry = self::getRegistry();
		
		$registry->get('load')->model('user/user_group');
		$registry->get('model_user_user_group')->addPermission($registry->get('user')->getId(), 'access', 'unzercw/transaction');
		$registry->get('model_user_user_group')->addPermission($registry->get('user')->getId(), 'modify', 'unzercw/transaction');
	}
	

	/**
	 * @param int $customerId
	 * @return Customweb_Payment_Authorization_IPaymentCustomerContext
	 */
	public static function getPaymentCustomerContext($customerId) {
		// Handle guest context. This context is not stored.
		if ($customerId === null || $customerId === 0 || $customerId === '0') {
			if (!isset(self::$paymentCustomerContexts['guestContext'])) {
				self::$paymentCustomerContexts['guestContext'] = new Customweb_Payment_Authorization_DefaultPaymentCustomerContext(array());
			}
				
			return self::$paymentCustomerContexts['guestContext'];
		}
	
		if (!isset(self::$paymentCustomerContexts[$customerId])) {
			$entities = self::getEntityManager()->searchByFilterName('UnzerCw_Entity_PaymentCustomerContext', 'loadByCustomerId', array(
				'>customerId' => $customerId,
			));
			if (count($entities) > 0) {
				self::$paymentCustomerContexts[$customerId] = current($entities);
			}
			else {
				$context = new UnzerCw_Entity_PaymentCustomerContext();
				$context->setCustomerId($customerId);
				self::$paymentCustomerContexts[$customerId] = $context;
			}
		}
		return self::$paymentCustomerContexts[$customerId];
	}
	
	public static function persistPaymentCustomerContext(Customweb_Payment_Authorization_IPaymentCustomerContext $context) {
		if ($context instanceof UnzerCw_Entity_PaymentCustomerContext) {
			$storedContext = self::getEntityManager()->persist($context);
			self::$paymentCustomerContexts[$storedContext->getCustomerId()] = $storedContext;
		}
	}
	
	/**
	 * @return UnzerCw_EndpointAdapter
	 */
	public static function getEndpointAdapter() {
		return self::getContainer()->getBean('UnzerCw_EndpointAdapter');
	}
	
	/**
	 * @return Customweb_Payment_Alias_Handler
	 */
	public static function getAliasHandler() {
		return self::getContainer()->getBean('Customweb_Payment_Alias_Handler');
	}
	
	/**
	 * This method returns the form data entered.
	 * 
	 * @return array
	 */
	public static function getFormData($params = null) {
		if ($params === null) {
			$params = $_REQUEST;
		}
		
		foreach ($params as $key => $value) {
			if (is_string($value)) {
				$params[$key] = Customweb_Util_Html::unescapeXml($value);
			}
			else if (is_array($value)) {
				$params[$key] = self::getFormData($value);
			}
		}
		return $params;
	}
	
	
	/**
	 * @return Customweb_Asset_IResolver
	 */
	public static function getAssetResolver() {
		if (self::$resolver === null) {
			
			// We are in the backend:
			if (defined('DIR_CATALOG')) {
				$templatePath = DIR_CATALOG . 'view/theme/';
			}
			else {
				$templatePath = DIR_TEMPLATE;
			}
			
			// Path part relative to the webroot (e.g. /catalog/view/theme/)
			$relativeTemplatePathPart = str_replace(dirname(DIR_SYSTEM), '', $templatePath);
			
			$addendum = UnzerCw_Template::getBasePath();
			if($addendum) {
				$addendum = '/' . $addendum;
			}
			
			$defaultTemplatePath = UnzerCw_Template::getBasePath() . $addendum . $templatePath . 'default/';
			$defaultTemplateUrl = UnzerCw_Store::getStoreBaseUrl() . $addendum  . $relativeTemplatePathPart . 'default/';
			
			$configs = UnzerCw_Store::getStoreConfigs();
			
			if (isset($configs['config_template'])) {
				$currentTemplate = $configs['config_template'];
			}
			else if (isset($configs['config_theme'])){
				$currentTemplate = $configs['config_theme'];
				if ($currentTemplate == 'theme_default') {
					$currentTemplate = 'default';
				}
			}
			else {
				$currentTemplate = 'default';
			}
			
			
			$currentTemplatePath = $addendum . $templatePath . $currentTemplate . '/';
			$currentTemplateUrl = UnzerCw_Store::getStoreBaseUrl() . $addendum . $relativeTemplatePathPart . $currentTemplate . '/';
			
			
			self::$resolver = new Customweb_Asset_Resolver_Composite(array(
				new Customweb_Asset_Resolver_Simple(
						$currentTemplatePath . '/template/unzercw/snippets/',
						$currentTemplateUrl . '/template/unzercw/snippets/',
						array('application/x-twig')
				),
				new Customweb_Asset_Resolver_Simple(
						$currentTemplatePath . '/stylesheet/unzercw/',
						$currentTemplateUrl . '/stylesheet/unzercw/',
						array('text/css')
				),
				new Customweb_Asset_Resolver_Simple(
						$currentTemplatePath . '/javascript/unzercw/',
						$currentTemplateUrl . '/javascript/unzercw/',
						array('application/javascript')
				),
				new Customweb_Asset_Resolver_Simple(
						$currentTemplatePath . '/image/unzercw/',
						$currentTemplateUrl . '/image/unzercw/',
						array('image/png')
				),
				new Customweb_Asset_Resolver_Simple(
						$defaultTemplatePath . '/template/unzercw/snippets/',
						$defaultTemplateUrl . '/template/unzercw/snippets/',
						array('application/x-twig')
				),
				new Customweb_Asset_Resolver_Simple(
						$defaultTemplatePath . '/stylesheet/unzercw/',
						$defaultTemplateUrl . '/stylesheet/unzercw/',
						array('text/css')
				),
				new Customweb_Asset_Resolver_Simple(
						$defaultTemplatePath . '/javascript/unzercw/',
						$defaultTemplateUrl . '/javascript/unzercw/',
						array('application/javascript')
				),
				new Customweb_Asset_Resolver_Simple(
						$defaultTemplatePath . '/image/unzercw/',
						$defaultTemplateUrl . '/image/unzercw/',
						array('image/png')
				),
				new Customweb_Asset_Resolver_Simple(
						dirname(dirname($defaultTemplatePath)) . '/asset/unzercw/',
						dirname(dirname($defaultTemplateUrl)) . '/asset/unzercw/'
				),
			));
		}
	
		return self::$resolver;
	}
	
	/**
	 * Merges two arrays
	 * It keeps the values of the first array, unless the value is empty and the value of the second array is not
	 * @param array $first
	 * @param array $second
	 * @return array
	 */
	public static function mergeArray(array $first, array $second) {
		$result = array();
		foreach($first as $key => $value) {
			if(is_array($value)) {
				if(isset($second[$key]) && is_array($second[$key])) {
					$result[$key] = self::mergeArray($value, $second[$key]);
				}
				else {
					$result[$key] = $value;
				}
			}
			elseif(!($value === null || $value === '')) {
				$result[$key] = $value;
			}
			else {
				if(isset($second[$key])) {
					$secondValue = $second[$key];
					if(!($secondValue === null || $secondValue === '')){
						$result[$key] = $secondValue;
					}
					else {
						$result[$key] = $value;
					}
				}
				else {
					$result[$key] = $value;
				}
			}
		}
		foreach($second as $key => $value) {
			if(!isset($result[$key])) {
				$result[$key] = $value;
			}
		}
		return $result;
		
	}
	
	public static function setMockCartProducts($orderId) {
		$db = self::$registry->get('db');
		$query = $db->query('SELECT product_id FROM '.DB_PREFIX. 'order_product WHERE order_id=' . $db->escape($orderId));
		self::$mockedCartProducts = $query->rows;
	}
	
	public static function getMockCartProducts() {
		return self::$mockedCartProducts;
	}	
}
