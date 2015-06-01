<?php
/**
 * Frontend additional rounting
 */
namespace Test\Config;

use webtFramework\Core\oPortal;


if (!isset($INFO)){
    $INFO = array();
}

/**
 * each route must consists of:
 * path: string
 * defaults: array(
 *      '_controller' => @see \webtFramework\Components\Request\oRoute class
 *      '_format' => type for request, can be 'json' or 'html',
 *      'any other parameter from pattern with default value'
 * ),
 * requirements : array(
 *      '_format' => type for request, can be 'json' or 'html',
 *      'any other parameter from pattern with default value'
 * ),
 * options : array(
 *      not used yet
 * ),
 * host : regexp for host
 * schemes : array() of regexp of possible schemes for this route, for example - http, https, ftp, etc.
 * methods : array() of possible methods for request (eg. get, post, delete)
 *
 */
$INFO['ROUTES'] = array(

    // assets
    'get_asset_css' => array(
        'path' => '/css/build/{subquery}',
        'defaults' => array(
            '_controller' => 'BUNDLE:APPLICATION:getAsset'
        ),
        'requirements' => array(
            'subquery' => '.+'
        )
    ),

    'get_asset_js' => array(
        'path' => '/js/build/{subquery}',
        'defaults' => array(
            '_controller' => 'BUNDLE:APPLICATION:getAsset'
        ),
        'requirements' => array(
            'subquery' => '.+'
        )
    ),

    // check if we want to get some file
    'get_file' => array(
        'path' => '/get_file/id/{id}',
        'defaults' => array(
            '_controller' => function(oPortal $p, $id){

                if ($id != '' && $p->user->checkUploadRules($id)){
                    // ... do something
                } else {
                    // ... do something else
                }
            }
        ),
        'requirements' => array(
            'id' => '.+'
        )
    ),

    'update_something' => array(
        'path' => '/'.$p->getVar('statistic')['counter_url'].'/{subquery}',
        'defaults' => array(
            '_controller' => '...'
        ),
        'requirements' => array(
            'subquery' => '.*'
        )
    ),

);

