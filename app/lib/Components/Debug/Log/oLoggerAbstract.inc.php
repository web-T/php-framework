<?php
/**
 * ...
 *
 * Date: 31.05.15
 * Time: 14:27
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	31.05.2015/goshi 
 */

namespace webtFramework\Components\Debug\Log;

use Psr\Log\AbstractLogger;
use webtFramework\Core\oPortal;

abstract class oLoggerAbstract extends AbstractLogger{

    /**
     * @var \webtFramework\Core\oPortal
     */
    protected $_p;

    /**
     * current output channel
     * @var null|string
     */
    protected $_channel;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    public function setChannel($channel){

        $this->_channel = $channel;

        return $this;

    }

    public function getChannel(){

        return $this->_channel;
    }

    /**
     * Interpolates context values into the message placeholders
     * @param $message
     * @param array $context
     * @return string
     */
    public function interpolate($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    abstract public function log($level, $message, array $context = array());

} 