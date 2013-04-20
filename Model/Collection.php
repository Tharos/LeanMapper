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
		return new Row($this, $id);
	}

	public function getData($id, $key)
	{
		if (array_key_exists($key, $this->data[$id])) {
			return $this->data[$id][$key];
		} else {
			if (!isset($this->related[$key])) {
				$ids = array();
				foreach ($this->data as $data) {
					$ids[$data[$key . '_id']] = true;
				}
				$ids = array_keys($ids);
				$this->related[$key] = $this->connection->select('*')
						->from($key)
						->where('[id] IN %in', $ids)
						->fetchAssoc('id');
			}
			$collection = new static($this->related[$key], $this->connection);
			return $collection->getRow($this->data[$id][$key . '_id']);
		}

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
