<?php
/**
 * Common class for shared memory drivers
 *
 * Date: 05.03.15
 * Time: 22:18
 * @version 1.0
 * @author goshi
 * @package web-T[SharedMemory]
 * 
 * Changelog:
 *	1.0	05.03.2015/goshi 
 */

namespace webtFramework\Components\SharedMemory;

abstract class SharedMemory_Common{

    /**
     * true if plugin was connected to backend
     *
     * @var bool
     *
     * @access private
     */
    protected $_connected = false;

    /**
     * connection handler
     *
     * @var string
     *
     * @access private
     */
    protected $_h;

    /**
     * Contains internal options
     *
     * @var string
     *
     * @access private
     */
    protected $_options;


    public function __construct($options){

        $this->_options = $options;
        $this->_connected = true;
    }

    /**
     * returns true if plugin was
     * successfully connected to backend
     *
     * @return bool true if connected
     * @access public
     */
    public function isConnected(){
        return $this->_connected;
    }


    /**
     * returns name of current engine
     *
     * @return string name of engine
     * @access public
     */
    public function engineName(){
        return substr(get_class($this), strlen('webtSharedMemory_'));
    }

    /**
     * fill non-set properties by def values
     *
     * @param array $options options array
     * @param array $def hash of pairs keys and default values
     *
     * @return array filled array
     * @access public
     */
    protected function _default($options, $def){
        foreach ($def as $key => $val) {
            if (!isset($options[$key])) {
                $options[$key] = $val;
            }
        }
        return $options;
    }

    /**
     * set value
     * @param $name
     * @param $value
     * @param int $ttl
     */
    public function set($name, $value, $ttl = 0){

        // do nothing

    }

    /**
     * delete value
     * @param $name
     * @param bool $ttl
     */
    public function rm($name, $ttl = false){

        // do nothing

    }

    /**
     * get value
     * @param $name
     * @return mixed
     */
    abstract public function get($name);



}