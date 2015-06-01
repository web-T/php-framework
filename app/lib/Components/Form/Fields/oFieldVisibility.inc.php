<?php
/**
 * Special visibility type
 * which used for save visibility status
 *
 * Date: 15.02.15
 * Time: 11:43
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldVisibility extends oField{


    /**
     * Method encoding visibility value
     * Structure:
     * array(
     *      'field1' => true,
     *      'field2' => false,
     *      ...
     *      'fieldx' => true
     * )
     * @param $value
     * @param array $row
     * @param $old_data
     * @param null $lang_id
     * @return mixed|string
     */
    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        if (is_array($value)){

            array_walk_recursive($value, function(&$item){
                $item = (int)$item;
            });

        }

        return serialize((array)$value);

    }


    public function getValue($data = null, $params = array()){

        return $data && is_string($data) ? unserialize($data) : (is_array($data) ? $data : array());

    }


    public function get($data = null, $params = array()){

        return array(
            'html' => '',
            'value_html' => '',
            'value' => $data
        );

    }

} 