<?php
/**
 * ...
 *
 * Date: 06.12.14
 * Time: 09:15
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	06.12.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class SessionHistory extends oModel {

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
        'title' => array(
            'maxlength' => 32,
            'sort' => true,
            'in_list' => 30,
            'duplicate' => true,
            'search' => true),

        'user_id' => array(
            'type' => 'integer',
            'in_list' => 10,
            'sort' => true,
            'search' => true
        ),
        'ip' => array(
            'maxlength' => 128,
            'in_list' => 10,
            'sort' => true,
            'search' => true
        ),
        'is_admin' => array(
            'type' => 'boolean',
            'in_list' => 5,
            'sort' => true,),

        'login_time' => array(
            'type' => 'unixtimestamp',
            'sort' => true,
            'in_list' => 5,
            'order' => 'desc'),
        'lastuse_time' => array(
            'type' => 'unixtimestamp',
            'sort' => true,
            'in_list' => 5,
            'order' => 'desc'),
        'page_nick' => array(
            'maxlength' => 128,
            'sort' => true,
            'in_list' => 40,
            'search' => true,
        ),

        'application' => array(
            'maxlength' => 24,
            'sort' => true,
            'in_list' => 10,
            'search' => true,
        ),



    );

    // override noindex property
    protected $_isNoindex = true;
    protected $_isNoCacheClean = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_sessions_history'));

        return parent::__construct($p);

    }


}
