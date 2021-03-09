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

require_once 'Customweb/Cron/Processor.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/AbstractController.php';


class ControllerUnzerCwCron extends UnzerCw_AbstractController
{

	/**
	 * This method must be called by a cron task. The call can be done by anyone!
	 * 
	 */
	public function cron() {
		$cron = new Customweb_Cron_Processor(UnzerCw_Util::getContainer());
		$cron->run();
	}
	
}