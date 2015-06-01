<?php
/**
 * ...
 *
 * Date: 24.12.14
 * Time: 19:17
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	24.12.2014/goshi 
 */

namespace webtFramework\Components\Templator;

use webtFramework\Core\oPortal;

abstract class oTemplatorAbstract implements iTemplator{

    /**
     * @var oPortal
     */
    protected $_p;

    /**
     * instance of the current templator
     * @var
     */
    protected $_instance;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    /**
     * return current instance
     * @return mixed
     */
    public function getInstance(){

        return $this->_instance;

    }

    /**
     * initislize instance
     * @param array $params
     * @return mixed
     */
    abstract public function init($params = array());

    /**
     * add token to the template vars
     * @param $tag
     * @param string $var
     * @param bool $by_ref
     * @return mixed
     */
    abstract public function addToken($tag, $var = '', $by_ref = false);

    /**
     * remove token from template vars
     * @param $tag
     * @return mixed
     */
    abstract public function removeToken($tag);

    /**
     * get token from template vars
     * @param $tag
     * @return mixed
     */
    abstract public function getToken($tag);

    /**
     * add template to the templates collection
     * @param $tpl_name
     * @param string $tpl_file
     * @param string|null $tpl_source
     * @return mixed
     */
    abstract public function add($tpl_name, $tpl_file = '', $tpl_source = null);

    /**
     * check if template is exists in the template dirs
     * @param $tpl_file
     * @return mixed
     */
    abstract public function exists($tpl_file);

    /**
     * remove template from collection
     * @param $tpl_name
     * @return mixed
     */
    abstract public function remove($tpl_name);

    /**
     * get compiled template from collection
     * @param string $tpl_name
     * @param array $vars
     * @return mixed
     */
    abstract public function get($tpl_name, $vars = array());

    /**
     * set template directories list
     * @param $template_dirs
     * @return mixed
     */
    abstract public function setTemplateDirs($template_dirs);

    /**
     * get template directories list
     * @return mixed
     */
    abstract public function getTemplateDirs();

    /**
     * set parameter of the instance
     * @param string $param
     * @param mixed $value
     * @return mixed
     */
    abstract public function setParam($param, $value);

    /**
     * transform templates path with bundle name
     * @param $tpl_name
     * @return array|string
     */
    protected function _transformTemplatePath($tpl_name){

        if (strpos($tpl_name, ':') !== false){
            $tpl_name = explode(':', $tpl_name);
            $tpl_name = 'src/'.$tpl_name[0].'/views/'.$tpl_name[1];
        }

        return $tpl_name;


    }

} 