<?php
/**
 * ...
 *
 * Date: 23.10.14
 * Time: 17:31
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

class BannedIP extends oModel {

    // define fields
    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => '5',
            'order' => 'desc',
            'search' => true),
        'title' => array(
            'maxlength' =>  255,
            'in_list' => '40',
            'sort' => true,
            'duplicate' => true,
            'default_sort' => true,
            'search' => true),

        'ip' => array(
            'maxlength' =>  15,
            'in_list' => 15,
            'sort' => true,
            'search' => true),
        'forward' => array(
            'maxlength' =>  100,
            'in_list' => 15,
            'sort' => true,
            'search' => true),
        'useragent' => array(
            'maxlength' =>  128,
            'in_list' => 15,
            'sort' => true,
            'search' => true),
        'is_on' => array(
            'type' => 'integer',
            'sort' => true,
            'in_list' => '5',
            'visual' => array(
                'type' => 'radio',
                'source' => array(
                    'arr_name' => '')
            )
        ),
        'date_add' => array(
            'type' => 'unixtimestamp',
            'sort' => true,
            'in_list' => '5',
        ),
    );

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_banned_ips'));

        $this->_fields['is_on']['visual']['source']['arr_name'] = $p->m['access'];

        // adding serializing cleaning
        $this->_linkedSerialized[] = 'banned_ips_list';

        return parent::__construct($p);

    }

} 