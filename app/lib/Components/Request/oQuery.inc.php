<?php
/**
 * Base class for queries
 *
 * Date: 24.02.15
 * Time: 23:03
 * @version 1.0
 * @author goshi
 * @package web-T[Request]
 * 
 * Changelog:
 *	1.0	24.02.2015/goshi 
 */

namespace webtFramework\Components\Request;


class oQuery {

    /**
     * parsed query
     * @var array|null
     */
    protected $_query = array();

    /**
     * queries subdomain
     * @var string|null
     */
    protected $_subdomain;

    /**
     * queries domain
     * @var string|null
     */
    protected $_domain;

    /**
     * query's content type
     * possible content types you can see at common.conf.php ['doc_types'] var
     * @var int|null
     */
    protected $_content_type;

    public function __construct($query = null, $content_type = null, $domain = null, $subdomain = null){

        if ($query){
            $this->_query = $query;
        }

        if ($content_type)
            $this->_content_type = $content_type;

        if ($domain)
            $this->_domain = $domain;

        if ($subdomain)
            $this->_subdomain = $subdomain;

    }

    /**
     * get current query
     * @param string|null $key
     * @return array|mixed|null
     */
    public function get($key = null){

        if ($key !== null){

            // emulate page item in query
            if ($key == 'page' && !isset($this->_query[$key]) && !empty($this->_query)){
                reset($this->_query);
                return key($this->_query);
            } elseif (isset($this->_query[$key]))
                return $this->_query[$key];
            else
                return null;
        } else
            return $this->_query;

    }

    /**
     * determines, if current query consists of certain key/keys
     * @param mixed $key
     * @return bool
     */
    public function has($key){

        if ($key){

            if (is_array($key)){
                return count(array_intersect($key, array_keys($this->_query))) == count($key);
            } else {
                return isset($this->_query[$key]);
            }

        } else
            return false;

    }

    /**
     * add key/keys to the query
     *
     * @param mixed $key
     * @param $value
     * @return $this
     */
    public function add($key, $value){

        if (!$key)
            return $this;

        if (is_array($key)){
            $this->_query = array_merge($this->_query, $key);
        } else  {
            $this->_query[$key]= $value;
        }

        return $this;

    }

    public function set($query){

        if (is_array($query))
            $this->_query = $query;

        return $this;
    }

    /**
     * remove key/keys from query
     * @param mixed $key
     * @return $this
     */
    public function remove($key){

        if ($key){

            if (is_array($key)){
                foreach ($key as $k){
                    unset($this->_query[$k]);
                }
            } else {
                unset($this->_query[$key]);
            }

        }

        return $this;

    }

    public function getContentType(){

        return $this->_content_type;

    }

    public function setContentType($type){

        $this->_content_type = $type;

        return $this;

    }

    /**
     * set current domain name
     * @param $domain
     * @return $this
     */
    public function setDomain($domain){

        $this->_domain = $domain;

        return $this;

    }

    /**
     * get current domain name
     * @return null
     */
    public function getDomain(){

        return $this->_domain;

    }

    /**
     * set current subdomain name
     * @param $subdomain
     * @return $this
     */
    public function setSubdomain($subdomain){

        $this->_subdomain = $subdomain;

        return $this;

    }

    /**
     * get current subdomain name
     * @return null
     */
    public function getSubdomain(){

        return $this->_subdomain;

    }

    /**
     * get compiled server name
     * @return string
     */
    public function getServerName(){

        return ($this->_subdomain !== null && $this->_subdomain !== '' ? $this->_subdomain.'.' : '').$this->_domain;

    }


} 