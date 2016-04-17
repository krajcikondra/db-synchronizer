<?php

namespace Helbrary\DbSynchronizer;

/**
 * Class Server
 * @package Helbrary\DbSynchronizer
 * @author Ondřej Krajčík <o.krajcik@seznam.cz>
 */
class Server extends Base
{

	const DUMP_FILE_PREFIX = 'dump-';
	const DUMP_FILE_SUFFIX = '.sql';

	/**
	 * @var string
	 */
	protected $tempDir;

	/**
	 * Server constructor.
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;
	}

	/**
	 * Is request authorized?
	 * @return bool
	 */
	private function isAuthorized()
	{
		return isset($_SESSION[self::SESSION_USER_NAME]) && isset($_SESSION[self::SESSION_IS_LOGGED]);
	}

	/**
	 * Set request as authorized
	 */
	public function setAsAuthorized()
	{
		$_SESSION[self::SESSION_IS_LOGGED] = TRUE;
		$_SESSION[self::SESSION_USER_NAME] = 'helbrary_user';
	}

	/**
	 * Destroy authorization
	 */
	private function destroyAuthorization()
	{
		unset($_SESSION[self::SESSION_USER_NAME]);
		unset($_SESSION[self::SESSION_IS_LOGGED]);
	}
	
	/**
	 * Dump database
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string|null $archiveDirectory - if is null save dump to temp directory, if is not null save dump to archive folder
	 * @return string - path to dump file
	 * @throws RequestIsNotAuthorizedException
	 */
	public function dump($server = 'localhost', $username, $password, $database, $archiveDirectory = NULL)
	{
		if (!$this->isAuthorized()) {
			throw new RequestIsNotAuthorizedException();
		}
		$this->destroyAuthorization();
		
		$dump = new \MySQLDump(new \mysqli($server, $username, $password, $database));
		$now = new \DateTime();
		$dumpName = self::DUMP_FILE_PREFIX . $now->format('Y-m-d_h-m-s') . self::DUMP_FILE_SUFFIX;

		if ($archiveDirectory) {
			$dumpName = $archiveDirectory . DIRECTORY_SEPARATOR . $dumpName;
		} else {
			$dumpName = $this->tempDir . DIRECTORY_SEPARATOR . $dumpName;
		}

		$dump->save($dumpName);
		return $dumpName;
	}

}
