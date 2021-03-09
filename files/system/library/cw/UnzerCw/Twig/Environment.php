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

require_once 'Customweb/Mvc/Template/Filter/Translate.php';



/**
 * Extended Twig Environment which supports the Customweb_Cache_IBackend
 * interface for caching.
 *
 * @author Nico Eigenmann
 */
class UnzerCw_Twig_Environment extends \Twig\Environment
{
	protected $customwebCache = null;

	public function __construct($loader, Customweb_Cache_IBackend $cache = null, array $options = array())
	{
		parent::__construct($loader, $options);
		$this->customwebCache = $cache;
		$this->initPlugins();
	}

	public function getCache($original = true)
	{
		throw new Exception('Not supported by this interface');
	}

	public function setCache($cache)
	{}

	public function clearCacheFiles()
	{
		throw new Exception('Not supported by this interface');
	}

	public function getCacheFilename($name)
	{
		throw new Exception('Not supported by this interface');
	}

	public function getCacheKey($name)
	{
		if ($this->customwebCache === null) {
			return false;
		}
		return hash('sha256', $name);
	}

	public function loadTemplate($name, $index = null)
	{
		$cls = $this->getTemplateClass($name, $index);
		
		if (isset($this->loadedTemplates[$cls])) {
			return $this->loadedTemplates[$cls];
		}
		
		if (! class_exists($cls, false)) {
			if (false === $cacheKey = $this->getCacheKey($name)) {
				eval('?>' . $this->compileSource($this->getLoader()
					->getSource($name), $name));
			} else {
				if (! $this->customwebCache->keyExists($cacheKey) || ($this->isAutoReload() && ! $this->isTemplateFresh($name, $this->customwebCache->get($cacheKey . 'Time')))) {
					$this->customwebCache->put($cacheKey, $this->compileSource($this->getLoader()
						->getSource($name), $name));
					$this->customwebCache->put($cacheKey . 'Time', time());
				}
				eval('?>' . $this->customwebCache->get($cacheKey));
			}
		}
		
		if (! $this->runtimeInitialized) {
			$this->initRuntime();
		}
		return $this->loadedTemplates[$cls] = new $cls($this);
	}

	public function clearCustomwebCache()
	{
		$this->customwebCache->clear();
	}
	
	protected function initPlugins()
	{
		$this->addCustomFilter(new Customweb_Mvc_Template_Filter_Translate());
	}
	
	protected function addCustomFilter(Customweb_Mvc_Template_IFilter $filter)
	{
		$this->addFilter(new \Twig\TwigFilter($filter->getName(), array($filter, 'filter')));
	}
}