<?php

/**
 *  * You are allowed to use this API in your web application.
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

require_once 'Customweb/Form/Control/MultiControl.php';
require_once 'Customweb/Form/Control/Radio.php';
require_once 'Customweb/Form/Control/SingleCheckbox.php';
require_once 'Customweb/Form/Control/Html.php';
require_once 'Customweb/Form/Control/MultiCheckbox.php';

require_once 'UnzerCw/Form/FrontendRenderer.php';
require_once 'UnzerCw/Configuration.php';
require_once 'UnzerCw/Store.php';

class UnzerCw_Form_BackendRenderer extends UnzerCw_Form_FrontendRenderer {
	private $configurationAdapter = null;

	public function __construct(){
		$this->configurationAdapter = new UnzerCw_Configuration();
	}

	protected function renderElementScope(Customweb_Form_IElement $element){
		if ($this->configurationAdapter->getStoreHierarchy() === null ||
				 UnzerCw_Store::getStoreId() == UnzerCw_Store::DEFAULT_STORE_ID) {
			return '';
		}
		return parent::renderElementScope($element);
	}

	public function renderElementAdditional(Customweb_Form_IElement $element){
		$output = '';
		
		$errorMessage = $element->getErrorMessage();
		if (!empty($errorMessage)) {
			$output .= $this->renderElementErrorMessage($element);
		}
		
		if (!$element->isGlobalScope()) {
			$output .= $this->renderElementScope($element);
		}
		
		return $output;
	}

	public function renderElementLabel(Customweb_Form_IElement $element){
		$for = '';
		if ($element->getControl() != null && $element->getControl()->getControlId() !== null && $element->getControl()->getControlId() != '') {
			$for = $element->getControl()->getControlId();
		}
		
		$cssClasses = $this->getCssClassPrefix() . $this->getElementLabelCssClass();
		$label = $element->getLabel();
		if ($element->isRequired()) {
			$cssClasses .= ' required';
		}
		
		$description = $element->getDescription();
		if (!empty($description)) {
			$label = '<span data-toggle="tooltip" data-container="#tab-general" title="' . $description . '">' . $label . '</span>';
		}
		
		return $this->renderLabel($for, $label, $cssClasses);
	}

	public function getFormCssClass(){
		return 'form-horizontal';
	}

	public function getElementScopeCssClass(){
		return 'col-sm-offset-3 col-sm-9';
	}

	public function getControlCssClass(){
		return 'controls col-sm-9' . ($this->isStaticControl ? ' form-control-static' : '');
	}

	protected function renderStopEventJavaScript(){
		return '		function stopEvent(e) {
			if ( e.stopPropagation ) { e.stopPropagation(); }
			e.cancelBubble = true;
			if ( e.preventDefault ) { e.preventDefault(); } else { e.returnValue = false; }
			if ( typeof validationFailed == "function") { validationFailed(); }
			return false;
		}
	';
	}

	public function renderElementGroupPrefix(Customweb_Form_IElementGroup $elementGroup){
		return '<div class="panel panel-default">';
	}

	public function renderElementGroupPostfix(Customweb_Form_IElementGroup $elementGroup){
		return '</div></div>';
	}

	public function renderElementGroupTitle(Customweb_Form_IElementGroup $elementGroup){
		$output = '';
		$title = $elementGroup->getTitle();
		if (!empty($title)) {
			$cssClass = $this->getCssClassPrefix() . $this->getElementGroupTitleCssClass();
			$output .= '<div class="panel-heading ' . $cssClass . '">' . $title . '</div>';
		}
		$output .= '<div class="panel-body">';
		return $output;
	}

	public function renderOptionPrefix(Customweb_Form_Control_IControl $control, $optionKey){
		$optionCssClass = '';
		if ($control instanceof Customweb_Form_Control_Radio) {
			$optionCssClass = 'radio';
		}
		elseif ($control instanceof Customweb_Form_Control_MultiCheckbox || $control instanceof Customweb_Form_Control_SingleCheckbox) {
			$optionCssClass = 'checkbox';
		}
		return '<div class="' . $this->getCssClassPrefix() . $this->getOptionCssClass() . ' ' . $optionCssClass . '" id="' . $control->getControlId() .
				 '-' . $optionKey . '-key">';
	}

	public function renderControl(Customweb_Form_Control_IControl $control){
		if (!($control instanceof Customweb_Form_Control_MultiControl)) {
			$control->setCssClass($this->getControlCss($control));
		}
		$this->isStaticControl = ($control instanceof Customweb_Form_Control_Html);
		return $control->render($this);
	}

	public function getControlCss(Customweb_Form_Control_IControl $control){
		if ($control instanceof Customweb_Form_Control_Radio || $control instanceof Customweb_Form_Control_MultiCheckbox ||
				 $control instanceof Customweb_Form_Control_SingleCheckbox) {
			return $control->getCssClass();
		}
		return 'form-control ' . $control->getCssClass();
	}
	
	public function renderButton(Customweb_Form_IButton $button, $jsFunctionPostfix = '')
	{
		$postfix = $jsFunctionPostfix;
		if ($this->getNamespacePrefix() !== NULL) {
			$postfix = $this->getNamespacePrefix().$postfix;
		}
		$validation = 'cwValidationRequired'.$postfix.' = false;';
		if($button->isJSValidationExecuted()){
			$validation = 'cwValidationRequired'.$postfix.' = true;';
		}
		return '<button onclick="'.$validation.' submitBackendForm(\'' . $button->getMachineName() . '\', \'' . $button->getId() . '\');" class="'.$this->getButtonClasses($button).'" id="' . $button->getId() . '">' . $button->getTitle() . '</button>';
	}
	
	protected function renderButtons(array $buttons, $jsFunctionPostfix = '')
	{
		$postfix = $jsFunctionPostfix;
		if ($this->getNamespacePrefix() !== NULL) {
			$postfix = $this->getNamespacePrefix().$postfix;
		}
		$output = '<div class="col-sm-9 col-sm-offset-3 text-right">';
		foreach ($buttons as $button) {
			$output .= $this->renderButton($button, $jsFunctionPostfix);
		}
		
		$output .= '</div>
		<script type="text/javascript">
			function submitBackendForm(buttonName, buttonId) {
				var button = jQuery("#" + buttonId);
				var form = button.parents("form");
				form.append("<input type=\"hidden\" value=\"" + buttonName + "\" name=\"pressed_button\" />");
				cwValidateFields'.$postfix.'(function(){form.submit();},function(errors, valid){alert(errors[Object.keys(errors)[0]]);});
			}
		</script>';
		return $output;
	}

	
}
	
