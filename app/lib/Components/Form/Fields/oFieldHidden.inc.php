<?php
/**
 * Hidden field
 *
 * Date: 11.02.15
 * Time: 09:01
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

class oFieldHidden extends oFieldVarchar{


    public function get($data = null, $params = array()){

        parent::get($data, $params);

        // preparing fields
        if ($this->_multilang){

            $visual = get_langdivs_tpl($this->_p,
                '<input type="hidden" name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'[{LANG}]" id="'.$this->_field_id.$params['name_add'].'[{LANG}]" lang="{LANG}" style="display: {IS_ACTIVE};" value="{FIELD_CONTENT}"  '.$params['readonly'].' />',
                $data);

        } else {

            $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" type="hidden" value="'.htmlspecialchars((string)$data, ENT_QUOTES).'" '.$params['readonly'].' />';
        }
        $value_html = $data;

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

} 