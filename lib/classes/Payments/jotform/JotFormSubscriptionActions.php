<?php

/**
 * This class is used as enumaration
 * because PHP hasn't got native
 * enumarations.
 */
class JotFormSubscriptionActions {
    
    /**
     * Sign up enumations
     * @var String constant
     */
    const SignUp    = "signup";
    const Payment   = "payment";
    const Cancel    = "cancel";
    const EOT       = "eot";
    const Modify    = "modify";
    const Failed    = "failed";
    const Refund    = "refund";
    const Overlimit = "overlimit";
}

