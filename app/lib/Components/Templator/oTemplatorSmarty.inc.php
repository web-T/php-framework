<?php
/**
 * ...
 *
 * Date: 24.12.14
 * Time: 19:07
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	24.12.2014/goshi 
 */

namespace webtFramework\Components\Templator;

class oTemplatorSmarty extends oTemplatorAbstract{

    /**
     * @var \Smarty
     */
    protected $_instance;

    public function init($params = array()){

        $this->_instance = new \Smarty;

        $this->_instance->compile_check = isset($params['compile_check']) ? $params['compile_check'] : true;
        $this->_instance->caching = isset($params['caching']) ? $params['caching'] : false;
        $this->_instance->force_compile = isset($params['force_compile']) ? $params['force_compile'] :  false; /* forcing compile for templates */

        $plugins_dirs = array('plugins');
        if (isset($params['plugins_dir'])){
            $plugins_dirs = array_merge($plugins_dirs, (array)$params['plugins_dir']);
        }
        $this->_instance->plugins_dir = $plugins_dirs;

        if ($this->_p->getVar('is_debug') == 0)
            $this->_instance->debugging = false;
        else
            $this->_instance->debugging = true;

        $this->_instance->template_dir	= $params['template_dir'];
        $this->_instance->compile_dir	= $params['compile_dir'];
        $this->_instance->cache_dir		= $params['cache_dir'];
        $this->_instance->trusted_dir	= $params['trusted_dir'];
        $this->_instance->config_dir		= isset($params['config_dir']) ? $params['config_dir'] : 'configs';

        $this->_instance->left_delimiter	= $params['begin_key'];
        $this->_instance->right_delimiter	= $params['end_key'];

        $this->_instance->php_handling	= isset($params['php_handling']) ? $params['php_handling'] : SMARTY_PHP_ALLOW;

    }

    public function addToken($tag, $var = '', $by_ref = false){

        if ($by_ref)
            $this->_instance->assign_by_ref($tag, $var);
        else
            $this->_instance->assign($tag, $var);

    }

    public function removeToken($tag){
        $this->_instance->clear_assign($tag);
    }

    public function getToken($tag){
        if (isset($this->_instance->_tpl_vars[$tag]))
            return $this->_instance->_tpl_vars[$tag];
        else
            return false;
    }

    public function add($tpl_name, $tpl_file = '', $tpl_source = null){

    }

    public function exists($tpl_file){

    }

    public function remove($tpl_name){

        $this->_instance->clear_compiled_tpl($tpl_name);

    }

    public function get($tpl_name, $vars = array()){

        if (!empty($vars)){
            foreach ($vars as $k => $v){
                $this->addToken($k, $v);
            }
        }

        return $this->_instance->fetch("var:".$tpl_name);
    }

    public function setTemplateDirs($template_dirs){

        $dirs = $this->_instance->template_dir;

        if (!is_array($dirs))
            $dirs = array($dirs);

        foreach ($template_dirs as $dir){
            if (!in_array($dir, $dirs))
                $dirs[] = $dir;
        }

        $this->_instance->template_dir = $dirs;


    }

    public function getTemplateDirs(){

        return $this->_instance->template_dir;
    }

    public function setParam($param, $value){

        if ($param == 'plugins_dir')
            $value = array_merge(array('plugins'), (array)$value);

        if ($param == 'template_dir'){
            $value = array_merge($this->_instance->template_dir, (array)$value);
            $value = array_unique($value);
        }

        $this->_instance->$param = $value;
    }

} 