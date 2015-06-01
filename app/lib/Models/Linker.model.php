<?php
/**
 * ...
 *
 * Date: 26.01.15
 * Time: 19:28
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	26.01.2015/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class Linker extends oModel {

    protected $_fields = array(

        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => 5,
            'order' => 'desc',
        ),
        'this_tbl_name' => array(
            'maxlength' =>  64,
            'in_list' => 20,
        ),
        'tbl_name' => array(
            'maxlength' =>  64,
            'in_list' => 20,
        ),
        'this_id' => array(
            'type' =>  'int',
            'in_list' => 5,
        ),
        'elem_id' => array(
            'type' =>  'int',
            'in_list' => 5,
        ),
        'weight' => array(
            'type' =>  'int',
            'in_list' => 5,
        ),
        'is_top' => array(
            'type' => 'boolen',
            'in_list' => 5,
        ),
        'model' => array(
            'maxlength' =>  64,
        ),
        'this_model' => array(
            'maxlength' =>  64,
        ),

    );

    protected $_isNoindex = true;

    protected $_isNoCacheClean = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_linker'));

        return parent::__construct($p);

    }

} 