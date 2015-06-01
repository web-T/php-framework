<?php
/**
 * Abstract serializer class
 *
 * Date: 15.02.15
 * Time: 14:02
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Serialize;

use webtFramework\Core\oPortal;

abstract class oCacheSerializeAbstract implements iCacheSerialize {

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){
        $this->_p = $p;
    }

    abstract function serialize($data);

    abstract function unserialize($data);

} 