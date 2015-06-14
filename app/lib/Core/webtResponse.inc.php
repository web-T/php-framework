<?php

/**
 * Core class for response operations
 *
 * Date: 30.12.13
 * Time: 17:03
 * @version 1.2
 * @author goshi
 * @package web-T[CORE]
 *
 * Changelog:
 *	1.0		30.12.13/goshi	...
 */

namespace webtFramework\Core;

use webtFramework\Helpers\MimeType;
use webtFramework\Components\Response\oResponse;

class webtResponse{

    /**
     * response status code
     * @var int
     */
    protected $_status_code = 200;

    /**
     * content type of the response
     * @var int
     */
    protected $_content_type;

    /**
     * response headers
     * @var array
     */
    protected $_headers = array();

    /**
     * @var null|oPortal
     */
    protected $_p = null;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    /**
     * response type factory
     * @param $type
     * @return \webtFramework\Components\Response\Type\iResponseType
     */
    public function getResponseTypeFactory($type){

        $class = 'html';

        if ($type){

            // some magic reflection
            $consts = get_defined_constants();

            if ($consts){

                foreach ($consts as $k => $v){

                    if ($v == $type && preg_match('/^CT_(.*)$/is', $k, $match) && class_exists('\webtFramework\Components\Response\Type\oResponseType'.ucfirst(strtolower($match[1])))){

                        $class = '\webtFramework\Components\Response\Type\oResponseType'.ucfirst(strtolower($match[1]));
                        break;

                    }

                }

            }

        }

        return new $class();

    }

    /**
     * response stream factory
     * @param $type
     * @return \webtFramework\Components\Response\Stream\iResponseStream
     */
    public function getResponseStreamFactory($type){

        $class = 'browser';

        if ($type){

            // some magic reflection
            $consts = get_defined_constants();

            if ($consts){

                foreach ($consts as $k => $v){

                    if ($v == $type && preg_match('/^ST_(.*)$/is', $k, $match) && class_exists('\webtFramework\Components\Response\Stream\oResponseStream'.ucfirst(strtolower($match[1])))){

                        $class = '\webtFramework\Components\Response\Stream\oResponseStream'.ucfirst(strtolower($match[1]));
                        break;

                    }

                }

            }

        }

        return new $class();

    }



    /**
     * experimental function that gets rid of tabs, line breaks, and extra spaces
     * got from http://davidwalsh.name/compress-xhtml-page-output-php-output-buffers)
     *
     * @param $buffer
     * @return mixed
     */
    public function compressHtml($buffer){

        $search = array('/>[\n\r\s]+/s','/[\t ]+/s');
        $replace = array('> ',' ');
        return preg_replace($search, $replace, $buffer);

    }

    /**
     * method set content type of the response
     * @param $content_type
     * @return $this
     */
    public function setContentType($content_type){

        if ($content_type)
            $this->_content_type = $content_type;

        return $this;

    }

    public function getContentType(){

        return $this->_content_type;

    }

    /**
     * method set response headers
     * @param null $header
     * @param null $value
     * @return webtResponse
     */
    public function setHeader($header = null, $value = null){

        if ($header){

            if (!is_array($header)){
                $header = array($header => $value);
            }

            $this->_headers = array_merge($this->_headers, $header);

        }

        return $this;

    }

    /**
     * method return free (not uploaded) file
     * TODO: make response content without webT::Framework (maybe nginx anf Lightty ? with X-accel)
     *
     * @param string $filename real filename
     * @param string $content_name nick of file
     * @param null|string $content file content
     * @param null|string $mime_type file mimetype
     * @param null|string $disposition type of content disposition ('inline' or 'attached')
     * @throws \Exception
     */
    public function sendFile($filename, $content_name = '', $content = null, $mime_type = null, $disposition = 'inline'){

        // check if set return file throw system
        if ($this->_p->getVar('response')['send_files_by_framework'] == 1 || $content !== null){

            if (!$mime_type)
                $mime_type = MimeType::getType($filename);

            // be careful ! Large files can't return throw webT::Framework
            if ($content === null){
                if (!file_exists($filename))
                    throw new \Exception($this->_p->trans('errors.response.no_file_found').': '.$filename);

                $content = file_get_contents($filename);
            }

            $content_len = strlen($content);
            if (!$content_name)
                $content_name = basename($filename);
            header('Pragma: public');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $this->_p->getTime()).' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
            header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
            header('Content-Transfer-Encoding: none');
            header("Content-Type: {$mime_type}; name=\"{$content_name}\"");

            header("Content-Disposition: ".($disposition ? $disposition : 'inline')."; filename=\"{$content_name}\"");
            header("Content-length: {$content_len}");
            echo $content;

        } else {
            // else - redirect to real file
            Header("Location: ".$filename);

        }

        exit;
    }

    /**
     * response from the server with ajax
     * @param array $data
     * @deprecated
     * @return mixed
     */
    public function sendAjax($data = array()){

        return $this->getResponseTypeFactory(CT_AJAX)->fetch($data);

    }

    /**
     * method send response to browser
     * @param mixed $data
     * @param int $response_code
     * @param int $content_type
     * @return mixed|string|void
     */
    public function send($data, $response_code = 200, $content_type = null){

        // send all headers
        if (!empty($this->_headers) && !headers_sent()){
            foreach ($this->_headers as $k => $v){
                Header($k.($v !== '' && $v !== null ? ': '.$v : ''), true);
            }
        }

        if ($data && $data instanceof oResponse && ($headers = $data->getHeaders())){
            foreach ($headers as $k => $v){
                Header($k.($v !== '' && $v !== null ? ': '.$v : ''));
            }
        }

        // restore response code
        if ($data && $data instanceof oResponse)
            $response_code = $data->getStatus();
        elseif (!$response_code){
            $response_code = 200;
        }

        // detecting data type
        if ($data && $data instanceof oResponse){
            $content_type = $data->getContentType();
        } elseif (!$content_type) {
            $content_type = $this->_content_type ? $this->_content_type : ($this->_p->getVar('is_ajax') ? CT_AJAX : (is_array($data) ? CT_JSON : CT_HTML));
        }

        // check  - if user is auth - then sending non cached headers
        /*if ($this->user->getId() > 0){
            Header('Cache-Control: no-cache, max-age=0, must-revalidate, proxy-revalidate', true);
            Header('Pragma: no-cache', true);
        }*/

        if ($data && $data instanceof oResponse){
            $tmp_data = $data->getContent();
        } else {
            $tmp_data = &$data;
        }

        $content = $this->getResponseTypeFactory($content_type)->fetch($tmp_data, $response_code);

        if ($data && $data instanceof oResponse && $data->getStreamType()){
            $stream_type = $data->getStreamType();
        } else {
            $stream_type = $this->_p->getVar('STREAM_TYPE');
        }

        $this->getResponseStreamFactory($stream_type)->push($content);

        unset($data);
        unset($tmp_data);
        unset($stream_type);

        return $content;

    }

}

