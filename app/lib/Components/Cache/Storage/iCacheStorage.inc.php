<?php
/**
 * Interface for cache storage
 *
 * Date: 15.02.15
 * Time: 14:31
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Storage;


interface iCacheStorage {

    public function exists($path, $prefix = null);

    public function save($path, $data, $prefix = null);

    public function get($path, $prefix = null);

    public function remove($path, $prefix = null);

    public function removeAll($path, $prefix = null);

    public function getInfo($path);

} 