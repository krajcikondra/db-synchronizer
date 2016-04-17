<?php

namespace Helbrary\DbSynchronizer;

use Nette\Object;

/**
 * Class WebApi
 * @package Helbrary\DbSynchronizer
 * @author Ondřej Krajčík <o.krajcik@seznam.cz>
 */
class WebApi extends Object
{

	/** @var  string */
	public $baseUrl;
	
	/** @var  string */
	public $authAction;
	
	/** @var  string */
	public $downloadDumpAction;
	
	/** @var  string */
	public $authUsername;
	
	/** @var  string */
	public $authPassword;

	/**
	 * WebApi constructor.
	 * @param string $baseUrl
	 * @param string $authAction
	 * @param string $downloadDumpAction
	 * @param string $authUsername
	 * @param string $authPassword
	 */
	public function __construct($baseUrl, $authAction, $downloadDumpAction, $authUsername, $authPassword)
	{
		$this->baseUrl = $baseUrl;
		$this->authAction = $authAction;
		$this->downloadDumpAction = $downloadDumpAction;
		$this->authUsername = $authUsername;
		$this->authPassword = $authPassword;
	}

}
