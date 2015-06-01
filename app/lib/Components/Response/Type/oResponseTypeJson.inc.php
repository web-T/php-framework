<?php
/**
 * JSON response type
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


class oResponseTypeJson implements iResponseType{

    public function render($data){

        if (is_array($data)){
            $content = json_encode($data);
        } else {
            $content = $data;
        }

        return $content;

    }

    public function fetch($data, $code = 200){

        if (!headers_sent())
            Header('Content-type: application/json', false, $code);

        return $this->render($data);

    }

}