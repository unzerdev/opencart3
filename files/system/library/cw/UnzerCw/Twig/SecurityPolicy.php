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



class UnzerCw_Twig_SecurityPolicy implements \Twig\Sandbox\SecurityPolicyInterface
{
	private $policy = null;

	private $allowedTags = array(
		'autoescape',
		'block',
		'filter',
		'do',
		'for',
		'if',
		'macro',
		'set',
		'spaceless',
		'verbatim'
	);

	private $allowedFilters = array(
		'abs',
		'batch',
		'capitalize',
		'convert_encoding',
		'date',
		'date_modify',
		'default',
		'escape',
		'first',
		'format',
		'join',
		'json_encode',
		'keys',
		'last',
		'length',
		'lower',
		'nl2br',
		'number_format',
		'merge',
		'upper',
		'raw',
		'replace',
		'reverse',
		'round',
		'slice',
		'sort',
		'split',
		'striptags',
		'title',
		'trim',
		'url_encode',
		
		// Custom filters
		'translate'
	);

	private $allowedFunctions = array(
		'attribute',
		'block',
		'cycle',
		'date',
		'max',
		'min',
		'random',
		'range'
	);

	public function __construct(Customweb_Mvc_Template_ISecurityPolicy $policy)
	{
		$this->policy = $policy;
	}

	public function checkMethodAllowed($obj, $method)
	{
		if ($obj instanceof \Twig\Template || $obj instanceof \Twig\TemplateWrapper || $obj instanceof \Twig\Markup) {
			return true;
		}
		try {
			return $this->policy->checkMethodAllowed($obj, $method);
		} catch (Exception $e) {
			throw new \Twig\Sandbox\SecurityError($e->getMessage());
		}
	}

	public function checkPropertyAllowed($object, $property)
	{
		throw new \Twig\Sandbox\SecurityError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, get_class($object)));
	}

	public function checkSecurity($tags, $filters, $functions)
	{
		foreach ($tags as $tag) {
			if (! in_array($tag, $this->allowedTags)) {
				throw new \Twig\Sandbox\SecurityError(sprintf('Tag "%s" is not allowed.', $tag));
			}
		}
		
		foreach ($filters as $filter) {
			if (! in_array($filter, $this->allowedFilters)) {
				throw new \Twig\Sandbox\SecurityError(sprintf('Filter "%s" is not allowed.', $filter));
			}
		}
		
		foreach ($functions as $function) {
			if (! in_array($function, $this->allowedFunctions)) {
				throw new \Twig\Sandbox\SecurityError(sprintf('Function "%s" is not allowed.', $function));
			}
		}
		
		return true;
	}

	public function getAllowedTags()
	{
		return $this->allowedTags;
	}

	public function setAllowedTags(array $allowedTags)
	{
		$this->allowedTags = $allowedTags;
		return $this;
	}

	public function getAllowedFilters()
	{
		return $this->allowedFilters;
	}

	public function setAllowedFilters(array $allowedFilters)
	{
		$this->allowedFilters = $allowedFilters;
		return $this;
	}

	public function getAllowedFunctions()
	{
		return $this->allowedFunctions;
	}

	public function setAllowedFunctions(array $allowedFunctions)
	{
		$this->allowedFunctions = $allowedFunctions;
		return $this;
	}
}