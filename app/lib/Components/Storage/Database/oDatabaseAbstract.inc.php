<?php
/**
 * Abstract class of the iDatabase interface
 *
 * Date: 16.01.15
 * Time: 01:08
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	1.0	16.01.2015/goshi 
 */

namespace webtFramework\Components\Storage\Database;

use webtFramework\Core\oPortal;

abstract class oDatabaseAbstract implements iDatabase {

    /**
     * @var oPortal
     */
    protected $_p;

    /**
     * current storage settings
     * @var array
     */
    protected $_settings;

    /**
     * concrete instance of the database
     * @var \StdClass
     */
    protected $_instance;


    /**
     * @param oPortal $p
     * @param array $settings concrete storage settings from config file
     */
    public function __construct(oPortal &$p, $settings = array()){

        $this->_p = &$p;
        $this->_settings = $settings;

    }

    /**
     * initialize instance and connection
     * @return mixed
     */
    abstract public function init();

    /**
     * closing connection and destroy instance
     * @return mixed
     */
    abstract public function close();

    /**
     * return last error from instance
     * @return mixed
     */
    abstract public function getLastError();

    /**
     * magic method for calling base functions from the concrete instance
     * @param $name
     * @param $args
     * @return mixed|null
     */
    public function __call($name, $args){

        if (method_exists($this->_instance, $name)) {
            return call_user_func_array(array($this->_instance, $name), $args);
        } else
            return null;

    }


} 