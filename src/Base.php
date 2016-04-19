<?php

namespace Helbrary\DbSynchronizer;

use Nette\Object;

/**
 * Class Base
 * @package Helbrary\DbSynchronizer
 * @author Ondřej Krajčík <o.krajcik@seznam.cz>
 */
class Base extends Object
{
	const SESSION_USER_NAME = 'helbrary_db_user';
	const SESSION_IS_LOGGED = 'helbrary_db_logged';

	const LOG_DIRECTORY_NAME = 'db_synchronizer';


	/**
	 * Check if dump file exists and is not empty
	 * @param string $dumpPath - path to dump file
	 * @return bool
	 * @throws DumpNotFoundException
	 * @throws DumpFileIsEmptyException
	 */
	public static function checkDump($dumpPath)
	{
		if (!file_exists($dumpPath)) {
			throw new DumpNotFoundException($dumpPath);
		}

		if (filesize($dumpPath) <= 1) {
			throw new DumpFileIsEmptyException($dumpPath);
		}

		return TRUE;
	}
}
