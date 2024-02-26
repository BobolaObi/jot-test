<?php

use Legacy\Jot\Integrations\DropBoxIntegration;
use Legacy\Jot\Integrations\FTPIntegration;
use Legacy\Jot\JotRequestServer as RequestServer;

include_once "lib/init.php";

$data = $_POST ? $_POST : $_GET;

$request = new RequestServer($data);