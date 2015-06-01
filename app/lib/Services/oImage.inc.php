<?php
/**
 * Image generation control
 *
 * Date: 11.01.15
 * Time: 19:38
 * @version 1.0
 * @author goshi
 * @package web-T[CMS]
 * 
 * Changelog:
 *	1.0	11.01.2015/goshi 
 */

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;

class oImage extends oBase{

    /**
     * @var null|array
     */
    protected $__instance = array();

    public function __construct(oPortal &$p, $params = array()){

        parent::__construct($p, $params);

    }

    protected function _initObject(){

        if ($this->_p->getVar('image') && strtolower($this->_p->getVar('image')['type']) != 'auto' &&
            class_exists('\webtFramework\Components\Image\oImageManager'.ucfirst(strtolower($this->_p->getVar('image')['type'])))){

            $class = '\webtFramework\Components\Image\oImageManager'.ucfirst(strtolower($this->_p->getVar('image')['type']));

            $this->__instance[$this->_p->getVar('image')['type']] = new $class($this->_p);

        } else {

            // fallback to auto mode
            // there we can call best-to-worst
            $drivers = array('imagick', 'imagickshell', 'gd');

            $class = null;

            foreach ($drivers as $driver){

                $tmp_class = '\webtFramework\Components\Image\oImageManager'.ucfirst($driver);

                if (class_exists($tmp_class) && $tmp_class::isExists($this->_p)){
                    $class = $tmp_class;
                    break;
                }

            }

            if ($class){

                $this->__instance['auto'] = new $class($this->_p);

            } else {
                throw new \Exception('error.image.cannot_detect_driver');
            }


        }

    }

    /**
     * magic method
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {

        if (!$this->__instance[$this->_p->getVar('image')['type']]){

            $this->_initObject();

        }

        return call_user_func_array(array($this->__instance[$this->_p->getVar('image')['type']], $name), $arguments);
    }


} 