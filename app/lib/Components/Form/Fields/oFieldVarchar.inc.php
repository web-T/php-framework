<?php
/**
 * Base varchar field type
 *
 * Date: 10.02.15
 * Time: 17:13
 * @version 1.0
 * @author goshi
 * @package web-T[Form]
 * 
 * Changelog:
 *	1.0	10.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Components\Form\oField;
use webtFramework\Services\oForms;
use webtFramework\Core\oPortal;

class oFieldVarchar extends oField {

    protected $_filters = array(
        'save' => array()
    );

    protected $_filters_plain = array(
        'save' => array('cleanupQuotes' => array('"'), 'cleanupDash' => array('—'), 'cleanupTags', 'cleanupRepeat')
    );

    protected $_filters_html = array(
        'save' => array(
            'cleanupMicrosoft',
            'cleanupQuotes' => array('&quot;'),
            'cleanupDash',
            'cleanupRepeat',
            'restoreLinks',
        )
    );

    public function __construct(oPortal &$p, oForms &$oForms, $params = array()){

        parent::__construct($p, $oForms, $params);

        foreach ($this->_filters as $k => $v){
            if (empty($v) && is_array($v)){
                if ($this->_datatype == 'html'){
                    $this->_filters[$k] = $this->_filters_html[$k];
                } else {
                    $this->_filters[$k] = $this->_filters_plain[$k];
                }
            }
        }

    }


    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        return parent::save($value, $row, $old_data, $lang_id);

    }

    /**
     * generate autcomplete field
     * @param null|array $data
     * @param array $params
     * @return array|void
     */
    public function get($data = null, $params = array()){

        parent::get($data, $params);

        $max_input_length = '';

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

            $visual = get_langdivs_tpl($this->_p,
                '<input type="'.(isset($this->_visual['attr_type']) ? $this->_visual['attr_type'] :'text').'" '.($max_input_length ? 'size="'.$max_input_length.'"' : '').' '.$params['class_compiled'].' name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'[{LANG}]" id="'.$this->_field_id.$params['name_add'].'[{LANG}]" lang="{LANG}" style="display: {IS_ACTIVE};'.$style.'" value="{FIELD_CONTENT}" '.($this->_helptext ? 'data-placeholder="'.$this->_helptext.'"': '').' '.$params['readonly'].' />',
                $data);

        } else {

            if ($data == '' && $this->_helptext)
                $data = $this->_helptext;
            //echo $this->_base_field_id."<br>";
            //echo var_dump($data)."<br>";
            $visual = '<input name="'.$this->_base.'['.$this->_base_field_id.']'.$params['name_add'].'" id="'.$this->_field_id.$params['name_add'].'" type="'.(isset($this->_visual['attr_type']) ? $this->_visual['attr_type'] :'text').'" '.$params['class_compiled'].' size="'.$max_input_length.'" value="'.htmlspecialchars((string)$data, ENT_QUOTES).'" style="'.$style.'"  '.$params['readonly'].' '.($this->_helptext ? 'data-placeholder="'.$this->_helptext.'"': '').' />';
        }
        $value_html = $data;

        return array('html' => $visual,
            'value' => $data,
            'value_html' => $value_html,
        );

    }

} 