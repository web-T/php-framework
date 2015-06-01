<?php
/**
 * Console stream
 *
 * Date: 06.03.15
 * Time: 09:59
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 * 
 * Changelog:
 *	1.0	06.03.2015/goshi 
 */

namespace webtFramework\Components\Response\Stream;


class oResponseStreamConsole implements iResponseStream {

    public function push($data){

        echo $data."\r\n";

    }

} 