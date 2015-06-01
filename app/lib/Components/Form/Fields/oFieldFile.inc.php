<?php
/**
 * File type field
 *
 * Date: 11.02.15
 * Time: 23:49
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;
use webtFramework\Helpers\MimeType;

class oFieldFile extends oField{


    public function check(&$data, $full_row = array()){

        $valid = true;

        if (!$this->_empty || !$this->_fullempty){
            if ($this->_p->getVar('upload')['service']){

                $valid = $this->_p->Module($this->_p->getVar('upload')['service'])->checkData(array(
                    'file' => $this->_base_field_id,
                    'upload_dir' => $this->_oForms->getUploadDir(),
                    'accept' => $this->_visual['accept'],
                    'file_max_size' => $this->_visual['file_max_size'],
                    'file_save_linked' => $this->_visual['file_save_linked']));

            } else {

                $valid = true;

            }

        }

        return array('valid' => $valid === true ? true : false, 'error' => $valid);

    }


    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return $data = $value;

    }

    /**
     * generate autcomplete field
     * @param null|array $data
     * @param array $params
     * @return array|void
     */
    public function get($data = null, $params = array()){

        parent::get($data, $params);

        if ($params['style']){
            $style = $params['style'];
        } elseif ($this->_style){
            $style = $this->_style;
        } else {
            $style = '';
        }

        // if set accept types for files - set files
        $accept_text = '';
        if (isset($this->_visual['accept']) && is_array($this->_visual['accept'])){
            $accept = array();

            foreach ($this->_visual['accept'] as $v){
                $accept[] = MimeType::findTypeByExt($v);
            }
            $accept = 'accept="'.join(',', $accept).'"';

            $accept_text = $this->_p->trans('accept_types').": ".mb_convert_case(join(', ', $this->_visual['accept']), MB_CASE_UPPER);
            unset($mimetype);
        } else
            $accept = '';

        $value_html = $visual = '';

        if ($params['id'])
            $upload_dir = $this->_oForms->getUploadDir().calc_item_path($params['id']);
        else
            $upload_dir = $this->_oForms->getUploadDir();

        // preparing fields
        if ($this->_multilang){

            if ($data != '' && !$this->_visual['file_download_hide'] && !$this->_visual['file_save_linked'])
                $visual .= get_langdivs_tpl($this->_p,
                    '<p class="b-always-uploaded" style="display: {IS_ACTIVE};" lang="{LANG}"><a href="'.$upload_dir.'{FIELD_CONTENT}" target="_blank">{FIELD_CONTENT}</a>
                                    <input type="checkbox" id="remove_files['.$this->_field_id.'][{LANG}]'.$params['name_add'].'" name="remove_files['.$this->_base_field_id.'][{LANG}]'.$params['name_add'].'" lang="{LANG}" style="display: {IS_ACTIVE};" '.$params['readonly'].'/> <label for="remove_files['.$this->_field_id.'][{LANG}]'.$params['name_add'].'" lang="{LANG}" style="display: {IS_ACTIVE};" >'.$this->_p->trans('upload.delete').'</label>
                                    </p>',
                    $data);

            if (!empty($_POST['presaved'][$this->_base_field_id]))// add presaved fields
                $visual .= get_langdivs_tpl($this->_p,
                    '<input type="hidden" name="presaved['.$this->_base_field_id.'][{LANG}]'.$params['name_add'].'" id="presaved['.$this->_field_id.'][{LANG}]'.$params['name_add'].'" lang="{LANG}" value="{FIELD_CONTENT}" /><p class="b-presaved" style="display: {IS_ACTIVE};" lang="{LANG}">{FIELD_CONTENT}</p>',
                    $_POST['presaved'][$this->_base_field_id]);

            $visual .= get_langdivs_tpl($this->_p,
                '<input type="file" name="'.$this->_base.'['.$this->_base_field_id.'][{LANG}]'.$params['name_add'].'" '.$params['class_compiled'].' id="'.$this->_field_id.'[{LANG}]'.$params['name_add'].'" lang="{LANG}" style="display: {IS_ACTIVE};'.$style.'" '.$accept.'  '.$params['readonly'].'  />
                                    ',
                $data);


        } else {

            if ($data != '' && !$this->_visual['file_download_hide'] && !$this->_visual['file_save_linked'] && file_exists($this->_p->getDocDir().$upload_dir.$data))
                $visual .= '<p class="b-always-uploaded"><a href="'.$upload_dir.$data.'" target="_blank">'.$data.'</a>, '.get_friendly_size(filesize($this->_p->getDocDir().$upload_dir.$data)).'
                                <input type="checkbox" id="remove_files['.$this->_field_id.']'.$params['name_add'].'" name="remove_files['.$this->_base_field_id.']'.$params['name_add'].'" '.$params['readonly'].'/> <label for="remove_files['.$this->_field_id.']'.$params['name_add'].'">'.$this->_p->trans('upload.delete').'</label></p>';

            if (!empty($_POST['presaved'][$this->_base_field_id]))
                $visual .= '<input type="hidden" name="presaved['.$this->_base_field_id.']'.$params['name_add'].'" id="presaved['.$this->_field_id.']'.$params['name_add'].'" value="'.$_POST['presaved'][$this->_base_field_id].'" /><p class="b-presaved">'.$_POST['presaved'][$this->_base_field_id].'</p>';


            $visual .= '<input type="file" name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'"  '.$params['class_compiled'].' style="'.$style.'" '.$accept.' '.$params['readonly'].' />';

            $value_html = $upload_dir.$data;


        }

        // append file help info
        if (!$this->_visual['file_help_hide']){

            // get max upload size
            if (get_real_size(ini_get('post_max_size')) > get_real_size(ini_get('upload_max_filesize')))
                $max_size = get_real_size(ini_get('upload_max_filesize'));
            else
                $max_size = get_real_size(ini_get('post_max_size'));

            // getting maximum filesize
            $filesize = isset($this->_visual['file_max_size']) && $this->_visual['file_max_size'] < $max_size ? $this->_visual['file_max_size'] : $max_size;

            $visual .= '<span class="b-filehelp">'.$this->_p->trans('max_file_size').': '.get_friendly_size($filesize).'. '.$accept_text.'</span>';

        }

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }


}