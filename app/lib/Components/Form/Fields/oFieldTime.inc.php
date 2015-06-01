<?php
/**
 * Time type
 *
 * Date: 11.02.15
 * Time: 08:42
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldTime extends oField{

    protected $_regularExp = '/(\d{1,2}:\d{1,2})?/';

    public function check(&$data, $full_row = array()){

        if (preg_match($this->_regularExp, $data))
            return array('valid' => true);
        else
            return array('valid' => false);
    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $value = parent::save($value, $row, $old_data, $lang_id);

        if ($row[$this->_base_field_id] && $row[$this->_base_field_id] != '00:00'){
            $value = strtotime_new($row[$this->_base_field_id]);
        } elseif ($value && $value != '00:00'){
            $value = (int)$value;
        } elseif ($this->_null == true)
            $value = 'NULL';

        return $value;

    }

    public function get($data = null, $params = array()){

        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        if (isset($value))
            $time = time_new($value);
        else
            $time = '00:00';

        $value_html = htmlspecialchars($time, ENT_QUOTES);

        $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" type="text" '.$params['class_compiled'].' size="11" style="'.$style.'" value="'.$value_html.'"  '.$params['readonly'].'  /><span id="trigger['.$this->_field_id.']" class="img-calendar"></span>';

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

}