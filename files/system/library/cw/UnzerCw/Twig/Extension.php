<?php

require_once 'Customweb/Core/Util/System.php';
require_once 'Customweb/Util/Currency.php';
require_once 'Customweb/Core/Util/Html.php';

require_once 'UnzerCw/Language.php';
require_once 'UnzerCw/Template.php';


class UnzerCw_Twig_Extension extends \Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        return array(
        	new \Twig\TwigFunction('UnzerCw_Translate', function($translate) {
        		return UnzerCw_Language::_($translate);
        	}, array('needs_environment' => false)),
        	new \Twig\TwigFunction('UnzerCw_IncludeCss', function($file) {
        		return UnzerCw_Template::includeCSSFile($file);
        	}, array('needs_environment' => false)),
        	new \Twig\TwigFunction('UnzerCw_IncludeJS', function($file) {
        		return UnzerCw_Template::includeJavaScriptFile($file);
        	}, array('needs_environment' => false)),
        	new \Twig\TwigFunction('UnzerCw_DefaultDateTimeFormat', function() {
        		return Customweb_Core_Util_System::getDefaultDateTimeFormat();
        	}, array('needs_environment' => false)),
        	new \Twig\TwigFunction('UnzerCw_HtmlToText', function($text) {
        		return Customweb_Core_Util_Html::toText($text);
        	}, array('needs_environment' => false)),
        	new \Twig\TwigFunction('UnzerCw_FormatAmount', function($amount, $currency) {
        		return Customweb_Util_Currency::formatAmount($amount, $currency);
        	}, array('needs_environment' => false)),
        	new \Twig\TwigFunction('UnzerCw_DecimalPlaces', function($currency) {
        		return Customweb_Util_Currency::getDecimalPlaces($currency);
        	}, array('needs_environment' => false)),
        );
    }

    public function getName()
    {
        return 'unzercw_translate';
    }
}