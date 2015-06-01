<?php
/**
 * ...
 *
 * Date: 03.01.15
 * Time: 10:46
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	03.01.2015/goshi 
 */

namespace webtFramework\Components\Console;


class oConsoleInput implements iConsoleInput{

    protected $_options = array();

    protected $_arguments = array();

    public function __construct($options, $arguments){

        $this->_options = $options;
        $this->_arguments = $arguments;

    }

    public function getOption($option){

        if ($option && isset($this->_options[$option])){

            return $this->_options[$option];

        } else {

            return null;

        }

    }

    public function setOption($option, $value){

        if ($option){

            $this->_options[$option] = $value;

        }

        return $this;

    }

    public function getArgs(){

        return $this->_arguments;

    }

    public function getArg($arg){

        if ($arg && isset($this->_arguments[$arg])){

            return $this->_arguments[$arg];

        } else {

            return null;

        }

    }

    public function setArg($arg, $value){

        if ($arg){

            $this->_arguments[$arg] = $value;

        }

        return $this;

    }

} 