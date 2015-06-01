<?php
/**
 * Base API controller class
 *
 * Date: 15.02.15
 * Time: 23:41
 * @version 1.0
 * @author goshi
 * @package web-T[Interfaces]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;

abstract class oApi extends oBase{

    /**
     * allowed methods for this controller
     * @var array
     */
    protected $_allowedMethods = array('get', 'post');

    /**
     * authorization methods, which called in API request controller before execute action
     * @var array
     */
    protected $_authMethods = array();

    public function getAllowedMethods(){

        return $this->_allowedMethods;

    }

    public function getAuthMethods(){

        return $this->_authMethods;
    }

} 