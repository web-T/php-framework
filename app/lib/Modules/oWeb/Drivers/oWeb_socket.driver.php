<?php
/**
 * oWeb driver for socket
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

class oWeb_socket extends oWeb_Common{

    private $_headers = array();

    private $_std_headers = array(
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Charset:utf-8,windows-1251;q=0.7,*;q=0.3',
        //'Accept-Encoding:gzip,deflate,sdch',
        'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
        'Cache-Control:max-age=0',
        'Connection:keep-alive',
        'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_4) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11');

    private $_std_port = 80;

    private $_std_timeout = 30;

    private $_hend = "\r\n";

    protected $_struct = null;

    public function open($params = array()){

        parent::open($params);

        if (is_array($params) && $params['url']){
            $this->_struct = parse_url($params['url']);
        } elseif ($params){
            $this->_struct = parse_url($params);
        } else
            $this->_struct = null;

        if ($this->_struct){
            try {
                if (!$this->_connection = @fsockopen($this->_struct['host'], $this->_struct['port'] ? $this->_struct['port'] : ($params['port'] ? $params['port'] : $this->_std_port), $err_number, $err_string, ($params['timeout'] ? $params['timeout'] : $this->_std_timeout)))
                    throw new \Exception($err_number.' :: '.$err_string);

            } catch (\Exception $e){

                if (is_object($this->_p->debug)){
                    if ($this->_p->getVar('is_debug'))
                        $this->_p->debug->add($e->getMessage(), array('error' => true));

                    $this->_p->debug->log($e->getMessage(), 'error');
                }
            }
        }

    }

    public function free(){
        if ($this->_connection){
            fclose($this->_connection);
        }
        $this->_headers = array();
        $this->_struct = null;
    }

    protected function _sendHeader($header){
        $this->_headers[] = $header;
    }

    /**
     * Accepts provided http content, checks for a valid http response,
     * unchunks if needed, returns http content without headers
     * @param null $content
     * @return bool|string
     */
    protected function _parseResponse($content = null) {
        if (empty($content)) {
            return false;
        }
        // split into array, headers and content.
        $hunks = explode($this->_hend.$this->_hend, trim($content));
        if (!is_array($hunks) or count($hunks) < 2) {
            return false;
        }
        $header  = $hunks[count($hunks) - 2];
        $body    = $hunks[count($hunks) - 1];
        $headers = explode("\n", $header);
        unset($hunks);
        unset($header);
        if (!$this->_validateResponse($headers)) {
            return false;
        }
        if (in_array('Transfer-Coding: chunked', $headers)) {
            return trim($this->_unchunkResponse($body));
        } else {
            return trim($body);
        }
    }

    /**
     * Validate http responses by checking header
     * @param null $headers
     * @return bool
     */
    protected function _validateResponse($headers = null) {
        if (!is_array($headers) or count($headers) < 1) { return false; }
        switch(trim(strtolower($headers[0]))) {
            case 'http/1.0 100 ok':
            case 'http/1.0 200 ok':
            case 'http/1.1 100 ok':
            case 'http/1.1 200 ok':
                return true;
                break;
        }
        return false;
    }

    /**
     * Unchunk http content
     * @param null $str
     * @return bool|null|string
     */
    protected function _unchunkResponse($str = null) {
        if (!is_string($str) or strlen($str) < 1) { return false; }
        $eol = "\r\n";
        $add = strlen($eol);
        $tmp = $str;
        $str = '';
        do {
            $tmp = ltrim($tmp);
            $pos = strpos($tmp, $eol);
            if ($pos === false) { return false; }
            $len = hexdec(substr($tmp,0,$pos));
            if (!is_numeric($len) or $len < 0) {
                return false;
            }
            $str .= substr($tmp, ($pos + $add), $len);
            $tmp  = substr($tmp, ($len + $pos + $add));
            $check = trim($tmp);
        } while(!empty($check));
        unset($tmp);
        return $str;
    }

    public function post($url, $post = array(), $params = array()){

        parent::post($url, $post, $params);

        if (!$this->_connection)
            return false;

        if (!is_array($post) || empty($post))
            return false;

        if (!$this->_struct)
            $this->_struct = parse_url($url);

        $result = '';
        try {

            fputs($this->_connection, "POST ".$this->_struct['path'].($this->_struct['query'] ? '?'.$this->_struct['query'] : '').($this->_struct['fragment'] ? '#'.$this->_struct['fragment'] : '')." ".strtoupper($this->_struct['scheme'])."/1.1".$this->_hend);
            fputs($this->_connection, "HOST: ".$this->_struct['host'].$this->_hend);
            if ($params['referer'])
                fputs($this->_connection, "Referer: ".$params['referer'].$this->_hend);
            else
                fputs($this->_connection, "Referer: ".$params['host'].$this->_hend);

            foreach ($this->_std_headers as $v){
                fputs($this->_connection, $v.$this->_hend);
            }

            if ($this->_headers){
                foreach ($this->_headers as $v){
                    fputs($this->_connection, $v.$this->_hend);
                }
            }

            fputs($this->_connection, "Content-type: application/x-www-form-urlencoded".$this->_hend);
            $out = trim(http_build_query($post));
            fputs($this->_connection, "Content-length: ".strlen($out).$this->_hend);
            fputs($this->_connection, "Connection: close".$this->_hend);

            // ending headers
            fputs($this->_connection, $this->_hend);

            // sending body
            fputs($this->_connection, $out);

            // sending end of body
            fputs($this->_connection, $this->_hend);
            while (!feof($this->_connection)){
                $result .= fgets($this->_connection, 256);
            }

            // check for debug mode
            if ($params['debug'])
                $this->_p->debug->log($result);

            $result = $this->_parseResponse($result);

        } catch (\Exception $e){

            if (is_object($this->_p->debug)){
                if ($this->_p->getVar('is_debug'))
                    $this->_p->debug->add(get_class()." :: ".$e->getMessage(), array('error' => true));

                $this->_p->debug->log(get_class()." :: ".$e->getMessage(), 'error');
            }
            $result = '';
        }

        // check for keep alive mode
        if (!$params['keep_alive'])
            $this->free();

        return $result;

    }


    public function get($url, $params = array()){

        parent::get($url, $params);

        if (!$this->_connection)
            return false;

        if (!$this->_struct)
            $this->_struct = parse_url($url);

        $result = '';
        try {

            fputs($this->_connection, "GET ".$this->_struct['path'].($this->_struct['query'] ? '?'.$this->_struct['query'] : '').($this->_struct['fragment'] ? '#'.$this->_struct['fragment'] : '')." ".strtoupper($this->_struct['scheme'])."/1.0".$this->_hend);
            fputs($this->_connection, "HOST: ".$this->_struct['host'].$this->_hend);
            if ($params['referer'])
                fputs($this->_connection, "Referer: ".$params['referer'].$this->_hend);
            else
                fputs($this->_connection, "Referer: ".$params['host'].$this->_hend);

            foreach ($this->_std_headers as $v){
                fputs($this->_connection, $v.$this->_hend);
            }

            if ($this->_headers){
                foreach ($this->_headers as $v){
                    fputs($this->_connection, $v.$this->_hend);
                }
            }

            fputs($this->_connection, "Connection: close".$this->_hend);

            // ending headers
            fputs($this->_connection, $this->_hend);

            while (!feof($this->_connection)){
                $result .= fgets($this->_connection, 4096);
            }

            // check for debug mode
            if ($params['debug'])
                $this->_p->debug->log($result);

            $result = $this->_parseResponse($result);

        } catch (\Exception $e){

            if (is_object($this->_p->debug)){
                if ($this->_p->getVar('is_debug'))
                    $this->_p->debug->add(get_class()." :: ".$e->getMessage(), array('error' => true));

                $this->_p->debug->log(get_class()." :: ".$e->getMessage(), 'error');
            }
            $result = '';
        }

        // check for keep alive mode
        if (!$params['keep_alive'])
            $this->free();

        return $result;

    }

}