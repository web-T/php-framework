<?php
/**
 * Abstract class for session storage
 *
 * Date: 15.02.15
 * Time: 14:44
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Storage;

use webtFramework\Core\oPortal;

abstract class oCacheStorageAbstract implements iCacheStorage {

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){
        $this->_p = $p;
    }

    /**
     * find item in cache
     * @param $path
     * @param null $prefix
     * @return int|bool if found return timestamp of saved item
     */
    abstract public function exists($path, $prefix = null);

    /**
     * save item to cache
     * @param $path
     * @param $data
     * @param null $prefix
     * @return mixed
     */
    abstract public function save($path, $data, $prefix = null);

    /**
     * get item from cache
     * @param $path
     * @param null $prefix
     * @return mixed
     */
    abstract public function get($path, $prefix = null);

    /**
     * remove item from cache
     * @param $path
     * @param null $prefix
     * @return mixed
     */
    abstract public function remove($path, $prefix = null);

    /**
     * purge cache
     * @param $path
     * @param null $prefix
     * @return mixed
     */
    abstract public function removeAll($path, $prefix = null);

    /**
     * get cache info
     * @param $path
     * @return array of 'count' and 'size'
     */
    abstract public function getInfo($path);

} 