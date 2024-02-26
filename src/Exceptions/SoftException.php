<?php

namespace Legacy\Jot\Exceptions;
use Legacy\Jot\Exceptions\DBException;
use Legacy\Jot\Exceptions\JotFormException;
use Legacy\Jot\Exceptions\LDAPException;
use Legacy\Jot\Exceptions\NoChangesMadeException;
use Legacy\Jot\Exceptions\RecordNotFoundException;
use Legacy\Jot\Exceptions\SoftException;
use Legacy\Jot\Exceptions\SystemException;
use Legacy\Jot\Exceptions\Warning;

/**
 * Throws error but send status 200
 */
class SoftException extends  JotFormException {}
