<?php
/**
 * Sphinx database driver
 * Uses default mysql library (mysqli) to connect to sphinx
 * Supports sphinx 2.2
 * We recommend you to use real-time indexes for more functionality
 *
 * Date: 12.03.15
 * Time: 18:00
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	1.0	12.03.2015/goshi 
 */

namespace webtFramework\Components\Storage\Database;

class oDatabaseSphinx extends oDatabaseDefault {

    public function init(){

        $reflectionClass = new \ReflectionClass('\DbSimple_Mysqli');
        $this->_reflectionProperty = $reflectionClass->getProperty('link');

        //  lets us invoke private and protected methods
        $this->_reflectionProperty->setAccessible(true);

        $this->_instance = \DbSimple_Generic::connect("mysqli://".$this->_settings['db_user'].":".$this->_settings['db_pass']."@".$this->_settings['db_host']."/".$this->_settings['db_name']);

        if ($this->_instance){

            $this->_instance->query('SET NAMES utf8');

            $this->_instance->setErrorHandler(array($this->_p->debug, 'logDBError'));
            if ($this->_p->getVar('is_debug') == 1 || $this->_p->getVar('is_dev_env') == 1)
                $this->_instance->setLogger(array($this->_p->debug, 'addSQL'));

        } else {
            throw new \Exception($this->_p->trans('error.db.no_connection'));
        }

    }

    /**
     * override standart escape for drivers
     * @param $data
     * @return mixed|string
     * @throws \Exception
     */
    public function cleanupEscape($data){

        return $this->_reflectionProperty->getValue($this->_instance)->escape_string($data);

    }

    public function select($query){

        return $this->_instance->select(is_array($query) ? $query['query'] : $query);

    }

    public function selectCell($query){

        return $this->_instance->selectCell(is_array($query) ? $query['query'] : $query);

    }

    public function selectRow($query) {

        return $this->_instance->selectRow(is_array($query) ? $query['query'] : $query);

    }

    public function selectCol($query){

        return $this->_instance->selectCol(is_array($query) ? $query['query'] : $query);

    }

    public function query($sql){

        if (is_array($sql) && isset($sql['index'])){

            // detect query type
            $type = null;
            if (isset($sql['type']))
                $type = $sql['type'];
            else {

                if (preg_match('/^\s*INSERT \s+/six', $sql)){
                    $type = 'insert';
                } elseif (preg_match('/^\s*UPDATE \s+/six', $sql)){
                    $type = 'update';
                } elseif (preg_match('/^\s*DELETE \s+/six', $sql)){
                    $type = 'update';
                } elseif (preg_match('/^\s*TRUNCATE \s+/six', $sql)){
                    $type = 'truncate';
                }

            }

            if ($type){
                // now detect operation support
                $tables = $this->_instance->query('SHOW TABLES');

                if ($tables){

                    $found = false;

                    foreach ($tables as $v){
                        if ($v['Index'] == $sql['index']){
                            $found = true;

                            if ($v['Type'] != 'rt' && in_array($type, array('insert', 'update', 'delete', 'truncate'))){
                                throw new \Exception($this->_p->trans('error.db.operation_'.$type.'_not_supported_on_'.$v['Type'].'_index'));
                            }

                            break;
                        }
                    }

                    if (!$found){
                        throw new \Exception($this->_p->trans('error.db.no_index_found'));
                    }

                } else {

                    throw new \Exception($this->_p->trans('error.db.no_indexes_found'));

                }

            }

            $result = $this->_instance->query($sql['query']);

            // emulate insert_id property of mysqli
            // if we have multiinsert, then we return last item
            if ($type == 'insert' && !$result && isset($sql['primary']))
                return is_array($sql['primary']) ? array_pop($sql['primary']) : $sql['primary'];
            else
                return $result;


        } else {

            return $this->_instance->query($sql);

        }

    }

} 