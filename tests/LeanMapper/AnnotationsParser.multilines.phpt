<?php

use LeanMapper\Reflection\AnnotationsParser;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

$phpdoc = "/**
 * @property int \$id
 * @property string \$singleline1 m:enum(self::STATE_*)
 * @property string \$singleline2 m:enum(static::STATE_*)
 * @property int \$singleline3 m:enum(DbLayer::ATTR_*)
 * @property int \$correctMultiline
 *  m:enum(DbLayer::ATTR_*)
 *  m:enum2(DbLayer::ATTR_*)
 *  m:enum3(DbLayer::ATTR_*)
 * @property int \$singleline4 m:enum(self::TYPE_*)
 * @property int \$withoutIndent
 *  m:enum(DbLayer::ATTR_*)
 * m:enum2(DbLayer::ATTR_*)
 * @property int \$emptyLineBetween
 *  m:enum(DbLayer::ATTR_*)
 *
 *  m:enum2(DbLayer::ATTR_*)
 * @property int \$emptyLineAfter
 *  m:enum(DbLayer::ATTR_*)
 *\t\tm:enum2(DbLayer::ATTR_*)
 *
 * Some comment.
 * @property string @property inline
 *
 * @property string \$whitespaces
 *\t\t\t
 *\t\t\tm:flag
 *\t\t\tm:flag2
 */";

Assert::equal([
    'int $id',
    'string $singleline1 m:enum(self::STATE_*)',
    'string $singleline2 m:enum(static::STATE_*)',
    'int $singleline3 m:enum(DbLayer::ATTR_*)',
    'int $correctMultiline
 *  m:enum(DbLayer::ATTR_*)
 *  m:enum2(DbLayer::ATTR_*)
 *  m:enum3(DbLayer::ATTR_*)',
    'int $singleline4 m:enum(self::TYPE_*)',
    'int $withoutIndent
 *  m:enum(DbLayer::ATTR_*)',
    'int $emptyLineBetween
 *  m:enum(DbLayer::ATTR_*)',
    "int \$emptyLineAfter
 *  m:enum(DbLayer::ATTR_*)
 *\t\tm:enum2(DbLayer::ATTR_*)",
    'string ',
    'inline',
    'string $whitespaces',
], AnnotationsParser::parseMultiLineAnnotationValues('property', $phpdoc));
