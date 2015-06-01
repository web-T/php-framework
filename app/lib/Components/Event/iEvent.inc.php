<?php
/**
 * Event's dispatcher interface
 *
 * Date: 02.02.15
 * Time: 08:38
 * @version 1.0
 * @author goshi
 * @package web-T[Event]
 * 
 * Changelog:
 *	1.0	02.02.2015/goshi 
 */

namespace webtFramework\Components\Event;


interface iEvent {

    public function getTarget();

    public function getEvent();

    public function getBubbling();

    public function setBubbling($bubbling);

    public function getContext();

    public function setContext($context);

} 