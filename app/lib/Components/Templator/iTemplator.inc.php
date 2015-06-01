<?php
/**
 * ...
 *
 * Date: 24.12.14
 * Time: 18:49
 * @version 1.0
 * @author goshi
 * @package web-T[Components]
 * 
 * Changelog:
 *	1.0	24.12.2014/goshi 
 */

namespace webtFramework\Components\Templator;

interface iTemplator {

    public function init($params = array());

    public function addToken($tag, $var = '', $by_ref = false);
    public function removeToken($tag);
    public function getToken($tag);

    public function add($tpl_name, $tpl_file = '', $tpl_source = null);
    public function exists($tpl_file);
    public function remove($tpl_name);
    public function get($tpl_name, $vars = array());

    public function setTemplateDirs($template_dirs);
    public function getTemplateDirs();

    public function setParam($param, $value);

}
