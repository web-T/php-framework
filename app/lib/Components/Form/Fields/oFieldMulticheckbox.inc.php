<?php
/**
 * Multicheckbox field
 *
 * Date: 14.02.15
 * Time: 18:50
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	14.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\Traits\oFieldBitTrait;

class oFieldMulticheckbox extends oFieldAbstractList {

    use oFieldBitTrait;

    protected function _getRowView($data, $arr, $primary, $row_class = null, $row_style = null, $attributes = null, $pre_add = null, $title, $params){

        return array(
            'visual' => '<p><input type="checkbox" name="'.$this->_base.'['.$this->_base_field_id.']['.$arr[$primary].']'.$params['name_add'].'" id="'.$this->_field_id.'['.$arr[$primary].']'.$params['name_add'].'" class="field-'.$this->_base_field_id.' '.$row_class.'" '.($row_style ? 'style="'.$row_style.'"' : '').' '.$attributes.' value="'.htmlspecialchars($arr[$primary], ENT_QUOTES).'"  '.$params['readonly'].' '.(is_array($data) && in_array($arr[$primary], $data) ? 'checked="checked"' : '').' /><label for="'.$this->_field_id.'['.$arr[$primary].']'.$params['name_add'].'"><span></span>'.$pre_add.$title.'</label></p>',
            'value_html' => is_array($data) && in_array($arr[$primary], $data) ? $title : null
        );

    }

}