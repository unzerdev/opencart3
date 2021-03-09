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

require_once 'Customweb/Grid/Renderer.php';

require_once 'UnzerCw/Language.php';


class UnzerCw_Grid_Renderer extends Customweb_Grid_Renderer {
	
	public function getFilterControlCssClass() {
		return 'form-control';
	}
	
	public function getHrefCssClass() {
		return 'ajax-event';
	}
	
	public function getGridCssClass() {
		return 'grid ajax-pane';
	}
	
	public function getTableCssClass() {
		return 'table table-bordered table-hover';
	}
	
	public function getPageCssClass() {
		return 'pagination col-lg-6';
	}
	
	public function getInfoBoxCssClass() {
		return 'info-box col-lg-4';
	}
	
	public function getResultSelectorButtonCssClass() {
		return 'btn btn-success';
	}
	
	public function getResultSelctorWrapperCssClass() {
		return 'col-lg-2 input-group'; 
	}
	
	public function renderResultSelectorButton() {
		return '<div class="input-group-btn">' . parent::renderResultSelectorButton() . '</div>';
	}
	
	public function getInfoPattern() {
		return UnzerCw_Language::_('Showing !startingItem to !endingItem of !totalItems items.');
	}
	
	public function getSubmitButtonLabel() {
		return UnzerCw_Language::_('Apply');
	}
	
	protected function renderFilters() {
		$html = '';
	
		$html .= '<tr class="filter">';
		foreach ($this->getLoader()->getColumns() as $column) {
			$html .= $this->renderFilter($column);
		}
		$html .= '</tr>';
	
		return $html;
	}
	
}