<?php

/**
* Base class of events
* @version 2.3
* @author goshi
* @package web-T[CORE]
*
* Changelog:
*	2.3		15.03.11/goshi	now you can pass to the email event personal title
*	2.2		15.03.11/goshi	add codepage protection for mb_strlen
*	2.1		18.11.10/goshi	update for php 5.3
*	2.0		29.08.10/goshi	add priority field, remove unnecessary code
*	1.95	27.08.10/goshi	add special field for admin user
*	1.9		20.08.10/goshi	add getLastEvents method
*	1.82	04.08.10/goshi	fix bug when event name and message both presents
*	1.81	24.05.10/goshi	add server_name for emails subject (for multisites administrators)
*	1.8		13.05.10/goshi	add add_message for events 
*	1.71	05.12.09/goshi	get_event now have another structure type
*	1.7	23.10.09/goshi	update sys_log table
*	1.6	22.09.09/goshi	add 'waiting' like default event, add save event state
*	1.5	16.09.09/goshi	fix bug with auto prepare variables and add max email subject length
*	1.4	18.08.09/goshi	add autoprepare variables in events buy list
*	1.3	07.07.09/goshi	add multilanguage support
*	1.2	06.04.09/goshi	add subscribe to events 
*	1.1	06.04.09/goshi	multiple events in one commit 
*	1.0	29.03.09/goshi	added it
 *
 * TODO: remove it to webT::CMS
*/

namespace webtFramework\Core;

use webtFramework\Components\Event\oEvent;

/**
* @package web-T[CORE]
*/
class webtEvent{

    /**
     * @var oPortal
     */
    protected $_p = null;

    /**
     * events queues
     * @var array
     */
    protected $__events = array();


    public function __construct(oPortal &$p){

		$this->_p = $p;
	}

    /**
     * method adds event listener to the event
     *
     * @param int $event
     * @param string|array $function can be method of array or anonymous function
     * @param array $params
     * @return bool|int
     */
    public function addEventListener($event, $function, &$params = array()){

        if (!isset($this->__events[$event])){
            $this->__events[$event] = array();
        }

        $p = &$this->_p;

        $this->__events[$event][] = function (oEvent $event) use($p, $function, &$params){

            if (is_array($function)){
                $function[0]->$function[1]($p, $event);
            } else {
                $function($p, $event);
            }
        };

        return $this;

    }

    /**
     * method removes special event function from events queue
     *
     * @param string $event
     * @param null|int $queue_id
     * @return bool
     */
    public function removeEventListener($event, $queue_id = null){

        if (isset($this->__events[$event])){
            if ($queue_id)
                $this->__events[$event][$queue_id] = null;
            else
                $this->__events[$event] = array();

        }

        return $this;
    }

    /**
     * dispatching selected event
     * @param oEvent $event
     * @return $this
     */
    public function dispatch(oEvent $event){

        if (isset($this->__events[$event->getEvent()])){

            $event->setBubbling(true);

            foreach ($this->__events[$event->getEvent()] as $function){

                $function($event);

                if (!$event->getBubbling()) break;
            }

        }

        return $this;

    }


}
