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
require_once 'UnzerCw/AbstractController.php';


class UnzerCw_LayoutController extends UnzerCw_AbstractController
{
	/**
	 * @var Customweb_Mvc_Layout_IRenderContext
	 */
	private $context = null;
	
	public function __construct(Customweb_Mvc_Layout_IRenderContext $context) {
		parent::__construct(UnzerCw_Util::getRegistry());
		$this->context = $context;
	}
	
	public function getContent() {
		
		$data = array();
		header('Content-Type: text/html; charset=UTF-8');
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
			'separator' => false
		);
		
		// Translations:
		$data['heading_title'] = $this->context->getTitle();
		$data['main_content'] = $this->context->getMainContent();
		
		foreach ($this->context->getCssFiles() as $css) {
			$this->document->addStyle($css);
		}
		foreach ($this->context->getJavaScriptFiles() as $js) {
			$this->document->addScript($js);
		}
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/unzercw/default.twig')) {
			$template = $this->config->get('config_template') . '/template/unzercw/default';
		}
		else {
			$template = 'default/template/unzercw/default';
		}
		
		return $this->renderView($template, $data, array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		));
	}
	
}