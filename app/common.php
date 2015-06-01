<?php

/**
* webT::Framework bootstrap file
*	
* @author goshi <info@web-t.com.ua>
* @package web-T[kernel]
*	
* since 2008
*
*/
chdir(substr(__FILE__, 0, strrpos(__FILE__, DIRECTORY_SEPARATOR)));

use webtFramework\Helpers\Debug;
use webtFramework\Core\oPortal;

require_once('lib/Helpers/Debug.inc.php');

global $INFO;

/**
* include debug library
*/
Debug::startTimer();

Debug::add('COMMON: Start', $INFO);

include('etc/common.conf.php');

date_default_timezone_set($INFO['timezone']);
setlocale(LC_ALL, $INFO['locale']);

Debug::add('COMMON: After set INFO', $INFO);

require_once('lib/Common/Autoloader.inc.php');
Debug::add('COMMON: After load FRAMEWORK AUTOLOADER class', $INFO);

if (file_exists($INFO['BASE_APP_DIR'].$INFO['vendor_dir'].'autoload.php')){
    require_once($INFO['BASE_APP_DIR'].$INFO['vendor_dir'].'autoload.php');
    Debug::add('COMMON: After load VENDOR AUTOLOADER class', $INFO);
}

// only now creating portal
// change directory to app
chdir($INFO['BASE_APP_DIR']);

$p = new oPortal();

if ($p->getVar('is_debug')){

	$p->debug->add("COMMON: After creating portal");

}

// setting server name for console applications
if (!isset($_SERVER['SERVER_NAME'])){
	$_SERVER['SERVER_NAME'] = $p->getVar('server_name');
	//$_SERVER['HTTP_HOST'] = $INFO['server_name'];
}
