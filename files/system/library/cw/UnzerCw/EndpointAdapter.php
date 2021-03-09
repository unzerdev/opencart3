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

require_once 'Customweb/Payment/Endpoint/AbstractAdapter.php';
require_once 'Customweb/Payment/Endpoint/IAdapter.php';

require_once 'UnzerCw/Form/FrontendRenderer.php';
require_once 'UnzerCw/Util.php';


/**
 * 
 * @author Thomas Hunziker
 * @Bean
 */
class UnzerCw_EndpointAdapter extends Customweb_Payment_Endpoint_AbstractAdapter implements Customweb_Payment_Endpoint_IAdapter
{
	protected function getBaseUrl() {
		return UnzerCw_Util::getUrl('endpoint');
	}
	
	protected function getControllerQueryKey() {
		return 'p_c';
	}
	
	protected function getActionQueryKey() {
		return 'p_a';
	}
	
	public function getFormRenderer() {
		$renderer = new UnzerCw_Form_FrontendRenderer();
		$renderer->setCssClassPrefix('unzercw-');
		return $renderer;
	}
}