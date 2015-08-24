<?php

namespace LegacyInteropBundle\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

//
// To make this work, add the following to the services.yml
// config file:
//
//  # override session service
//  session:
//      class: "%session.class%"
//      arguments: ["@session.storage", "@session.attribute_bag", "@session.flash_bag"]
//      configurator: [ "@session.legacy_configurator", initLegacySession]
//
//  # define session configurator service
//  session.legacy_configurator:
//      class : LegacyInteropBundle\Session\SessionConfigurator

class SessionConfigurator
{
    public function initLegacySession(SessionInterface $session, $session_name = 'LegacyApp')
    {
        if (php_sapi_name() == 'cli') {
            return;   // command line does not use sessions
        }

        $session->setName($session_name);
    }
}
