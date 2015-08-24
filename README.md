## Welcome to Symfony - Legacy App Edition ##

Have you ever wanted to wanted to convert your legacy code to Symfony, but didn't know where to start?

At first, getting a large legacy application converted to Symfony can seem like a daunting task.  Doing a "Big Rewrite" is usually not practical (or advisable, as [most of these projects result in failure](http://chadfowler.com/blog/2006/12/27/the-big-rewrite/ "Big Rewrite Failures")).

You know that Symfony offers many advantages, but how do you keep your current code running while getting to your ultimate goal?

For several months, I had been attempting to convert old code by using Silex to gradually refactor a legacy application into a more modern architecture.  This was meant as a "stepping-stone" to full-blown Symfony.

Then I discovered an [article](http://www.enotogorsk.ru/en/2014/07/21/introduction/ "Symfony and Legacy Code") by Alexander Ulyanov and realized I could skip the Silex step and go straight to Symfony.

Using his ideas and some of my own improvements, I created this package that will let you run **BOTH** legacy code and Symfony together. Now you can keep your legacy code running while you gradually convert to the Symfony framework.

This package lets you create a "wrapper" around your current legacy code.  All requests will be run through a single controller and then routed to your legacy code (or sent to your beautiful new Symfony code).

If you're lucky, you may be able to run your legacy code with little or no modifications.  

It supports the following:

- All routing will be handled by Symfony (which means better security)
- Global variables in legacy code
- Register globals in legacy code (ugh!)
- Read/write of session variables between legacy code and Symfony code
- Legacy code can easily access Symfony services
- Symfony Web Debug toolbar can be used with legacy code

Does this all sound too good to be true?

I can't promise you this package will handle all the quirks of your legacy code, but it should give you a good headstart on the road to a successful Symfony conversion.

Let's get started...

## Setup ##

Clone this repository into your project directory:

    git clone https://github.com/lfjeff/Symfony-Legacy-App.git my_project

Install dependencies:

    cd my_project
    composer install


You should now have a simple working demo website that will show how session variables can be used between legacy and Symfony code.

Your legacy code is run from the `legacy-app` directory.  New code will run from the `src/AppBundle` directory (like a normal Symfony project).

Code in `src/LegacyInteropBundle` provides the bridge between old and new.

For details on how everything works, I suggest you read Alexander's article at [http://www.enotogorsk.ru/en/2014/07/21/introduction/](http://www.enotogorsk.ru/en/2014/07/21/introduction/ "Symfony and Legacy Code: Introduction")



## Running Your Legacy Code ##


Once you have the demo working, now it's time for the real test of running your legacy code.

To do this, first copy your legacy code to the `legacy-app` directory or make `legacy-app` a symlink to your legacy code:

    mv legacy-app legacy-app.save
    ln -s /my_legacy/www legacy-app

Next, you'll need to copy all your static files (those without a `.php` suffix) to the `web` directory.  I've created the `rsync_static_to_web` helper program to make things easier.  This program calls the `rsync_static_to_web.sh` script which does the actual work.

Before blindly running the script, I suggested you review the `rsync_static_to_web.sh` script and tweak as necessary.  You may want to include or exclude certain files specific to your application.

Everything tweaked? OK, let's copy the files:

    php bin/rsync_static_to_web

Please note that except for `app.php` and `app_dev.php` (on your development system), there should be NO `.php` files in the `web` directory or subdirectories.

Now you'll need to define which global variables are used by by your legacy code.  Edit the `app/legacy_globals.php` file and create a `global $varname` entry for each global variable.

To enable communication of session variables between legacy code and Symfony, edit the `app/legacy_session_keys.php` file and create an array with all the session keys and their type.  For example, if your legacy code uses the `$_SESSION['userid']` variable (which contains a single value) and the `$_SESSION['cart']` variable (which is an array), you would define the following array:

    $sessionKeysType = array(
    	'userid' => 'scalar',
    	'cart' => 'array',
    );

Finally, you'll need to create the routing for your legacy code using another helper routine. We'll generate the routes and then paste the results into the `src/LegacyInteropBundle/Controller/LegacyController.php` file.

    php bin/generate_legacy_routing > /tmp/routes

Now edit the `LegacyController.php` file and paste the `/tmp/routes` information immediately above the `legacyAction()` function.  Make sure you replace any old routes that may have been leftover from the demo.

We're using annotation routing.  If you prefer putting the routes in YAML files and know what you're doing, feel free to do so.

To verify the routes, run the following command:

    php app/console debug:route

The `showdata` route may still be visible, since it is part of the demo.  This can be changed by editing the `src/AppBundle/Controller/DefaultController.php` file.  You'll probably be doing this anyway as you migrate your legacy code to Symfony.

## Accessing Symfony Services and Parameters From Legacy Code ##

This package provides `s()` and `p()` functions that will allow you to access Symfony services and parameters from within your legacy code.  If you're moving towards a Service Oriented Architecture, these functions might be helpful.

For example, if your legacy code needs to access a mailer service you have set up in Symfony, you can do something like this:

    $mailer = s('mailer');

Similarly, if your legacy code needs to access a Symfony parameter, just do this:

	$db_name = p('db_name');

If the `s()` and `p()` functions are already used in your legacy code, you can change the names by editing the `AppKernel.php` file.

## Tips and Caveats ##

This package assumes your legacy code is using URLs that all end with a `.php` suffix.  If your legacy code has its own routing system or uses different suffixes, this package may not work for you.  However, with a little work, you could probably modify things to do the job.  It's impossible to create a package to handle all legacy code, so consider this package as part of your toolbox for migrating to Symfony.

If your legacy code requires "register globals" to work, edit the  `parameters` section of the `config.yml` file and set `legacy_register_globals` to `true`.


If you are using `app_dev.php` for debugging legacy code and notice problems with how the page is displayed, check the `adjust_relative_links_for_dev_mode()` function in `LegacyController.php`.  This is the function that rewrites links on-the-fly to adjust for the offset caused by the "virtual" `app_dev.php/` directory.

If your legacy code uses the `ob_start()` routine to capture output, then this package may not work correctly.

This package does a `chdir()` to the `legacy-app` directory in an attempt to make old `include` statements work correctly.  If you have problems, we recommend converting your legacy `include` statements that reference relative files to an equivalent `include` or `require` that references an absolute path.  For example:

    include("includethis.php");

should be converted to:

    require __DIR__.'/includethis.php';

## Feedback ##

If you have any suggestions, improvements or bug reports, please let me know.

Thanks,

Jeff Groves
