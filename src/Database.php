<?php

namespace Helbrary\DbSynchronizer;

use Nette\Object;

/**
 * Class Database
 * @package Helbrary\DbSynchronizer
 */
class Database extends Object
{

	/** @var  string */
	public $host;

	/** @var  string */
	public $username;

	/** @var  string */
	public $password;

	/** @var  string */
	public $dbName;

	/**
	 * Database constructor.
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string|null $dbName
	 */
	public function __construct($host, $username, $password, $dbName = NULL)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->dbName = $dbName;
	}


}