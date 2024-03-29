<?php


namespace Quarantine;;

use forms;

class Funnel extends ABTesting{

        public
        $myName = __CLASS__;

        protected
        $totalGroup = 1;
        protected
        $maxParticipient = 100000;
        protected
        $resetOnLogin = true;

        static function getClass()
        {
            return __CLASS__;
        }

        static function getGroupNames()
        {
            return self::$groupNames;
        }

        public
        function checkParticipant()
        {

            if ($this->user->accountType == 'GUEST') {
                return true;
            }

            return false;
        }

    }
