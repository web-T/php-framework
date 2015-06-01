<?php
/**
 * Interface of response type
 *
 * Date: 05.03.15
 * Time: 22:06
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 * 
 * Changelog:
 *	1.0	05.03.2015/goshi 
 */

namespace webtFramework\Components\Response\Type;


interface iResponseType {

    /**
     * render resposne type and send necessary headers
     * @param $data
     * @param int $code
     * @return mixed
     */
    public function fetch($data, $code = 200);

    /**
     * render response type
     * @param $data
     * @return mixed
     */
    public function render($data);

} 