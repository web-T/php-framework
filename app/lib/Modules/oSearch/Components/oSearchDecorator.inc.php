<?php
/**
 * oSearch base decorator
 *
 * Date: 24.07.15
 * Time: 08:12
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	24.07.2015/goshi 
 */

namespace webtFramework\Modules\oSearch\Components;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;

/**
 * declare decorator
 * @package web-T[share]
 */
class oSearchDecorator extends oBase{

    /**
     * @var oSearchCommon
     */
    protected $_oSearchCommon;

    public function __construct(oPortal &$p, oSearchCommon &$oSearchCommon, $params = array()){

        parent::__construct($p, $params);

        $this->_oSearchCommon = $oSearchCommon;
    }

    public function find($params){

        return $this->_oSearchCommon->find($params);

    }

    public function count($params){

        return $this->_oSearchCommon->count($params);

    }

    public function remove($params){

        return $this->_oSearchCommon->remove($params);

    }

    public function update($params){

        return $this->_oSearchCommon->update($params);

    }

    public function save($params){

        return $this->_oSearchCommon->save($params);

    }

    public function index($params = array()){

        return $this->_oSearchCommon->index($params);

    }
}
	