<?php

/**
* Base class of templates parser
* @version 4.0
* @author goshi
* @package web-T[CORE]
*
* Changelog:
 *  4.0     01.01.15/goshi  full refactoring, add template drivers
 *  3.6     10.09.13/goshi  refactor initTemplator method
*	3.51	23.01.12/goshi	add getMainTemplate method
*	3.5	08.12.11/goshi	add remove_token
*	3.4	18.12.10/goshi	add get_template_src method
*	3.3	16.12.10/goshi	fix bug with var property
*	3.2	13.10.10/goshi	add add_tokens method
*	3.1	06.03.10/goshi	using new dirs structure, initialize templator here, remove deprecated methods parse_templates
*	3.0	06.09.09/goshi	remove old tplworker code
*	2.2	01.03.09/goshi	add template_exists method
*	2.1	25.02.09	add_token can now take arrays like key w/o values
*	2.0	11.08.08	based on Smarty
*	1.0	Based on X64Templates
*/

namespace webtFramework\Core;

/**
* @package web-T[CORE]
*/
class webtTpl{
		
	/**
	 * Main template name
	 *
	 * @var string
	 * @access protected
	 */
	protected $_mainTemplate 	= null;

	
	/**
	 * Templates array
	 *
	 * @var array
	 */
	protected $_templates 	= array();

    /**
     * inmemory templates timestamps
     * @var array
     */
    protected $_templates_ts = array();

	/**
	 * templator facade
     * @var \webtFramework\Components\Templator\oTemplatorAbstract
	*/
	protected $_commonTemplator = null;
	
    /**
     * current templator type
     * @var null|string
     */
    private $_mt_type = null;

    /**
     * direct oPortal object
     * @var oPortal
     */
    private $_p;
		
	/**
	 * Object constructor, set $reset to reset the arrays after processing
	 *
	 * @access public
	 */
    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    public function getInstance(){
        return $this->_commonTemplator->getInstance();
    }

	
	/**
	* templator initializing adapter
	*/
	public function initTemplator($params = array()){
	
        if (!$this->_commonTemplator){

            $this->_mt_type = $params['type'];

            // try to load templator
            $class = 'webtFramework\Components\Templator\oTemplator'.ucfirst($this->_mt_type);
            $this->_commonTemplator = new $class($this->_p);

            $custom_params = array();

            if (!isset($params['template_dir'])){
                $custom_params['template_dir'] = array();
            }

            // add application root to the templates dir and empty dir for direct template path
            $custom_params['template_dir'][] = $this->_p->getVar('BASE_APP_DIR').$this->_p->getVar('APP_DIR');
            $custom_params['template_dir'][] = '/';

            if (!isset($params['compile_dir']))
                $params['compile_dir'] = $this->_p->getVar('compile_dir');

            $params['compile_dir'] .= $this->_mt_type;

            $this->_p->filesystem->rmkdir($params['compile_dir']);

            if (!isset($params['cache_dir']))
                $params['cache_dir'] = $this->_p->getVar('templator')['cache_dir'];

            $params['cache_dir'] .= $this->_mt_type;

            $this->_p->filesystem->rmkdir($params['cache_dir']);

            if (!isset($params['trusted_dir']))
                $params['trusted_dir'] = array($this->_p->getVar('lib_dir'));

            $this->_commonTemplator->init(array_merge_recursive_distinct($params, $custom_params, 'combine'));


        } elseif ($params && is_array($params)) {

            foreach ($params as $k => $v){
                $this->_commonTemplator->setParam($k, $v);
            }
        }

		if ($this->_p->getVar('is_debug')){
			$this->_p->debug->add("TEMPLATES: After templator init");
		}
	
	}

    /**
     * add templates directory to the lists
     * @param $template_dir
     * @return bool
     */
    public function addTemplateDir($template_dir){

        if (!$template_dir)
            return false;

        if (!is_array($template_dir)){
            $template_dir = array($template_dir);
        }

        if ($this->_commonTemplator == null)
            $this->initTemplator($this->_p->getVar('templator'));

        return $this->_commonTemplator->setTemplateDirs($template_dir);

    }
	
    /**
     * Set tokens to the templates
     * @param array $tags
     * @param bool $by_ref
     */
    public function addTokens($tags, $by_ref = false)
	{
		if (is_array($tags)){
			foreach ($tags as $k => $v){
				$this->addToken($k, $v, $by_ref);
			}
		}
	}
	
	
	/**
	 * Set a tag with a value, $var can be a string or an array
	 *
	 * @param string $tag
	 * @param mixed [$var]
	 * @param boolean [$by_ref]
	 */
	public function addToken($tag, $var = '', $by_ref = false)
	{
		
		// check for init templator
		if ($this->_commonTemplator == null)
			$this->initTemplator($this->_p->getVar('templator'));
	
	
		if (is_array($tag) && !$var){
			$this->addTokens($tag, $by_ref);
		} else {

            $this->_commonTemplator->addToken($tag, $var, $by_ref);

		}
		
	}

	/**
	* Sets the name of the main page
	*
	* @param string $tpl_name
	* @access public
	*/
	public function setMainTemplate($tpl_name){

		if (isset($tpl_name) && in_array($tpl_name, array_keys($this->_templates)))
			$this->_mainTemplate = $tpl_name;
		
	}

    /**
     * get main template, which will be rendered by @compile method
     * @return string
     */
    public function getMainTemplate(){
		return $this->_mainTemplate;
	}

	/**
	 * adding template to the array
	 * if is_var - template added from $tpl_file like var (not loading form disk)
     *
     * @param $tpl_name
     * @param string $tpl_file
     * @param bool $is_main_template
     * @param bool $is_var
     * @param null $_ROOT_DIR
     * @throws \Exception
     */
    public function add($tpl_name, $tpl_file = '', $is_main_template = false, $is_var = false, $_ROOT_DIR = null){
		
		// check for init templator
		if ($this->_commonTemplator == null)
			$this->initTemplator($this->_p->getVar('templator'));

		if ($tpl_file == '')
			$tpl_file = $tpl_name;

		if (!isset($this->_templates[$tpl_name]))
			$this->_templates[$tpl_name] = "";

        try {

            if (!$is_var){

                $bundle = null;
                if (strpos($tpl_file, ':') !== false){
                    $tpl_file = explode(':', $tpl_file);
                    $bundle = preg_replace('/[^0-9a-zA-Z_]/is', '', $tpl_file[0]);
                    $tpl_file = $tpl_file[1];
                    // add new templates directory to the dir
                    $this->addTemplateDir($this->_p->getVar('BASE_APP_DIR').$this->_p->getVar('bundles_dir').$bundle.WEBT_DS.$this->_p->getVar('templator')['dir'].WEBT_DS);

                }

                if (!$_ROOT_DIR)
                    $_ROOT_DIR = $this->_commonTemplator->getTemplateDirs();

                if (!is_array($_ROOT_DIR))
                    $_ROOT_DIR = array($_ROOT_DIR);

                $base_path = null;
                foreach ($_ROOT_DIR as $dir){
                    if ((!$bundle || ($bundle && strpos($dir, $bundle))) && file_exists($dir.WEBT_DS.$tpl_file)){
                        $base_path = $dir.WEBT_DS.$tpl_file;
                        break;
                    }
                }

                if (!$base_path){
                    throw new \Exception('CORE :: Cannot find template '.$tpl_name);
                }

                $this->_templates_ts[$tpl_name] = filemtime($base_path);

                $this->_templates[$tpl_name] .= file_get_contents($base_path);
                //echo 'set no var: '.$tpl_name." : ".date('Y-m-d H:i:s', $this->_templates_ts[$tpl_name])."<br>";
            } else {
                // for main page ALWAYS add time part for multiuser concurence
                $this->_templates_ts[$tpl_name] = microtime(1)+1000000;//$this->getTime() + $time_add;
                //echo 'set var: '.$tpl_name." : ".date('Y-m-d H:i:s', $this->_templates_ts[$tpl_name])."<br>";
                $this->_templates[$tpl_name] .= $tpl_file;

                $this->_commonTemplator->add($tpl_name, null, $tpl_file);
            }

        } catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
		
		if ($is_main_template)
			$this->setMainTemplate($tpl_name);
		
	}

	/**
	* try to find template in templates directories
	*/
	public function exists($tpl_file, $_ROOT_DIR = false){

		// check for init templator
		if ($this->_commonTemplator == null)
			$this->initTemplator($this->_p->getVar('templator'));
		
		if (!$_ROOT_DIR)
			$_ROOT_DIR = $this->_commonTemplator->getTemplateDirs();

        if (!is_array($_ROOT_DIR))
            $_ROOT_DIR = array($_ROOT_DIR);

        $base_path = null;
        foreach ($_ROOT_DIR as $dir){
            if (file_exists($dir.WEBT_DS.$tpl_file)){
                return true;
            }
        }

		return false;
	
	}

    /**
	* method remove template from list
     * now it deprecated
	*/
	public function remove($tpl_name){
		
		// check for init templator
		if ($this->_commonTemplator == null)
			$this->initTemplator($this->_p->getVar('templator'));
		
		if (!is_array($tpl_name))
			$tpl_name = array($tpl_name);
		
		foreach ($tpl_name as $v){
			unset($this->_templates[$v]);
			$this->_commonTemplator->remove($v);
		}
		
	}

    /**
     * method returns template from list and parses it
     * @param $tpl_name
     * @param array $vars
     * @return bool|mixed
     */
    public function get($tpl_name, $vars = array()){
	
		// check for init templator
		if ($this->_commonTemplator == null)
			$this->initTemplator($this->_p->getVar('templator'));

		// try to upload
		if (!isset($this->_templates[$tpl_name])){
			$this->add($tpl_name);
		}

        // check for compile dir
        if (!file_exists($this->_p->getVar('compile_dir').$this->_mt_type))
            $this->_p->filesystem->rmkdir($this->_p->getVar('compile_dir').$this->_mt_type);

		if (isset($this->_templates[$tpl_name]))

			return $this->_commonTemplator->get($tpl_name, $vars);

		else
			return false;
		
	}

	/**
	* method returns template from list without parse
	*/
	public function getSrc($tpl_name){

        // try to load dynamicaly
        if (!isset($this->_templates[$tpl_name]))
            $this->add($tpl_name);

		return isset($this->_templates[$tpl_name]) ? $this->_templates[$tpl_name] : false;
		
	}

    /**
     * method returns templates timestamp
     */
    public function getSrcTimestamp($tpl_name){

        return isset($this->_templates_ts[$tpl_name]) ? $this->_templates_ts[$tpl_name] : time();

    }
	
	
	/**
	* method returns token from list
	*/
	public function getToken($tag){

		// check for init templator
		if ($this->_commonTemplator == null)
			$this->initTemplator($this->_p->getVar('templator'));

        return $this->_commonTemplator->getToken($tag);

		
	}
	
	
	/**
	* remove token from list
	*/
	public function removeToken($tag){
	
		// check for init templator
		if ($this->_commonTemplator == null)
			return false;			
		else {
			if (!is_array($tag))
                $tag = array($tag);
			foreach ($tag as $v){
				$this->_commonTemplator->removeToken($v);
			}
			return true;
		}
		
	}


    /**
     * compile main template an applies all callbacks on it
     *
     * @param string $tpl_name $tpl_name[option] name of the template for output
     * @param array $params $silent_mode[option] if false set to true - no output will be done
     * @return bool|mixed|string
     * @throws \Exception
     */
    public function compile($tpl_name = "", $params = array()){

        // now we need a little of the previous functionality
        if (!$tpl_name)
            $tpl_name = $this->getMainTemplate();

        if (isset($this->_templates[$tpl_name])){

            $content = $this->get($tpl_name);

            // checking for callback function
            if ($params && is_array($params) && isset($params['callback'])){

                if (strpos($params['callback'], '::')){
                    $func = explode('::', $params['callback']);
                    // fucking php
                    $athis = &$this->_p;
                    $acontent = &$content;
                    $content = call_user_func($func[0] .'::'.$func[1], $athis, $acontent);
                } else
                    $content = call_user_func($params['callback'], $this->_p, $content);

            }

            if ($this->_p->getVar('templator')['compress_html'])
                $content = $this->_p->response->compressHtml($content);

            if ($this->_p->getVar('is_debug') || ($this->_p->getVar('is_dev_env') && $this->_p->debug->hasErrors())){
                // seek in query string _debug - for users, who don't know this tip - nothing echos
                if (strpos($_SERVER['REQUEST_URI'], $this->_p->getVar('debugger')['request_key']) !== false || $this->_p->getVar('debugger')['debug_show_panel'] || ($this->_p->getVar('is_dev_env') && $this->_p->debug->hasErrors()))
                    $content = $this->_p->debug->getProfilerView($content);

            }

        } else {
            $this->_p->debug->log($this->_p->trans('err_nothing_compile'), 'error');
            throw new \Exception($this->_p->trans('err_nothing_compile'));
        }

        // return content for save in cache
        return $content;
    }

}