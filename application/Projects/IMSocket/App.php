<?php
use Projects\IMSocket\SwooleServer;

require_once dirname(dirname(__DIR__)).'/APP/BaseApp.php';

global $command;

switch ($command)
{
	case "e":
	    new SwooleServer();
	    break;
	default:
	    echo "Nothing to do!\n";
}