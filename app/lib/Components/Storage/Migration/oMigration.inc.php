<?php
/**
 * Base migration class
 *
 * Date: 23.01.15
 * Time: 10:58
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	1.0	23.01.2015/goshi 
 */

namespace webtFramework\Components\Storage\Migration;

use webtFramework\Core\oPortal;

class oMigration implements iMigration{

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){

        $this->_p = &$p;

    }

    public function up(){}

    public function down(){}

} 