<?php
/**
 * ...
 *
 * Date: 23.10.14
 * Time: 18:59
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

class UserRule extends oModel {

    // define fields
    protected $_fields = array(
        'real_id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => '5',
            'order' => 'desc',
            'search' => true),
        'title' => array(
            'maxlength' =>  128,
            'multilang' => true,
            'empty' => false,
            'sort' => true,
            'in_list' => '50',
            'duplicate' => true,
            'search' => true),
        'nick' => array(
            'maxlength' =>  20,
            'sort' => true,
            'in_list' => '20',
            'duplicate' => true,
            'search' => true,
            'unique' => array()
        ),
        'is_anonymous' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5'),
        'is_default' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5'),
        'id' => array(
            'type' => 'integer'),
        'lang_id' => array(
            'type' => 'integer',
        ),
    );

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_usr_rules'));

        $this->_linkedSerialized[] = '';

        $this->_fields['is_default']['title'] = $p->m['fields']['is_default_auth'];


        return parent::__construct($p);

    }

    public function postDelete($data = null){

        // delete all pages managing rules
        $sql = "DELETE FROM ".$this->_p->getVar('tbl_usr_rules_lnk')."
			WHERE this_id=".(int)$this->getPrimaryValue();

        $this->_p->db->query($sql);

        parent::postDelete($data);

    }

} 