<?php
/**
 * ...
 *
 * Date: 15.10.14
 * Time: 07:54
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	15.10.2014/goshi 
 */

namespace webtFramework\Models;

use webtFramework\Interfaces\oModel;
use webtFramework\Core\oPortal;


class Field extends oModel{


    // define fields
    protected $_fields = array(
        'real_id' => array(
            'type' => 'integer',
            'primary' => true,
            'sort' => true,
            'in_list' => '5',
            'order' => 'desc',
            'search' => true),
        'id' => array(
            'type' => 'integer'),

        'nick' => array(
            'maxlength' => '64',
            'fieldReg' => 'field_nick',
            'visual' => array(
                'type' => 'translit',
                'handler' => 'transliterate_field',
                'source' => array(
                    'field' => 'title')),
            'unique' => array(),
            'in_list' => 10,
            'in_ajx_list' => 10,
            'search' => true),
        'title' => array(
            'maxlength' =>  255,
            'multilang' => true,
            'empty' => false,
            'sort' => true,
            'in_list' => '30',
            'in_ajx_list' => 50,
            'duplicate' => true,
            'search' => true),
        'is_on' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 1
        ),

        'is_top' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',),

        'field_type' => array(
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'empty' => false)
            )
        ),
        'visual_type' => array(
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'empty' => false)
            )
        ),
        'arr_name' => array(
            'type' => 'text',
            //'width' => '300',
            //'height' => '150',
            'visual' => array(
                'type' => 'multi'
            ),
            'children' => array(
                'id' => array(
                    'type' => 'integer',
                    'maxlength' => 10,
                    'style' => 'background: #FFFBEA'
                    //'unique' => array()
                ),
                'title' => array(
                    'type' => 'text',
                    'maxlength' => 255,
                    'style' => 'background: #ECFFF2'
                    //'multilang' => true,
                ),
                'weight' => array(
                    'type' => 'integer',
                    'maxlength' => 5,
                    'style' => 'background: #D8F3FF'
                    //'multilang' => true,
                ),
                /*'picture' => array(
                    'type' => 'text',
                    'visual' => array(
                        'type' => 'webtCMS:simplepicture',
                        'accept' => array('jpg', 'jpeg', 'png', 'gif'),
                        'file_max_size' => '2M',
                        'picture_props' => array(0 => array(
                            'size' => array('height' => 73),
                            'can_resize' => false,
                            'crop' => true),
                        ),
                        // we need to define controller and field for uploader
                        'source' => array(
                            'apps' => 'fields',
                            'field' => 'arr_name/picture'
                        )
                    )
                ), */
            )
        ),

        'tbl_name' => array(
            'maxlength' => 64),

        'multilang' => array(
            'type' => 'boolean',
            'default_value' => 0
        ),

        'empty' => array(
            'type' => 'boolean',
            'default_value' => 0
        ),
        'fullempty' => array(
            'type' => 'boolean',
            'default_value' => 0
        ),
        'fulleditor'  => array(
            'type' => 'boolean',
            'default_value' => 0
        ),
        'fieldReg' => array(
            'maxlength' =>  255,
            'search' => true),
        'datatype' => array(
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'empty' => false)
            )
        ),

        'unique' => array(
            'type' => 'boolean',
            'default_value' => 0
        ),
        'visual_accept' => array(
            'maxlenght' => 255
        ),
        'default_value' => array(
            'maxlenght' => 255
        ),
        'helptext' => array(
            'maxlenght' => 255
        ),
        'max_input_size' => array(
            'type' => 'integer',
            'maxlength' => 6),
        'maxlength' => array(
            'type' => 'integer',
            'maxlength' => 6),

        'width' => array(
            'type' => 'integer',
            'maxlength' => 5),

        'height' => array(
            'type' => 'integer',
            'maxlength' => 5),

        'lang_id' => array(
            'type' => 'integer',
        ),
        'weight' => array(
            'type' => 'integer',
            'empty' => false,
            'maxlength' => '5',
            'default_value' => '0'),
        'owner_id' => array(
            'type' => 'integer',
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'subtype' => 'tree',
                    'tbl_name' => 'tbl_fields',
                    'multilang' => true)
            )
        ),

        'picture' => array(
            'maxlength' => '65535',
            'in_list' => '10',
            'in_ajx_list' => '10',
            'visual' => array(
                'type'=> 'picture',
                'picture_props' => array(
                    0 => array(
                        /*'ratio' => '1.765',*/
                        'size' => array('maxwidth' => 200),
                        'can_resize' => false,
                        'crop' => true),
                    100 => array(
                        'size' => array('width' => 22, 'height' => 22),
                        'can_resize' => false,
                        'secondary' => 0,
                        'allowed_lists' => true,
                        'crop' => true),
                )
            ),
        ),

        'is_filter' => array(
            'type' => 'boolean',
            'sort' => true,
            'in_list' => '5',
            'default_value' => 0
        ),

        'is_compare' => array(
            'type' => 'boolean',
        ),

        'dimension' => array(
            'maxlength' => 32,
            'multilang' => true,),

        'filter_weight' => array(
            'type' => 'integer',
            'maxlength' => 5,
        ),

        'filter_type' => array(
            'visual' => array(
                'type' => 'select',
                'source' => array(
                )
            )
        ),

        'top_visual_type' => array(
            'type' => 'integer',
            'visual' => array(
                'type' => 'select',
                'source' => array(
                    'arr_name' => '')
            )
        )

    );

    /**
     * define field types
     */
    protected $_field_types = array('text' => 'text',
        'varchar' => 'varchar',
        'integer' => 'integer',
        'float' => 'float',
        'datetime' => 'datetime',
        'date' => 'date',
        'time' => 'time',
        'unixtimestamp' => 'timestamp',
        'boolean' => 'boolean',
        //				virtual, !!! - this type of the field cosnsist of subs by fields, declared in 'handlerNodes' property
        'set' => 'set');

    protected $_datatypes = array('plain' => 'plain', 'html' => 'html');

    protected $_visual_types = array(
        '0' => '-- group --', // group item
        'input' => 'input', // this is an alias for field_type=varchar and visual_type=text
        'text' => 'textarea',
        'select' => 'select',
        'radio' => 'radio',
        'checkbox' => 'checkbox', // this is an alias for field_type=boolean and  visual_type=''
        'datetime' => 'datetime', // this is an alias for field_type=datetime and visual_type=''
        'date' => 'date',		// this is an alias for field_type=date and visual_type=''
        'time' => 'time',		// this is an alias for field_type=time and visual_type=''
        'unixtimestamp' => 'timestamp', // this is an alias for field_type=unixtimestamp and visual_type=''
        'multiselect' => 'multiselect',
        'multicheckbox' => 'multicheckbox',
        'set' => 'set',   // special type with structure |VAL1||VAL2||VAL3|...|VALN|
        'range' => 'range',   // range structure: X1-X2, or simply X1, or X2

        //'tree' => 'tree',
        //'picture' => 'picture',
        //'translit' => 'translit',
        'file' => 'file',
        /*'weight' => 'weight'*/);

    protected $_filter_types = '';

    public function __construct(oPortal &$p){

        $this->setModelTable($p->getVar('tbl_fields'));
        $this->_fields['picture']['visual']['img_dir'] = $p->getVar('files_dir').'fields/'.$p->getVar('images_dir');

        // adding serializing objects
        foreach ($p->getVar('langs') as $v){
            $this->_linkedSerialized[] = 'custom_fields.'.$v['nick'].'.dump';
        }

        // exclude all reserved nicks from unique checker
        $this->_fields['field_type']['visual']['source']['arr_name'] = $this->_field_types;
        $this->_fields['datatype']['visual']['source']['arr_name'] = $this->_datatypes;
        $this->_fields['visual_type']['visual']['source']['arr_name'] = $this->_visual_types;
        $this->_fields['top_visual_type']['visual']['source']['arr_name'] = $p->m['top_field_visual_types'];

        $this->_fields['is_top']['title'] = $p->m['project']['main_property'];
        $this->_fields['is_filter']['title'] = $p->m['project']['filter_property'];

        $this->_fields['filter_type']['visual']['source']['arr_name'] = $p->m['filter_types'];
        $this->_fields['filter_type']['visual']['source']['empty_helptext'] = $p->m['fields']['filter_empty_helptext'];

        return parent::__construct($p);

    }

    // override delete
    public function postDelete($data = null){

        $fw = $this->_p->Model('FieldValue');
        $qb = $this->_p->db->getQueryBuilder($fw->getModelStorage());
        $sql = $qb->compileDelete($this, array(
            'where' => array(
                'field_nick' =>  $this->_data['nick']
            )
        ));

        $this->_p->db->query($sql, $fw->getModelStorage());

        $this->_p->cache->removeMeta();
        $this->_p->cache->removeSerial();

        parent::postDelete($data);
    }


    public function postSave($data = null){

        parent::postSave($data);

        $this->_p->cache->removeMeta();
        $this->_p->cache->removeSerial();

    }


    public function postUpdate($data = null){

        // check for is_filter
        if ($this->_oldData && $data['param'] == 'is_filter' && $data['is_filter'] != $this->_oldData['is_filter']){

            $model = $this->_p->Model('FieldValue');
            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileUpdate($model,
                array(
                    'is_filter' => $data['is_filter']
                ),
                array('where' => array(
                    'field_nick' => $this->_oldData['nick']
                ))
            );

            $this->_p->db->query($sql, $model->getModelStorage());

            unset($model);
        }

        $this->_p->cache->removeMeta();
        $this->_p->cache->removeSerial();

        return parent::postUpdate($data);
    }


}
