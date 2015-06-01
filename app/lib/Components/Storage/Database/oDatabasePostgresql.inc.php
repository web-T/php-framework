<?php
/**
 * PostgreSQL database driver
 *
 * Date: 23.03.15
 * Time: 21:36
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	23.03.2015/goshi 
 */

namespace webtFramework\Components\Storage\Database;


class oDatabasePostgresql extends oDatabaseDefault{

    public function query($sql){

        if (trim($sql) == '')
            return false;

        $res = $this->_instance->query($sql);

        // this patch for DBSimple and its wrong works with last insert id
        if (preg_match('/^INSERT\s.*/is', $sql) && !$res){
            $res = $this->_instance->selectCell('SELECT LASTVAL();');
        }

        return $res;

    }

} 