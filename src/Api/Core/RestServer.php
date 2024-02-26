<?php
/**
* Class RestServer 
*/

namespace Legacy\Jot\Api\Core;


class RestServer {

    private $response ;
    private $request ;
    private $authenticator ;
    private $versionController;
    private $mapper;
    
    private $stack ;
    
    /**
     * Contructor of RestServer
     * @param string $query Optional query to be treat as the URL
     * @return \\ RestServer $rest;
    */
    public function __construct($query=null) {
        
    	# Request handler
    	$this->versionController = new RestVersionController($this);   # Version controller handler
    	$this->mapper = new RestMapper($this);      # Mapper holder
        $this->request = new RestRequest($this);    # Request holder    
        $this->response = new RestResponse($this);  # Response holder
        $this->authenticator = new RestAuthenticator($this);   # Authenticator holder
        
        # If custom uri is send use it.
        if($query!==null) $this->getRequest()->setURI($query);
    }
    
    /**
     * Sets the mapper of the rest object
     * @param RestMapper $mapper
     */
    public function setMapper($mapper){
    	$this->mapper = $mapper;
    }
    
    /**
     * Returns the mapper of the RestServer
     * @return \\ RestMapper $mapper
     */
    public function getMapper(){
    	return $this->mapper;
    }

    /**
    * Sets a parameter in a global scope that can be recovered at any request.
    * @param mixed $key The identifier of the parameter
    * @param mixed $value The content of the parameter
    * @return \\ RestServer $rest
    */
    public function setParameter($key,$value) {
    	return $this->getResponse()->setParameter($key, $value);
    }

    /**
    * Return the specified parameter
    * @param mixed $key The parameter identifier
    * @return \\ mixed
    */
    public function getParameter($key) {
        return $this->getResponse()->getParameter($key);
    }

    /**
    * Get the Response handler object
    * @return \\ RestResponse
    */
    public function getResponse() {
        return $this->response ;
    }

    /**
     * Get the Request handler object
    * @return \\ RestRequest
    */
    public function getRequest() {
        return $this->request ;
    }

    /**
     * Get the Authentication handler object
    * @return \\ RestAuthenticator
    */
    public function getAuthenticator() {
        return $this->authenticator ;
    }
    
    /**
     * Get the Version handler object
     * @return \\ RestVersionController
     */
    public function getVersionController(){
        return $this->versionController;
    }
    
    /**
    * Return last class name from RestServer stack trace
    * @return \\ string 
    */
    public function lastClass() {
        $i = count($this->stack);
        return $this->stack[$i - 1];
    }

    /**
    * Run the Server to handle the request and prepare the response
    * @return \\ string $responseContent
    */
    public function execute() {
    	# If auth is required and its not ok, response is 401
        if(!$this->getAuthenticator()->tryAuthenticate()) {
            return $this->show();
        }
        
        # Get the api version. Return HTTP 3xx response in  a failure
        if ($this->getVersionController()->setCurrentVersion() === false){
            # Redirect to latest API Version
            $this->getResponse()->addHeader("Location: " . 
                    HTTP_URL . $this->getRequest()->getAPIURI() . 
                    $this->getRequest()->getURI() );
            return $this->show();
        }

        # This is the class name to call
        $responseClass = $this->getMapper()->getResponseClass();
        $responseMethod = $this->getMapper()->getResponseMethod();
        
        # If no class was found, response is 404
        if(!$responseClass || !$this->includeControllerFile($responseClass)) {
            $this->getResponse()->cleanHeader();
            $this->getResponse()->addHeader("HTTP/1.1 404 Not found");
            $this->getResponse()->setResponse("HTTP/1.1 404 NOT FOUND");
            return $this->show();
        }
        
        # Call the class and return the response
        return $this->call(new $responseClass,$responseMethod)->show();
    }

    private function call($class,$method=null) {
    	          
        $this->stack[] = get_class($class) ;
        if($method != null) {
        	# If method is not null execute uri method.
        } else if($class instanceof RestView) { # If is a view, call show(RestServer)
            $method="show";
        } else if($class instanceof RestController)  {  # If is a controller, call execute(RestServer)
            $method="execute";
        } else {
            throw new \Exception(get_class($class)." is not a RestAction");
        }
        
        if (method_exists($this->lastClass(), $method)){
            $class = $class->$method($this);
        }else{
            throw new \Exception("Cannot find method: " . $this->lastClass() . "::" . $method);
        }

        if($class instanceof RestAction 
            && get_class($class) != $this->lastClass() ) {
            return $this->call($class); # May have another class to follow the request
        }

        return $this ;
    }

    # 
    private function show() {
    	# Call headers, if no yet
        if(!$this->getResponse()->headerSent()) {
            $this->getResponse()->showHeader();
        }
        # Return response content;
        return $this->getResponse()->getResponse() ;
    }
    
    /**
     * Includes the control file that the method will be
     * called.
     * @param string $class: The action that is defined in the map
     * @throws Exception: Throws exception when the file cannot be included.
     */
    private function includeControllerFile($className){
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR .
                    "../controllers" . DIRECTORY_SEPARATOR .
                    $this->getVersionController()->getCurrentVersion() . DIRECTORY_SEPARATOR .
                    $className . ".php";
        if (is_file($filename)){
            require_once $filename;
        }else{
            return false;
        }
        return true;
    }
}

?>