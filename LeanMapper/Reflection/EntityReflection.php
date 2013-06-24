<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidStateException;
use LeanMapper\IMapper;

/**
 * Entity reflection
 *
 * @author Vojtěch Kohout
 */
class EntityReflection extends \ReflectionClass
{

	/** @var IMapper */
	private $mapper;

	/** @var Property[] */
	private $properties;

	/** @var array */
	private $aliases;

	/** @var string */
	private $docComment;


	/**
	 * @param mixed $argument
	 * @param IMapper|null $mapper
	 */
	public function __construct($argument, IMapper $mapper = null)
	{
		parent::__construct($argument);
		$this->mapper = $mapper;
	}

	/**
	 * Gets requested entity property
	 *
	 * @param string $name
	 * @return Property|null
	 */
	public function getEntityProperty($name)
	{
		$this->initProperties();
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * Gets array of entity properties
	 *
	 * @return Property[]
	 */
	public function getEntityProperties()
	{
		$this->initProperties();
		return $this->properties;
	}

	/**
	 * Gets LeanMapper\Reflection\Aliases instance valid for current class
	 *
	 * @return Aliases
	 */
	public function getAliases()
	{
		if ($this->aliases === null) {
			$this->aliases = AliasesParser::parseSource(file_get_contents($this->getFileName()), $this->getNamespaceName());
		}
		return $this->aliases;
	}

	/**
	 * Returns parent entity reflection
	 *
	 * @return self|null
	 */
	public function getParentClass()
	{
		return ($reflection = parent::getParentClass()) ? new self($reflection->getName()) : null;
	}

	/**
	 * Returns doc comment of current class
	 *
	 * @return string
	 */
	public function getDocComment()
	{
		if ($this->docComment === null) {
			$this->docComment = parent::getDocComment();
		}
		return $this->docComment;
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
		$this->properties = array();
		$annotationTypes = array('property', 'property-read');
		$columns = array();
		foreach ($this->getFamilyLine() as $member) {
			foreach ($annotationTypes as $annotationType) {
				foreach (AnnotationsParser::parseAnnotationValues($annotationType, $member->getDocComment()) as $definition) {
					$property = PropertyFactory::createFromAnnotation($annotationType, $definition, $this, $this->mapper);
					// collision check
					$column = $property->getColumn();
					if ($column !== null) {
						if (isset($columns[$column])) {
							throw new InvalidStateException("Mapping collision on field '{$property->getName()}' (column $column).");
						}
						$columns[$column] = true;
					}
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
