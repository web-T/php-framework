<?php
/**
 * ...
 *
 * Date: 11.02.15
 * Time: 00:33
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldBoolean extends oField{

    public function get($data = null, $params = array()){

        parent::get($data, $params);

        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        if ($this->_multilang){

            $bvalues = array();
            foreach ($this->_p->getLangs() as $k => $v)
                $bvalues[$k] = $data[$k] == 1 ? 'checked="checked"' : '';

            $visual = get_langdivs_tpl($this->_p,
                '<input name="'.$this->_base.'['.$this->_base_field_id.'][{LANG}]'.$params['name_add'].'" id="'.$this->_field_id.'[{LANG}]'.$params['name_add'].'" value="1" type="checkbox" lang="{LANG}" style="display: {IS_ACTIVE};'.$style.'" {FIELD_CONTENT}  '.$params['readonly'].' /><label for="'.$this->_field_id.$params['name_add'].'" lang="{LANG}" style="display: {IS_ACTIVE};" ><span></span>'.$this->_p->trans('fields.'.$this->_base_field_id).'</label>',
                $bvalues);

        } else {
            $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" value="1" type="checkbox"  '.($data == 1? 'checked="checked"' : '').'  '.$params['readonly'].' /><label for="'.$this->_field_id.$params['name_add'].'"><span></span>'.(!$this->_visual['no_title'] ? ($this->_title ? $this->_p->trans($this->_title) : $this->_p->trans('fields.'.$this->_base_field_id)) : '').'</label>';
        }
        // add flag for detecting checkbox present
        $visual .= '<input name="'.$this->_base.'['.$this->_base_field_id.'_exists]'.$params['name_add'].'" id="'.$this->_field_id.'_exists'.$params['name_add'].'" value="1" type="hidden" />';

        $value_html = $this->_title ? $this->_p->trans($this->_title) : $this->_p->trans('fields.'.$this->_base_field_id);

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return (int)parent::save($value, $row, $old_data, $lang_id);

    }


    public function check(&$data, $full_row = array()){

        // prepare boolean value
        $data = ($data == 1 ? 1 : 0);

        return array('valid' => true);
    }

} 