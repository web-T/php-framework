<?php
/**
 * Access key model
 *
 * Date: 04.12.14
 * Time: 10:20
 * @version 1.0
 * @author goshi
 * @package web-T[models]
 * 
 * Changelog:
 *	1.0	04.12.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class AccessKey extends oModel{

    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => 5,
            'order' => 'desc',
            //'search' => true
        ),
        'key' => array(
            'maxlength' =>  64,
            'in_list' => 40,
            //'search' => true
        ),
        'expired' => array(
            'type' => 'unixtimestamp',
        ),
        'tbl_name' => array(
            'maxlength' => 32,
        ),
        'elem_id' => array(
            'type' => 'integer',
        ),
        'adm_user_id' => array(
            'type' => 'integer',
        ),
        'model' => array(
            'maxlength' => 32,
        ),

    );

    protected $_isNoindex = true;
    protected $_isNoCacheClean = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_access_keys'));

        return parent::__construct($p);

    }

}