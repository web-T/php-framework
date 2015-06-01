<?php
/**
 * ...
 *
 * Date: 03.01.15
 * Time: 14:47
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	03.01.2015/goshi 
 */

namespace webtFramework\Components\Console;

use webtFramework\Core\oPortal;

class oConsoleOutput implements iConsoleOutput{

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){

        $this->_p = &$p;

    }

    public function send($data){

        if ($this->_p->getVar('STREAM_TYPE') != ST_CONSOLE)
            $this->_p->setVar('STREAM_TYPE', ST_CONSOLE);

        $this->_p->response->send($data);

    }

} 