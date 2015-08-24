<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/showdata", name="showdata")
     */
    public function showdataAction(Request $request)
    {
        // If you want to access $_SESSION variables from
        // your legacy code, you must define each key
        // in app/legacy_session_keys.php

        $session = $request->getSession();

        // value of $_SESSION[userid] from legacy code
        $userid = $session->getBag('userid')->get();

        // value of $_SESSION[username] from legacy code
        $username = $session->getBag('username')->get();

        return $this->render('showdata.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
            'userid' => $userid,
            'username' => $username,
        ));
    }
}
