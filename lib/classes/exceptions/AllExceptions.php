<?php

@include_once 'JotFormException.php';
/**
 * When record not found
 * @package JotForm_Exceptions
 */
class RecordNotFoundException extends JotFormException {}
/**
 * When No Changes has made
 * @package JotForm_Exceptions
 */
class NoChangesMadeException extends JotFormException {}
/**
 * When a system exception has occured
 * @package JotForm_Exceptions
 */
class SystemException extends  JotFormException {}

class Warning extends  JotFormException {}

class DBException extends  JotFormException {}
/**
 * Throws error but send status 200
 */
class SoftException extends  JotFormException {}
/**
 * Throws error but send status 200
 */
class LDAPException extends  JotFormException {}
