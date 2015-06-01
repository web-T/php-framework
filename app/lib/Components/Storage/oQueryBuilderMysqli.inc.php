<?php
/**
 * Simply override driver for Mysqli
 *
 * Date: 02.12.14
 * Time: 23:51
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	02.12.2014/goshi 
 */

namespace webtFramework\Components\Storage;

class oQueryBuilderMysqli extends oQueryBuilderMysql{

    public function _cleanQuoteString($data, $magic_quotes_active){

        if (is_array($data)){
            foreach ($data as $k => $v){
                $data[$k] = $this->_cleanQuoteString($v, $magic_quotes_active);
            }

        } else {
            //undo any magic quote effects so mysql_real_escape_string can do the work
            if ($magic_quotes_active) {
                $data = stripslashes($data);
            }

            if (!is_numeric($data) && !is_object($data)) {
                $data = $this->_p->db->cleanupEscape($data);
            }
        }

        return $data;

    }

    public function quoteString($s){

        // i.e PHP >= v4.3.0
        $magic_quotes = get_magic_quotes_gpc();
        if ($this->_p->db && $this->_p->db->instance != null) {

            if (is_array($s)){
                foreach ($s as $k => $v){
                    $s[$k] = $this->_cleanQuoteString($v, $magic_quotes);
                }

            } else {
                //undo any magic quote effects so mysql_real_escape_string can do the work
                if ($magic_quotes) {
                    $s = stripslashes($s);
                }

                if (!is_numeric($s) && !is_object($s)) {
                    $s = $this->_cleanQuoteString($s, $magic_quotes);
                }
            }

            return $s;

        } else { // before PHP v4.3.0

            return $this->_deprecatedQuoteString($s, $magic_quotes);

        }

    }

} 