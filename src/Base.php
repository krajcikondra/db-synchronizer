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

}
