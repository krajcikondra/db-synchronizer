<?php

namespace Helbrary\DbSynchronizer;

use Nette\NotImplementedException;
use Nette\Object;

/**
 * Class Client
 * @package Helbrary\DbSynchronizer
 * @author Ondřej Krajčík <o.krajcik@seznam.cz>
 */
class Client extends Object
{

	/**
	 * @var string
	 */
	protected $tempDir;

	/**
	 * @var \GuzzleHttp\Client
	 */
	protected $client;

	/**
	 * @var callable - (string $pathToDumpFile)
	 */
	public $onDumDownload = [];
	
	/**
	 * Client constructor.
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;
	}

	/**
	 * Download remote dump
	 * @param WebApi $webApi
	 * @param Database $remoteDb
	 * @return string - path to dump in disk
	 */
	public function downloadRemoteDump(WebApi $webApi, Database $remoteDb)
	{
		$this->authenticate($webApi);
		return $this->downloadDump($webApi->baseUrl, $webApi->downloadDumpAction, $remoteDb);
	}

	public function backupRemoteDatabase()
	{
		throw new NotImplementedException();
	}

	/**
	 * Synchronize remote database with local database
	 * @param WebApi $webApi
	 * @param Database $localDb
	 * @param Database $remoteDb
	 */
	public function syncLocalWithRemote(WebApi $webApi, Database $localDb, Database $remoteDb)
	{
		$pathToSqlDump = $this->downloadRemoteDump($webApi, $remoteDb);
		$this->onDumDownload($pathToSqlDump);
		$this->executeSqlDump($pathToSqlDump, $localDb);
	}

	/**
	 * Execute sql file on db
	 * @param string $dumpFile - path to dump file
	 * @param Database $localDatabase
	 */
	public function executeSqlDump($dumpFile, Database $localDatabase)
	{
		$command = "mysql $localDatabase->dbName --user=" . $localDatabase->username;
		if (!empty($localDatabase->password)) {
			$command .= ' --password=' . $localDatabase->password;
		}
		$command .= ' < ';
		$command .= ' ' . $dumpFile;
		shell_exec($command);
	}

	/**
	 * Return client instance
	 * @return \GuzzleHttp\Client
	 */
	private function getClient()
	{
		if (!isset($this->client)) {
			$this->client = new \GuzzleHttp\Client(array('cookies' => true));
		}
		return $this->client;
	}

	/**
	 * Authenticate request
	 * @param WebApi $webApi
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	private function authenticate(WebApi $webApi)
	{
		return $this->getClient()
			->request('GET', $webApi->baseUrl . '/' . $webApi->authAction, array(
				'query' => array(
					'username' => $webApi->authUsername,
					'password' => $webApi->authPassword,
					)
				)
			);
	}

	/**
	 * Download dump
	 * @param string $webUrl
	 * @param string $webDownloadDumpAction
	 * @param Database $remoteDb
	 * @return string - path to dump in disk
	 */
	private function downloadDump($webUrl, $webDownloadDumpAction, Database $remoteDb)
	{
		$response = $this->getClient()->request('GET', $webUrl . '/' . $webDownloadDumpAction, array(
			'query' => array(
				'host' => $remoteDb->host,
				'db' => $remoteDb->dbName,
				'username' => $remoteDb->username,
				'password' => $remoteDb->password,
			)
		));
		$dump = $response->getBody();
		$dumpPath = $this->tempDir . DIRECTORY_SEPARATOR . 'dump.sql';
		file_put_contents($dumpPath, $dump);
		return $dumpPath;
	}
}
