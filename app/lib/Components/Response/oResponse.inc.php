<?php
/**
 * Response base class
 *
 * Date: 16.02.15
 * Time: 00:04
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 * 
 * Changelog:
 *	1.0	16.02.2015/goshi 
 */

namespace webtFramework\Components\Response;


class oResponse implements iResponse{

    protected $_headers = array();

    protected $_status = 200;

    protected $_content;

    protected $_contentType = CT_HTML;

    protected $_streamType = null;

    public function __construct($content = '', $status = 200, $headers = array(), $contentType = CT_HTML, $streamType = null){

        $this->setContent($content);
        $this->setStatus($status);
        $this->setHeaders($headers);
        $this->setContentType($contentType);
        $this->setStreamType($streamType);

    }

    public function setHeader($header, $value = null){

        if ($header){
            $this->_headers[$header] = $value !== null ? $value : null;
        }

        return $this;

    }

    public function getHeader($header){

        return isset($this->_headers[$header]) ? $this->_headers[$header] : null;

    }

    public function getHeaders(){

        return $this->_headers;

    }

    public function setHeaders($headers = array()){

        if ($headers && is_array($headers)){
            foreach ($headers as $k => $v){
                if (!is_numeric($k)){
                    $this->setHeader($k, $v);
                } else {
                    $this->setHeader($v);
                }
            }
        }

        return $this;

    }

    public function setStatus($status = 200){

        $this->_status = $status;

        return $this;

    }

    public function getStatus(){

        return $this->_status;

    }

    public function setContent($content = ''){

        $this->_content = $content;

        return $this;

    }

    public function getContent(){

        return $this->_content;

    }

    public function setContentType($type){

        $this->_contentType = $type;
        return $this;

    }

    public function getContentType(){

        return $this->_contentType;

    }

    public function setStreamType($type){

        $this->_streamType = $type;
        return $this;

    }

    public function getStreamType(){

        return $this->_streamType;

    }

} 