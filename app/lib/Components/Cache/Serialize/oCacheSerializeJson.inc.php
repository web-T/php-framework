<?php
/**
 * Json serialize driver
 *
 * Date: 15.02.15
 * Time: 14:23
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Serialize;

class oCacheSerializeJson extends oCacheSerializeAbstract{

    public function serialize($data){
        return json_encode($data);
    }

    public function unserialize($data){
        if (!is_array($data))
            $data = json_decode($data, true);

        return $data;
    }

}