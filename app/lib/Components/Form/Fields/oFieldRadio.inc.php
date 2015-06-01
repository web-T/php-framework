<?php
/**
 * Radio field
 *
 * Date: 14.02.15
 * Time: 18:48
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	14.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;


class oFieldRadio extends oFieldAbstractList {

    protected function _getRowView($data, $arr, $primary, $row_class = null, $row_style = null, $attributes = null, $pre_add = null, $title, $params){

        return array(
            'visual' => '<p><input class="input-radio field-'.$this->_base_field_id.' '.$row_class.'" '.($row_style ? 'style="'.$row_style.'"' : '').' '.$attributes.' type="radio" data-owner="'.$this->_base_field_id.$params['name_add'].'" name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.'['.$arr[$primary].']'.$params['name_add'].'" value="'.htmlspecialchars($arr[$primary], ENT_QUOTES).'" '.$params['readonly'].' '.($data == $arr[$primary] ? 'checked="checked"' : '').' /><label for="'.$this->_field_id.'['.$arr[$primary].']'.$params['name_add'].'"><span></span>'.$pre_add.$title.'</label></p>',
            'value_html' => $data == $arr[$primary] ? $title : null
        );

    }


    protected function _getContainerView($visual, $data, $primary, $custom_value = '', $style = '', $params){

        if ($this->_visual['is_custom_value']){

            $max_input_length = '';

            if ($this->_max_input_size && $this->_max_input_size < $this->_maxlength){
                $max_input_length = $this->_max_input_size;
            }

            $visual .= '<p><input type="radio" name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.'_custom_'.$params['name_add'].'"  class="field-'.$this->_base_field_id.' input-radio" '.$params['readonly'].' value="_custom_" '.($data == $custom_value && $custom_value != '' && $custom_value != $this->_helptext ? 'checked="checked"' : '').' /><label for="'.$this->_field_id.'[_custom_]'.$params['name_add'].'">'.($this->_visual['is_custom_title'] ? $this->_visual['is_custom_title'] : $this->_p->trans('fields.is_custom_title')).'</label>';
            $visual .= '<input name="'.$this->_base.'['.$this->_base_field_id.'_custom]'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'_custom" type="text" '.$params['class_compiled'].' size="'.$max_input_length.'" value="'.htmlspecialchars((string)$custom_value, ENT_QUOTES).'" style="'.$style.'; display: '.($custom_value != '' && $custom_value != $this->_helptext? 'block' : 'none').';"  '.$params['readonly'].' '.($this->_helptext ? 'data-placeholder="'.htmlspecialchars((string)$this->_helptext, ENT_QUOTES).'"': '').' /></p>';
        }

        return $visual;

    }

} 