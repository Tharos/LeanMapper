<?php

namespace Model;

use DibiRow;
use DibiConnection;

/**
 * @author Vojtěch Kohout
 */
class Collection implements \Iterator
{

	private $data;

	private $keys;

	private $connection;

	private $related = array();


	public function __construct($data, DibiConnection $connection)
	{
		if ($data instanceof DibiRow) {
			$this->data = array($data->id => $data->toArray());
		} else {
			foreach ($data as $record) {
				$this->data[$record->id] = $record->toArray();
			}
		}
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

	public function getRelatedRow($id, $table, $viaColumn = null)
	{
		if ($viaColumn === null) {
			$viaColumn = $table . '_id';
		}
		$key = "$table($viaColumn)";
		if (!isset($this->related[$key])) {
			$ids = array();
			foreach ($this->data as $data) {
				if ($data[$viaColumn] === null) continue;
				$ids[$data[$viaColumn]] = true;
			}
			$ids = array_keys($ids);
			$this->related[$key] = $this->connection->select('*')
					->from($table)
					->where('[id] IN %in', $ids)
					->fetchAssoc('id');
		}
		$collection = new self($this->related[$key], $this->connection);

		return $collection->getRow($this->data[$id][$viaColumn]);
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
