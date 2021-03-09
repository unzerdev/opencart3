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

require_once 'Customweb/Core/Http/ContextRequest.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/HttpRequest.php';


class UnzerCw_HttpRequest extends Customweb_Core_Http_ContextRequest {
	
	private static $instance = null;
	
	/**
	 * @return Customweb_Core_Http_ContextRequest
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new UnzerCw_HttpRequest();
		}
		return self::$instance;
	}
	
	public function getParameters() {
		return UnzerCw_Util::getFormData(array_merge($_GET, $_POST));
	}
	
	public function getParsedBody() {
		return UnzerCw_Util::getFormData($_POST);
	}

	public function getParsedQuery() {
		return UnzerCw_Util::getFormData($_GET);
	}
	
}