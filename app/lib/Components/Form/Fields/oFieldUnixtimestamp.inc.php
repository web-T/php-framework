<?php
/**
 * ...
 *
 * Date: 11.02.15
 * Time: 07:59
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldUnixtimestamp extends oField{

    protected $_no_predefined_value = false;

    protected $_regularExp = '/\d{1,2}:\d{1,2}(:\d{1,2})?\s\d{2}-\d{2}-\d{4}/';

    public function check(&$data, $full_row = array()){

        if (!$data)
            $data = date('H:i:s d-m-Y', $this->_p->getTime());

        if (preg_match($this->_regularExp, $data))
            return array('valid' => true);
        elseif (is_numeric($data) && preg_match('/[0-9]{10}/', $data))
            return array('valid' => true);
        else
            return array('valid' => false);
    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $value = parent::save($value, $row, $old_data, $lang_id);

        if ($row[$this->_base_field_id]){
            // all fields for us prepare oFieldController
            if (is_numeric($row[$this->_base_field_id])){
                $value = (int)$row[$this->_base_field_id];
            } else {
                $dt_arr = strptime($row[$this->_base_field_id], $this->_p->getVar('formats')['date']);
                $value = mktime($dt_arr['tm_hour'], $dt_arr['tm_min'], $dt_arr['tm_sec'], $dt_arr['tm_mon']+1, $dt_arr['tm_mday'], 1900 + $dt_arr['tm_year']);
            }

        } elseif ($value)
            $value = (int)$value;
        elseif (!$this->_no_predefined_value)
            $value = $this->_p->getTime();

        return $value;

    }

    public function get($data = null, $params = array()){

        // parsing value from standart type
        if ($data && preg_match($this->_p->getVar('regualars')['datetime'], $data)){
            // do nothing
        } elseif ($data || (!$data && !$this->_no_predefined_value)) {
            $data = date('H:i:s d-m-Y', $data ? $data : $this->_p->getTime());
        } else
            $data = '';

        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        $value_html = htmlspecialchars($data, ENT_QUOTES);

        $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" type="text" '.$params['class_compiled'].' size="23" style="'.$style.'" value="'.$value_html.'"  '.$params['readonly'].'  /><span id="trigger['.$this->_field_id.']" class="img-calendar"></span>';

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

} 