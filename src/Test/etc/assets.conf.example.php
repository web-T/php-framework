<?php
/**
 * Web assets configuration
 */

namespace Test\Config;

if (!isset($INFO)){
    $INFO = array();
}

$INFO['assets']['map'] = array(

    'test_main.css' => array(
        'version' => 1,
        'build' => array(
            '/'.$p->getVar('DOC_DIR').'css/share/reset.css',
            '/'.$p->getVar('DOC_DIR').'css/project/main.css',
        )
    ),

    'test_core.js' => array(
        'version' => 1,
        'build' => array(
            '/'.$p->getVar('DOC_DIR').'js/common.js',
            '/'.$p->getVar('DOC_DIR').'js/functions.js',

        )
    ),

);
