<?php
/**
 * Controller for all web interfaces
 *
 * Date: 11.01.13
 * Time: 19:01
 * @version 1.0
 * @author goshi
 * @package web-T[share]
 *
 * Changelog:
 *    1.0    11.01.2013/goshi
 */

namespace webtFramework\Modules;

use webtFramework\Interfaces\oModule;
use webtFramework\Core\oPortal;

abstract class oWeb_Common{

    /**
     * current connection resource
     * @var mixed
     */
    protected $_connection = null;

    /**
     * array of response headers
     * @var array
     */
    protected $_response_headers = array();

    /**
     * @var null|oPortal
     */
    protected $_p = null;

    public function __construct(oPortal &$p, $params = array()){

        $this->_p = $p;
        //return $this;
    }

    public function open($params = array()){
        if ($this->_connection){
            $this->free();
        }
    }

    abstract public function free();

    abstract protected function _sendHeader($header);

    public function getInstance(){
        return $this->_connection;
    }

    public function headers($headers){

        if ($headers && is_array($headers) && !empty($headers)){
            foreach($headers as $value){
                $this->_sendHeader($value);
            }
        }
    }

    /**
     * method return response headers
     * @return array
     */
    public function getResponseHeaders(){
        return $this->_response_headers;
    }

    /**
     * @param $url
     * @param array $post
     * @param array $params
     * @return string post result of concrete driver
     */
    public function post($url, $post = array(), $params = array()){

        if (!$this->_connection){
            $this->open($params);
        }
        if (isset($params['headers'])){
            $this->headers($params['headers']);
        }

        $this->_response_headers = array();

        return null;

    }


    public function get($url, $params = array()){

        if (!$this->_connection){
            $this->open($params);
        }
        if (isset($params['headers'])){
            $this->headers($params['headers']);
        }

        $this->_response_headers = array();

        return true;

    }

}



class oWeb extends oModule {

    // constructor
    public function __construct(oPortal &$p, $params = false){

        parent::__construct($p, $params);

        return $this;

    }


    /**
     * initialize stream object for http response
     * @param array $params
     * @return oWeb_Common
     * @throws \Exception
     */
    public function init($params = array()){

        if (!$params['driver']){
            $params['driver'] = 'curl';
        }

        $params['driver'] = strtolower($params['driver']);
        $driver_name = $this->extractClassname().'_'.$params['driver'];
        try {
            if (file_exists($this->_ROOT_DIR.'/'.$this->_p->getVar('drivers_dir').escapeshellcmd($driver_name).'.driver.php')){
                require_once($this->_ROOT_DIR.'/'.$this->_p->getVar('drivers_dir').escapeshellcmd($driver_name).'.driver.php');
                //dump(array($this->_ROOT_DIR.'/'.$this->_p->getVar('drivers_dir').escapeshellcmd($driver_name).'.driver.php', $driver_name, class_exists('\\'.__NAMESPACE__.'\\'.$driver_name), '\\'.__NAMESPACE__.'\\'.$driver_name));
                $driver_name = '\\'.__NAMESPACE__.'\\'.$driver_name;
                $ref = new $driver_name($this->_p, array_merge($params, array('driver' => $params['driver'])));
                return $ref;
            } else
                throw new \Exception(get_class().' :: '.$this->_p->trans('errors.drivers.no_file_driver'));

        } catch (\Exception $e){

            if (is_object($this->_p->debug)){
                if ($this->_p->getVar('is_debug'))
                    $this->_p->debug->add($e->getMessage(), array('error' => true));

                $this->_p->debug->log($e->getMessage(), 'error');
            }

        }

        return null;
    }

}
