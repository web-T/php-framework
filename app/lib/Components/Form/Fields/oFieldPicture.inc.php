<?php
/**
 * Picture field
 *
 * Date: 11.02.15
 * Time: 01:19
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;


use webtFramework\Components\Form\oField;
use \webtFramework\Services\oImagesUploader;

class oFieldPicture extends oField{


    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $value = parent::save($value, $row, $old_data, $lang_id);

        // if you dont want to change data of the image - simply send the old data as current row (btw - it is set by default)
        if ($this->_oForms->getMultilang() && isset($old_data[$row['lang_id']])){

            $img_params['old_img_data'] = $old_data[$row['lang_id']][$this->_base_field_id];

        } elseif (isset($old_data[$this->_base_field_id])) {

            $img_params['old_img_data'] = $old_data[$this->_base_field_id];

        } else {

            $img_params['old_img_data'] = '';
        }

        $data = '';

        if ($value || $img_params['old_img_data']){

            // check current value as simply string
            $save_current_value = false;

            if ($value && is_string($value) && $value != $img_params['old_img_data']){
                $temp = tempnam(sys_get_temp_dir() ? sys_get_temp_dir() : $this->_p->getVar('DOC_DIR').'/'.$this->_p->getVar('temp_dir'), 'User');

                $this->_p->filesystem->writeData($temp, @file_get_contents($value), 'w');
                $info = @getimagesize($temp);

                if ($info) {

                    // save image
                    $value = array();
                    $_FILES = array();

                    // change files format
                    $_file_props = array(
                        'name'		=> $this->_oForms->getModel()->getPrimaryValue(),
                        'type'		=> $info['mime'],
                        'tmp_name'	=> $temp,
                        'error'		=> 0,
                        'size'		=> filesize($temp)
                    );

                    foreach ($this->_visual['picture_props'] as $pid => $pval){

                        $value[$pid] = array(
                            'keep_ratio'	=> 1,
                            'state'			=> isset($pval['secondary']) ? 2 : 3,
                        );

                        if (!isset($pval['secondary'])){
                            foreach($_file_props as $z => $x) {
                                $_FILES[$this->_base_field_id][$pid]['disk'][$z] = $x;
                            }
                        }

                    }

                } else {
                    $value = null;
                    $this->_oForms->setDataValue($this->_base_field_id, null);
                }
            } elseif ($value && is_string($value) && $value == $img_params['old_img_data']) {
                $save_current_value = true;
                $data = $value;
            }

            if (!$save_current_value && ($value || $img_params['old_img_data'])){
                $img_params['elem_id'] = $this->_oForms->getModel()->getPrimaryValue();

                $img_params['elem_title'] = extractFirstValue($this->_oForms->getData(), 'title', $old_data, $this->_oForms->getModel()->getModelFields(), $this->_oForms->getModel()->getIsMultilang());
                if (!$img_params['elem_title'])
                    extractFirstValue($this->_oForms->getData()[$this->_base_field_id], 'title', null, $this->_oForms->getModel()->getModelFields(), $this->_oForms->getModel()->getIsMultilang());

                $img_params['name'] = $this->_base_field_id;
                $img_params['images'] = $this->_visual['picture_props'];
                $img_params['img_dir'] = $this->_visual['img_dir'];

                $imgLoader = $this->_p->Service($this->_p->getVar('image')['service'])->cleanup()->AddParams($img_params);

                $value = $imgLoader->saveData($value);

                unset($imgLoader);

                $data = $value['images'] ? generate_picture_value($value) : '';
            }
        }

        return $data;

    }

    /**
     * generate autcomplete field
     * @param null|array $data
     * @param array $params
     * @return array|void
     */
    public function get($data = null, $params = array()){

        parent::get($data, $params);

        $imgLoader = $this->_p->Service($this->_p->getVar('image')['service']);
        $vars['img_params'] = array();
        $vars['img_params']['images'] = $this->_visual['picture_props'];
        $vars['img_params']['img_dir'] = $this->_visual['img_dir'];

        $vars['img_params']['name'] = $this->_base_field_id;
        //$vars['img_params'] = ;//$img_params;
        $vars['img_params']['old_img_data'] = $data;

        $vars['img_params']['elem_id'] = ($this->_oForms->getModel() ? $this->_oForms->getData()[$this->_oForms->getModel()->getPrimaryKey()] : $this->_oForms->getData()[$this->_oForms->getPrimaryKey()]);

        if (isset($this->_visual['title']))
            $vars['img_params']['images'][0]['title'] = $this->_oForms->getData()[$this->_visual['title']];

        $imgLoader->AddParams($vars['img_params']);

        $visual = $imgLoader->getTemplate();

        unset($imgLoader);


        return array('html' => $visual,
            'value' => $data,
            'value_html' => $data,
            'vars' => $vars
        );

    }

} 