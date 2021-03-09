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

require_once DIR_SYSTEM . '/library/cw/loader.php';
require_once 'Customweb/Core/Util/System.php';

class CwRegistryHolder {
	
	private static $registry;
	
	public static function setRegistry($registry) {
		if ($registry !== null && $registry instanceof Registry) {
			self::$registry = $registry;
		}
		else {
			throw new Exception("The registry could not be set.");
		}
	}
	
	public static function getRegistry() {
		if (self::$registry === null) {
			throw new Exception("The registry is not set.");
		}
		else {
			return self::$registry;
		}
	}
	
}
