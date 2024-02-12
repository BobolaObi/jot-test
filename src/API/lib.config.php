<?php

# Register the library to the system.
LibraryLoader::getInstance()->register(dirname(__FILE__), array("Rest" => "core"));

# Define the versions of the API system.
$versionSystem = RestVersions::getInstance();
$versionSystem->addVersion("v0.0", true);