<?php


namespace forms.
datalynk . ca\Quarantine;

use formsclass;

UpgradeTest extends ABTesting{

        public
        static $myName = __CLASS__;

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

            if ($this->user->accountType == "FREE") {
                if ($usage = $this->user->getMonthlyUsage()) {
                    if ($usage['submissions'] > 70) {
                        return true;
                    }
                }
            }

            return false;
        }

    }
