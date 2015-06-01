<?php
/**
 * Standart select field
 *
 * Date: 14.02.15
 * Time: 18:17
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	14.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Helpers\Text;

class oFieldSelect extends oFieldAbstractList{

    // special 'no' value
    protected $_special_no_value = '~_no_~';

    protected function _getRowView($data, $arr, $primary, $row_class = null, $row_style = null, $attributes = null, $pre_add = null, $title, $params){

        return array(
            'visual' => '<option class="'.$row_class.'" '.($row_style ? 'style="'.$row_style.'"' : '').' '.$attributes.' value="'.htmlspecialchars($arr[$primary], ENT_QUOTES).'" '.($data == $arr[$primary] ? 'selected="selected"' : '').'>'.$pre_add.$title.'</option>',
            'value_html' => $data == $arr[$primary] ? $title : null
        );

    }

    protected function _getContainerView($visual, $data, $primary, $custom_value = '', $style = '', $params){

        // adding no row
        if ($this->_visual['source']['is_clearly_add_no']){
            $visual = '<option '.($primary && $this->_oForms->getData()[$primary] && isset($this->_oForms->getData()[$this->_base_field_id]) && $this->_oForms->getData()[$this->_base_field_id] == 0 ? 'selected="selected"': '').' value="'.htmlspecialchars($this->_special_no_value, ENT_QUOTES).'">- '.$this->_p->trans('no').' -</option>'.$visual;
        }

        // adding empty row
        $min = $this->_visual['source']['empty'];
        if (!isset($min) || (isset($min) && $min)){
            $visual = '<option value="0">'.($this->_visual['source']['empty_helptext'] ? $this->_p->trans($this->_visual['source']['empty_helptext']) : ' - '.$this->_p->trans('select').' - ').'</option>'.$visual;
        }
        // custom value
        if ($this->_visual['is_custom_value']){
            $visual .= '<option '.($custom_value != '' && $custom_value != $this->_helptext? 'selected="selected"' : '').' value="_custom_">'.($this->_visual['is_custom_title'] ? $this->_visual['is_custom_title'] : $this->_p->trans('fields.is_custom_title')).'</option>';
        }

        $visual = '<select '.($style != '' ? 'style="'.$style.'"' : '').' id="'.$this->_field_id.$params['name_add'].'" '.($this->_visual['type'] == 'multiselect' ? 'name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'[]"  multiple="multiple"' : 'name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'"').'  '.$params['readonly'].' '.$params['class_compiled'].'>'.$visual.'</select>';

        // custom field
        if ($this->_visual['is_custom_value']){

            $max_input_length = '';

            if ($this->_max_input_size && $this->_max_input_size < $this->_maxlength){
                $max_input_length = $this->_max_input_size;
            }

            $visual .= '<input name="'.$this->_base.'['.$this->_base_field_id.'_custom]'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'_custom" type="text" '.$this->_oForms->compileAttr('class', array_merge(is_array($this->_class) ? $this->_class : array(), array('b-field-custom'))).' size="'.$max_input_length.'" value="'.htmlspecialchars((string)$custom_value, ENT_QUOTES).'" style="'.$style.'; display: '.($custom_value != '' && $custom_value != $this->_helptext? 'block' : 'none').';"  '.$params['readonly'].' '.($this->_helptext ? 'data-placeholder="'.htmlspecialchars((string)$this->_helptext, ENT_QUOTES).'"': '').' />';

        }

        return $visual;

    }

    /**
     * override save
     * @param $value
     * @param array $row
     * @param $old_data
     * @param null $lang_id
     * @return mixed
     */
    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        if (is_string($value) && $value == $this->_special_no_value){
            $value = '';
        }

        return $this->_applyFilters($value, $this->_filters['save']);

    }


    protected function _getTableView($data, $primary, $params){

        if ($this->_visual['source']['subtype'] == 'tree'){

            // reset custom value
            $custom_value = '';

            $tbl_data = $this->_oForms->getTblSource($this->_base_field_id, $this->_visual['source'], array('field_name_add' => $this->_field_name_add));

            $tree_list = $this->prepareTreeList($tbl_data);
            $curr_owner =  $data ? $data : ($this->_p->query->get()->get('owner_id') && $this->_base_field_id == 'owner_id' ? (int)$this->_p->query->get()->get('owner_id') : 0);
            $owner_id = null;
            //echo $data; die();
            // if current type is multiselect - then get full array
            if ($this->_visual['type'] == 'multiselect'){

                $owner_id = array();
                foreach ($data as $i) {
                    if ($tree_list[$i])
                        $owner_id[] = $tree_list[$i]['real_id'];
                    else
                        $custom_value = $i;
                }

            } elseif (!$curr_owner){
                if ($tree_list[$data])
                    $owner_id = $tree_list[$data]['real_id'];
                else
                    $custom_value = $data;
            } elseif ($curr_owner) {
                $owner_id = $curr_owner;
                $custom_value = $data;
            }

            // try to find custom value

            if ($tree_list && $this->_visual['is_custom_value']){
                $primary = null;
                foreach ($tree_list as $x){
                    if (!$primary){
                        $primary = get_primary(array_keys($x));
                    }
                    // check for custom value
                    if ($data == $x[$primary]){
                        // cleanup custom value
                        $custom_value = '';
                    }
                }
            }

            return  array(
                'view' => $this->getTreeSelectOptions(0, 0, $tree_list, $owner_id, $this->_oForms->getData()[$primary], true, $primary, $this->_visual),
                'custom_value' => $custom_value,
                'value_html' => array()
                /**
                 * TODO: generate value HTML
                 */
            );

        } else {

            return parent::_getTableView($data, $primary, $params);

        }

    }


    /**
     * standart tree list with standart variables
     */
    public function prepareTreeList($array){

        $data = array();

        if (is_array($array) && !empty($array)){
            foreach ($array as $k => $v){
                //prepare array for saving
                $v['type'] = 'item';
                $data[$k] = $v;
            }

            // prepare children
            reset($data);
            foreach ($data as $v){
                if($v['owner_id'] != '') {
                    $data[$v['owner_id']]['children'][] = $v['real_id'];
                    $data[$v['owner_id']]['type'] = 'list';
                }
            }

        }

        return $data;
    }



    /**
     * return SELECT options for standart tree
     *
     * @param int $level
     * @param int $deep
     * @param array $items array of children
     * @param int $curr_owner
     * @param int $curr_id current id of element - it can't be owner for itself (can be array)
     * @param bool $allow_curr_id flag for forcing selectoing current id and down to the tree (for linked data)
     * @param string $primary
     * @param array $params
     * @return string
     */
    public function getTreeSelectOptions($level, $deep, $items, $curr_owner = 0, $curr_id = 0, $allow_curr_id = false, $primary = 'real_id', $params = array()){

        $divider = '&nbsp;';
        $prep = '_';
        $dep = '_';

        $rows = '';
        $level = (int)$level;

        reset($items);

        $deep++;

        //dump($level."---".$deep."---".$items."---".$curr_owner."---".$curr_id."---".$allow_curr_id."---".$primary);
        foreach ($items as $i => $v){
            // we must set checking for present field 'owner_id'
            if (isset($v['owner_id']) && $v['owner_id'] == $level && $i != 0){

                // check if elem is not this elem
                $not_curr_id = true;
                if (is_array($curr_id)){
                    foreach ($curr_id as $x){
                        if ($x == $v[$primary]){ $not_curr_id = false; break;}
                    }
                } else $not_curr_id = $curr_id != $v[$primary];

                if ($not_curr_id || $allow_curr_id){
                    // check for selected (for some using this function we can set owner_id=for_id)

                    if (is_array($curr_owner))
                        $selected = in_array($v[$primary], $curr_owner);
                    else
                        $selected = $v[$primary] == $curr_owner;

                    if ($selected){
                        $title = str_repeat($divider, 5*$deep).$prep.Text::upper($v['title']).$dep;
                        $sel = 'selected="selected"';
                    } else {
                        $title = str_repeat($divider, 5*$deep).$v['title'];
                        $sel = '';
                    }

                    if (isset($v['weight']) && isset($this->_visual['show_weights']))
                        $weight = ' ('.$v['weight'].')';
                    else
                        $weight = '';

                    if (isset($v['is_on']) && !$v['is_on']){
                        $class = ' class="imghidden red" ';
                        $pre_add = ' * ';
                    } else {
                        $class = '';
                        $pre_add = '';
                    }

                    $rows .= '<option value="'.$v[$primary].'" '.$class.' '.$sel.' >'.$pre_add.$title.(!$params['no_weight'] ? $weight : '').'</option>';

                    // making recurtion
                    if (isset($v['children']) && !empty($v['children'])){
                        $rows .= $this->getTreeSelectOptions($v[$primary], $deep, $items, $curr_owner, $curr_id, $allow_curr_id, $primary, $params);

                    }
                }
            }

        }

        return $rows;
    }


}