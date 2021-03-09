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

require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/I18n/Util.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';

class UnzerCw_Language {
	private static $translationMap = null;
	private static $languages = null;

	/**
	 * This method translates a given string.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function _($input, $args = array()){
		$key = Customweb_I18n_Util::cleanLanguageKey($input);
		
		self::load();
		
		if (isset(self::$translationMap[$key])) {
			$output = self::$translationMap[$key];
		}
		else {
			$registry = UnzerCw_Util::getRegistry();
			$output = $registry->get('language')->get($input);
		}
		
		if (count($args) > 0) {
			return Customweb_I18n_Translation::formatString($output, $args);
		}
		else {
			return $output;
		}
	}

	private static function load(){
		if (self::$translationMap === null) {
			self::$translationMap = array();
			
			$langs = self::getLanguages();
			if (version_compare(VERSION, '2.2.0.0') < 0) {
				$langPath = dirname(DIR_SYSTEM) . '/catalog/language/' . $langs[self::getCurrentLanguageCode()]['directory'] .
						 '/unzercw/unzercw.php';
			}
			else {
				$langPath = dirname(DIR_SYSTEM) . '/catalog/language/' . $langs[self::getCurrentLanguageCode()]['code'] .
						 '/unzercw/unzercw.php';
			}
			
			if (file_exists($langPath)) {
				$_ = array();
				require_once $langPath;
				
				self::$translationMap = $_;
			}
			else {
				self::$translationMap = array();
			}
		}
	}

	/**
	 * This method returns a list of languages available.
	 * The key is the code of the language.
	 *
	 * @throws Exception
	 */
	public static function getLanguages(){
		if (self::$languages === null) {
			self::$languages = array();
			$rs = UnzerCw_Database::getInstance()->query("SELECT * FROM `" . DB_PREFIX . "language`");
			while (($row = UnzerCw_Database::getInstance()->fetch($rs)) !== false) {
				self::$languages[$row['code']] = $row;
			}
		}
		
		return self::$languages;
	}

	public static function getLanguageCodeByLanguageId($languageId){
		$languages = self::getLanguages();
		foreach ($languages as $language) {
			if ($language['language_id'] == $languageId) {
				return $language['code'];
			}
		}
		throw new Exception("Could not find language with the given language id '" . $languageId . "'.");
	}

	public static function getCurrentLanguageCode(){
		return self::getLanguageCodeByLanguageId(self::getCurrentLanguageId());
	}

	public static function getCurrentLanguageId(){
		$config = UnzerCw_Util::getRegistry()->get('config');
		if ($config->has('config_language_id')) {
			return $config->get('config_language_id');
		}
		else {
			throw new Exception("Could not load current language id.");
		}
	}
}