<?php

namespace Quarantine;;

use Legacy\Jot\Utils\DB;


class AccountUtils
{
    public static function emailAlreadyRegistered($email)
    {
        $response = DB::read("SELECT `id` FROM `users` WHERE `email` = ':email' AND `account_type` != 'GUEST'", $email);
        if ($response->rows > 0) {
            return true;
        }
        return false;
    }

}