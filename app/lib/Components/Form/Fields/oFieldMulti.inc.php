<?php
/**
 * Multi field
 *
 * Date: 11.08.14
 * Time: 20:51
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.08.2014/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;
use webtFramework\Services\oForms;
use webtFramework\Core\oPortal;

/**
 * Multi fields
 * @package web-T[CMS]
 */
class oFieldMulti extends oField{

    public function __construct(oPortal $p, oForms $oForms, $params = array()){

        parent::__construct($p, $oForms, $params);

    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        if (is_array($value)){

            $result = array();
            if ($this->_children){

                $fc = new oForms($this->_p, array(
                    'base' => $this->_base,
                    'multilang' => $this->_oForms->getMultilang(),
                    'upload_dir' => $this->_oForms->getUploadDir(),
                ));

                //dump($value);
                $i = 1;

                // checking for maximum rows
                if ($this->_visual['max_values']){
                    $value = array_slice($value, 0, $this->_visual['max_values'], true);
                }

                // patching old data
                $new_old_data = isset($old_data[$this->_p->getLangId()]) ? $old_data[$this->_p->getLangId()][$this->_field_id] : $old_data[$this->_field_id];
                if ($new_old_data){
                    $new_old_data = unserialize($new_old_data);
                }

                // save row for restoring
                $base_row = $row;
                foreach ($value as $id => $values){
                    $result[$i] = array();

                    foreach ($this->_children as $k => $v){
                        $row['__internal_id__'] = $id;
                        // get new field name
                        $field_name = $this->_field_id;

                        if (is_array($values)){
                            $row[$this->_field_id] = $values[$k];

                            //dump(array($this->_field_id => $values[$k]), false);
                            if ($v['visual']['is_custom_value']){
                                //dump(array($this->_field_id, $id, $row[$this->_field_id.'_custom']), false);
                                $row[$this->_field_id.'_custom'] = is_array($row) && is_array($row[$this->_field_id.'_custom']) && is_array($row[$this->_field_id.'_custom'][$id]) ? $row[$this->_field_id.'_custom'][$id][$k] : '';
                            }

                            //dump(array('ZKM:', $values[$k], $this->_field_id.'_custom', $row[$this->_field_id.'_custom']), false);

                            // special field translation
                            $fc->AddParams(array('data' => array($field_name => $values[$k]), 'fields' => array($field_name => $v)));
                            //dump(array('data' => array($field_name => $values[$k]), 'fields' => array($field_name => $v)), false);
                            $tmp_old_data = $new_old_data ? ($this->_oForms->_multilang ? array($this->_p->getLangId() => array($field_name => $new_old_data['items'][$id][$k])) : array($field_name => $new_old_data['items'][$id][$k])) : $new_old_data;

                            // if multilang - then save for each field
                            if ($v['multilang']){
                                $result[$i][$k] = array();
                                $old_row_lang_id = $row['lang_id'];
                                foreach ($this->_p->getLangTbl() as $lid){
                                    $row['lang_id'] = $lid;
                                    $result[$i][$k][$lid] = unqstr($this->_fixDoubleQuoted($fc->getSaveField($field_name, $row, $tmp_old_data, $lid/*, '['.$id.']['.$k.']'*/)));
                                }
                                $row['lang_id'] = $old_row_lang_id;
                            } else
                                $result[$i][$k] = unqstr($this->_fixDoubleQuoted($fc->getSaveField($field_name, $row, $tmp_old_data, $lang_id/*, '['.$id.']['.$k.']'*/)));
                        } else {
                            $row[$this->_field_id] = $result[$i][$k] = '';
                        }
                    }
                    $i++;
                }
                // remove special id
                unset($row['__internal_id__']);
                $row = $base_row;
                //dump($result);
            }

            return serialize(array('items' => $result));
        } else {
            return $value;
        }

    }

    protected function _fixDoubleQuoted($data){

        return str_replace(array('\r\n', '\t', '\\'), array("\r\n", "\t", ''), $data);

    }

    /**
     * generate field.
     * Multifield format:
     *  array(
     *     'items' => array(
     *      'field1' => value1,
     *      'field2' => value2,
     *      'field3' => value3
     *     ),
     * )
     *
     * @param null|array $data
     * @param array $params
     * @return array|void
     */
    public function get($data = null, $params = array()){

        parent::get($data, $params);

        $value = array();

        $visual = '<div class="b-fields-multi" id="'.$this->_field_id.'" '.$this->_oForms->compileAttr('class', $this->_class).' style="'.$this->_style.'">';
        if (is_array($this->_children)){

            if ($data && is_string($data)){
                $data = (array)unserialize($data);
            } elseif (is_array($data) && !isset($data['items'])){
                // restore from broken post
                $data['items'] = $data;
            }

            if (!is_array($data))
                $data = array();


            $fc = new oForms($this->_p, array(
                'base' => $this->_base,
                'multilang' => $this->_oForms->getMultilang(),
                'upload_dir' => $this->_oForms->getUploadDir(),
            ));

            if (!$data['items'] || !is_array($data['items'])){
                $i = $this->_visual['min_values'] ? $this->_visual['min_values'] : 1;
                $data['items'] = array();
                for ($z = 1; $z <= $i; $z++){
                    $data['items'][$z] = array();
                }
            }

            $value = $data['items'];

            $options = array();

            $ccount = count($this->_children);

            //dump($data['items'], false);
            foreach ($data['items'] as $id => $values){

                if (!is_array($values))
                    $values = array();

                //dump($this->_non_valid_details['controller'], false);
                $error_state = !$this->_non_valid_details['controller']['valid'] ? $this->_non_valid_details['controller']['details'][$id] : false;

                $fc->AddParams(array(/*'fields_postfix' => '['.$id.']', */'debug' => true));

                $visual .= '<div class="b-field-item" id="'.$this->_field_id.'_'.$id.'"><div class="b-subfields-cont">';

                // compile visibility value
                $visibility = array();
                if ($this->_oForms->_temp['visibility'][$this->_field_id][$id] && is_array($this->_oForms->_temp['visibility'][$this->_field_id][$id])){
                    foreach ($this->_oForms->_temp['visibility'][$this->_field_id][$id] as $k => $v){
                        $visibility[$this->_field_id.'['.$id.']['.$k.']'] = $v;
                    }
                }

                foreach ($this->_children as $k => $v){
                    // get new field name
                    $field_name = $this->_field_id;
                    $this->_children[$k]['owner_id'] = $v['owner_id'] = $field_name;
                    //dump($error_state, false);

                    $v['error'] = $error_state && in_array($k, $error_state['non_valid']) ? $error_state['valid_details'][$k] : false;
                    //dump(array($k => $v['error']), false);
                    // special field translation
                    //dump('['.$id.']['.$k.']');
                    // rebuild error state
                    // dump(array('Имя: '.$field_name => $v), false);

                    // update field title
                    if (!isset($v['title'])){
                        $v['title'] = $this->_p->trans('fields.'.$k);
                    }

                    $options = array(
                        'data' => array(
                            $this->_field_id => $values[$k],
                            'visibility' => $visibility,),
                        'fields' => array($field_name => $v),
                        'non_valid_fields' => $error_state && in_array($k, $error_state['non_valid']) ? array($field_name) : false,
                        'non_valid_details' => $error_state && in_array($k, $error_state['non_valid']) ? array($field_name => $error_state['valid_details'][$k]) : false
                    );

                    // hack for custom field
                    //dump_file($this->_p->query->request->getPost($this->_base)[$this->_field_id.'_custom']);
                    if (isset($this->_p->query->request->getPost($this->_base)[$this->_field_id.'_custom']) && is_array($this->_p->query->request->getPost($this->_base)[$this->_field_id.'_custom'][$id])){
                        $options['data'][$this->_field_id.'_custom'] = $this->_p->query->request->getPost($this->_base)[$this->_field_id.'_custom'][$id][$k];
                    }

                    $fc->AddParams($options);
                    $fc->updateDefaultValues();
                    //dump(array($field_name => $options), false);

                    $fld = $fc->getField($field_name, $params['id'], '['.$id.']['.$k.']', array('__internal_id__' => $id));

                    $visual .= '<div class="b-subfield b-subfield-'.$k.'">'.($ccount  > 1 ? '<span class="b-subfield-title">'.($v['type'] != 'boolean' ? $this->_p->trans($v['title']) : '&nbsp;').'</span>': '').$fld['html'].'<del class="clr"></del></div>';
                }
                unset($options);
                // adding increment/decrement
                $visual .= '</div><span class="b-field-minus" data-id="'.$this->_field_id.'_'.$id.'"></span><span class="b-field-plus" data-id="'.$this->_field_id.'_'.$id.'"></span>';

                $visual .= '<del class="clr"></del></div>';
            }
        }
        $visual .= '</div>';

        return array('value' => $value, 'value_html' => $value, 'html' => $visual);

    }

    public function getValue($data = null, $params = array()){


        if ($data && is_string($data)){
            $data = (array)unserialize($data);
        } elseif (is_array($data) && !isset($data['items'])){
            // restore from broken post
            $data['items'] = $data;
        }

        if (!is_array($data))
            $data = array();

        if (!$data['items'] || !is_array($data['items'])){
            $i = $this->_visual['min_values'] ? $this->_visual['min_values'] : 1;
            $data['items'] = array();
            for ($z = 1; $z <= $i; $z++){
                $data['items'][$z] = array();
            }
        }

        $value = $data['items'];

        return $value;

    }


    /**
     * check multi field, we use cascade method
     * @param $data
     * @param array $full_row
     * @return bool
     */
    public function check(&$data, $full_row = array()){

        parent::get($data, $full_row);

        $result = array('valid' => true, 'details' => array());

        if (is_array($this->_children)){

            $adata = $data;

            if ($adata && is_string($adata)){
                $adata = (array)unserialize($adata);
            } elseif (is_array($adata) && !isset($adata['items'])){
                // restore from broken post
                $adata['items'] = $adata;
            }

            if (!is_array($adata))
                $adata = array();


            $fc = new oForms($this->_p, array(
                'base' => $this->_base,
                'fields' => $this->_children,
                'caller' => $this,
                'multilang' => $this->_oForms->getMultilang(),
                'upload_dir' => $this->_oForms->getUploadDir(),
                'callbacks' => array(
                    'unique' => array($this->_p->db->getManager(), 'checkValueExists')
                ),
            ));

            if (!$adata['items'] || !is_array($adata['items'])){
                $i = $this->_visual['min_values'] ? $this->_visual['min_values'] : 1;
                $adata['items'] = array();
                for ($z = 1; $z <= $i; $z++){
                    $adata['items'][$z] = array();
                }
            }

            foreach ($adata['items'] as $id => $values){
                //dump($values);
                $fc->AddParams(array('data' => $values));
                $result['details'][$id] = $fc->validate();

                if (!empty($result['details'][$id]['non_valid']))
                    $result['valid'] = false;

            }

            unset($adata);
        }
        return $result;
    }

}
