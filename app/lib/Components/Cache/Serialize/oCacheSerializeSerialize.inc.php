<?php
/**
 * Standart PHP serializer
 *
 * Date: 15.02.15
 * Time: 14:17
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Serialize;

class oCacheSerializeSerialize extends oCacheSerializeAbstract{

    public function serialize($data){
        return serialize($data);
    }

    public function unserialize($data){
        if (!is_array($data))
            $data = unserialize($data);

        return $data;
    }

} 