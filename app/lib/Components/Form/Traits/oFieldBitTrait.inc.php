<?php
/**
 * Trait for bit-based fields
 *
 * Date: 15.02.15
 * Time: 10:38
 * @version 1.0
 * @author goshi
 * @package web-T[Forms]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Traits;

trait oFieldBitTrait {

    public function getDefaultValue(){

        if ($this->_default_value){

            $tmp_v = 0;
            $tmp_v |= 1 << $this->_default_value;
            return $tmp_v;

        } else {

            return $this->_default_value;

        }

    }

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $nv = 0;

        if (is_array($value) && count($value)){
            foreach($value as $k => $v){
                $nv |= 1 << $v;
            }
        }
        return $nv;

    }

    public function getValue($data = null, $params = array()){

        $nv = array();

        // else - saving standart base order
        for($i = 0; $i < 32; $i++){
            if ($data & (1 << $i)){
                $nv[] = $i;
            }
        }
        $value = $nv;

        return $value;

    }


    public function get($data = null, $params = array()){

        if (is_array($data)){
            // from unsuccess post
            //$multifield = $value;
        } else {
            // from DB
            $nv = array();

            // else - saving standart base order
            for($i = 0; $i < 32; $i++){
                if ($data & (1 << $i)){
                    $nv[] = $i;
                }
            }
            $data = $nv;
        }

        return parent::get($data, $params);

    }

} 