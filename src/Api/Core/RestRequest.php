<?php

/** Class RestRequest
 * Holds the Request in a RestServer
 */

namespace Legacy\Jot\Api\Core;

class RestRequest
{

    /**
     * @var //RestServer
     */
    private $rest;

    private $requestURI;
    private $URIParts;

    private $requestMethod;
    private $get;
    private $post;
    private $files;

    /**
     * Holds the wanted api version from
     * the URI
     * @var //string
     */
    private $wantedAPIVersion;

    /**
     * The root uri of the api
     * @var //string
     */
    private $apiURI;

    /**
     * Constructor of RestRequest
     * @param RestServer $rest = null, Parent RestServer
     */
    public function __construct(RestServer $rest = null)
    {

        // Sets most of the parameters
        $this->rest = $rest;

        if (isset($_SERVER["REQUEST_METHOD"]))
            $this->requestMethod = $_SERVER["REQUEST_METHOD"];
        if (isset($_SERVER["REQUEST_URI"]))
            $this->setURI($_SERVER["REQUEST_URI"]);

        $this->get = $_GET ? $_GET : [];
        $this->post = $_POST ? $_POST : [];
        $this->files = $_FILES ? $_FILES : [];

    }

    /**
     * Return  RestServer used;
     * @return // RestServer
     */
    public function getRest()
    {
        return $this->rest;
    }

    /**
     * Returns if Request is GET
     * @return // boolean
     */
    public function isGet()
    {
        if ($this->requestMethod == "GET") {
            return true;
        }
        return false;
    }

    /**
     * Returns if Request is POST
     * @return // boolean
     */
    public function isPost()
    {
        if ($this->requestMethod == "POST") {
            return true;
        }
        return false;
    }

    /**
     * Return if Request is PUT
     * @return // boolean
     */
    public function isPut()
    {
        if ($this->requestMethod == "PUT") {
            return true;
        }
        return false;
    }

    /**
     * Return true if Request is DELETE
     * @return // boolean
     */
    public function isDelete()
    {
        if ($this->requestMethod == "DELETE") {
            return true;
        }
        return false;
    }


    /**
     * Get parameters sent with GET (url parameters)
     * @param mixed $k get[$key]
     * @return // mixed
     */
    public function getGet($k = null)
    {
        if ($k == null) return $this->get;
        else return $this->get[$k];
    }

    /**
     * Return parameters sent on a POST
     * @param mixed $k post[$key]
     * @return // mixed
     */
    public function getPost($k = null)
    {
        if ($k == null) return $this->post;
        else return $this->post[$k];
    }

    /**
     * Return FILES sent on a POSt
     * @param mixed $k file[$key]
     * @return // mixed
     */
    public function getFiles($k = null)
    {
        if ($k == null) return $this->files;
        else return $this->files[$k];
    }

    /**
     * Return content sent with PUT
     * @param mixed $k
     * @return // mixed
     */
    public function getPut($k = null)
    {
        $_PUT = [];
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            $putdata = file_get_contents('php://input');
            $exploded = explode('&', $putdata);
            foreach ($exploded as $pair) {
                $item = explode('=', $pair);
                if (count($item) == 2) {
                    $_PUT[urldecode($item[0])] = urldecode($item[1]);
                }
            }
        }
        if ($k == null) return $_PUT;
        else return $_PUT[$k];
    }

    /**
     * Return content sent with PUT
     * @return // mixed
     */
    public function getInput()
    {
        return file_get_contents('php://input');
    }

    /**
     * Return request BODY
     * @return // string
     */
    public function getBody()
    {
        return $this->getInput();
    }

    /**
     * Return user sent on BASIC Authentication
     * @return // string
     */
    public function getUser()
    {
        return $this->rest->getAuthenticator()->getUser();
    }

    /**
     * Return password sent on Basic Authentication
     * @return // string
     */
    public function getPassword()
    {
        return $this->rest->getAuthenticator()->getPassword();
    }

    /**
     * Return Request Method(PUT, DELETE, OPTION, GET...)
     * @return // string
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set request method
     * @param string $method
     * @return // RestRequest
     */
    public function setMethod($method)
    {
        $this->requestMethod = $method;
        return $this;
    }

    /**
     * Return the URI requested
     * @return // string
     */
    public function getRequestURI()
    {
        return $this->requestURI;
    }

    /**
     * Return part of the URL
     * @param int $i part of the uri
     * @return // string
     */
    public function getURIpart($i)
    {
        if (isset($this->URIParts[$i]))
            return $this->URIParts[$i];
        else
            return null;
    }

    /**
     * Return the URI or part of it
     * @param int $i part of the uri
     * @return // string
     */
    public function getURI($i = null)
    {
        if ($i !== null) return $this->getURIpart($i);
        return $this->getRequestURI();
    }

    /**
     * Sets the URI to deal
     * @param string $uri
     * @return // RestRequest
     */
    public function setURI($uri)
    {
        $tempParts = explode("/", $uri);

        # This is the api folder name. Remove it.
        if (isset($tempParts[1])) {
            $spliceResult = array_splice($tempParts, 1, 1);
            $this->apiURI = $spliceResult[0];
        }

        # Remove the version if the version is in correct syntax
        if (RestVersions::getInstance()->isVersionSyntaxCorrect($tempParts[1])) {
            $spliceResult = array_splice($tempParts, 1, 1);
            # If the version is like v3 convert to v3.0
            $version = $spliceResult[0];
            if (count(explode('.', $version)) === 1) {
                $version = $version . ".0";
            }
            $this->wantedAPIVersion = $version;
        }

        $this->requestURI = implode("/", $tempParts);
        $this->URIParts = explode("/", $this->requestURI);

        return $this;
    }

    /**
     * Return the uri of the api.
     * @return // string $apiURI
     */
    public function getAPIURI()
    {
        return $this->apiURI;
    }

    /**
     * Return the extension of the URI (if any)
     * @return // string
     */
    public function getExtension()
    {
        $reg = [];
        preg_match('@\.([a-zA-Z0-9]{1,5})$@', $this->getURI(), $reg);
        if (isset($reg[1]))
            return $reg[1];
        else
            return false;
    }

    /**
     * Return true if given mime is accepted
     * @param string $mime to check
     * @return // boolean
     */
    public function acceptMime($mime)
    {
        if (($pos = strpos($_SERVER["HTTP_ACCEPT"], $mime)) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the wanted API version fetched from the url.
     * @return // string $wantedAPIVersion
     */
    public function getWantedAPIVersion()
    {
        return $this->wantedAPIVersion;
    }

}

?>