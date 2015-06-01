<?php
/**
 * Integer field
 *
 * Date: 11.02.15
 * Time: 01:07
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;


class oFieldInteger extends oFieldVarchar{


    public function check(&$data, $full_row = array()){

        $data = (int)$data;

        return array('valid' => true);
    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return (int)parent::save($value, $row, $old_data, $lang_id);

    }


} 