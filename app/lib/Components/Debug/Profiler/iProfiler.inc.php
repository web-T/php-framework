<?php
/**
 * Interface for profiler drivers
 *
 * Date: 21.02.15
 * Time: 15:40
 * @version 1.0
 * @author goshi
 * @package web-T[Debug]
 * 
 * Changelog:
 *	1.0	21.02.2015/goshi 
 */

namespace webtFramework\Components\Debug\Profiler;


interface iProfiler {

    public function start($parameters = null);

    public function stop();

    public function getView($content, $data = null);

    public function add($string, $params = array());

} 