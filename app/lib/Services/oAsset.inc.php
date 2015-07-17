<?php
/**
 * Asset service control
 *
 * Date: 31.01.15
 * Time: 18:17
 * @version 1.0
 * @author goshi
 * @package web-T[share]
 * 
 * Changelog:
 *	1.0	31.01.2015/goshi 
 */

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;

class oAsset extends oBase{

    /**
     * array of loaded asset controllers
     * @var array
     */
    protected $__instance = array();

    public function __construct(oPortal &$p, $params = array()){

        parent::__construct($p, $params);

    }

    /**
     * build asset
     * @param string $filename filename from assets map
     * @param null|int $version asset version
     * @param array $filters additional filters
     * @return mixed normaly - return string with asset content
     * @throws \Exception
     */
    public function build($filename, $version = null, $filters = array()){

        $ext = $this->_p->filesystem->getFileExtension($filename);

        if ($filename != '' &&
            isset($this->_p->getVar('assets')['map'][$filename])
        ){

            if (!$this->__instance[$ext]){
                if (class_exists('\webtFramework\Components\Asset\oAsset'.ucfirst(strtolower($ext)))){
                    $class = '\webtFramework\Components\Asset\oAsset'.ucfirst(strtolower($ext));
                    $this->__instance[$ext] = new $class($this->_p);
                } else {
                    throw new \Exception('errors.asset.cannot_detect_driver');
                }
            } else {
                $this->__instance[$ext]->cleanup();
            }

            return $this->__instance[$ext]->
                cleanup()->
                addSources($this->_p->getVar('assets')['map'][$filename]['build'])->
                addTarget($filename)->
                addFilters($filters)->
                build($version ? $version : $this->_p->getVar('assets')['map'][$filename]['version']);


        } else {
            throw new \Exception('errors.asset.no_registered_filename');
        }

    }


}