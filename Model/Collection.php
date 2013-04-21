<?php

namespace Model;

use DibiRow;
use DibiConnection;
use Nette\Callback;
use Closure;

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
		} elseif (is_array($data)) {
			foreach ($data as $record) {
				if (isset($record->id)) {
					$this->data[$record->id] = $record->toArray();
				} else {
					$this->data[] = $record->toArray();
				}
			}
		} else {
			// TODO: Throw Exception
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

	public function getReferencedRow($id, $table, Closure $filter = null, $viaColumn = null)
	{
		if ($viaColumn === null) {
			$viaColumn = $table . '_id';
		}
		return $this->getReferencedCollection($table, $viaColumn, $filter)->getRow($this->data[$id][$viaColumn]);
	}

	public function getReferencingRows($id, $table, Closure $filter = null, $viaColumn = null)
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

	////////////////////
	////////////////////

	private function getReferencedCollection($table, $viaColumn, $filter = null)
	{
		$key = "$table($viaColumn)";
		$statement = $this->connection->select('*')->from($table);

		if ($filter === null) {
			if (!isset($this->referenced[$key])) {
				$data = $statement->where('%n.[id] IN %in', $table, $this->extractReferencedIds($viaColumn))
						->fetchAll();
				$this->referenced[$key] = new self($data, $table, $this->connection);
			}
		} else {
			call_user_func($filter, $statement);
			$statement->where('%n.[id] IN %in', $table, $this->extractReferencedIds($viaColumn));

			$sql = (string)$statement;
			$key .= '#' . md5($sql);

			if (!isset($this->referenced[$key])) {
				$this->referenced[$key] = new self($this->connection->query($sql)->fetchAll(), $table, $this->connection);
			}
		}
		return $this->referenced[$key];
	}

	private function getReferencingCollection($table, $viaColumn, $filter = null)
	{
		$key = "$table($viaColumn)";
		$statement = $this->connection->select('*')->from($table);

		if ($filter === null) {
			if (!isset($this->referencing[$key])) {
				$data = $statement->where('%n.%n IN %in', $table, $viaColumn, $this->extractReferencedIds())
						->fetchAll();
				$this->referencing[$key] = new self($data, $table, $this->connection);
			}
		} else {
			call_user_func($filter, $statement);
			$statement->where('%n.%n IN %in', $table, $viaColumn, $this->extractReferencedIds());

			$sql = (string)$statement;
			$key .= '#' . md5($sql);

			if (!isset($this->referencing[$key])) {
				$this->referencing[$key] = new self($this->connection->query($sql)->fetchAll(), $table, $this->connection);
			}
		}
		return $this->referencing[$key];
	}

	private function extractReferencedIds($column = 'id')
	{
		$ids = array();
		foreach ($this->data as $data) {
			if ($data[$column] === null) continue;
			$ids[$data[$column]] = true;
		}
		return array_keys($ids);
	}
}
