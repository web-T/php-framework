<?php
/**
 * Abstract profiler class
 *
 * Date: 21.02.15
 * Time: 16:19
 * @version 1.0
 * @author goshi
 * @package web-T[Debug]
 * 
 * Changelog:
 *	1.0	21.02.2015/goshi 
 */

namespace webtFramework\Components\Debug\Profiler;

use webtFramework\Core\oPortal;

abstract class oProfilerAbstract implements iProfiler {

    /**
     * @var \webtFramework\Core\oPortal
     */
    protected $_p;

    /**
     * internal profiler data
     * @var mixed
     */
    protected $_data;

    /**
     * flag if debug process always started
     * @var bool
     */
    protected $_is_stared = false;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    abstract public function start($parameters = null);

    abstract public function stop();

    abstract public function getView($content, $data = null);

    abstract public function add($string, $params = array());

    public function getRawData(){

        return $this->_data;

    }

    public function getIsStarted(){

        return $this->_is_stared;

    }

    public function hasErrors(){

        return false;

    }


} 