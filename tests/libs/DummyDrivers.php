<?php

declare(strict_types=1);

use LeanMapper\Connection;
use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class ResultDummyDriver implements \Dibi\ResultDriver
{
    /** @var array<mixed> */
    private $data;

    /** @var int */
    private $position;


    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->position = 0;
    }


    public function getRowCount(): int
    {
        return count($this->data);
    }


    public function seek(int $row): bool
    {
        $this->position = $row;
        return true;
    }


    /**
     * @return array<mixed>
     */
    public function fetch(bool $assoc): ?array
    {
        $raw = array_slice($this->data, $this->position, 1, true);
        $data = is_array($raw) && !empty($raw) ? reset($raw) : [];
        $this->position++;

        if (!is_array($raw)) {
            return null;
        }

        if ($assoc) {
            return $data;
        }

        $tmp = [];

        foreach ($data as $value) {
            $tmp[] = $value;
        }

        return $tmp;
    }


    /**
     * @return array<array<string, mixed>>
     */
    public function getResultColumns(): array
    {
        return [];
    }


    public function getResultResource(): mixed
    {
        return null;
    }


    public function free(): void
    {
        $this->data = [];
        $this->position = 0;
    }


    public function unescapeBinary(string $value): string
    {
        return $value;
    }
}

class PostgreDummyDriver extends \Dibi\Drivers\PostgreDriver
{
    /** @var array<mixed> */
    private $resultData = [];


    public function __construct()
    {
    }


    /**
     * @param array<mixed> $resultData
     */
    public function setResultData(string $sql, array $resultData): void
    {
        $sql = trim($sql);
        $this->resultData[$sql] = $resultData;
    }


    /**
     * @param  array<mixed> $config
     */
    public function connect(array & $config): void
    {
    }


    public function query(string $sql): ?Dibi\ResultDriver
    {
        $sql = trim($sql);
        if (isset($this->resultData[$sql])) {
            return new ResultDummyDriver($this->resultData[$sql]);
        }
        throw new \RuntimeException("Missing data for query: $sql");
    }


    public function escapeText(string $value): string
    {
        return "'" . strtr($value, ["'" => "\\'"]) . "'";
    }
}
