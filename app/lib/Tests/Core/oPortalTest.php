<?php
/**
 * ...
 *
 * Date: 01.03.15
 * Time: 13:05
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	01.03.2015/goshi 
 */

namespace webtFramework\Tests\Core;


class oPortalTest extends \PHPUnit_Framework_TestCase{

    public function testInitLangs(){

        global $p;
        $p->initLangs('ua');

        $this->assertEquals('ua', $p->getLangNick());

    }


} 