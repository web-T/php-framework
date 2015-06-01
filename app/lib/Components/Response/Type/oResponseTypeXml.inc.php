<?php
/**
 * XML response type
 *
 * Date: 05.03.15
 * Time: 22:09
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 *
 * Changelog:
 *	1.0	05.03.2015/goshi
 */

namespace webtFramework\Components\Response\Type;

use webtFramework\Tools\Array2XML;

class oResponseTypeXml implements iResponseType{

    public function fetch($data, $code = 200){

        if (!headers_sent())
            Header('Content-type: application/xml', false, $code);

        return $this->render($data);

    }

    public function render($data){

        if (is_array($data)){

            $arr2xml = new Array2XML();
            $content = $arr2xml->convert($data);
        } else {
            $content = $data;
        }

        return $content;

    }

}