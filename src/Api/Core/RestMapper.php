<?php


/**
 * This class is used for calling the right controller object
 * from the url
 *
 */

namespace Legacy\Jot\Api\Core;

class RestMapper
{

    /**
     * @var //RestServer
     */
    private $rest;

    const ACTION_NAME = "action";

    /**
     * Contructer of the RestMapper
     * @param RestServer $rest
     * @return // RestMapper
     */
    public function __constructer(RestServer $rest = null)
    {
        $this->rest = $rest;
    }

    public function getResponseClass()
    {
        return "FormController";
    }

    public function getResponseMethod()
    {
        return "get";
    }

}
