<?php
/**
 * Base inteface for all web-controllers
 *
 * Date: 15.07.15
 * Time: 18:19
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	15.07.2015/goshi 
 */

namespace webtFramework\Interfaces;


interface iController {

    public function parseQuery(&$params);

} 