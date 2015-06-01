<?php
/**
 * ...
 *
 * Date: 01.03.15
 * Time: 17:34
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	01.03.2015/goshi 
 */

namespace webtFramework\Tests\Core;


class webtQueryTest extends \PHPUnit_Framework_TestCase{

    public function testBuildStatWithoutPartsNonEmpty(){

        global $p;

        $p->query->setPart('part1', 'part1Value');

        $this->assertEquals('test/november/xxx', $p->query->buildStat(array('page' => 'test', 'november' => 'xxx'), false, true));

        $p->query->removePart('part1');

    }

    public function testBuildStatWithoutPartsEmpty(){

        global $p;

        $p->query->setPart('part1', 'part1Value');

        $this->assertEquals('test/november/xxx/lang/.*', $p->query->buildStat(array('page' => 'test', 'november' => 'xxx', 'lang' => ''), true, true));

        $p->query->removePart('part1');

    }

    public function testBuildStatWithPartsEmpty(){

        global $p;

        $p->query->setPart('part1', 'part1Value');

        $this->assertEquals('test/november/xxx/lang/.*/part1/part1Value', $p->query->buildStat(array('page' => 'test', 'november' => 'xxx', 'lang' => ''), true, false));

        $p->query->removePart('part1');

    }

    public function testBuildStatWithPartsNonEmpty(){

        global $p;

        $p->query->setPart('part1', 'part1Value');

        $this->assertEquals('test/november/xxx/part1/part1Value', $p->query->buildStat(array('page' => 'test', 'november' => 'xxx', 'lang' => ''), false, false));

        $p->query->removePart('part1');

    }


    public function testParseStat(){

        global $p;

        $this->assertEquals(array("page" => "test", "november" => "xxx", "part1" => "part1Value", "lang" => ".*"), $p->query->parseStat('test/november/xxx/lang/.*/part1/part1Value'));

    }


    public function testParseServerNameNoServerNameDefined(){

        global $p;

        $this->assertEquals(array("subdomain" => "", "domain" => "test.test.com"), $p->query->parseServerName('test.test.com'));

    }

    public function testParseServerNameServerNameDefinedSubdomainNotDefined(){

        global $p;

        $p->setVar('server_name', 'test.com');

        $this->assertEquals(array("subdomain" => "", "domain" => "test.test.com"), $p->query->parseServerName('test.test.com'));

        $p->setVar('server_name', '');

    }


    public function testParseServerNameServerNameDefinedSubdomainDefined(){

        global $p;

        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals(array("subdomain" => "test", "domain" => "test.com"), $p->query->parseServerName('test.test.com'));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

    }


    public function testParseNoStrictWithSubdomain(){

        global $p;

        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals(array("page" => "test", "xkey" => "xvalue", "ykey" => "yvalue"), $p->query->parse('http://test.test.com/xkey/xvalue/ykey/yvalue/', true, false));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

    }

    public function testParseStrictWithSubdomain(){

        global $p;

        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals(array("page" => "notest", "xkey" => "xvalue", "ykey" => "yvalue"), $p->query->parse('http://test.test.com/notest/xkey/xvalue/ykey/yvalue/', true, true));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

    }


    public function testBuildNoFullNoContentNoAddtree(){

        global $p;

        $p->setVar('server_name', 'test.com');

        $this->assertEquals('/test/xkey/xvalue/ykey/yvalue/', $p->query->build(array('test' => '', 'xkey' => 'xvalue', 'ykey' => 'yvalue')));

        $p->setVar('server_name', '');

    }

    public function testBuildFullNoContentNoAddtree(){

        global $p;

        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals('http://test.test.com/xkey/xvalue/ykey/yvalue/', $p->query->build(array('test' => '', 'xkey' => 'xvalue', 'ykey' => 'yvalue'), true));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

    }

    public function testBuildFullContentNoAddtree(){

        global $p;

        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals('http://test.test.com/xkey/xvalue/ykey/yvalue/index.xml', $p->query->build(array('test' => '', 'xkey' => 'xvalue', 'ykey' => 'yvalue'), true, CT_XML));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

    }


    public function testBuildFullContentAddtree(){

        global $p;

        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals('http://test.test.com/level0/level1/level2/xkey/xvalue/ykey/yvalue/index.xml', $p->query->build(array('test' => '', 'xkey' => 'xvalue', 'ykey' => 'yvalue'), true, CT_XML, '/level0/level1/level2/'));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

    }

    public function testBuildNoFullContentAddtreeNoFriendly(){

        global $p;

        $p->setVar('router', array('is_friendly_URL' => false), true);
        $p->setVar('server_name', 'test.com');
        //$p->setvar('subdomains', array('test'));

        $this->assertEquals('/index.php?page=test&xkey=xvalue&ykey=yvalue', $p->query->build(array('test' => '', 'xkey' => 'xvalue', 'ykey' => 'yvalue'), false, CT_XML, '/level0/level1/level2/'));

        $p->setVar('server_name', '');
        //$p->setvar('subdomains', null);

        $p->setVar('router', array('is_friendly_URL' => true), true);

    }

    public function testBuildFullContentAddtreeNoFriendly(){

        global $p;

        $p->setVar('router', array('is_friendly_URL' => false), true);
        $p->setVar('server_name', 'test.com');
        $p->setvar('subdomains', array('test'));

        $this->assertEquals('http://test.test.com/index.php?xkey=xvalue&ykey=yvalue', $p->query->build(array('test' => '', 'xkey' => 'xvalue', 'ykey' => 'yvalue'), true, CT_XML, '/level0/level1/level2/'));

        $p->setVar('server_name', '');
        $p->setvar('subdomains', null);

        $p->setVar('router', array('is_friendly_URL' => true), true);

    }

} 