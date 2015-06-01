<?php
/**
 * Base textarea class
 *
 * Date: 10.02.15
 * Time: 22:46
 * @version 1.0
 * @author goshi
 * @package web-T[Form]
 * 
 * Changelog:
 *	1.0	10.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;


class oFieldText extends oFieldVarchar{

    public function get($data = null, $params = array()){

        parent::get($data, $params);

        $max_input_length = '';
        /*if ($this->_maxlength){
            $max_input_length = $this->_maxlength;
        }*/

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

        // preparing fields
        if ($this->_multilang){

            if ($this->_helptext)
                foreach ($this->_p->getLangTbl() as $v)
                    if ($data[$v] == '')
                        $data[$v] = $this->_helptext;

            if ($this->_fulleditor)
                $visual = '<textarea data-type="'.$this->_datatype.'" name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'[tmp_elem]" id="'.$this->_field_id.''.$params['name_add'].'[tmp_elem]"  '.$params['readonly'].' '.($this->_helptext ? 'data-placeholder="'.$this->_helptext.'"': '').' ></textarea>'.get_langdivs_tpl($this->_p,
                        '<textarea data-type="'.$this->_datatype.'" name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'[{LANG}]" style="display: none" id="'.$this->_field_id.''.$params['name_add'].'[{LANG}]"  '.$params['readonly'].' >{FIELD_CONTENT}</textarea>',
                        $data, false, true, false);
            else
                $visual = get_langdivs_tpl($this->_p,
                    '<textarea data-type="'.$this->_datatype.'" '.$params['class_compiled'].' name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'[{LANG}]" id="'.$this->_field_id.$params['name_add'].'[{LANG}]'.'" lang="{LANG}" style="display: {IS_ACTIVE};'.$style.'"  '.$params['readonly'].' '.($max_input_length ? 'cols="'.intval($max_input_length/1.3).'" rows="'.intval($max_input_length/20).'"' : '').' '.($this->_helptext ? 'data-placeholder="'.$this->_helptext.'"': '').' >{FIELD_CONTENT}</textarea>',
                    $data);

        } else {

            if ($data == '' && $this->_helptext)
                $data = $this->_helptext;

            if ($this->_fulleditor)
                $visual = '<textarea data-type="'.$this->_datatype.'" '.$params['class_compiled'].' name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'"  '.$params['readonly'].' '.($this->_helptext ? 'data-placeholder="'.$this->_helptext.'"': '').' >'.htmlspecialchars((string)$data, ENT_QUOTES).'</textarea>';
            else
                $visual = '<textarea data-type="'.$this->_datatype.'" '.$params['class_compiled'].' name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" '.($max_input_length ? 'cols="'.intval($max_input_length/1.3).'" rows="'.intval($max_input_length/20).'"' : '').' style="'.$style.'" '.($this->_helptext ? 'data-placeholder="'.$this->_helptext.'"': '').' '.$params['readonly'].' >'.htmlspecialchars((string)$data, ENT_QUOTES).'</textarea>';


        }
        $value_html = $data;

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

} 