<?php

use Legacy\Jot\Utils\DB;

# no idea why, but this doesn't work with the name RequestServer....


class AccountUtils {
    public static function emailAlreadyRegistered($email) {
        $response = DB::read("SELECT `id` FROM `users` WHERE `email` = ':email' AND `account_type` != 'GUEST'", $email);
        if ($response->rows > 0) {
            return true;
        }
        return false;
    }
    
}