<?php
/**
 * Old AJAX response type
 *
 * Date: 06.03.15
 * Time: 09:51
 * @version 1.0
 * @author goshi
 * @package web-T[Response]
 * 
 * Changelog:
 *	1.0	06.03.2015/goshi 
 */

namespace webtFramework\Components\Response\Type;

class oResponseTypeAjax implements iResponseType {


    public function fetch($data, $code = 200){

        global $_RESULT, $p;

        $p->initAjaxCore();

        $_RESULT = $this->render($data);

        return exit;

    }

    public function render($data){

        global $_RESULT;

        return array_extend($_RESULT, (array)$data);

    }

} 