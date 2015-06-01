<?php
/**
 * oWeb driver for CURL
 *
 * Date: 12.01.13
 * Time: 10:10
 * @version 1.0
 * @author goshi
 * @package web-T[share]
 *
 * Changelog:
 *    1.0    12.01.2013/goshi
 */

namespace webtFramework\Modules;

class oWeb_curl extends oWeb_Common{

    private $_headers = array();

    private $_std_headers = array(
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Charset:utf-8,windows-1251;q=0.7,*;q=0.3',
        //'Accept-Encoding:gzip,deflate,sdch',
        'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
        'Cache-Control:max-age=0',
        'Connection:keep-alive',
        'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_4) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11');

    public function open($params = array()){

        parent::open($params);
        $this->_connection = curl_init();

    }

    public function free(){
        if ($this->_connection){
            curl_close($this->_connection);
        }
        $this->_connection = null;
        $this->_headers = array();
    }

    protected function _sendHeader($header){
        $this->_headers[] = $header;
    }

    public function post($url, $post = array(), $params = array()){

        parent::post($url, $post, $params);

        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $post && is_array($post) ? http_build_query($post) : $post
        );

        $defaults = ($defaults + array(CURLOPT_HTTPHEADER => array_merge($this->_std_headers, $this->_headers)));

        curl_setopt_array($this->_connection, ($params + $defaults));

        try {
            if (!$result = curl_exec($this->_connection)){

                throw new \Exception(curl_error($this->_connection));

            }
            $this->_response_headers = curl_getinfo($this->_connection);

        } catch (\Exception $e){

            if (is_object($this->_p->debug)){
                if ($this->_p->getVar('is_debug'))
                    $this->_p->debug->add(get_class()." :: ".$e->getMessage(), array('error' => true));

                $this->_p->debug->log(get_class()." :: ".$e->getMessage(), 'error');
            }
            $result = '';
        }

        // check for debug mode
        if ($params['debug'])
            $this->_p->debug->log(curl_getinfo($this->_connection));

        // check for keep alive mode
        if (!$params['keep_alive'])
            $this->free();

        return $result;

    }


    public function get($url, $params = array()){

        parent::get($url, $params);

        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_ENCODING => true
        );

        $defaults = ($defaults + array(CURLOPT_HTTPHEADER => array_merge($this->_std_headers, $this->_headers)));

        curl_setopt_array($this->_connection, ($params + $defaults));

        try {
            if (!$result = curl_exec($this->_connection)){
                throw new \Exception(curl_error($this->_connection));

            }
            $this->_response_headers = curl_getinfo($this->_connection);

        } catch (\Exception $e){

            if (is_object($this->_p->debug)){
                if ($this->_p->getVar('is_debug'))
                    $this->_p->debug->add(get_class()." :: ".$e->getMessage(), array('error' => true));

                $this->_p->debug->log(get_class()." :: ".$e->getMessage(), 'error');
            }
            $result = '';
        }

        if ($params['debug'])
            $this->_p->debug->log(curl_getinfo($this->_connection));

        // check for keep alive mode
        if (!$params['keep_alive'])
            $this->free();

        return $result;

    }

}
