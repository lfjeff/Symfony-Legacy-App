<?php

namespace LegacyInteropBundle\Session;

use Theodo\Evolution\Bundle\SessionBundle\Manager\BagManagerConfigurationInterface;

//
//  To make everything work, add this to the config.yml file:
//
//  # Handle sessions for legacy code
//  theodo_evolution_session:
//  bag_manager:
//      class: Theodo\Evolution\Bundle\SessionBundle\Manager\BagManager
//      configuration_class: LegacyInteropBundle\Session\LegacyBagManagerConfiguration
//

class LegacyBagManagerConfiguration implements BagManagerConfigurationInterface
{
    private $sessionKeysType = array();
    private $keysArray = array();

    public function __construct() {
        //
        // THIS IS THE IMPORTANT PART
        //
        // Define an array of all $_SESSION keys (and their type)
        // which you want to be available as bags.
        //
        // For convenience, the values are defined in a
        // separate configuration file.
        //
        require __DIR__.'/../../../app/legacy_session_keys.php';

        $this->sessionKeysType = $sessionKeysType;
    }

    public function getNamespace($key)
    {
        return $key;
    }

    public function getNamespaces()
    {
        foreach ($this->sessionKeysType as $key => $type) {
            $this->keysArray[] = $key;
        }

        return $this->keysArray;
    }

    public function isArray($key)
    {
        if (isset($sessionKeysType[$key]) && $sessionKeysType[$key] === 'array') {
            return true;
        }

        return false;
    }
}
