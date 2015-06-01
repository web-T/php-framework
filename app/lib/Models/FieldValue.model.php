<?php
/**
 * Custom field value
 *
 * Date: 30.11.14
 * Time: 22:02
 * @version 1.0
 * @author goshi
 * @package web-T[models]
 * 
 * Changelog:
 *	1.0	30.11.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class FieldValue extends oModel {

    // define fields
    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => 7,
            'order' => 'desc',
            'search' => true),
        'field_nick' => array(
            'maxlength' => 64,
            'sort' => true,
            'in_list' => 20,
            'search' => true),

        'adm_page_id' => array(
            'type' => 'integer',
            'in_list' => 10,
            'sort' => true,
            'search' => true),

        'value' => array(
            'type' => 'text',
            'maxlength' => 65535,
            'in_list' => 10,
            'search' => true
        ),
        'value2' => array(
            'type' => 'text',
            'maxlength' => 65535,
            'in_list' => 10,
            'search' => true
        ),
        'real_id' => array(
            'type' => 'integer',
            'in_list' => 5,
            'sort' => true,),

        'lang_id' => array(
            'type' => 'integer',
            'sort' => true,
            'in_list' => 5,),

        'is_filter' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => 5),

        'weight' => array(
            'type' => 'integer',
            'sort' => true,
            'in_list' => 5,
            'search' => true,
            /*'visual' => array(
                'type' => 'weight'
            ) */
        ),

        'model' => array(
            'maxlength' => 32,
            'sort' => true,
            'in_list' => 10,
            'search' => true
        ),

        'field_type' => array(
            'maxlength' => 32,
            'sort' => true,
            'in_list' => 10,
            'search' => true
        ),

        'category' => array(
            'type' => 'integer',
        ),

        'is_on' => array(
            'type' => 'boolean',
        ),


    );

    // override noindex property
    protected $_isNoindex = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_fields_values'));

        return parent::__construct($p);

    }


}