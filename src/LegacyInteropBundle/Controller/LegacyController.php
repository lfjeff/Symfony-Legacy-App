<?php

/*
 * For an explanation of how things work, refer to this article:
 * http://www.enotogorsk.ru/en/2014/07/23/wrap-legacy-url/
 *
 */

namespace LegacyInteropBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use LegacyInteropBundle\LegacyBridge;

// If you are using Route annotations, you must
// configure the routing.yml file like this:
//
// app:
//     resource: "@LegacyInteropBundler/Controller/"
//     type:     annotation
//
// Also, the classes should be configured in services.yml
// like this:
//
// services:
//     legacy.controller:
//         class: LegacyInteropBundle\Controller\LegacyController
//         arguments: ["@legacy.bridge"]
//         calls:
//             - [ setContainer, ["@service_container"]]
//
//     legacy.bridge:
//         class: LegacyInteropBundle\LegacyBridge
//         arguments: ["@session", "%legacy_app_path%", "%legacy_globals_path%"]

/**
 * @Route(service="legacy.controller")
 */
class LegacyController extends ContainerAware
{
    private $legacy_bridge;

    private $use_template = false;

    private $filename;

    public function __construct(LegacyBridge $legacy_bridge)
    {
        $this->legacy_bridge = $legacy_bridge;
    }

    // Use the bin/generate_legacy_routing command to create these annotations.
    // If you don't like using annotations, you could also create a
    // legacy_routing.yml config file and store the details there (be sure to
    // import the file into routing.yml).  It should be fairly easy to modify
    // the script to output YAML commands instead of annotations.  If your
    // legacy code is changing a lot, it might make more sense to store the
    // routing details in a separate file that can be automatically updated.
    // However, in most cases, generating the legacy routing details is likely
    // to be a one-time job, so I put them all in the annotations..

    /**
     * @Route("/{filename}.php", name="_legacy", requirements={"filename": ".+"})
     * @Route("/", name="_legacy_index", defaults={"filename": "index"})
     */
    public function legacyAction(Request $request, $filename)
    {
        $this->filename = $filename;

        $do_redirect = false;
        $environment = $this->container->get('kernel')->getEnvironment();

        $legacy_response = $this->legacy_bridge->enterLegacyApp($filename.'.php');

        // If running under app_dev.php, adjust links so the legacy
        // app can find images, stylesheets, javascript, etc.
        //
        if ($environment === 'dev') {
            $legacy_response = $this->adjust_relative_links_for_dev_mode($legacy_response);
        }

        $response = new Response($legacy_response);

        // preserve status code
        $response->setStatusCode($this->getStatusCode());

        $headers_list = headers_list();
        foreach ($headers_list as $header) {
            // preserve Content-Type:
            if (stripos($header, 'content-type: ') === 0) {
                preg_match('/^content-type: (.*)$/i', $header, $match);
                $response->headers->set('Content-Type', $match[1]);
            }

            // handle redirects
            // NOTE: Under nginx, this code does not seem
            // to be reached.  Nginx appears to handle
            // the redirect by itself.  This needs to be investigated further.
            if (stripos($header, 'location: ') === 0) {
                preg_match('/^location: (.*)$/i', $header, $match);
                $response = new RedirectResponse($match[1], $this->getStatusCode());
                $do_redirect = true;
            }
        }

        if ($do_redirect) {
            return $response;
        }

        // NOTE - if the Symfony Toolbar goes missing in dev mode,
        // check to make sure the HTML is well-formed.  Symfony
        // will only insert the toolbar on properly formed HTML
        // pages.  It will not appear on plain text output or on
        // garbage HTML pages.

        if ($this->use_template) {
            return $this->render('default/legacy.html.twig', array(
                'legacy_html' => $legacy_response,
            ));
        }

        return $response;
    }

    // NOTE: This function will NOT adjust relative links in PHP code files
    // "include" or "require" statements.  These may break when running
    // under app_dev.php.  It is recommended that you edit your legacy
    // code and change all relative includes to ones that are absolute.
    //
    // For example:
    //
    //    include("includeme.php");
    //
    // should be changed to
    //
    //    require __DIR__.'/includeme.php';
    //
    // If you do this, the correct file should always be included.
    //
    // I cannot guarantee this function will work to correctly rewrite
    // all links output by your legacy code.  You may need to tweak
    // things if you notice something is not working correctly or
    // is displayed weird.
    //
    // Thanks to www.regextester.com for tools that are helpful in
    // building the regular expression patterns.
    //
    public function adjust_relative_links_for_dev_mode($input)
    {
        // get the base directory so we can
        // convert relative links to absolute
        $dir = dirname($this->filename);
        if ($dir === '.') {
            $prefix = '/';
        } else {
            $prefix = "/$dir/";
        }

        $absolute_url = '((?:https?:)?//|/|javascript)';
        $raw_data = '(?:data\:?:)';
        $relative_url = '\s*([\'""]?(('.$absolute_url.')|('.$raw_data.')))';

        $search = array(
            '~(src=|href=)(?!'.$relative_url.')\s*([\'""])?~',
            '~([:\s]url\(["\']?)(?!(https?://|/))~',
        );
        $replace = array(
            "\\0{$prefix}",
            "\\0{$prefix}",
        );

        return preg_replace($search, $replace, $input);
    }

    private function getStatusCode()
    {
        if (function_exists('http_response_code')) {
            $statusCode = http_response_code();
        } else {
            $statusCode = 200;  // fake it for older versions of PHP
        }

        return $statusCode;
    }

    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->container->get('templating')->renderResponse($view, $parameters, $response);
    }
}
