<?php
/**
 * ...
 *
 * Date: 23.10.14
 * Time: 17:48
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	23.10.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class Cache extends oModel  {

    // define fields
    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => '7',
            'order' => 'desc',
            'search' => true),
        'title' => array(
            'type' =>  'virtual',
            'sort' => true,
            'in_list' => '30',
            'in_ajx_list' => '60',
            'search' => true,
            'handlerNodes' => array('query')),
        'query' => array(
            'maxlength' => 255,
            'sort' => true,
            'search' => true),
        'filename' => array(
            'maxlength' => 50,
            'sort' => true,
            'search' => true),
        'page_type' => array(
            'maxlength' => 8,
            'in_list' => '10',
            'sort' => true,
            'search' => true),
        'date_add' => array(
            'type' => 'unixtimestamp',
            'sort' => true,
            'in_list' => '5',
            'order' => 'desc'),
        'last_modified' => array(
            'type' => 'unixtimestamp',
            'sort' => true,
            'default_sort' => true,
            'in_list' => '5',
            'order' => 'desc'),
        'hits' => array(
            'type' => 'integer',
            'sort' => true,
            'in_list' => '5',
            'search' => true,
        ),


    );

    // override noindex property
    protected $_isNoindex = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_cache'));

        return parent::__construct($p);

    }

} 