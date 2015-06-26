<?php
/**
 * Password field
 *
 * Date: 11.02.15
 * Time: 21:45
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldPassword extends oField{

    protected $_helptext_check;

    public function check(&$data, $full_row = array()){

        return array('valid' => $data == $this->_oForms->getData()[$this->_base_field_id.'_check']);

    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $value = parent::save($value, $row, $old_data, $lang_id);

        if ($this->_oForms->getMultilang() && isset($old_data[$row['lang_id']])){

            $old_value = $old_data[$row['lang_id']][$this->_base_field_id];

        } elseif (isset($old_data[$this->_base_field_id])) {

            $old_value = $old_data[$this->_base_field_id];

        } else {

            $old_value ='';

        }

        // if values are equivalent
        if ($old_value != $value){
            $value = $this->_p->user->encryptPassword($value);
        }

        return $value;

    }

    public function get($data = null, $params = array()){

        parent::get($data, $params);

        $max_input_length = '';

        if ($this->_max_input_size && $this->_max_input_size < $this->_maxlength){
            $max_input_length = $this->_max_input_size;
        }

        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        $value_html = htmlspecialchars($data, ENT_QUOTES);

        $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" type="password" '.$params['class_compiled'].' '.($max_input_length ? 'size="'.$max_input_length.'"' : '').' style="'.$style.'" value="" '.$params['readonly'].' '.($this->_helptext ? 'placeholder="'.htmlspecialchars($this->_p->trans($this->_helptext), ENT_QUOTES).'"' : '').'/>
                        <span class="helptext">'.$this->_p->trans($this->_helptext ? $this->_helptext : 'fields.password_check').'</span>
                        <input name="'.$this->_base.'['.$this->_base_field_id.'_check]'.$params['name_add'].'" id="'.$this->_field_id.'_check'.$params['name_add'].'" type="password" '.$params['class_compiled'].' '.($max_input_length ? 'size="'.$max_input_length.'"' : '').' style="'.$style.'" value="" '.$params['readonly'].' '.($this->_helptext_check ? 'placeholder="'.htmlspecialchars($this->_p->trans($this->_helptext_check), ENT_QUOTES).'"' : '').'/>
                        ';

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

}