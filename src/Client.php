<?php

namespace Helbrary\DbSynchronizer;

use Nette\NotImplementedException;
use Nette\Object;
use Tracy\Debugger;

/**
 * Class Client
 * @package Helbrary\DbSynchronizer
 * @author Ondřej Krajčík <o.krajcik@seznam.cz>
 */
class Client extends Base
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
	 * @throws DumpNotFoundException
	 * @throws DumpFileIsEmptyException
	 * @throws BadResponseException
	 */
	public function downloadRemoteDump(WebApi $webApi, Database $remoteDb)
	{
		$this->authenticate($webApi);
		$dumpPath = $this->downloadDump($webApi, $remoteDb);
		$this->checkDump($dumpPath);
		return $dumpPath;
	}

	/**
	 * Backup remote database
	 * @param WebApi $webApi
	 * @param Database $remoteDb
	 * @return mixed|\Psr\Http\Message\ResponseInterface
	 * @throws BadResponseException
	 */
	public function backupRemoteDatabase(WebApi $webApi, Database $remoteDb)
	{
		$this->authenticate($webApi);
		$response = $this->dumpRemote($webApi, $remoteDb);
		if ($response->getStatusCode() !== 200) {
			throw new BadResponseException($response->getStatusCode());
		}
		return $response;
	}

	/**
	 * Dump remote database
	 * @param WebApi $webApi
	 * @param Database $remoteDb
	 * 
	 * @return mixed|\Psr\Http\Message\ResponseInterface
	 */
	private function dumpRemote(WebApi $webApi, Database $remoteDb)
	{
		return $this->getClient()->request('POST', $webApi->baseUrl . '/' . $webApi->downloadDumpAction, array(
			'query' => array(
				'host' => $remoteDb->host,
				'db' => $remoteDb->dbName,
				'username' => $remoteDb->username,
				'password' => $remoteDb->password,
			)
		));
	}

	/**
	 * Synchronize remote database with local database
	 * @param WebApi $webApi
	 * @param Database $localDb
	 * @param Database $remoteDb
	 * @param bool $skipImportErrors
	 * @throws DumpNotFoundException
	 * @throws DumpFileIsEmptyException
	 * @throws BadResponseException
	 */
	public function syncLocalWithRemote(WebApi $webApi, Database $localDb, Database $remoteDb, $skipImportErrors = FALSE)
	{
		$pathToSqlDump = $this->downloadRemoteDump($webApi, $remoteDb);
		$this->onDumDownload($pathToSqlDump);
		$this->executeSqlDump($pathToSqlDump, $localDb, $skipImportErrors);
	}

	/**
	 * Execute sql file on db
	 * @param string $dumpFile - path to dump file
	 * @param Database $localDatabase
	 * @param bool $skipErrors
	 */
	public function executeSqlDump($dumpFile, Database $localDatabase, $skipErrors = FALSE)
	{
		$command = "mysql $localDatabase->dbName --user=" . $localDatabase->username;
		if (!empty($localDatabase->password)) {
			$command .= ' --password=' . $localDatabase->password;
		}
		
		if ($skipErrors) {
			$command .= ' -f';
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
			->request('POST', $webApi->baseUrl . '/' . $webApi->authAction, array(
				'query' => array(
					'username' => $webApi->authUsername,
					'password' => $webApi->authPassword,
					)
				)
			);
	}

	/**
	 * Download dump
	 * @param WebApi $webApi
	 * @param Database $remoteDb
	 * @return string - path to dump in disk
	 * @throws BadResponseException
	 */
	private function downloadDump(WebApi $webApi, Database $remoteDb)
	{
		$response = $this->dumpRemote($webApi, $remoteDb);
		if ($response->getStatusCode() !== 200) {
			throw new BadResponseException($response->getStatusCode());
		}
		$dump = $response->getBody();
		$dumpPath = $this->tempDir . DIRECTORY_SEPARATOR . 'dump.sql';
		file_put_contents($dumpPath, $dump);
		return $dumpPath;
	}
}