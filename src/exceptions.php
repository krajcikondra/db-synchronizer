<?php

namespace Helbrary\DbSynchronizer;

class RequestIsNotAuthorizedException extends \LogicException
{}

class DumpNotFoundException extends \RuntimeException
{}

class DumpFileIsEmptyException extends \RuntimeException
{}

class BadResponseException extends \RuntimeException
{
	function __construct($code)
	{
		parent::__construct('Reponse return http code ' . $code);
	}
}

class DatabaseConnectionFailedException extends \RuntimeException
{}
