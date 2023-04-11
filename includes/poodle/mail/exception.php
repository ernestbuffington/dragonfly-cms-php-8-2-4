<?php

namespace Poodle\Mail;

class Exception extends \Poodle\Exception
{
	protected $response;
	function __construct($message, $code, $response)
	{
		parent::__construct($message, $code);
		$this->response = $response;
	}
	final function getResponse() { return $this->response; }
}
