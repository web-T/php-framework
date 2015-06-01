<?php
/**
 * Special internal type for field groups
 *
 * Date: 11.02.15
 * Time: 22:21
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

class oFieldGroup extends oField{

    public function get($data = null, $params = array()){

        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        // getting from source database tree
        $value_html = '';
        $visual = '<div class="b-group" id="group-'.$this->_field_id.$params['name_add'].'" '.$params['class_compiled'].' style="'.$style.'" >&nbsp;</div>';

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );


    }

} 