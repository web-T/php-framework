<?php
/**
 * Filesystem based cache storage
 *
 * Date: 15.02.15
 * Time: 14:46
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Storage;


class oCacheStorageFiles extends oCacheStorageAbstract {

    protected function _checkpath($filename){
        $pathinfo = pathinfo($filename);

        if (!file_exists($pathinfo['dirname']))
            return $this->_p->filesystem->rmkdir($pathinfo['dirname']);
        else
            return true;
    }


    public function exists($path, $prefix = null){

        if (file_exists($path) && is_file($path)){
            return filemtime($path);
        } else {
            return false;
        }

    }

    public function save($path, $data, $prefix = null){

        $this->_checkpath($path);

        if (file_exists($path))
            @unlink($path);

        return $this->_p->filesystem->writeData($path, $this->_p->cache->serialize($data));

    }

    public function get($path, $prefix = null){

        if (is_file($path)){
            return (array)$this->_p->cache->unserialize(file_get_contents($path));
        } else {
            return false;
        }

    }

    public function remove($path, $prefix = null){

        @unlink($path);
        foreach ($this->_p->getLangs() as $k => $v){
            @unlink($path.'_'.$k);
        }

        return true;

    }

    public function removeAll($path, $prefix = null){

        return $this->_p->filesystem->removeFilesFromDir($path, true);

    }

    public function getInfo($path){

        return $this->_p->filesystem->getFilesCount($path, true, true);

    }

} 