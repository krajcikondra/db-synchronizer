<?php

namespace Helbrary\DbSynchronizer\Commands;
use Helbrary\DbSynchronizer\BadResponseException;
use Helbrary\DbSynchronizer\Client;
use Helbrary\DbSynchronizer\Database;
use Helbrary\DbSynchronizer\WebApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;

/**
 * Class DabaseCommand
 * @package App\Model\Commands
 */
class DatabaseCommand extends Command
{

	/**
	 * @var null|string
	 */
	private $tempDir;

	/**
	 * @var
	 */
	private $config;

	/**
	 * DatabaseCommand constructor.
	 * @param null|string $tempDir
	 * @param array $config
	 */
	public function __construct($tempDir, $config)
	{
		parent::__construct();
		$this->tempDir = $tempDir;
		$this->config = $config;
	}


	protected function configure()
	{
		$this->setName('db')
			->addOption('sync-local', NULL, InputOption::VALUE_NONE, 'Synchronize local database from remote database')
			->addOption('backup-remote', NULL, InputOption::VALUE_NONE, 'Backup remote database')
			->addOption('skip-import-errors', NULL, InputOption::VALUE_NONE, 'Skip errors when import database');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($input->getOption('sync-local') && !$input->getOption('backup-remote')) {
			if ($input->getOption('skip-import-errors')) {
				$this->syncLocal($output, TRUE);
			} else {
				$this->syncLocal($output, FALSE);
			}
		} elseif ($input->getOption('backup-remote')) {
			$this->backupRemoteDb($output, $this->getClient(), $this->createWebApi(), $this->createProductionDb());
		}
	}

	/**
	 * Return client
	 * @return Client
	 */
	private function getClient()
	{
		return new Client($this->tempDir);
	}
	
	/**
	 * Synchronize local db from remote db
	 */
	private function syncLocal(OutputInterface $output, $skipImportErrors = FALSE)
	{
		$output->writeln('[INFO] Start synchronize local database from remote');
		$client = $this->getClient();
		$client->onDumDownload[] = function() use ($output) {
			$output->writeln('[INFO] Remote dump is successfully downloaded');
		};

		try {
			$client->syncLocalWithRemote($this->createWebApi(), $this->createLocalDb(), $this->createProductionDb(), $skipImportErrors);
			$output->writeln('[SUCCESS] Local db is synchonized');
		} catch(\Exception $e) {
			$output->writeln('[ERROR] Synchronization of local database failed: ' . $e->getMessage());
			Debugger::log($e);
		}
	}

	/**
	 * @param OutputInterface $output
	 * @param Client $client
	 * @param WebApi $webApi
	 * @param Database $remoteDb
	 * @throws BadResponseException
	 */
	private function backupRemoteDb(OutputInterface $output, Client $client, WebApi $webApi, Database $remoteDb)
	{
		$output->writeln('[INFO] Start backup remote db of web: ' . $webApi->baseUrl);
		try {
			$client->backupRemoteDatabase($webApi, $remoteDb);
			$output->writeln('[SUCCESS] Backup of remote database done: ' . $webApi->baseUrl);
		} catch (BadResponseException $e) {
			Debugger::log($e);
			$output->writeln('[ERROR] Backup remote failed: ' . $e->getMessage());
		}
	}
	
	/**
	 * @return WebApi
	 */
	public function createWebApi()
	{
		return new WebApi(
			$this->config['web']['productionBaeUrl'],
			$this->config['web']['authAction'],
			$this->config['web']['dumpDownloadAction'],
			$this->config['web']['webAdminUsername'],
			$this->config['web']['webAdminPassword']
		);
	}

	/**
	 * @return Database
	 */
	private function createLocalDb()
	{
		return new Database(
			$this->config['database']['local']['host'],
			$this->config['database']['local']['username'],
			$this->config['database']['local']['password'],
			$this->config['database']['local']['dbName']
			);
	}

	/**
	 * @return Database
	 */
	private function createProductionDb()
	{
		return new Database(
			$this->config['database']['production']['host'],
			$this->config['database']['production']['username'],
			$this->config['database']['production']['password'],
			$this->config['database']['production']['dbName']
		);
	}
}
