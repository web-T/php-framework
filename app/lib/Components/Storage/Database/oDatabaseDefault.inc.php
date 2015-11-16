<?php
/**
 * Default database adapter for DBSimple library
 *
 * Date: 16.01.15
 * Time: 01:07
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	1.0	16.01.2015/goshi 
 */

namespace webtFramework\Components\Storage\Database;

use webtFramework\Core\oPortal;

class oDatabaseDefault extends oDatabaseAbstract {

    /**
     * @var \DBSimple_Generic|\DBSimple_Database
     */
    protected $_instance;

    /**
     * @var \ReflectionProperty
     */
    protected $_reflectionProperty;

    public function __construct(oPortal &$p, $settings = array()){

        parent::__construct($p, $settings);

        if (!defined("PATH_SEPARATOR"))
            define("PATH_SEPARATOR", getenv("COMSPEC")? ";" : ":");
        ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));


    }

    public function getConnection(){
        dump($this->_reflectionProperty);
        return $this->_reflectionProperty;
    }

    public function init(){

        switch ($this->_settings['db_type']){

            case 'mypdo':

                $reflectionClass = new \ReflectionClass('\DbSimple_Mypdo');
                $this->_reflectionProperty = $reflectionClass->getProperty('link');

                //  lets us invoke private and protected methods
                $this->_reflectionProperty->setAccessible(true);

                break;

            case 'mysqli':

                $reflectionClass = new \ReflectionClass('\DbSimple_Mysqli');
                $this->_reflectionProperty = $reflectionClass->getProperty('link');

                //  lets us invoke private and protected methods
                $this->_reflectionProperty->setAccessible(true);


                break;

            case 'mysql':
                $reflectionClass = new \ReflectionClass('\DbSimple_Mysql');
                $this->_reflectionProperty = $reflectionClass->getProperty('link');

                //  lets us invoke private and protected methods
                $this->_reflectionProperty->setAccessible(true);

                break;

            case 'postgresql':
                $reflectionClass = new \ReflectionClass('\DbSimple_Postgresql');
                $this->_reflectionProperty = $reflectionClass->getProperty('link');

                //  lets us invoke private and protected methods
                $this->_reflectionProperty->setAccessible(true);

                break;

        }

        $this->_instance = \DbSimple_Generic::connect($this->_settings['db_type']."://".$this->_settings['db_user'].":".$this->_settings['db_pass']."@".$this->_settings['db_host']."/".$this->_settings['db_name']);

        if ($this->_instance && !$this->_instance->errmsg){

            if (preg_match('/^my.*/is', $this->_settings['db_type']))
                $this->_instance->query('SET NAMES utf8');

            $this->_instance->setErrorHandler(array($this->_p->debug, 'logDBError'));
            if ($this->_p->getVar('is_debug') == 1 || $this->_p->getVar('is_dev_env') == 1)
                $this->_instance->setLogger(array($this->_p->debug, 'addSQL'));

        } else {
            throw new \Exception($this->_p->trans('error.db.no_connection'));
        }

    }

    public function close(){

        if ($this->_instance){
            if (method_exists($this->_instance, 'close'))
                $this->_instance->close();

            $this->_instance = null;
            $this->_reflectionProperty = null;
        }

    }

    public function getLastError(){

        if ($this->_instance){
            return $this->_instance->error;
        } else {
            return null;
        }

    }

    public function query($query){

        return $this->_instance->query($query);

    }

    public function select($query){

        return $this->_instance->select($query);

    }

    public function selectCell($query){

        return $this->_instance->selectCell($query);

    }

    public function selectRow($query) {

        return $this->_instance->selectRow($query);

    }

    public function selectCol($query){

        return $this->_instance->selectCol($query);

    }

    public function escape($data){

        return $this->_instance->escape($data);

    }

    /**
     * override standart escape for drivers
     * @param $data
     * @return mixed|string
     * @throws \Exception
     */
    public function cleanupEscape($data){

        // because by default this method is not accessible
        switch ($this->_settings['db_type']){

            case 'mypdo':

                return preg_replace("/^'(.*)'$/is", '$1', $this->_reflectionProperty->getValue($this->_instance)->quote($data));
                break;

            case 'mysqli':

                return $this->_reflectionProperty->getValue($this->_instance)->escape_string($data);

                break;

            case 'mysql':
                return mysql_real_escape_string($data, $this->_reflectionProperty->getValue($this->_instance));
                break;

            case 'postgresql':
                return pg_escape_string($this->_reflectionProperty->getValue($this->_instance), $data);
                break;

            default:
                throw new \Exception('error.db.no_cleanupescape_method_found');
                break;

        }

    }

    public function transaction($mode = null){

        return $this->_instance->transaction($mode);

    }

    public function commit(){

        return $this->_instance->commit();

    }

    public function rollback(){

        return $this->_instance->rollback();

    }



} 