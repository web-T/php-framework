<?php
/**
 * IP-address class type
 *
 * Date: 11.02.15
 * Time: 21:34
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

class oFieldIp extends oFieldVarchar {

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $value = parent::save($value, $row, $old_data, $lang_id);

        $value = strpos($value, '.') !== false ? ip2long($value) : $value;

        return $value;

    }

    public function get($data = null, $params = array()){

        $data = $value_html = is_numeric($data) ? long2ip($data) : $data;

        return parent::get($data, $params);

    }

} 