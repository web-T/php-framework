<?php
/**
 * ...
 *
 * Date: 11.02.15
 * Time: 01:12
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;


class oFieldFloat extends oFieldVarchar{

    public function check(&$data, $full_row = array()){

        $data = str_replace(',', '.', $data);
        $data = (float)$data;

        return array('valid' => true);
    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return (float)str_replace(',', '.', parent::save($value, $row, $old_data, $lang_id));

    }

} 