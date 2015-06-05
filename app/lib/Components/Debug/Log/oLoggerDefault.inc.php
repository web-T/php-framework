<?php
/**
 * ...
 *
 * Date: 31.05.15
 * Time: 14:15
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	31.05.2015/goshi 
 */

namespace webtFramework\Components\Debug\Log;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

class oLoggerDefault extends oLoggerAbstract{

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = array()){

        if (!$this->_channel)
            $this->_channel = key($this->_p->getVar('debugger')['logs']);

        // check for level
        if (!$level){
            $level = LogLevel::INFO;
        }

        $reflect = new \ReflectionClass(get_class(new LogLevel));
        $const = array_flip($reflect->getConstants());
        if (!isset($const[$level])){
            throw new InvalidArgumentException('Unfortunately, log level '.$level.' not defined');
        }

        $file_log = isset($this->_p->getVar('debugger')['logs'][$this->_channel]) ? $this->_p->getVar('debugger')['logs'][$this->_channel] : $this->_p->getVar('debugger')['logs']['app'];

        if (!file_exists($this->_p->getVar('BASE_APP_DIR').$file_log)){
            $this->_p->filesystem->rmkdir($this->_p->getVar('BASE_APP_DIR').dirname($file_log), PERM_DIRS);
            $this->_p->filesystem->writeData($this->_p->getVar('BASE_APP_DIR').$file_log, '', 'w', PERM_FILES);
        }

        // apply context and print value
        dump_file($level." :: ".(!is_array($message) ? $this->interpolate($message, $context) : 'Array'), false, $this->_p->getVar('BASE_APP_DIR').$file_log);
        if (is_array($message))
            dump_file($this->interpolate($message, $context), false, $this->_p->getVar('BASE_APP_DIR').$file_log);

        return $this;

    }

} 