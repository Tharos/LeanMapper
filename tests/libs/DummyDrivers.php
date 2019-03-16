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


    public function getRowCount()
    {
        return count($this->data);
    }


    public function seek($row)
    {
        $this->position = $row;
    }


    public function fetch($assoc)
    {
        $raw = array_slice($this->data, $this->position, 1, TRUE);
        $data = reset($raw);
        $this->position++;

        if (!is_array($raw)) {
            return FALSE;
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


    public function getResultColumns()
    {
        return [];
    }


    public function getResultResource()
    {
    }


    public function free()
    {
        $this->data = [];
        $this->position = 0;
    }


    public function unescapeBinary($value)
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


    public function query($sql)
    {
        $sql = trim($sql);
        if (isset($this->resultData[$sql])) {
            return new ResultDummyDriver($this->resultData[$sql]);
        }
        throw new \RuntimeException("Missing data for query: $sql");
    }


    public function escapeText($value)
    {
        return "'" . strtr($value, ["'" => "\\'"]) . "'";
    }
}
