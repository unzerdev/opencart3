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
require_once 'abstractsetting.php';

require_once 'Customweb/Core/Stream/Input/File.php';
require_once 'Customweb/Core/Util/Xml.php';

require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/OrderStatus.php';
require_once 'UnzerCw/Store.php';


class ModelUnzerCwSetting extends ModelUnzerCwAbstractSetting
{

	public function render(UnzerCw_SettingApi $api) {
		$this->api = $api;
		$output = '<div class="tab-content">';
		foreach ($this->getStoresIncludingDefaultStore() as $store) {
			$output .= $this->renderStoreSettings($store['store_id']);
		}
	
		$output .= '</div>';
		return $output;
	}
	
	protected function renderPreSettingForm($storeId) {
		$classes = 'tab-pane ';
		if ($storeId == 0) {
			$classes .= 'active ';
		}
		return '<div role="tabpanel" class="' . $classes . '" id="tab-store-' . $storeId . '">';
	}
	
	protected function renderPostSettingForm($storeId) {
		return '</div>';
	}
	
	public function renderStoreTabs($baseurl) {
		$output = '<ul class="nav nav-tabs" role="tablist">';
		$first = true;
		foreach ($this->getStoresIncludingDefaultStore() as $store) {
			$output .= '<li role="presentation"';
			if ($first) {
				$output .= ' class="active"';
				$first = false;
			}
			$output .= '><a href="' . $baseurl . '#tab-store-' . $store['store_id'] . '" data-toggle="tab" role="tab">' . $store['name'] . '</a></li>';
		}
		$output .= '</ul>';
		
		return $output;
	}
	
	protected function renderElement($item, $controlHtml, $storeId) {
		$isDefault = false;
		$formGroupClasses = 'form-group required control-box-wrapper';
		if (UnzerCw_Store::DEFAULT_STORE_ID != $storeId) {
			$defaultValue = $this->api->getValue($item['key'], UnzerCw_Store::DEFAULT_STORE_ID);
			if ($defaultValue == $item['value']) {
				$isDefault = true;
				$formGroupClasses .= ' unzercw-use-default';
			}
		}
		$output = '<div class="' . $formGroupClasses . '">';
		
		$output .= '<label class="col-sm-2 control-label" for="' . $this->getControlId($item, $storeId) . '">
				<span data-toggle="tooltip" data-container="#tab-general" 
				title="" data-original-title="' . UnzerCw_Language::_($item['description']) . '">' . UnzerCw_Language::_($item['title']) . '</span></label>';
		
		// Add default checkbox
		if (UnzerCw_Store::DEFAULT_STORE_ID != $storeId) {
			
			$checked = '';
			$classes = 'unzercw-control-box';
			if ($isDefault) {
				$checked = ' checked="checked" ';
			}
			
			$id = 'use-default-' . $item['key'] . '-' . $storeId;
			$output .= '<div class="col-sm-10 ' . $classes . '">' . $controlHtml . '</div>
				<div class="col-sm-offset-2 col-sm-10">
					<div class="checkbox">
						<label class="unzercw-default-box">
							<input type="checkbox" id="' . $id .'"  name="use_default[' . $item['key'] . '][' . $storeId . ']" value="true" ' . $checked . ' />
							' .  UnzerCw_Language::_('Use default store value') . '
						</label>
					</div>
				</div>';
		}
		else {
			$output .= '<div class="col-sm-10">' . $controlHtml . '</div>';
		}
		
		$output .= '</div>';
		return $output;
	}
	
	protected function renderSelect(array $item, $storeId) {
		$output = '<select class="form-control" name="' . $item['key'] . '[' . $storeId . ']" id="' . $this->getControlId($item, $storeId) . '">';
	
		foreach ($item['options'] as $key => $value) {
				
			$key = (string)$key;
			$item['value'] = (string)$item['value'];
				
			$output .= '<option value="' . $key . '"';
			if ($item['value'] === $key) {
				$output .= ' selected="selected" ';
			}
			$output .= '>' . $value . '</option>';
		}
	
		$output .= '</select>';
		return $output;
	}
	
	protected function renderTextfield(array $item, $storeId) {
		return '<input class="form-control" type="text" name="' . $item['key'] . '[' . $storeId . ']" id="' . $this->getControlId($item, $storeId) . '" value="' . Customweb_Core_Util_Xml::escape($item['value']) . '" />';
	}
	
	protected function renderMultiselect(array $item, $storeId) {
		$output = '<select class="form-control" name="' . $item['key'] . '[' . $storeId . '][]" id="' . $this->getControlId($item, $storeId) . '" multiple="multiple">';
	
		foreach ($item['options'] as $key => $value) {
			$output .= '<option value="' . $key . '"';
			if ( in_array($key, $item['value'])) {
				$output .= ' selected="selected" ';
			}
			$output .= '>' . $value . '</option>';
		}
	
		$output .= '</select>';
		return $output;
	}
	
	protected function renderPassword(array $item, $storeId) {
		return $this->renderTextfield($item, $storeId);
	}
	
	protected function renderMultiLangfield(array $item, $storeId) {
		$output = '';
		if (!is_array($item['value'])) {
			$item['value'] = array($item['value']);
		}
		foreach (UnzerCw_Util::getLanguages() as $language) {
			$langId = $language['language_id'];
			
			if (isset($item['value'][$langId])) {
				$value = $item['value'][$langId];
			}
			else {
				$value = current($item['value']);
			}
			$output .= strtoupper($language['code']) . ': ' .
				'<input class="form-control" type="text" id="' . $this->getControlId($item, $storeId) . '-' . $langId . '" name="' . $item['key'] .'[' . $storeId . '][' . $langId . ']" value="' . Customweb_Core_Util_Xml::escape($value) . '" /> <br />';
		}
		return $output;
	}
	
	protected function renderTextarea(array $item, $storeId) {
		return '<textarea class="form-control" id="' . $this->getControlId($item, $storeId) . '" name="' . $item['key'] . '[' . $storeId . ']">' . Customweb_Core_Util_Xml::escape($item['value']) . '</textarea>';
	}
	
	protected function renderOrderStatusSelect(array $item, $storeId) {
		if (!isset($item['options'])) {
			$item['options'] = array();
		}		
		$item['options'] = $item['options'] + UnzerCw_OrderStatus::getOrderStatuses();
		return $this->renderSelect($item, $storeId);
	}

	protected function renderOrderStatusMultiselect(array $item, $storeId) {
		if (!isset($item['options'])) {
			$item['options'] = array();
		}
		$item['options'] = $item['options'] + UnzerCw_OrderStatus::getOrderStatuses();
		return $this->renderMultiselect($item, $storeId);
	}

	protected function renderFile(array $item, $storeId) {
		$output =  '<input class="form-control" type="file" name="' . $item['key'] . '___' . $storeId . '" id="' . $this->getControlId($item, $storeId) . '"  />';
		
		$output .= '<div class="reset-default-checkbox"><input type="checkbox" name="reset[' . $item['key'] . '][' . $storeId . ']" id="reset-setting-' . $item['key'] . '-' . $storeId .'" value="reset" />';
		$output .= '<label for="reset-setting-' . $item['key'] . '-' . $storeId .'">' . UnzerCw_Language::_('Reset') . '</label></div>';
		
		$stream = $item['value'];
		if ($stream instanceof Customweb_Core_Stream_Input_File) {
			$output .= '<div class="current-file-path">' . UnzerCw_Language::_('File Path: ') . $stream->getFilePath() . '</div>';
		}
		
		return $output;
	}
		

	
	
}