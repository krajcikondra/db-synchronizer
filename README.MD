# helbrary/db-synchronizer

DbSynchronizer is library for speed-up often repeated operations with remote database of some web which does not enable connect to database from external. Library is intended for nette framework.


Functions:

  - Backup remote database
  - Download dump of remote database
  - Import dump of remote database to local database


## QuickStart

Parameters for config
```sh
		dbSyncParams:
			web:
				productionBaeUrl: 'http://somewebpage.cz'
				authAction: 'db-dump-login' # it means thath authorization action must be available on url 'http://somewebpage.cz/db-dump-login'
				dumpDownloadAction: 'db-download-dump' # it means thath download dump action must be available on url 'http://somewebpage.cz/db-download-dump'
				authUsername: 'admin'
				authPassword: 'admin_password'
			database:
				production:
					host: 'some_host'
					dbname: 'my_remote_db'
					user: 'admin'
					password: 'admin_password'
				local:
					host: 'localhost'
					dbname: 'my_db'
					user: 'root'
					password:
```

Register commands for console

```sh
	console:
		commands:
			- Helbrary\DbSynchronizer\Commands\DatabaseCommand(%tempDir%, %dbSyncParams%)
```

### Server side
Now is neccessary implement API for db synchronizator on server side. It can looks like this:

```sh

    // function for authorization
	public function actionAuthenticateForDump($username, $password)
	{
		try {
			$identity = $this->authenticator->authenticate( array( $username, $password ) );
			if (in_array('admin', $identity->roles)) {
				$this->dbSynchronizer->setAsAuthorized();
			}
		} catch (AuthenticationException $e) {
			Debugger::log($e, Base::LOG_DIRECTORY_NAME);
		}
		$this->terminate();
	}



	public function actionDownloadDump($host, $username, $password, $db)
	{
		try {
			$dump = $this->dbSynchronizer->dump($host, $username, $password, $db);
			$fileResponse = new FileResponse($dump, 'dump.sql', 'application/sql', TRUE);
			$this->presenter->sendResponse($fileResponse);
		} catch (\Exception $e) {
			Debugger::log($e, Base::LOG_DIRECTORY_NAME);
			header("HTTP/1.0 404 Not Found");
		}
		$this->terminate();
	}
```


### Client side

Open console and execute:
```sh
php index.php db --sync-local # for download remote dump and import to local db
php index.php db --backup-remote # for backup remote db, dump of db will be saved in temp/dumps directory of web
```


