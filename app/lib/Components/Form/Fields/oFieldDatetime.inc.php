<?php
/**
 * ...
 *
 * Date: 11.02.15
 * Time: 08:20
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldDatetime extends oField{

    protected $_regularExp = '/(\d{4}-\d{2}-\d{2}(\s\d{1,2}:\d{1,2})?)?/';

    public function check(&$data, $full_row = array()){

        if (preg_match($this->_regularExp, $data))
            return array('valid' => true);
        else
            return array('valid' => false);
    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $value = parent::save($value, $row, $old_data, $lang_id);

        if ($row[$this->_base_field_id])
            $value = date('Y-m-d H:i:s', $row[$this->_base_field_id]);
        elseif ($value){
            // do nothing
        } else
            $value = date('Y-m-d H:i:s', $this->_p->getTime());


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

        $value_html = htmlspecialchars($data, ENT_QUOTES);

        $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" type="text" '.$params['class_compiled'].' size="23" style="'.$style.'" value="'.$value_html.'"  '.$params['readonly'].'  /><span id="trigger['.$this->_field_id.']" class="img-calendar"></span>';

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

}