<?php
/**
 * ...
 *
 * Date: 08.08.14
 * Time: 09:16
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	08.08.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;

class User extends oModel{

    // define fields
    protected $_fields = array(
        'id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => '5',
            'in_ajx_list' => '5',
            'order' => 'desc',
            'search' => true
        ),
        'lang_id' => array(
            'type' => 'integer',
        ),
        'real_id' => array(
            'type' => 'integer',
            //	'primary' => true,
            //	'sort' => true,
            //	'in_list' => '5',
            //	'order' => 'desc',
            //	'search' => true
        ),
        'city_id' => array(
            'type' => 'integer',
            'sort' => true,
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    //'subtype' => 'tree',
                    'empty' => true,
                    'tbl_name' => 'tbl_city',
                    'multilang' => true
                )
            )
        ),
        'country_id' => array(
            'type' => 'integer',
            'sort' => true,
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    //'subtype' => 'tree',
                    'empty' => true,
                    'tbl_name' => 'tbl_countries',
                    'multilang' => true,
                    'order' => array('weight' => 'desc', 'title' => 'asc')
                ),
                'is_custom_value' => true,
            ),
        ),

        'usernick' => array(
            'maxlength' => 64,
            'sort' => true,
            'search' => true,
            'in_list' => 10,
            'empty' => false,
            'unique' => array()
        ),

        'title' => array(
            'type' =>  'virtual',
            //'multilang' => true,
            'sort' => true,
            'in_list' => '30',
            'in_ajx_list' => '60',
            'align' => 'left',
            'search' => true,
            'handlerNodes' => array(
                'sname',
                'fname',
                'mname'
            )
        ),


        'fname' => array(
            'maxlength' =>  60,
            //'multilang' => true,
            //'empty' => false,
            'order' => 'asc',
            'search' => true
        ),
        'mname' => array(
            'maxlength' =>  60,
            //'multilang' => true,
            'order' => 'asc',
            //'empty' => false,
            'search' => true,
        ),
        'sname' => array(
            'maxlength' =>  60,
            'minlength' =>  1,
            //'multilang' => true,
            'order' => 'asc',
            //'empty' => false,
            'search' => true,
            //'fieldReg' => 'sname',
        ),
        'letter' => array(
            'maxlength' =>  1,
            //'empty' => false
        ),
        'date_birth' => array(
            'type' => 'date',
            'sort' => true,
            'order' => 'desc',
            //'in_list' => '10'
        ),
        'username' => array(
            'maxlength' => 64,
            'sort' => true,
            'empty' => false,
            'in_list' => '20',
            'search' => true,
            'unique' => array()
        ),
        'email' => array(
            'maxlength' => 64,
            'fieldReg' => 'email',
            'sort' => true,
            'search' => true,
            'unique' => array(),
        ),
        'password' => array(
            'maxlength' => '255',
            'empty' => false,
            //'fieldReg' => 'password',
            'visual' => array(
                'type' => 'password'
            ),
            'rules' => array('admin')
        ),
        'post' => array(
            'maxlength' => '64',
            'search' => true,
        ),
        'company' => array(
            'maxlength' => '128',
            'search' => true,
        ),

        'phone' => array(
            'maxlength' => '64',
            'search' => true,

            /*'type' => 'text',
            'search' => true,
            'visual' => array(
                'type' => 'multi'
            ),
            'children' => array(
                'item' => array(
                    'maxlength' => '64',
                )
            ) */
        ),
        'descr' => array(
            'type' => 'text',
            'maxlength' =>  65000,
            //'multilang' => false,
            'search' => true,
            'fulleditor' => true,
            'datatype' => 'html'
            //'in_list' => '10'
        ),
        'date_add' => array(
            'type' => 'unixtimestamp',
            'sort' => true,
            'in_list' => '5',
            'default_sort' => true
        ),
        'picture' => array(
            'in_list' => '10',
            'in_ajx_list' => 10,
            'visual' => array(
                'type'=> 'picture',
                'picture_props' => array(
                    0 => array(
                        'size'			=> array('maxwidth' => 220),
                        'can_resize'	=> false,
                        'crop'			=> true,
                        //'watermark'		=> true
                    ),
                    1 => array(
                        'size'			=> array('width' => 225, 'height' => 128),
                        'can_resize'	=> false,
                        'crop'			=> true,
                        'secondary'		=> 0,
                        //'watermark'		=> true
                    ),
                    2 => array(
                        'size'			=> array('width' => 140, 'height' => 140),
                        'can_resize'	=> false,
                        'crop'			=> true,
                        'secondary'		=> 0,
                        //'watermark'		=> true
                    ),
                    3 => array(
                        'size' => array('width' => 225, 'height' => 103),
                        'can_resize' => false,
                        'secondary' => 0,
                        'crop' => true
                    ),
                    4 => array(
                        'size' => array('width' => 190, 'height' => 190),
                        'can_resize' => false,
                        'secondary' => 0,
                        'crop' => true
                    ),
                    100 => array(
                        'src'			=> '',
                        'size'			=> array('width' => 60, 'height' => 60),
                        'can_resize'	=> false,
                        'crop'			=> true,
                        'secondary'		=> 0,
                        //'watermark'		=> true
                    ),
                )
            ),
        ),

        'last_modified' => array(
            'type' => 'unixtimestamp',
        ),

        'ip' => array(
            'type' => 'integer',
            'sort' => true,
            'visual' => array(
                'type' => 'ip'
            ),
        ),
        'forward' => array(
            'type' => 'integer',
            'sort' => true,
            'visual' => array(
                'type' => 'ip'
            ),
        ),
        'is_on' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 1
        ),

        'is_activate' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 1
        ),

        'is_register_agree' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'empty' => false,
            'default_value' => 0
        ),


        'is_contacts_for_friends' => array(
            'type' => 'boolean',
            'default_value' => 0
        ),
        'is_disable_add_friend' => array(
            'type' => 'boolean',
            'default_value' => 0
        ),


        'is_in' => array(
            'type' => 'boolean',
            //'default_value' => 0
        ),
        'is_banned' => array(
            'type' => 'integer',
            'sort' => true,
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'arr_name' => '',
                )
            )
        ),

        'banned_start' => array(
            'type' => 'unixtimestamp',
        ),
        'banned_time' => array(
            'type' => 'time'
        ),

        'act_code' => array(
            'maxlength' => '65',
        ),
        'reg_lang' => array(
            'type' => 'integer',
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'arr_name' => '',
                    'empty' => false
                )
            )
        ),

        'url' => array(
            'maxlength' => 65535,
            'fieldReg' => 'url',
            'multistore' => 3
        ),

        'rating' => array(
            'type' => 'float',
            //'in_list' => 5,
        ),

        'rating_today' => array(
            'type' => 'float',
            //'in_list' => 5,
        ),

        'rating_out_today' => array(
            'type' => 'float',
            //'in_list' => 5,
        ),

        'rating_out_plus' => array(
            'type' => 'integer',
            //'in_list' => 5,
        ),

        'rating_out_minus' => array(
            'type' => 'integer',
            //'in_list' => 5,
        ),

        'rating_pos' => array(
            'sort' => true,
            //'in_list' => true,
            'type' => 'integer'),


        'upload_out' => array(
            'type' => 'integer',
            //'in_list' => 5,
        ),

        'published_out' => array(
            'type' => 'integer',
            //'in_list' => 5,
        ),


        'username_facebook' => array(
            'maxlength' =>  255,
            'search' => true
        ),
        'username_twitter' => array(
            'maxlength' =>  255,
            'search' => true
        ),
        'username_vkontakte' => array(
            'maxlength' =>  255,
            'search' => true
        ),
        'username_google' => array(
            'maxlength' =>  255,
            'search' => true
        ),
        'username_odnoklassniki' => array(
            'maxlength' =>  128,
            'search' => true
        ),
        'username_mailru' => array(
            'maxlength' =>  128,
            'search' => true
        ),

        'session_facebook' => array(
            'maxlength' => 65000,
            'filters' => array('save' => null),
            'search' => true
        ),
        'session_twitter' => array(
            'maxlength' => 65000,
            'filters' => array('save' => null),
            'search' => true
        ),
        'session_vkontakte' => array(
            'maxlength' => 65000,
            'filters' => array('save' => null),
            'search' => true
        ),
        'session_google' => array(
            'maxlength' => 65000,
            'filters' => array('save' => null),
            'search' => true
        ),

        'session_odnoklassniki' => array(
            'maxlength' => 65000,
            'filters' => array('save' => null),
            'search' => true
        ),

        'session_mailru' => array(
            'maxlength' => 65000,
            'filters' => array('save' => null),
            'search' => true
        ),

        'email_public' => array(
            'maxlength' => 64,
            'fieldReg' => 'email',
            'sort' => true,
            'search' => true,
            'unique' => array()
        ),
        'is_email' => array(
            'type' => 'boolean',
            'sort' => true
        ),
        'is_send' => array(
            'type' => 'boolean',
            'sort' => true
        ),
        'city_raw' => array(
            'maxlength' =>  255,
            'width' => 261,
            'search' => true
            //'multilang' => false
        ),
        'gender' => array(
            'type' => 'integer',
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'arr_name' => '',
                    'empty' => false
                )
            ),
            'default_value' => 1
        ),

        'is_birthday_hide' => array(
            'type' => 'boolean',
        ),

        'icq' => array(
            'maxlength' =>  16,
            'width' => '98'
        ),

        'gtalk' => array(
            'maxlength' =>  64,
            'width' => '98'
        ),
        'skype' => array(
            'maxlength' =>  64,
            'width' => '98'
        ),
        'livejournal' => array(
            'maxlength' =>  64,
            'width' => '98'
        ),
        'linkedin' => array(
            'maxlength' =>  64,
            'width' => '98'
        ),
        'profeo' => array(
            'maxlength' =>  64,
            'width' => '98'
        ),
        /*'specialization_user' => array(
            'type' => 'text',
            'search' => true,
            'visual' => array(
                'type' => 'multi'
            ),
            'children' => array(
                'item' => array(
                    'type' => 'integer',
                    'visual' => array(
                        'type' => 'select',
                        'source' => array(
                            'tbl_name' => 'tbl_occ',
                            'empty' => false,
                            'multilang' => true,
                            'cache' => array()
                        ),
                        'is_custom_value' => true,
                    ),
                )
            ),

        ), */

        'fields_status' => array(
            'type' => 'text',
            'maxlength' => 65535,
        ),

        'rating_abs' => array(
            'type' => 'float',
        ),

        'popularity' => array(
            'type' => 'integer',
        ),

        'comments_out' => array(
            'type' => 'integer',
        ),

        'comments_in'  => array(
            'type' => 'integer',
        ),

        'popularity_in' => array(
            'type' => 'integer',
        ),

        'favorites_in' => array(
            'type' => 'integer',
        ),

        'items_count' => array(
            'type' => 'integer',
        ),

        'friends_count' => array(
            'type' => 'integer',
        ),

        'allow_closed' => array(
            'maxlengt' => 65535,
            'type' => 'text'),

        'counts' => array(
            'maxlength' => 65000,
            
            'filters' => array('save' => null),
            'search' => true
        ),

        'session' => array(
            'type' => 'text',
            'maxlength' =>  65000,
        ),

        'is_delete' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 0
        ),

        'warning_count' => array(
            'type' => 'integer',
        ),

        'contests_data' => array(
            'maxlength' => 65000,
        ),


        'nick' => array(
            'maxlength' => 64,
            'fieldReg' => 'nick',
            'visual' => array(
                'type' => 'translit',
                'source' => array(
                    'field' => 'usernick')),
            'search' => true),

        'contacts' => array(

        ),

        'last_modified_usernick' => array(
            'type' => 'unixtimestamp',
            'default_sort' => true
        ),

        'last_login_date' => array(
            'type' => 'unixtimestamp',
        ),

        'account' => array(
            'type' => 'float',
            'sort' => true,
            //'in_list' => 5,
        ),

        'cnt_money' => array(
            'type' => 'float',
            'sort' => true,
            //'in_list' => 5,
        ),

        'is_paid_anonymous' => array(
            'type' => 'integer',
        ),

        'is_paid_blacklist' => array(
            'type' => 'integer',
        ),

        'is_ban_apply' => array(
            'type' => 'text',
            
            'filters' => array('save' => null),
            'maxlength' => 255,
        ),

        'is_author' => array(
            'type' => 'boolean',
            //'in_list' => 5,
            'sort' => true,
        ),

        'visibility' => array(
            'type' => 'visibility',
        ),

        'access_token' => array(
            'maxlength' => 64,
            'filters' => array('save' => null),
        ),

        'status' => array(
            'type' => 'integer',
            'default_value' => 0,
            'in_list' => '5',
            'sort' => true,
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    //'empty' => false,
                    'arr_name' => '')),
            'bulk_action' => true
        ),

    );

    // links with other pages
    protected $_links = array('upload', 'adm_users');

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_users'));
        $this->_fields['picture']['visual']['img_dir'] = $p->getVar('files_dir').'users/'.$p->getVar('images_dir');
        $this->setModelLinkTable($p->getVar('tbl_users_lnk'));

        // adding serializing cleaning
        $this->_linkedSerialized = array('');

        $langs = array();
        foreach ($p->getVar('langs') as $k => $v){
            $langs[$k] = $v['title'];
        }
        $this->_fields['reg_lang']['visual']['source']['arr_name'] = $langs;
        $this->_fields['gender']['visual']['source']['arr_name'] = $p->m['genders'];
        $this->_fields['is_banned']['visual']['source']['arr_name'] = $p->m['banned'];

        $this->_fields['status']['visual']['source']['arr_name'] = $p->m['fields']['statuses'];

        return parent::__construct($p);

    }

} 
