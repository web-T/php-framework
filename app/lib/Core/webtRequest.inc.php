<?php
/**
 * Core Request object
 *
 * Date: 23.11.14
 * Time: 10:24
 * @version 1.0
 * @author goshi
 * @package web-T[CORE]
 * 
 * Changelog:
 *	1.0	23.11.2014/goshi 
 */

namespace webtFramework\Core;


use webtFramework\Helpers\Files;

class webtRequest {

    /**
     * @var oPortal
     */
    protected $_p;

    /**
     * @var array $_GET parameters
     */
    protected $_get;

    /**
     * @var array $_POST parameters
     */
    protected $_post;

    /**
     * @var array normalized $_FILES array
     */
    protected $_files;

    /**
     * @var array $_SEVER parameters
     */
    protected $_server;

    /**
     * current server port
     * @var int|null
     */
    protected $_port;

    /**
     * @var array request headers
     */
    protected $_headers;

    /**
     * @var string current URI
     */
    protected $_uri;

    /**
     * @var string request method
     */
    protected $_method;

    /**
     * @var string request scheme
     */
    protected $_scheme;

    /**
     * @var string content type of the request
     */
    protected $_content_type;

    /**
     * @var string current domain name
     */
    protected $_domain;

    public function __construct(oPortal &$p){
        $this->_p = &$p;
    }

    /**
     * initialize request instance parameters
     * @param null $uri
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $server
     * @return $this
     */
    public function init($uri = null, $get = array(), $post = array(), $files = array(), $server = array()){

        $this->setUri($uri);
        $this->setGet($get);
        $this->setPost($post);
        $this->setFiles($files);
        $this->setServer($server);

        return $this;

    }

    /**
     * fix post fields on servers with magic_quotes on
     */
    public function fixPostMagic(){

        if (get_magic_quotes_gpc() && !empty($_POST)){

            $f = function($data) use (&$f){
                foreach ($data as $k => $v){
                    if (is_array($v)){
                        $data[$k] = $f($v);
                    } else
                        $data[$k] = stripslashes($v);
                }
                return $data;
            };

            $_POST = $f($_POST);
        }

    }

    /**
     * initialize request object from global request
     * @return $this
     */
    public function createFromGlobals(){

        $this->init($_SERVER['REQUEST_URI'], $_GET, $_POST, $_FILES, $_SERVER);

        return $this;
    }

    /**
     * getter for _get
     *
     * @param string $var
     * @return mixed if $var is empty - return whole $_GET array
     */
    public function getGet($var = null){

        if ($var){
            if (isset($this->_get[$var]))
                return $this->_get[$var];
            else
                return null;
        } else
            return $this->_get;

    }

    /**
     * setter for _GET
     * @param $get
     * @return $this
     */
    public function setGet($get){

        $this->_get = $get;

        return $this;

    }

    /**
     * update GET values
     * @param $key
     * @param null $value
     * @return $this
     */
    public function updateGet($key, $value = null){

        if (is_array($key))
            $this->_get = array_merge($this->_get, $key);
        elseif (isset($key))
            $this->_get[$key] = $value;

        return $this;

    }


    /**
     * getter for _post
     * @param string $var
     * @return mixed if $var is empty - return whole $_GET array
     */
    public function getPost($var = null){

        if ($var){
            if (isset($this->_post[$var]))
                return $this->_post[$var];
            else
                return null;
        } else
            return $this->_post;

    }

    /**
     * setter for post
     * @param $post
     * @return $this
     */
    public function setPost($post){

        $this->_post = $post;

        return $this;

    }

    /**
     * update POST value
     *
     * @param $key
     * @param null $value
     * @return $this
     */
    public function updatePost($key, $value = null){

        if (is_array($key))
            $this->_post = array_merge($this->_post, $key);
        elseif (isset($key))
            $this->_post[$key] = $value;

        return $this;

    }

    /**
     * getter for headers
     * @return array
     */
    public function getHeaders(){

        if (!$this->_headers && $this->_server){
            $this->_headers = $this->_p->query->parseHeaders($this->_server);
        }

        return $this->_headers;

    }

    /**
     * getter for _server
     * @param null $var
     * @return mixed if $var is empty - return whole $_GET array
     */
    public function getServer($var = null){

        if ($var){
            if (isset($this->_server[$var]))
                return $this->_server[$var];
            else
                return null;
        } else
            return $this->_server;
    }

    public function setServer($server){

        // set scheme
        $this->setScheme(isset($server["HTTPS"]) && $server["HTTPS"] == "on" ? 'https' : 'http');

        if (isset($server["HTTP_HOST"]) && strpos($server["HTTP_HOST"], ':') != false){
            $this->setPort(explode(':', $server["HTTP_HOST"])[1]);
        } else {
            $this->setPort(80);
        }

        // set method
        $this->setMethod(isset($server['REQUEST_METHOD']) && $server['REQUEST_METHOD'] ? $server['REQUEST_METHOD'] : 'GET');

        // set domain
        $this->setDomain(isset($server["HTTP_HOST"]) && $server["HTTP_HOST"] != "" ? $server["HTTP_HOST"] : ($server["SERVER_NAME"] ? $server["SERVER_NAME"] : $this->_p->query->get()->getDomain()));

        $ct = isset($server['HTTP_CONTENT_TYPE']) ? $server['HTTP_CONTENT_TYPE'] : $server['CONTENT_TYPE'];

        if (!$ct){
            $ct = $server['HTTP_ACCEPT'];
            if ($ct){
                $ct = explode(',', $ct);
                if (strpos($ct[0], ';') !== false){
                    $ct = explode(';', $ct[0]);
                }
                $ct = trim($ct[0]);
            }
        }

        if ($ct)
            $ct = strtolower($ct);

        switch ($ct){

            case 'application/json':
                $this->setContentType(CT_JSON);
                break;

            case 'application/xml':
                $this->setContentType(CT_XML);
                break;

            case 'application/pdf':
                $this->setContentType(CT_PDF);
                break;

            default:
                $this->setContentType(CT_HTML);
                break;
        }

        $this->_server = $server;

        return $this;

    }

    /**
     * update SERVER values
     *
     * @param $key
     * @param null $value
     * @return $this
     */
    public function updateServer($key, $value = null){

        if (is_array($key))
            $this->setServer(array_merge($this->_server, $key));
        elseif (isset($key))
            $this->setServer(array_merge($this->_server, array($key => $value)));

        return $this;

    }


    public function setFiles($files){

        // always normalize files
        $this->_files = Files::normalizeFilesArray($files);

        return $this;

    }

    public function getFiles(){

        return $this->_files;

    }


    /**
     * getter for _uri
     * @return string
     */
    public function getUri(){
        return $this->_uri;
    }

    public function setUri($uri){
        $this->_uri = $uri;

        return $this;
    }

    /**
     * return cleared URI without language
     * @return mixed|string
     */
    public function getClearedUri(){

        if ($this->_p->getLangNick() && preg_match('#^/'.$this->_p->getLangNick().'/.*$#', $this->getUri())){
            return preg_replace('#^/'.$this->_p->getLangNick().'(/.*)$#', '$1', $this->getUri());
        } else {
            return $this->getUri();
        }

    }

    /**
     * get current request scheme
     * @return string
     */
    public function getScheme(){

        return $this->_scheme;
    }

    public function setScheme($scheme){

        $this->_scheme = strtolower($scheme);

        return $this;

    }

    /**
     * get current port
     * @return int|null
     */
    public function getPort(){

        return $this->_port;
    }

    /**
     * set current server port
     * @param int $port
     * @return $this
     */
    public function setPort($port = 80){

        $this->_port = intval($port);

        return $this;

    }


    /**
     * get current request method ('GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS')
     * @return string
     */
    public function getMethod(){

        return $this->_method;

    }

    public function setMethod($method){

        $this->_method = strtoupper($method);

        return $this;
    }

    public function getContentType(){

        return $this->_content_type;

    }

    public function setContentType($content_type){

        $this->_content_type = $content_type;

        return $this;

    }

    public function getDomain(){

        return $this->_domain;

    }

    public function setDomain($domain){

        $this->_domain = $domain;

        return $this;

    }

    /**
     * method return variable from get request
     * @param bool $key
     * @return mixed if $var is empty - return whole $_GET array
     */
    public function get($key = false){

        if ($key && $this->_get[$key]){
            return $this->_get[$key];
        } elseif ($key && is_array($this->_post) && isset($this->_post[$key])){
            return $this->_post[$key];
        } else
            return null;

    }


} 
