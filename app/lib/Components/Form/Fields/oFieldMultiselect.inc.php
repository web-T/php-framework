<?php
/**
 * Multiselect field
 *
 * Date: 14.02.15
 * Time: 18:44
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	14.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\Traits\oFieldBitTrait;

// TODO: make saving list of items to the separate field (like json, or external table)

class oFieldMultiselect extends oFieldSelect{

    use oFieldBitTrait;

    protected function _getRowView($data, $arr, $primary, $row_class = null, $row_style = null, $attributes = null, $pre_add = null, $title, $params){

        return array(
            'visual' => '<option class="'.$row_class.'" '.$attributes.' '.($row_style ? 'style="'.$row_style.'"' : '').' value="'.htmlspecialchars($arr[$primary], ENT_QUOTES).'" '.(is_array($data) && in_array($arr[$primary], $data) ? 'selected="selected"' : '').'>'.$pre_add.$title.'</option>',
            'value_html' => is_array($data) && in_array($arr[$primary], $data) ? $title : null
        );

    }



} 