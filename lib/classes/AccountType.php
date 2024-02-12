<?php
/**
 * This class models account types. Kinda like an account factory.
 * Limits must be dynamically adjustable.
 * Also, we will be adding account types from the config.php file in the beginning.
 * @package JotForm_User_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\User_Management;

class AccountType {
    # Account variables.
    private static $accounts = array();
    public $name, $prettyName;
    public $limits = array();

    /**
     * Constructs account types
     * @param string $name Unique type name
     * @param string $prettyName Reprensentitive type name
     * @param object $newLimits hash of limits
     * @return mixed
     */
    public function __construct($name, $prettyName, $newLimits) {
        $this->name = $name;
        $this->prettyName = $prettyName;
        foreach(MonthlyUsage::$limitFields as $classField => $dbField) {
            if (isset($newLimits[$classField])) {
                $this->limits[$classField] = $newLimits[$classField];
            } else {
                // Set the limit with an empty value.
                $this->limits[$classField] = 0;
            }
        }
    }
    
    /**
     * Creates an account and adds it to the $accounts array.
     * @param object $conf 
     * @return null
     */
    public static function create($conf) {
        self::$accounts[$conf['name']] = new AccountType($conf['name'], $conf['prettyName'], $conf['limits']);
    }
    
    /**
     * Gets all properties of an account type. Use this static method to 
     * retrieve an account type you want. Ex.
     * <code>
     * $free = AccountType::find('FREE');
     * $pre = AccountType::find('PREMIUM');
     * if ($currentSub > $pre->limits['submissions']) {
     *     return "You have exceeded your limits";
     * } else {
     *     return "You have used " . $currentSub . " free submissions.";
     * }
     * </code>
     * @param object $name
     * @return AccountType specified account type or FREE account by default
     */
    public static function find($name) {
        if (isset($name) && self::$accounts[$name]) {
            return self::$accounts[$name];
        } else {
            # Return FREE account type if appropriate one is not found.
            return self::$accounts['FREE'];
        }
    }
    
    public static function getAllAccountTypes () {
        $accountTypes = array();
        foreach (self::$accounts as $account){
            array_push($accountTypes, $account->name);
        }
        return $accountTypes;
    }
}
