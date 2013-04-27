<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
 */

namespace LeanMapper\Reflection;

/**
 * @author Vojtěch Kohout
 */
class EntityReflection extends \Nette\Reflection\ClassType
{

	/** @var Property[] */
	private $properties;

	/** @var array */
	private $aliases;


	/**
	 * @param string $name
	 * @return Property|null
	 */
	public function getEntityProperty($name)
	{
		$this->initProperties();
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * @return array
	 */
	public function getAliases()
	{
		if ($this->aliases === null) {
			$this->aliases = AliasesParser::parseSource(file_get_contents($this->getFileName()));
		}
		return $this->aliases;
	}

	////////////////////
	////////////////////

	private function initProperties()
	{
		if ($this->properties === null) {
			$this->parseProperties();
		}
	}

	private function parseProperties()
	{
		foreach ($this->getFamilyAnnotations() as $member) {
			$annotations = $member->getAnnotations();

			foreach (array('property', 'property-read') as $type) {
				if (isset($annotations[$type])) {
					foreach ($annotations[$type] as $annotation) {
						$property = PropertyFactory::createFromAnnotation($type, $annotation, $this);
						$this->properties[$property->getName()] = $property;
					}
				}
			}
		}
	}

	/**
	 * @return self[]
	 */
	private function getFamilyAnnotations()
	{
		$family = array();
		$member = $this;
		do {
			$family[] = $member;
		} while ($member = $member->getParentClass());

		return array_reverse($family);
	}

}
