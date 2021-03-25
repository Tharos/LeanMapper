<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

declare(strict_types=1);

namespace LeanMapper;

/**
 * Pointer to specific position within Result instance
 *
 * @author Vojtěch Kohout
 */
class Row
{

    /** @var Result */
    private $result;

    /** @var int|string */
    private $id;

    /** @var array<string, self|null> */
    private $referencedRows = [];


    /**
     * @param  int|string $id
     */
    public function __construct(Result $result, $id)
    {
        $this->result = $result;
        $this->id = $id;
    }


    /**
     * Gets value of given column
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->result->getDataEntry($this->id, $name);
    }


    /**
     * Sets value of given column
     *
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        if (array_key_exists($name, $this->referencedRows)) {
            if ($value === null) {
                $this->referencedRows[$name] = null;
            } else {
                unset($this->referencedRows[$name]);
            }
        }
        $this->result->setDataEntry($this->id, $name, $value);
    }


    /**
     * Tells whether Row has given column and is not null
     */
    public function __isset(string $name): bool
    {
        return $this->hasColumn($name) and $this->$name !== null;
    }


    /**
     * Tells whether Row has given column
     */
    public function hasColumn(string $name): bool
    {
        return $this->result->hasDataEntry($this->id, $name);
    }


    /**
     * Unsets given column
     */
    public function __unset(string $name): void
    {
        $this->result->unsetDataEntry($this->id, $name);
    }


    public function setConnection(Connection $connection): void
    {
        $this->result->setConnection($connection);
    }


    public function hasConnection(): bool
    {
        return $this->result->hasConnection();
    }


    public function setMapper(IMapper $mapper): void
    {
        $this->result->setMapper($mapper);
    }


    public function getMapper(): ?IMapper
    {
        return $this->result->getMapper();
    }


    /**
     * Returns values of columns
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->result->getData($this->id);
    }


    /**
     * Returns values of columns that were modified
     *
     * @return array<string, mixed>
     */
    public function getModifiedData(): array
    {
        return $this->result->getModifiedData($this->id);
    }


    /**
     * Tells whether Row is in modified state
     */
    public function isModified(): bool
    {
        return $this->result->isModified($this->id);
    }


    /**
     * Tells whether Row is in detached state
     */
    public function isDetached(): bool
    {
        return $this->result->isDetached();
    }


    /**
     * Detaches Row (it means mark it as non-persisted)
     */
    public function detach(): void
    {
        $data = $this->result->getData($this->id);
        $this->result = Result::createDetachedInstance();
        foreach ($data as $key => $value) {
            $this->result->setDataEntry(Result::DETACHED_ROW_ID, $key, $value);
        }
        $this->id = Result::DETACHED_ROW_ID;
    }


    /**
     * Marks Row as attached
     *
     * @param  int|string $id
     */
    public function attach($id, string $table): void
    {
        $this->result->attach($id, $table);
        $this->id = $id;
    }


    /**
     * Marks Row as non-modified (isModified returns false right after this method call)
     */
    public function markAsUpdated(): void
    {
        $this->result->markAsUpdated($this->id);
    }


    /**
     * Gets referenced Row instance
     */
    public function referenced(string $table, ?string $viaColumn = null, ?Filtering $filtering = null): ?Row
    {
        if (array_key_exists($viaColumn, $this->referencedRows)) {
            return $this->referencedRows[$viaColumn];
        }
        return $this->result->getReferencedRow($this->id, $table, $viaColumn, $filtering);
    }


    /**
     * Gets array of Row instances referencing current Row
     *
     * @return Row[]
     */
    public function referencing(string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = null): array
    {
        return $this->result->getReferencingRows($this->id, $table, $viaColumn, $filtering, $strategy);
    }


    public function setReferencedRow(?self $row = null, string $viaColumn): void
    {
        $this->referencedRows[$viaColumn] = $row;
    }


    /**
     * Adds new data entry to referencing Result
     *
     * @param array<string, mixed> $values
     */
    public function addToReferencing(array $values, string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = null): void
    {
        $this->result->addToReferencing($values, $table, $viaColumn, $filtering, $strategy);
    }


    /**
     * Remove given data entry from referencing Result
     *
     * @param array<string, mixed> $values
     */
    public function removeFromReferencing(array $values, string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = null): void
    {
        $this->result->removeFromReferencing($values, $table, $viaColumn, $filtering, $strategy);
    }


    public function createReferencingDataDifference(string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = null): DataDifference
    {
        return $this->result->createReferencingDataDifference($table, $viaColumn, $filtering, $strategy);
    }


    /**
     * Cleans in-memory cache with referenced rows
     */
    public function cleanReferencedRowsCache(?string $table = null, ?string $viaColumn = null): void
    {
        $this->result->cleanReferencedResultsCache($table, $viaColumn);
    }


    /**
     * Cleans in-memory cache with referencing rows
     */
    public function cleanReferencingRowsCache(?string $table = null, ?string $viaColumn = null): void
    {
        $this->result->cleanReferencingResultsCache($table, $viaColumn);
    }


    public function cleanReferencingAddedAndRemovedMeta(string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = null): void
    {
        $this->result->cleanReferencingAddedAndRemovedMeta($table, $viaColumn, $filtering, $strategy);
    }


    public function getResultProxy(string $proxyClass = ResultProxy::class): ResultProxy
    {
        return $this->result->getProxy($proxyClass);
    }

}
