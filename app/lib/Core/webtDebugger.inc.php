<?php

/**
* Debugger base class
* @version 2.0
* @author goshi
* @package web-T[CORE]
*/

namespace webtFramework\Core;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use webtFramework\Components\Debug\Log\oLoggerDefault;

/**
* @package web-T[CORE]
*/
class webtDebugger {

    use LoggerAwareTrait;

    /**
     * @var oPortal
     */
    private $_p;

    /**
     * array of profiler instances
     * @var array
     */
    private $_profilerInstances = array();

	
	public function __construct(oPortal &$p){
		
		$this->_p = $p;
		
	}
		

    /**
     * Add text string to debug level + memory usage
     *
     * @param string $string what to add
     * @param array $params
     * @return $this;
     */
    public function add($string, $params = array()){

        $this->getProfilerInstance()->add($string, $params);

        return $this;

	}

    /**
     * Logging SQL errors
     * @param string $message what to add
     * @param string $info additional information
     * @return $this;
     */
    public function logDBError($message, $info){

		$this->log(array($message, $info, $_SERVER ? $_SERVER['REQUEST_URI']: 'CGI'), 'error');

        return $this;

	}

    /**
     * Add SQL string to debug level
     * @param \DbSimple_Mysql|\DBSimple_Generic $db
     * @param string $message
     * @return $this;
     */
    public function addSQL(&$db, $message)
	{

        $profiler = $this->getProfilerInstance();

        if (method_exists($profiler, 'addSQL')){
            $profiler->addSQL($db, $message);
        }

        return $this;

	}


	/**
	 * login vars to special logs
	 *
	 * @param $what
	 * @param string $type log channel
     * @param string $level log level
     * @param array|null $context message context
     * @return $this;
	 */
	public function log($what, $type = 'app', $level = LogLevel::INFO, $context = array()){

        if (!$this->logger)
            $this->logger = new oLoggerDefault($this->_p);

		$this->logger->setChannel($type)->log($level, $what, $context);

        return $this;

	}

    /**
     * detect if debug log has errors
     * @return bool
     */
    public function hasErrors(){

        return $this->getProfilerInstance()->hasErrors();

    }


	/**
	* print debug information
	*/
	public function getProfilerView($content){

        return $this->getProfilerInstance()->getView($content);

	}


    /**
     * get selected profiler instance
     * @param null $driver
     * @return \webtFramework\Components\Debug\Profiler\oProfilerAbstract
     */
    public function getProfilerInstance($driver = null){

        if (!$driver)
            $driver = $this->_p->getVar('debugger')['profiler'];

        if (isset($this->_profilerInstances[$driver]))
            return $this->_profilerInstances[$driver];

        // try to load profiler
        $class = 'webtFramework\Components\Debug\Profiler\oProfiler'.ucfirst($driver);
        $this->_profilerInstances[$driver] = new $class($this->_p);

        return $this->_profilerInstances[$driver];

    }


}
