<?php

use LeanMapper\Connection;
use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class ResultDummyDriver implements \Dibi\ResultDriver
{
    private $data;
    private $position;


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
    }


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


    public function getResultColumns(): array
    {
        return [];
    }


    public function getResultResource()
    {
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
    /** @var array */
    private $resultData = [];


    public function __construct()
    {
    }


    public function setResultData($sql, array $resultData)
    {
        $sql = trim($sql);
        $this->resultData[$sql] = $resultData;
    }


    public function connect(array & $config)
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
