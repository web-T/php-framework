<?php
/**
 * ...
 *
 * Date: 15.02.15
 * Time: 13:55
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Serialize;


interface iCacheSerialize {

    public function serialize($data);

    public function unserialize($data);

} 