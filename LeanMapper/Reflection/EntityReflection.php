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
		foreach ($this->getFamilyLine() as $member) {
			$annotations = $member->getAnnotations();

			if (isset($annotations['property'])) {
				foreach ($annotations['property'] as $annotation) {
					$property = PropertyFactory::createFromAnnotation($annotation, $this);
					$this->properties[$property->getName()] = $property;
				}
			}
		}
	}

	/**
	 * @return self[]
	 */
	private function getFamilyLine()
	{
		$line = array($member = $this);
		while ($member = $member->getParentClass()) {
			if ($member->name === 'LeanMapper\Entity') break;
			$line[] = $member;
		}
		return array_reverse($line);
	}

}
