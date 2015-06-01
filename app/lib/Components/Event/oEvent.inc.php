<?php
/**
 * Base event class
 *
 * Date: 02.02.15
 * Time: 08:41
 * @version 1.0
 * @author goshi
 * @package web-T[Event]
 * 
 * Changelog:
 *	1.0	02.02.2015/goshi 
 */

namespace webtFramework\Components\Event;


class oEvent implements iEvent{

    /**
     * target class
     * @var mixed
     */
    protected $_target;

    /**
     * event code
     * @var int
     */
    protected $_event;

    /**
     * any data, which you set to an event
     * @var null|mixed
     */
    protected $_context;

    /**
     * flag for bubbling event
     * @var bool
     */
    protected $_bubbling = true;

    public function __construct($event, &$target, $context = null){

        $this->_event = $event;
        $this->_target = &$target;
        $this->_context = $context;

    }

    public function getTarget(){

        return $this->_target;

    }

    public function getEvent(){

        return $this->_event;
    }

    public function getContext(){

        return $this->_context;

    }

    public function setContext($context){

        $this->_context = $context;

        return $this;
    }


    public function getBubbling(){

        return $this->_bubbling;

    }

    public function setBubbling($bubbling){

        $this->_bubbling = boolval($bubbling);

        return $this;
    }

} 