<?php

namespace Model;

use DibiRow;
use DibiConnection;
use Nette\Callback;

/**
 * @author Vojtěch Kohout
 */
class Collection implements \Iterator
{

	private $data;

	private $table;

	private $keys;

	private $connection;

	private $referenced = array();

	private $referencing = array();


	public function __construct($data, $table, DibiConnection $connection)
	{
		if ($data instanceof DibiRow) {
			$this->data = array(isset($data->id) ? $data->id : 0 => $data->toArray());
		} else {
			foreach ($data as $record) {
				if (isset($record->id)) {
					$this->data[$record->id] = $record->toArray();
				} else {
					$this->data[] = $record->toArray();
				}
			}
		}
		$this->table = $table;
		$this->connection = $connection;
	}

	public function getRow($id)
	{
		if (!array_key_exists($id, $this->data)) {
			return null;
		}
		return new Row($this, $id);
	}

	public function getData($id, $key)
	{
		return $this->data[$id][$key];
	}

	public function getReferencedRow($id, $table, $filter = null, $viaColumn = null)
	{
		if ($viaColumn === null) {
			$viaColumn = $table . '_id';
		}
		$key = "$table($viaColumn)";

		$statement = $this->connection->select('*')->from($table);
		if ($filter !== null) {
			call_user_func($filter, $statement);
			$key .= '#' . md5((string) $statement);
		}

		if (!isset($this->referenced[$key])) {
			$ids = array();
			foreach ($this->data as $data) {
				if ($data[$viaColumn] === null) continue;
				$ids[$data[$viaColumn]] = true;
			}
			$ids = array_keys($ids);
			$data = $statement->where('[id] IN %in', $ids)
					->fetchAll();

			$this->referenced[$key] = new self($data, $table, $this->connection);
		}
		return $this->referenced[$key]->getRow($this->data[$id][$viaColumn]);
	}

	public function getReferencingRows($id, $table, $filter = null, $viaColumn = null)
	{
		if ($viaColumn === null) {
			$viaColumn = $this->table . '_id';
		}
		$collection = $this->getReferencingCollection($table, $viaColumn, $filter);
		$rows = array();
		foreach ($collection as $key => $row) {
			if ($row[$viaColumn] === $id) {
				$rows[] = new Row($collection, $key);
			}
		}
		return $rows;
	}

	private function getReferencingCollection($table, $viaColumn, $filter = null)
	{
		$key = "$table($viaColumn)";

		$statement = $this->connection->select('*')->from($table);
		if ($filter !== null) {
			call_user_func($filter, $statement);
			$key .= '#' . md5((string) $statement);
		}

		if (!isset($this->referencing[$key])) {
			$ids = array();
			foreach ($this->data as $data) {
				if ($data['id'] === null) continue;
				$ids[$data['id']] = true;
			}
			$ids = array_keys($ids);
			$data =	$statement->where('%n IN %in', $viaColumn, $ids)
					->fetchAll();

			$this->referencing[$key] = new self($data, $table, $this->connection);
		}
		return $this->referencing[$key];
	}

	//========== interface \Iterator ====================

	public function current()
	{
		$key = current($this->keys);
		return $this->data[$key];
	}

	public function next()
	{
		next($this->keys);
	}

	public function key()
	{
		return current($this->keys);
	}

	public function valid()
	{
		return current($this->keys) !== false;
	}

	public function rewind()
	{
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}
}
