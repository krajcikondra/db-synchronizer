<?php

namespace Helbrary\DbSynchronizer;

use Nette\Utils\FileSystem;

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
		FileSystem::createDir($tempDir);
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
	 * Dump db
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string|null $archiveDirectory - if is null save dump to temp directory, if is not null save dump to archive folder
	 * @return string
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

	/**
	 * Download file
	 * @param string $file
	 */
	private function downloadFile($file)
	{
		if (file_exists($file)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($file).'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			readfile($file);
			exit;
		}
	}


	/**
	 * Dump db and download
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string|null $archiveDirectory
	 * @throws RequestIsNotAuthorizedException
	 */
	public function dumpAndDownload($server = 'localhost', $username = 'root', $password = '', $database, $archiveDirectory = NULL)
	{
		$file = $this->dump($server, $username, $password, $database, $archiveDirectory);
		$this->downloadFile($file);
	}
}