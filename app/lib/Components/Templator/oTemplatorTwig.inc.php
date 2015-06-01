<?php
/**
 * Twig templator driver for framework
 *
 * Date: 24.03.15
 * Time: 22:38
 * @version 1.0
 * @author goshi
 * @package web-T[Templator]
 * 
 * Changelog:
 *	1.0	24.03.2015/goshi 
 */

namespace webtFramework\Components\Templator;

class oTemplatorTwig extends oTemplatorAbstract{

    /**
     * @var \Twig_Environment
     */
    protected $_instance;

    protected $_vars = array();

    /**
     * templates from variables
     * @var array
     */
    protected $_vars_templates = array();

    public function init($params = array()){

        $loader = new \Twig_Loader_Filesystem($params['template_dir']);

        $config = array(
            'cache' => $params['compile_dir'],
            'debug' => $this->_p->getVar('is_dev_env') ? true : false
        );

        $this->_instance = new \Twig_Environment($loader, $config);

        if (isset($params['plugins_dir'])){

            // read plugins
            foreach ($params['plugins_dir'] as $dir){
                if (file_exists($dir) && is_dir($dir)){
                    $handle = opendir($dir);
                    while (false !== ($entry = readdir($handle))) {
                        if (preg_match('/^.*\.php$/', $entry)) {
                            include($dir.WEBT_DS.$entry);
                            $entry = explode('.', $entry);
                            $this->_instance->addExtension(new $entry[0]());
                        }
                    }
                    closedir($handle);

                }
            }

        }

        /*$plugins_dirs = array('plugins');
        if (isset($params['plugins_dir'])){
            $plugins_dirs = array_merge($plugins_dirs, (array)$params['plugins_dir']);
        }
        $this->_instance->plugins_dir = $plugins_dirs; */

        //$this->_instance->cache_dir		= $params['cache_dir'];

    }

    public function addToken($tag, $var = '', $by_ref = false){

        if (is_array($tag)){

            foreach ($tag as $k => $v){
                $this->_vars[$k] = $v;
            }

        } else {
            $this->_vars[$tag] = $var;
        }

    }

    public function removeToken($tag){

        unset($this->_vars[$tag]);

    }

    public function getToken($tag){
        if (isset($this->_vars[$tag]))
            return $this->_vars[$tag];
        else
            return false;
    }

    public function add($tpl_name, $tpl_file = '', $tpl_source = null){

        if ($tpl_source){
            $this->_vars_templates[$tpl_name] = $this->_instance->createTemplate($tpl_source);
        }

    }

    public function exists($tpl_file){

    }

    public function remove($tpl_name){

        $tpl_name = $this->_transformTemplatePath($tpl_name);
        try {
            unset($this->_vars_templates[$tpl_name]);
            $this->_instance->compileSource($tpl_name, $tpl_name);
        } catch (\Exception $e){
            if ($this->_p->getVar('is_debug')){
                $this->_p->debug->log($e->getMessage(), 'error');
            }
        }

    }

    public function get($tpl_name, $vars = array()){

        if (isset($this->_vars_templates[$tpl_name]))
            $tpl = $this->_vars_templates[$tpl_name];
        else
            $tpl = $this->_instance->loadTemplate($this->_transformTemplatePath($tpl_name));

        return $tpl->render($vars && !empty($vars) ? array_merge($this->_vars, $vars) : $this->_vars);
    }

    public function setTemplateDirs($template_dirs){

        foreach ($template_dirs as $dir){
            $this->_instance->getLoader()->addPath($dir);
        }

    }

    public function getTemplateDirs(){

        return $this->_instance->getLoader()->getPaths();
    }

    public function setParam($param, $value){

        //if ($param == 'plugins_dir')
        //    $value = array_merge(array('plugins'), (array)$value);

        if ($param == 'template_dir'){
            $this->setTemplateDirs((array)$value);
        } else {
            //$this->_instance->$param = $value;
        }


    }

}