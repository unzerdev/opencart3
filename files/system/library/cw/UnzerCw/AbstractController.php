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


class UnzerCw_AbstractController extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		require_once 'UnzerCw/Util.php';
		UnzerCw_Util::setRegistry($registry);
	}
	
	/**
	 * Render implementation which is able to handle the different implementation for 2.x and 1.x.
	 *
	 * @param string $template
	 * @param array $data
	 * @param array $children
	 */
	public function renderView($template, $data, array $children = array()){
		if (version_compare(VERSION, '2.0.0.0') >= 0) {
			$children[] = 'common/column_left';
			if (!isset($data['error_warning'])) {
				$data['error_warning'] = false;
			}
			foreach ($children as $child) {
				$data[basename($child)] = $this->load->controller($child);
			}
			
			if (version_compare(VERSION, '2.2.0.0') >= 0) {
				if (strrpos($template, 'default/template') === 0) {
					$template = substr($template, strlen('default/template'));
				}
			}
			
			return $this->load->view($template, $data);
		}
		else {
			$this->template = $template;
			$this->data = $data;
			$this->children = $children;
			return $this->render();
		}
	}
}
