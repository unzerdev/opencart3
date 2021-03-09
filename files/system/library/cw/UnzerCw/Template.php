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



require_once 'UnzerCw/Util.php';


final class UnzerCw_Template {
	
	private function __construct() {}
	
	const PAYMENT_FORM_TEMPLATE = 'template/unzercw/payment_form';
		
	
	public static function resolveTemplatePath($path) {
		if (strrpos($path, 'template') === 0) {
			return substr($path, strlen('template'));
		}
		return $path;
	}
	
	public static function getBasePath(){
		if (class_exists('MijoShop')) {
			return 'components/com_mijoshop/opencart/';
		}
		if(class_exists('AceShop')) {
			return 'components/com_aceshop/opencart/';
		}
		return '';
	}
	
	public static function includeJavaScriptFile($path) {
		$basePath = self::getBasePath();
		
		return '<script type="text/javascript" src="' . $basePath . 'catalog/view/javascript/' . $path . '"></script>';
	}
	
	public static function includeCSSFile($path) {
		$basePath = self::getBasePath();
		
		$config = UnzerCw_Util::getRegistry()->get('config');
		
		$path = 'stylesheet/' . $path;
		if (file_exists(DIR_TEMPLATE . $config->get('config_template') . '/' . $path)) {
			$path = 'catalog/view/theme/' . $config->get('config_template') . '/' . $path;
		}
		else {
			$path = 'catalog/view/theme/default/' . $path;
		}
		
		return '<link rel="stylesheet" type="text/css" href="' . $basePath . $path . '" />';
	}
	
	
}
