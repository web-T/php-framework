<?php
/**
 * Cache tag model
 *
 * Date: 15.02.15
 * Time: 19:56
 * @version 1.0
 * @author goshi
 * @package web-T[Models]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class CacheTag extends oModel  {

    // define fields
    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => 7,
            'order' => 'desc',
            'search' => true
        ),

        'tag_crc' => array(
            'type' =>  'integer',
            'sort' => true,
        ),

        'filename_crc' => array(
            'type' =>  'integer',
            'sort' => true,
        ),

        'tag' => array(
            'maxlength' => 255,
            'in_list' => 20,
            'search' => true
        ),

        'filename' => array(
            'maxlength' => 32,
            'in_list' => 20,
            'search' => true
        ),

    );

    // override noindex property
    protected $_isNoindex = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_cache_tags'));

        return parent::__construct($p);

    }

}