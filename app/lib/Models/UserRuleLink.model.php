<?php
/**
 * User <-> Rules link model
 *
 * Date: 01.02.15
 * Time: 17:35
 * @version 1.0
 * @author goshi
 * @package web-T[Models]
 * 
 * Changelog:
 *	1.0	01.02.2015/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class UserRuleLink extends oModel {

    protected $_fields = array(

        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => 5,
            'order' => 'desc',
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
        'type' => array(
            'type' =>  'int',
            'in_list' => 5,
        ),
        'model' => array(
            'maxlength' =>  32,
        ),

    );

    protected $_isNoindex = true;

    protected $_isNoCacheClean = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_usr_rules_lnk'));

        return parent::__construct($p);

    }

}