<?php
/**
 * Shared memory based storage
 *
 * Date: 15.02.15
 * Time: 17:20
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Storage;


use webtFramework\Core\oPortal;

class oCacheStorageShmem  extends oCacheStorageAbstract {


    public function __construct(oPortal &$p){

        if (!($p->shmem && $p->shmem->isConnected())){
            throw new \Exception('error.cache.shmem_not_initialized');
        }

        parent::__construct($p);

    }


    protected function _getServerName(){

        return $this->_p->query->request->getServer('SERVER_NAME') ? $this->_p->query->request->getServer('SERVER_NAME') : $this->_p->getVar('server_name');

    }

    public function exists($path, $prefix = null){

        $mtime = $this->_p->shmem->get($this->_getServerName().$path.'_mtime');

        return $this->_p->shmem->get($this->_getServerName().$path) && $mtime ? $mtime : false;

    }

    public function save($path, $data, $prefix = null){

        $sname = $this->_getServerName();

        $this->_p->shmem->rm($sname.$path);

        $this->_p->shmem->set($sname.$path, $this->_p->cache->serialize($data));

        // setting time for timeout
        $this->_p->shmem->set($sname.$path.'_mtime', time());

        // save in common list
        $list = $this->_p->shmem->get($sname.'_all_list');

        if (!$list)
            $list = array();
        $list[$path] = 1;
        $this->_p->shmem->set($sname.'_all_list', $list);

        return true;

    }

    public function get($path, $prefix = null){

        $h_arr = $this->_p->shmem->get($this->_getServerName().$path);
        if ($h_arr)
            return (array)$this->_p->cache->unserialize($h_arr);
        else
            return false;

    }

    public function remove($path, $prefix = null){

        $this->_p->shmem->rm($this->_getServerName().$path);
        $this->_p->shmem->rm($this->_getServerName().$path.'_mtime');

        foreach ($this->_p->getLangs() as $k => $v){
            $this->_p->shmem->rm($this->_getServerName().$path.'_'.$k);
            $this->_p->shmem->rm($this->_getServerName().$path.'_'.$k.'_mtime');
        }

        $list = $this->_p->shmem->get($this->_getServerName().'_all_list');
        unset($list[$path]);
        $this->_p->shmem->set($this->_getServerName().'_all_list', $list);

        return true;

    }

    public function removeAll($path, $prefix = null){

        $sname = $this->_getServerName();

        $list = $this->_p->shmem->get($sname.'_all_list');
        foreach ($list as $k => $v){
            $this->_p->shmem->rm($sname.$k);
            $this->_p->shmem->rm($sname.$k.'_mtime');
        }
        $list = array();
        $this->_p->shmem->set($sname.'_all_list', $list);

        return true;

    }

    public function getInfo($path){

        $sname = $this->_getServerName();

        $list = $this->_p->shmem->get($sname.'_all_list');

        if (is_array($list)){

            $info['shmem']['count'] = count($list);
            foreach ($list as $k => $v)
                $info['shmem']['size'] += (int)strlen($this->_p->shmem->get($sname.$k));

        } else
            $info['shmem'] = array('count' => '0', 'size' => '0');

        return $info;


    }

} 