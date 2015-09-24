<?php
header('p3p: CP="NOI ADM DEV PSAi COM NAV OUR OTR STP IND DEM"');
/**
 *	web-T::Portal
 *
 *	@version 6.0
 *	@author web-T <info@web-t.com.ua>
 *	@package web-T[Frontend]
 *	@copyright Copyright (c) 2006, web-T 2006-2015
 *
 */

define('WEBT_APP', 'Frontend');

include('../app/common.php');

if ($p->getVar('is_debug')){
    $p->debug->add("INDEX: After common.php");
}

// fix post var
$p->query->request->fixPostMagic();

// create request for routing
$p->query->request->createFromGlobals();

// connect development controller, which prepare dev. environment or check for overloading
if ($p->getVar('is_dev_env')){
    // make something, if you on development version
} else {
    // check server overload
    if ($p->server->checkOverload()){
        // do something if there is overload happened
    }

    if ($p->getVar('is_debug'))
        $p->debug->add("INDEX: check_overload");
}

// check banned ips
if (!$p->user->checkAccessIP()){
    $p->redirect('http://google.com');
}

if ($p->getVar('is_debug'))
    $p->debug->add("INDEX: After user->checkAccessIP");


// if we have post data - do something (maybe turn off caching)
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // ...
}


// apply anonymous rules
if (!$p->user->isAuth())
    $p->user->authAnonymous();

if ($p->getVar('is_debug'))
    $p->debug->add("INDEX: After checkAuth");

// add some GEO targeting magic
//...

// add default route
$p->query->addRoute('__default__', new \webtFramework\Components\Request\oRoute(
    '.*',
    null,
    array('_controller' => 'APPLICATION:CONTROLLER:ACTION')
));


$p->query->route();


