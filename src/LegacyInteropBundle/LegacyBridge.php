<?php

/*
 * This code inspired by (or stolen from) a blog post by Alexander Ulyanov:
 * http://www.enotogorsk.ru/en/2014/07/21/introduction/
 *
 * Also, ideas from this article:
 * http://www.slideshare.net/fabrice.bernhard/modernisation-of-legacy-php-applications-using-symfony2-php-northeast-conference-2013
 *
 */

namespace LegacyInteropBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// this class should be registered as a service in
// the services.yml config file

class LegacyBridge
{
    private $session;

    private $legacy_app_path;
    private $legacy_globals_path;
    private $legacy_error_reporting = E_ERROR;
    private $legacy_PHP_SELF;
    private $legacy_DOCUMENT_ROOT;
    private $legacy_REQUEST_URI;
    private $legacy_SCRIPT_FILENAME;
    private $legacy_register_globals = false;

    private $cwd;

    private $save_error_reporting;

    public function __construct(SessionInterface $session,
        $legacy_app_path,
        $legacy_globals_path,
        $legacy_register_globals = false)
    {
        $this->session = $session;
        $this->legacy_app_path = $legacy_app_path;
        $this->legacy_globals_path = $legacy_globals_path;
        $this->legacy_register_globals = $legacy_register_globals;
    }

    public function enterLegacyApp($legacy_filename = 'init.php', Request $request = null)
    {
        if ($this->legacy_register_globals && $request) {
            // extract $_GET and $_POST variables
            // to emulate register globals
            if ($request->request instanceof ParameterBag) {
                extract($request->request->all());
            }

            if ($request->query instanceof ParameterBag) {
                extract($request->query->all());
            }
        }

        // Start Symfony session before calling legacy code.
        // This means you can remove any session_start() calls
        // in the legacy code (which will eliminate E_NOTICE messages).
        if (php_sapi_name() != 'cli') {
            $this->session->start();
        }

        // define all globals used by legacy code
        require_once $this->legacy_globals_path;

        // Set this so legacy code can detect if it is
        // running under this bridge.
        if (!defined('SYMFONY_LEGACY_BRIDGE')) {
            define('SYMFONY_LEGACY_BRIDGE', true);

            // Symfony has already loaded Swiftmailer
            define('SWIFT_REQUIRED_LOADED', true);
        }

        // Make sure we don't include the bootstrap (init.php)
        // code twice.  'INCLUDED_INIT_PHP' is defined in the
        // init.php file.
        if (defined('INCLUDED_INIT_PHP')) {
            if ($legacy_filename == 'init.php') {
                // do nothing if we try to include bootstrap file twice
                return;
            }
            throw new \RuntimeException("LegacyApp already initialized, unable to include '$legacy_filename'");
        }

        $this->legacy_DOCUMENT_ROOT = realpath($this->legacy_app_path);
        $this->legacy_SCRIPT_FILENAME = $this->legacy_DOCUMENT_ROOT.'/'.$legacy_filename;

        // Make sure this is a readable file before we try to process it.
        // If not, throw a 404 error.
        if (!(is_file($this->legacy_SCRIPT_FILENAME)
            && is_readable($this->legacy_SCRIPT_FILENAME))) {
            throw new NotFoundHttpException();
        }

        // set error reporting
        $this->save_error_reporting = error_reporting($this->legacy_error_reporting);

        // set current directory to handle code that uses relative paths
        $this->cwd = getcwd();
        chdir($this->legacy_DOCUMENT_ROOT);

        // adjust $_SERVER variables so that legacy code
        // does not know it is running under Symfony.
        $this->legacy_REQUEST_URI = preg_replace('~^/app_dev\.php~', '', $_SERVER['REQUEST_URI']);
        $this->legacy_PHP_SELF = preg_replace('/\?.*$/', '', $this->legacy_REQUEST_URI);

        $_SERVER['REQUEST_URI'] = $this->legacy_REQUEST_URI;
        $_SERVER['PHP_SELF'] = $this->legacy_PHP_SELF;
        $_SERVER['SCRIPT_NAME'] = $this->legacy_PHP_SELF;
        $_SERVER['SCRIPT_FILENAME'] = $this->legacy_SCRIPT_FILENAME;
        $_SERVER['DOCUMENT_ROOT'] = $this->legacy_DOCUMENT_ROOT;

        // Run legacy code in output buffer mode and
        // capture the response.
        //
        // You should inspect legacy code for any exit() or die()
        // calls that may cause problems by terminating
        // before we can capture the output.  Also, look for
        // any files that use ob_*() functions, as those could
        // cause problems.
        //
        ob_start();
        require $this->legacy_SCRIPT_FILENAME;
        $legacy_response = ob_get_clean();

        // restore error reporting for new code
        error_reporting($this->save_error_reporting);

        // restore original directory
        chdir($this->cwd);

        return $legacy_response;
    }
}
