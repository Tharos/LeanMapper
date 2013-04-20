<?php

namespace Model;

use DibiRow;
use DibiConnection;

/**
 * @author Vojtěch Kohout
 */
class Collection
{

	private $data;

	private $connection;


	public function __construct($data, DibiConnection $connection)
	{
		if ($data instanceof DibiRow) {
			$this->data = array($data->id => $data);
		} else {
			foreach ($data as $record) {
				$this->data[$record->id] = $data;
			}
		}
		$this->connection = $connection;
	}

	function __get($name)
	{
		if (isset($this->data->$name)) {
			return $this->data->$name;
		}
	}

}
