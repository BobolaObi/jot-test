<?php
include "lib/init.php";

User::logout();
Utils::redirect(Utils::get('backTo')? Utils::get('backTo') : HTTP_URL);
