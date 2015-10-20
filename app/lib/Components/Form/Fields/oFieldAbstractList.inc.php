<?php
/**
 * Abstract list field class
 *
 * Date: 14.02.15
 * Time: 10:15
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	14.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;

abstract class oFieldAbstractList extends oField{

    abstract protected function _getRowView($data, $arr, $primary, $row_class = null, $row_style = null, $attributes = null, $pre_add = null, $title, $params);

    /**
     * create whole container for field rows
     * @param string $visual
     * @param mixed $data
     * @param string $primary
     * @param string $custom_value
     * @param string $style
     * @param array $params
     * @return mixed
     */
    protected function _getContainerView($visual, $data, $primary, $custom_value, $style, $params){

        return $visual;

    }

    protected function _getTableView($data, $primary, $params){

        $tbl_data = $this->_oForms->getTblSource($this->_base_field_id, $this->_visual['source'], array('field_name_add' => $this->_field_name_add));
        // if we have tree like style of the select
        $custom_value = $data;

        $visual = '';
        $value_html = array();

        if ($tbl_data && !empty($tbl_data)){

            foreach ($tbl_data as $arr){

                $add_class = '';

                if (isset($arr['is_on']) && !$arr['is_on']){
                    $add_class .= 'imghidden red';
                    $pre_add = ' * ';
                } else {
                    $pre_add = '';
                }

                // check for custom value
                //dump(array($data, $arr[$primary]), false);
                if ($data == $arr[$primary]){

                    // cleanup custom value
                    $custom_value = '';
                }

                // prepare additional attributes
                $attr = '';
                if (!empty($this->_visual['source']['attributes'])){
                    foreach ((array)$this->_visual['source']['attributes'] as $z){
                        if ($arr[$z]){
                            $attr .= $z.'="'.htmlspecialchars(trim($arr[$z])).'" ';
                        }
                    }
                }

                if (isset($this->_visual['source']['row_title']) && isset($arr[$this->_visual['source']['row_title']]))
                    $title = $arr[$this->_visual['source']['row_title']];
                else
                    $title = get_row_title($arr);

                list($visual_row, $value_html_row) = array_values($this->_getRowView($data, $arr, $primary, $add_class, null, $attr, $pre_add, $title, $params));

                $visual .= $visual_row;

                if ($value_html_row !== null){
                    $value_html[$arr[$primary]] = $value_html_row;
                }

            }

        }

        return  array(
            'view' => $visual,
            'custom_value' => $custom_value,
            'value_html' => $value_html
        );
    }

    protected function _getArrayView($data, $primary, $params){

        $visual = '';
        $value_html = array();

        $arr_name = $this->_visual['source']['arr_name'];

        // for callable arguments - simply call the method
        if (is_callable($arr_name)){
            $arr_name = call_user_func($arr_name, $this->_p);
        }

        // make array from translate
        if (is_string($arr_name)){
            $arr_name = $this->_p->Service('oLanguages')->getMessage($arr_name);
        }

        // setting custom value
        $custom_value = $data;

        if (is_array($arr_name)){
            foreach ($arr_name as $z => $x){

                // check for custom value
                if ($data == $z){
                    $custom_value = '';
                }

                // check if array has inner array with data
                $add_style = '';
                if (is_array($x)){
                    if (isset($x['background']) && $x['background'] != '')
                        $add_style .= 'background: '.$x['background'].';';
                    if (isset($x['color'])  && $x['color'] != '')
                        $add_style .= 'color: '.$x['color'].';';

                    $title = $this->_p->trans($x['title']);
                } else {
                    $title = $x;
                }

                $pre_add = '';
                $attr = '';
                $add_class = '';

                list($visual_row, $value_html_row) = array_values($this->_getRowView($data, array('id' => $z), 'id', $add_class, $add_style, $attr, $pre_add, $title, $params));

                $visual .= $visual_row;

                if ($value_html_row !== null){
                    $value_html[] = $value_html_row;
                }

            }
        }

        return  array(
            'view' => $visual,
            'custom_value' => $custom_value,
            'value_html' => $value_html
        );

    }

    public function get($data = null, $params = array()){

        parent::get($data, $params);

        if ($this->_p->getVar('is_debug')){
            $this->_p->debug->add("oForms: start compile field '".$this->_base_field_id."'");
        }

        // some hack for models
        // todo: create model instead of filling array
        if (isset($this->_visual['source']['model']) && $this->_visual['source']['model'] != ''){
            $m = $this->_p->Model($this->_visual['source']['model']);
            if ($m){
                $this->_visual['source']['tbl_name'] = array_flip($this->_p->getVar('tables'))[$m->getModelTable()];
                if (isset($m->getModelFields()['owner_id']))
                    $this->_visual['source']['subtype'] = 'tree';
                $this->_visual['source']['multilang'] = $m->getIsMultilang();
                unset($m);
            }
        }

        $visual = $custom_value = '';
        $value_html = array();
        $primary = null;

        if (isset($this->_visual['source']['tbl_name']) && $this->_p->getVar($this->_visual['source']['tbl_name'])){

            if ($this->_visual['source']['field']){
                $primary = $this->_visual['source']['field'];
            } else if ($this->_visual['source']['multilang']){
                $primary = 'real_id';
            } else {
                $primary = 'id';
            }

            list($visual, $custom_value, $value_html) = array_values($this->_getTableView($data, $primary, $params));

        } elseif (isset($this->_visual['source']['arr_name'])){

            list($visual, $custom_value, $value_html) = array_values($this->_getArrayView($data, $primary, $params));

        }

        // check for bad post data
        //dump(array($this->_base_field_id => array($custom_value, $this->_data[$this->_base_field_id.'_custom'], $this->_visual['is_custom_title'], $this->_helptext)), false);
        if ($custom_value == '_custom_' && $this->_oForms->getData()[$this->_base_field_id.'_custom']){
            $custom_value = $this->_oForms->getData()[$this->_base_field_id.'_custom'];
        } elseif ($custom_value != '' &&
            $custom_value === '0' &&
            (!$this->_oForms->getData()[$this->_base_field_id.'_custom'] ||
                $this->_oForms->getData()[$this->_base_field_id.'_custom'] == $this->_visual['is_custom_title'] ||
                $this->_oForms->getData()[$this->_base_field_id.'_custom'] == $this->_helptext
            )){

            $custom_value = '';
        }

        // update custom value for helptext
        if ($this->_visual['is_custom_value'] && $this->_helptext && $custom_value == ''){
            $custom_value = $this->_helptext;
        }



        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        $visual = $this->_getContainerView($visual, $data, $primary, $custom_value, $style, $params);

        if ($this->_p->getVar('is_debug')){
            $this->_p->debug->add("oForms: end compile field '".$this->_base_field_id."'");
        }

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }


} 