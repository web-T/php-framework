<?php
/**
 * ...
 *
 * Date: 24.12.14
 * Time: 23:46
 * @version 1.0
 * @author goshi
 * @package web-T[Components]
 * 
 * Changelog:
 *	1.0	24.12.2014/goshi 
 */

namespace webtFramework\Components\Console;

Interface iConsoleInput {

    public function getOption($option);

    public function setOption($option, $value);

    public function getArgs();

    public function getArg($arg);

    public function setArg($arg, $value);

}
