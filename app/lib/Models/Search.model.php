<?php
/**
 * ...
 *
 * Date: 01.12.14
 * Time: 01:25
 * @version 1.0
 * @author goshi
 * @package web-T[models]
 * 
 * Changelog:
 *	1.0	01.12.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class Search extends oModel {

    // define fields
    protected $_fields = array(
        'this_id' => array(
            'type' => 'int',
            'primary' => true,
            'sort' => true,
            'in_list' => '7',
            'order' => 'desc',
            'search' => true),
        'tbl_name' => array(
            'maxlength' => 64,),

        'tbl_id' => array(
            'type' => 'int',
            ),
        'elem_id' => array(
            'type' => 'int',
        ),
        'weight' => array(
            'type' => 'int',
        ),
        'category' => array(
            'type' => 'int',
        ),

        'cats' => array(
            'maxlength' => 255,
        ),
        'is_spec' => array(
            'type' => 'boolean'),

        'cost' => array(
            'type' => 'float',
            ),
        'picture' => array(
            'type' => 'text',
            'maxlenght' => 65535),

        'title' => array(
            'type' => 'text',
            'maxlength' => 65535,
            'in_list' => '40',
        ),

        'title_hash' => array(
            'type' => 'int'
        ),

        'descr' => array(
            'type' => 'text',
            'maxlength' => 1600000,
        ),

        'date_add' => array(
            'type' => 'unixtimestamp',
        ),

        'date_post' => array(
            'type' => 'unixtimestamp',
        ),

        'last_modified' => array(
            'type' => 'unixtimestamp',
        ),

        'is_on' => array(
            'type' => 'boolean'
        ),

        'lang_id' => array(
            'type' => 'integer'
        ),

        'is_top' => array(
            'type' => 'boolean'
        ),

        'is_photo' => array(
            'type' => 'boolean'
        ),

        'is_video' => array(
            'type' => 'boolean'
        ),

        'cost_usd' => array(
            'type' => 'float'
        ),

        'type_id' => array(
            'type' => 'int'
        ),

        'region_id' => array(
            'type' => 'int'
        ),

        'tags' => array(
            'maxlength'
        ),

        'user_id' => array(
            'type' => 'int'
        ),

        'club_id' => array(
            'type' => 'int'
        ),

        'car_id' => array(
            'type' => 'int'
        ),

        'comments_today' => array(
            'type' => 'int'
        ),

        'rating_today' => array(
            'type' => 'float'
        ),

        'comments' => array(
            'type' => 'int'
        ),

        'popularity' => array(
            'type' => 'int'
        ),

        'model' => array(
            'maxlength' => 32
        )

    );

    // override noindex property
    protected $_isNoindex = true;

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_search'));

        return parent::__construct($p);

    }


}