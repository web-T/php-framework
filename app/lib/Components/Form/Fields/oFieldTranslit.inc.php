<?php
/**
 * Translit field
 *
 * Date: 11.02.15
 * Time: 22:36
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Helpers\Text;

class oFieldTranslit extends oFieldVarchar{

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $data = null;

        // translit fields are always one language and based on first language in the list

        if ($this->_oForms->getFields()[$this->_visual['source']['field']]['type'] == 'virtual'){

            $data = $this->_oForms->getValue($this->_visual['source']['field']);

        } elseif (isset($row[$this->_visual['source']['field']])){

            if (is_array($row[$this->_visual['source']['field']])){

                reset($row[$this->_visual['source']['field']]);
                list(, $data) = each($row[$this->_visual['source']['field']]);

            } else {

                $data = $row[$this->_visual['source']['field']];

            }

        }

        if ($value == '' && $data)
            $src = $data;
        else
            $src = $value;

        if ($this->_visual['handler'])
            $handler = $this->_visual['handler'];
        else
            $handler = 'transliterate';

        $data = Text::$handler(
            $this->_maxlength ? mb_substr($src, 0, $this->_maxlength) : $src,
            true,
            array('fieldReg' => $this->_p->getVar('regualars')[$this->_fieldReg.'_neg'] ? $this->_p->getVar('regualars')[$this->_fieldReg.'_neg'] : $this->_p->getVar('regualars')['url_nick_neg'])
        );

        $data = Text::cleanupRepeat($data);

        // checking for unique
        if ($this->_unique && $this->_oForms->getCallbacks() && isset($this->_oForms->getCallbacks()['unique'])){
            $found = false;
            $counter = 0;
            $base_data = $data;
            while (!$found){

                $_data = array(
                    'field' => $this->_base_field_id,
                    'value' => $data,
                    'exclude' => $this->_unique['exclude'],
                    'params' => array()
                );

                if (isset($this->_unique['params'])){
                    foreach ($this->_unique['params'] as $pk => $pv){
                        if ($pk == 'owner_field'){
                            $_data['params']['owner_id'] = $pv;
                        } else {
                            $_data['params'][$pk] = $pv;
                        }
                    }
                }

                if (is_callable($this->_oForms->getCallbacks()['unique'])){

                    $method = $this->_oForms->getCallbacks()['unique'];
                    $m = $this->_oForms->getModel();
                    $fr = $method($m, $_data);

                } else {

                    $fr = call_user_func_array($this->_oForms->getCallbacks()['unique'], array($this->_oForms->getModel(), $_data));

                }

                if (!$fr){
                    $found = true;
                } else {
                    $counter++;
                    $data = $base_data.$counter;
                }

            }
        }

        return $data;

    }

}