<?php

namespace Legacy\Jot\Api;

class FormController implements RestController{

	public function execute(RestServer $rest){
		$rest->getResponse()->setResponse("Hello, world!");
		return $rest;
	}
	
    public function get(RestServer $rest){
    	$view = new View();
    	$uri = $rest->getRequest()->getURI(1);

    	$formCode = "xxxxx";
        
    	$rest->setParameter("data", $formCode);
    	return $view;
    }
	
}
