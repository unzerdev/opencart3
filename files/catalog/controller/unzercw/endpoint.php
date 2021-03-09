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

require_once 'Customweb/Payment/Endpoint/Dispatcher.php';
require_once 'Customweb/Core/Http/Response.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/AbstractController.php';



class ControllerUnzerCwEndpoint extends UnzerCw_AbstractController
{ 
	public function index()
	{
		header_remove('set-cookie');
		$dispatcher = new Customweb_Payment_Endpoint_Dispatcher(UnzerCw_Util::getEndpointAdapter(), UnzerCw_Util::getContainer(), array(
			0 => 'Customweb_Unzer',
 			1 => 'Customweb_Payment_Authorization',
 		));
		$response = new Customweb_Core_Http_Response($dispatcher->dispatch(UnzerCw_HttpRequest::getInstance()));
		$response->send();
		die();
	}
}
