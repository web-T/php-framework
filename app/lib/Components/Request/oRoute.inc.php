<?php
/**
 * Standart route class
 *
 * Date: 20.11.14
 * Time: 18:36
 * @version 1.0
 * @author goshi
 * @package web-T[Request]
 * 
 * Changelog:
 *	1.0	20.11.2014/goshi 
 */

namespace webtFramework\Components\Request;


class oRoute {

    private $_path = null;

    private $_query = null;

    private $_defaults = null;

    private $_requirements = null;

    private $_options = null;

    private $_host = null;

    private $_schemes = null;

    private $_methods = null;

    /**
     * Constructor.
     *
     * Available options:
     *
     *  * compiler_class: A class name able to compile this route instance (RouteCompiler by default)
     *
     * @param string       $path         The path pattern to match
     * @param array        $query        The query array to match
     * @param array        $defaults     An array of default parameter values, must consists of '_controller',
     *                                   which can be path to controller with scheme
     *                                   'App name from uppercase letter':'Controllername from uppercase letter':'MethodName',
     *                                   OR callable function must get three parameters: oPortal $p, current parameters from query
     *                                   It always can has a '_strict_check' property, which determine compare mode for 'query' route checking
     * @param array        $requirements An array of requirements for parameters (regexes)
     * @param array        $options      An array of options
     * @param string       $host         The host pattern to match
     * @param string|array $schemes      A required URI scheme or an array of restricted schemes
     * @param string|array $methods      A required HTTP method or an array of restricted methods
     * @param string|callable $callback  A callback (closure or anonymous function) for additional checking,
     *                                   it must get three parameters: oPortal $p, Route current route, bool current status
     *
     * @api
     */
    public function __construct($path = null, $query = null, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array(), $callback = null){

        $this->setPath($path);
        $this->setQuery($query);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
        $this->setOptions($options);
        $this->setHost($host);
        $this->setSchemes($host);
        $this->setMethods($host);

    }

    public function setPath($path = null){

        $this->_path = $path;

        return $this;

    }

    public function getPath(){

        return $this->_path;

    }

    public function setQuery($query = null){

        $this->_query = $query;

        return $this;

    }

    public function getQuery(){

        return $this->_query;

    }

    public function setDefaults($defaults = array()){

        $this->_defaults = $defaults;

        return $this;

    }

    public function getDefaults(){
        return $this->_defaults;
    }

    public function setRequirements($requirements = null){

        $this->_requirements = $requirements;

        return $this;

    }

    public function getRequirements(){

        return $this->_requirements;

    }

    public function setOptions($options = null){

        $this->_options = $options;

        return $this;

    }

    public function setHost($host = null){

        $this->_host = $host;

        return $this;

    }

    public function setSchemes($schemes = null){

        $this->_schemes = $schemes;

        return $this;

    }

    public function setMethods($methods = null){

        $this->_methods = $methods;

        return $this;

    }

} 