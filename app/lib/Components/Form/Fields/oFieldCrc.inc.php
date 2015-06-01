<?php
/**
 * CRC field type
 *
 * Date: 11.02.15
 * Time: 22:29
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	11.02.2015/goshi 
 */

namespace webtFramework\Components\Form\Fields;

use webtFramework\Helpers\Text;

class oFieldCrc extends oFieldInteger{

    public function save($value, &$row = array(), &$old_data, $lang_id = null){

        $data = null;

        if (isset($row[$this->_visual['source']['field']])){

            if (is_array($row[$this->_visual['source']['field']])){
                $data = $row[$this->_visual['source']['field']][$lang_id];
            } else
                $data = $row[$this->_visual['source']['field']];

        }

        if ($value == '' && $data)
            $src = $data;
        else
            $src = $value;

        $src = trim(Text::cleanupRepeat($src));
        $value = $src == '' ? 0 : crc16($src);

        return $value;

    }

} 