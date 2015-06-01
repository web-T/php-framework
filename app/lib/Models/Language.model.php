<?php
/**
 * ...
 *
 * Date: 07.08.14
 * Time: 17:34
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	07.08.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class Language extends oModel {

    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => '5',
            'order' => 'desc',
            'search' => true),
        'nick' => array(
            'maxlength' =>  8,
            'empty' => false,
            'sort' => true,
            'in_list' => '20',
            'duplicate' => true,
            'search' => true,
            'unique' => array()),
        'title' => array(
            'maxlength' =>  40,
            'empty' => false,
            'sort' => true,
            'in_list' => '40',
            'duplicate' => true,
            'search' => true),
        'aliases' => array(
            'maxlength' =>  80,
            'search' => true),
        'server_name' => array(
            'maxlength' =>  80,
            'search' => true),
        'codepage' => array(
            'maxlength' =>  40,
            'empty' => false,
            'search' => true),
        'weight' => array(
            'maxlength' => '5',
            'empty' => false,
            'in_list' => '10',
            'sort' => true,
            'default_value' => '0',
            'default_sort' => true,
            'visual' => array(
                'type' => 'weight')),
        'lang_pack' => array(
            'maxlength' =>  40,
            'visual' => array(
                'type' => 'file')
        ),
        'is_on' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 1,
        ),

        'is_publish' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 1,
        ),

        'picture' => array(
            'in_list' => '10',
            'visual' => array(
                'type'=> 'picture',
                'picture_props' => array(
                    0 => array('src' => '',
                        'size' => array('width' => 16),
                        'crop' => true)
                )
            ),
        ),

        'altname' => array(
            'maxlength' =>  64,
            'search' => true),

    );

    protected $_linkedSerialized = array('webt.langs');


    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_langs'));
        $this->_fields['picture']['visual']['img_dir'] = $p->getVar('files_dir').'languages/'.$p->getVar('images_dir');

        return parent::__construct($p);

    }

} 