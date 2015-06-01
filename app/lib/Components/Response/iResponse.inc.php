<?php
/**
 * interface for response component
 *
 * Date: 16.02.15
 * Time: 00:00
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 * 
 * Changelog:
 *	1.0	16.02.2015/goshi 
 */

namespace webtFramework\Components\Response;


interface iResponse {

    public function setHeader($header, $value = null);

    public function getHeader($header);

    public function getHeaders();

    public function setHeaders($headers = array());

    public function setStatus($status = 200);

    public function getStatus();

    public function setContent($content = '');

    public function getContent();

    public function setContentType($type);

    public function getContentType();

    public function setStreamType($type);

    public function getStreamType();

} 