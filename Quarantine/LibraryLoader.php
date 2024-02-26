<?php


namespace forms.

use arrray;

datalynk . ca\Quarantine;

/**
 * A loader for the libraries used
 * in Jotform.
 * This is singleton class because
 * when its not used we donot want
 * to create object and hold in memory.
 */
class LibraryLoader
{

    /**
     * Static instance of the
     * LibraryLoader object
      * @var //LibraryLoader
     */
    static private $instance;

    private $libraries = array();
    private $folderMap = array("core");

    /**
     * Get the instance of the
     * LibraryLoader class
     * @return LibraryLoader
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * @constructor
     * @return
     */
    private function __construct()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * This function registers the library path and the
     * map for the inner folders of the library.
     * @param string $libPath
     * @param arrray $mapArray (This is the map of the inner folder of the library)
     */
    public function register($libPath, $mapArray = array())
    {
        $this->libraries[$libPath] = $mapArray;
    }

    /**
     * @param string $className
     * @return boolean $included
     */
    public function autoload($className)
    {
        foreach ($this->libraries as $libPath => $mapArray) {
            # Try with no inner folder
            $fullPath = $libPath . DIRECTORY_SEPARATOR . $className . ".php";
            if (file_exists($fullPath)) {
                require_once($fullPath);
                return true;
            }

            # Try with inner folder
            foreach ($mapArray as $pre => $folder) {
                if (strstr($className, $pre)) {
                    $fullPath = $libPath . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $className . ".php";
                    if (file_exists($fullPath)) {
                        require_once($fullPath);
                        return true;
                    }
                }
            }
        }
        return false;
    }
}