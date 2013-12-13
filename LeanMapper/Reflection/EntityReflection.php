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
use ReflectionMethod;

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
	private $getters;

	/** @var array */
	private $setters;

	/** @var array */
	private $aliases;

	/** @var string */
	private $docComment;

	/** @var array */
	private $internalGetters = array('getData', 'getRowData', 'getModifiedRowData', 'getCurrentReflection', 'getReflection', 'getHasManyRowDifferences', 'getEntityClass');


	/**
	 * @param mixed $argument
	 * @param IMapper|null $mapper
	 */
	public function __construct($argument, IMapper $mapper = null)
	{
		parent::__construct($argument);
		$this->mapper = $mapper;
		$this->parseProperties();
		$this->initGettersAndSetters();
	}

	/**
	 * Gets requested entity's property
	 *
	 * @param string $name
	 * @return Property|null
	 */
	public function getEntityProperty($name)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * Gets array of all entity's properties
	 *
	 * @return Property[]
	 */
	public function getEntityProperties()
	{
		return $this->properties;
	}

	/**
	 * Gets Aliases instance relevant to current class
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
	 * Gets parent entity's reflection
	 *
	 * @return self|null
	 */
	public function getParentClass()
	{
		return ($reflection = parent::getParentClass()) ? new self($reflection->getName()) : null;
	}

	/**
	 * Gets doc comment of current class
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

	/**
	 * Gets requested getter's reflection
	 *
	 * @param string $name
	 * @return ReflectionMethod|null
	 */
	public function getGetter($name)
	{
		return isset($this->getters[$name]) ? $this->getters[$name] : null;
	}

	/**
	 * Gets array of getter's reflections
	 *
	 * @return ReflectionMethod[]
	 */
	public function getGetters()
	{
		return $this->getters;
	}

	/**
	 * Gets requested setter's reflection
	 *
	 * @param string $name
	 * @return ReflectionMethod|null
	 */
	public function getSetter($name)
	{
		return isset($this->setters[$name]) ? $this->setters[$name] : null;
	}

	////////////////////
	////////////////////

	/**
	 * @throws InvalidStateException
	 */
	private function parseProperties()
	{
		$this->properties = array();
		$annotationTypes = array('property', 'property-read');
		$columns = array();
		foreach ($this->getFamilyLine() as $member) {
			foreach ($annotationTypes as $annotationType) {
				foreach (AnnotationsParser::parseAnnotationValues($annotationType, $member->getDocComment()) as $definition) {
					$property = PropertyFactory::createFromAnnotation($annotationType, $definition, $member, $this->mapper);
					// collision check
					$column = $property->getColumn();
					if ($column !== null and $property->isWritable()) {
						if (isset($columns[$column])) {
							throw new InvalidStateException("Mapping collision in property '{$property->getName()}' (column '$column') in entity {$this->getName()}. Please fix mapping or make chosen properties read only (using property-read).");
						}
						$columns[$column] = true;
					}
					$this->properties[$property->getName()] = $property;
				}
			}
		}
	}

	private function initGettersAndSetters()
	{
		$this->getters = $this->setters = array();
		foreach ($this->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			$name = $method->getName();
			if (strlen($name) > 3) {
				$prefix = substr($name, 0, 3);
				if ($prefix === 'get') {
					$this->getters[$name] = $method;
				} elseif ($prefix === 'set') {
					$this->setters[$name] = $method;
				}
			}
		}
		$this->getters = array_diff_key($this->getters, array_flip($this->internalGetters));
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
