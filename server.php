<?php

use Legacy\Jot\RequestServer;

include_once "lib/init.php";

$data = $_POST ? $_POST : $_GET;

$request = new RequestServer($data);