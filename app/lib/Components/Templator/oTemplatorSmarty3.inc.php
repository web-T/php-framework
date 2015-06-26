<?php
/**
 * ...
 *
 * Date: 24.12.14
 * Time: 19:15
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	24.12.2014/goshi 
 */

namespace webtFramework\Components\Templator;


class oTemplatorSmarty3 extends oTemplatorAbstract{

    /**
     * @var \Smarty
     */
    protected $_instance;

    protected $_tplExt = 'tpl';

    public function init($params = array()){

        $this->_instance = new \Smarty();

        $this->_instance->setCompileCheck(isset($params['compile_check']) ? $params['compile_check'] : true);
        $this->_instance->setCaching(isset($params['caching']) ? $params['caching'] : false);
        /* forcing compile templates */
        $this->_instance->setForceCompile(isset($params['force_compile']) ? $params['force_compile'] :  false);

        $plugins_dirs = array('plugins');
        if (isset($params['plugins_dir'])){
            $plugins_dirs = array_merge($plugins_dirs, (array)$params['plugins_dir']);
        }
        $this->_instance->setPluginsDir($plugins_dirs);

        if ($this->_p->getVar('is_debug') == 0)
            $this->_instance->setDebugging(false);
        else
            $this->_instance->setDebugging(true);

        foreach (($params['template_dir']) as $tpl_dir){
            $this->_instance->setTemplateDir($tpl_dir);
        }
        $this->_instance->setCompileDir($params['compile_dir']);
        $this->_instance->setCacheDir($params['cache_dir']);
        $this->_instance->setConfigDir(isset($params['config_dir']) ? $params['config_dir'] : 'configs');

        $this->_instance->setLeftDelimiter($params['begin_key']);
        $this->_instance->setRightDelimiter($params['end_key']);

        $this->_instance->setPhpHandling(isset($params['compile_check']) ? $params['compile_check'] : \Smarty::PHP_ALLOW);

    }

    public function addToken($tag, $var = '', $by_ref = false){

        if ($by_ref)
            $this->_instance->assignByRef($tag, $var);
        else
            $this->_instance->assign($tag, $var);

    }

    public function removeToken($tag){
        $this->_instance->clearAssign($tag);
    }

    public function getToken($tag){
        if (isset($this->_instance->tpl_vars[$tag]))
            return $this->_instance->tpl_vars[$tag];
        else
            return false;
    }

    public function add($tpl_name, $tpl_file = '', $tpl_source = null){

    }

    public function exists($tpl_file){

    }

    public function remove($tpl_name){
        $this->_instance->clearCompiledTemplate($tpl_name);
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

        if (!$template_dirs)
            return;

        foreach ((array)$template_dirs as $dir){
            $this->_instance->addTemplateDir($dir);
        }
    }

    public function getTemplateDirs(){
        return $this->_instance->getTemplateDir();
    }

    public function setParam($param, $value){

        switch ($param){

            case 'plugins_dir':
                $value = array_merge(array('plugins'), (array)$value);
                break;

            case 'template_dir':
                $value = array_merge($this->_instance->getTemplateDir(), (array)$value);
                $value = array_unique($value);
                break;

            case 'begin_key':
                $param = 'left_delimiter';
                break;

            case 'end_key':
                $param = 'right_delimiter';
                break;

        }

        $key = explode('_', $param);
        $key_new = 'set';
        foreach ($key as $k){
            $key_new .= ucfirst($k);
        }

        if (property_exists($this->_instance, $param)){
            $this->_instance->$key_new($value);
        }


    }

} 