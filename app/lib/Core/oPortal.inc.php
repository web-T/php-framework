<?php

/**
* web-T::CMS Portal class 
* @version 5.0
* @author goshi
* @package web-T[CORE]
*/

namespace webtFramework\Core;

/**
* include base class and tools
*/

use webtFramework\Components\Event\oEvent;
use webtFramework\Components\Request\oRoute;
use webtFramework\Components\Request\oQuery;
use webtFramework\Tools;
use webtFramework\Interfaces\oModel;


class cProxy{

    /**
     * proxied instance
     * @var null
     */
    public $instance = null;

	/**
	 * @var oPortal
	 */
	protected $_p = null;

    /**
     * parameters for initialize object
     * @var array
     */
    protected $_params = array();

	/**
	* @params array $params	array of parameters
	*/
	public function __construct(oPortal &$p, $params){
		$this->_p = $p;
		$this->_params = $params;
	}

    protected function _initObject(){

        if (isset($this->_params['include']))
            require_once($this->_params['include']);
        if (isset($this->_params['instance'])){
            $instance = '\webtFramework\Core\\'.$this->_params['instance'];
            $this->instance = new $instance($this->_p);
            unset($instance);
        }
        if ($this->_p->getVar('is_debug') && is_object($this->_p->debug))
            $this->_p->debug->add("PROXY: After connect ".(isset($this->_params['instance']) ? $this->_params['instance'] : $this->_params['include']));

        if (isset($this->_params['init_function'])){
            $init_function = $this->_params['init_function'];
            if (is_array($init_function)){
                $init_function[0]->$init_function[1]($this->_p, $this->instance);
            } else {
                $init_function($this->_p, $this->instance);
            }

            unset($init_function);
        }

        unset($this->_p);
        //unset($this->_params);

    }

    /**
     * method get instance
     * @return null
     */
    public function __getProxiedInstance(){

        return $this->instance;

    }

    /**
     * magic method used for initialize object
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {

		if ($this->instance == null){

            $this->_initObject();

		}

		// fixing the problem with fucking PHP 5.2.12 with simply string
		//$exists = $this->_params['instance'] ? class_exists($this->_params['instance']) : class_exists('DbSimple_Generic');
		//if ($exists)
		return call_user_func_array(array($this->instance, $name), $arguments);
    }


    /**
     * magic method used for object properties
     * @param $name
     * @return mixed
     */
    public function __get($name) {

        if ($this->instance == null){

            $this->_initObject();

        }

        if (isset($this->instance->$name)) {
            return $this->instance->$name;
        } else
            return null;
    }


    /**
     * return if object initialized
     * @return bool
     */
    public function isInit(){

        return $this->instance != null;

    }

}

/**
 * Class works with cookies
 * Class cCookie
 * @package webtFramework\Core
 */
class cCookie {

    /**
     * @var null|oPortal
     */
    private $_p = null;

	public function __construct(oPortal &$p){
		
		$this->_p = &$p;

	}
	
	/**
	* cleanup all duplicated cookies
	*/
	public function cleanup(){
	
		$cookies = $_COOKIE;
		foreach ($_COOKIE as $k => $v){
			$this->remove($k);
		}
		
		// restore cookies
		foreach ($cookies as $k => $v){
			$this->set($k, $v, 140, "/");
		}
	
	}

    /**
     * method get cookie value
     * @param $name
     * @return null
     */
    public function get($name){
	
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	
	}

    /**
     * method set cookie value
     * @param $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function set($name, $value='', $expire = 0, $path = '/', $domain='', $secure=false, $httponly=false)
    { 

		if (!isset($expire) || $expire === null) $expire = $this->_p->getVar('user')['cookie_timeout'];
		if (!$domain) $domain = $this->_p->getVar('server_name');

        if ($expire !== 0)
		    $expire = $this->_p->getTime() + 60*60*24*$expire;

		// foreach server_name and aliases
		try{

			// it is PHP warning
            if (date('Y', $expire) <= 9999){
		
				if ($this->_p->getVar('server_aliases'))
					foreach($this->_p->getVar('server_aliases') as $v){
						setcookie($name, $value, $expire, $path, ".".$v, $secure, $httponly);
					}
		        $_COOKIE[$name] = $value;
                $cookies = $this->_p->getVar('cookies');
				$cookies[$name] = $value;
                $this->_p->setVar('cookies', $cookies);
				setcookie($name, $value, $expire, $path, ".".$domain, $secure, $httponly);
				
				// because IE and Chrome cant set cookie on subdomains - setup global cookies for them
				setcookie($name, $value, $expire, $path, "", $secure, $httponly);
			}
			
		} catch (\Exception $e){

			if ($this->_p->getVar('is_debug')){
				echo $e->getMessage();
			}
		}	
				
    }

    /**
     * method delete cookie
     * @param $name
     * @param string $path
     * @param string $domain
     */
    public function remove($name, $path = '/', $domain = ''){ 
        unset($_COOKIE[$name]);
		unset($this->_p->vars['cookies'][$name]);

		// foreach server_name and aliases
		if ($this->_p->getVar('server_aliases'))
			foreach($this->_p->getVar('server_aliases') as $v){
				setcookie($name, NULL, -1, $path, ".".$v);
			}

        
		setcookie($name, NULL, -1, $path, ".".$domain);
		// because IE and Chrome cant set cookie on subdomains - setup global cookies for them
		setcookie($name, NULL, -1, $path, "");
    } 

}


/**
 * main core class
* @package web-T[CORE]
*/
class oPortal {
		
	/**
     * portal configuration
     * direct use now @deprecated
     * use setter and getter instead
     */
	public $vars 	= array();

    /**
     * portal language
     * @var array
     * @deprecated use getter and setter instead
     */
    public $mess	= array();

    /**
     * pointer to the $mess
     * @var null
     */
    public $m	    = null;

    /**
     * parsed request uri
     * @var array
     * @deprecated
     */
    public $q	= array();

    /**
     * Current element item id
     * @var null|int
     * @deprecated
     */
    public $item_id = null;

    /**
     * Current element (if exists)
     * @var null|array
     * @deprecated
     */
    public $item = null;

    /**
     * frontend pages tree
     * @var null|array
     * @deprecated
     */
    public $ap = null;


    /**
     * @var cProxy|webtResponse
     */
    public $response = null;

    /**
     * @var null|webtQuery|cProxy
     */
    public $query = null;

	/**
	 * @var webtUser
	 */
	public $user = null;

	/**
	 * @var webtCache
	 */
	public $cache = null;

	/**
	 * @var webtJobs
	 */
	public $jobs = null;

	/**
	 * @var webtEvent
	 */
	public $events = null;

    /**
     * @var webtServer
     */
    public $server = null;

    /**
     * @var webtFilesystem
     */
    public $filesystem = null;
	
    /**
     * languages table
     * format: nick => id
     * @var array
     */
    protected $_lang_tbl	= array();


	/**
	 * @var null|integer
     * @deprecated use getter and setter instead
	 */
	public $lang_id = null;

    /**
     * @var null|string
     */
    protected $_lang_nick = null;

	/**
	 * @var webtDB|\webtFramework\Components\Storage\Database\oDatabaseAbstract|\DBSimple_Mysql|\DbSimple_Mysqli|\DbSimple_Mypdo|cProxy
	 */
	public $db = null;

    /**
     * @var webtTpl new templates module
     */
    public $tpl = null;

	/**
	 * @var webtSharedMemory
	 */
	public $shmem = null;

    /**
     * @var webtApi
     */
    public $api;

    /**
	 * @var cProxy|webtDebugger
	 */
	public $debug = null;


    /**
     * Threads object
     * @var cProxy|webtThreads|null
     */
    public $threads = null;

    /**
     * @var null|\JsHttpRequest
     * @deprecated
     */
    public $AJAX = null;

	/**
	 * @var array cached apps
	 */
	private $_cached_apps = array();

    /**
     * @var array cached console apps
     */
    private $_cached_consoles = array();

    /**
     * @var array cached api apps
     */
    private $_cached_apis = array();

    /**
	 * @var array cached modules
	 */
	private $_cached_modules = array();


	/**
	 * @var array cached controls
	 */
	private $_cached_controls = array();


    /**
     * @var string current application
     */
    private $_application;

    /**
     * special flag for determine if core was initialized
     * @var bool
     */
    private $_is_init = false;

	// constructor
	public function __construct($new_config = ''){

		global $INFO;

        // injects functions to the global
        include($INFO['FW_DIR'].$INFO['lib_dir'].$INFO['tools_dir'].'functions.php');
		
		// init variables
		if (!isset($new_config) || !is_array($new_config))
			$this->vars = &$INFO;
		else
			$this->vars = &$new_config;

		mb_internal_encoding($this->vars['codepage']);
		mb_regex_encoding($this->vars['codepage']);

        // activate filesystem interface
        $this->filesystem = new cProxy($this, array('instance' => 'webtFilesystem', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtFilesystem.inc.php'));

        // check for debug interface
		// activate debugger
        $this->debug = new cProxy($this, array('instance' => 'webtDebugger', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtDebugger.inc.php'));

        if ($this->vars['is_debug']){

            $this->debug->add("CORE: After load FILESYSTEM core module");
        }


        if ($this->vars['is_debug']){
		
			$this->debug->add("CORE: After load DEBUG class");
		}

        $this->query = new cProxy($this, array('instance' => 'webtQuery', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtQuery.inc.php'));

        if ($this->vars['is_debug'])
            $this->debug->add("CORE: After connect Query module");


        $this->response = new cProxy($this, array('instance' => 'webtResponse', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtResponse.inc.php'));

        if ($this->vars['is_debug'])
            $this->debug->add("CORE: After connect Response core module");

        // activate jobs interface
		$this->jobs = new cProxy($this, array('instance' => 'webtJobs', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtJobs.inc.php'));

		if ($this->vars['is_debug']){

			$this->debug->add("CORE: After load JOBS core module");
		}

        // activate server interface
        $this->server = new cProxy($this, array('instance' => 'webtServer', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtServer.inc.php'));

        if ($this->vars['is_debug']){

            $this->debug->add("CORE: After load SERVER core module");
        }

		if ($this->vars['is_debug'])
			$this->debug->add('REQUEST_URI: '.$_SERVER['REQUEST_URI']);

		// detect debug level
		if ($this->vars['is_debug'] && $this->vars['debugger']['debug_show_all_errors']){
			ini_set('display_errors', 1);
			error_reporting(E_ALL);
		} else {
			//ini_set('display_errors', 0);
			error_reporting(E_ALL & ~E_NOTICE);		
		}
		
		// parsing tables
		foreach ($this->vars['tables'] as $k => $v){		
			$this->vars[$k] = $v;		
		}
		
		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After init tables");

		//session_cache_limiter('nocache');
		//session_cache_limiter('private, must-revalidate');
		// be careful when using Proxy object - PHP 5.xx is not stable with new features!!!
        $this->db = new cProxy($this, array('instance' => 'webtDB', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtDB.inc.php'));

		// activate eventer
		$this->events = new cProxy($this, array('instance' => 'webtEvent', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtEvent.inc.php'));

        if ($this->vars['is_debug'])
			$this->debug->add("CORE: After connect events controller");


        // activate multithreading
        $this->threads = new cProxy($this, array('instance' => 'webtThreads', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtThreads.inc.php'));

        if ($this->vars['is_debug'])
            $this->debug->add("CORE: After connect threads controller");


        //initialized shared memory controller
		$this->shmem = &webtSharedMemory::factory($this);
		
		if ($this->vars['is_debug'])		
			$this->debug->add("CORE: After init shared memory controller");


        //initialized shared api controller
        $this->api = new cProxy($this, array('instance' => 'webtApi', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtApi.inc.php'));

        if ($this->vars['is_debug'])
            $this->debug->add("CORE: After init shared api controller");


        // activate cache controller
		$this->cache = new webtCache($this);

		// prepare debug interface
		if ($this->vars['is_debug'])		
			$this->debug->add("CORE: After init cache controller");
		
		// init site block tags
		$this->_temp = array('tags' => array());

        $this->tpl = new cProxy($this, array('instance' => 'webtTpl', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtTpl.inc.php'));;

        // getting some variables
		if (isset($this->vars['core']['settings']) && isset($this->vars['core']['settings']['model']) && $this->vars['core']['settings']['model'] && !($tmp_vars = $this->cache->getSerial('webt.settings'))){

            $smodel = $this->Model($this->vars['core']['settings']['model']);

			$result = $this->db->select($this->db->getQueryBuilder($smodel->getModelStorage())->compile($smodel, array('no_array_key' => 'true')));

			if ($result){

                $nick = isset($this->vars['core']['settings']['model_map_nick']) ? $this->vars['core']['settings']['model_map_nick'] : 'set_nick';
                $value = isset($this->vars['core']['settings']['model_map_value']) ? $this->vars['core']['settings']['model_map_value'] : 'set_value';

				foreach ($result as $row){
				
					/**
					* hack for server aliases
					*/
					if ($row[$nick] == 'server_aliases' && !empty($row[$value])){
						$row[$value] = str_replace(array(';', ','), ';', str_replace(' ', '', $row[$value]));
						$row[$value] = explode(';', $row[$value]);
					}
						
					if ($row['lang_id'] != '0')
						$tmp_vars[$row[$nick]][$row['lang_id']] = $this->vars[$row[$nick]][$row['lang_id']] = $row[$value];
					else
						$tmp_vars[$row[$nick]] = $this->vars[$row[$nick]] = $row[$value];
					
				}
			}

            unset($smodel);
			
			$this->cache->saveSerial('webt.settings', $tmp_vars);

		} else {
			$this->vars = array_merge($this->vars, (array)$tmp_vars);
		}

		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After get all settings");

        // now detect additional application config
        if (defined('WEBT_APP')){
            $this->initApplication(WEBT_APP);
        }

        // checking for domain merging
		if ($this->vars['router']['merge_www_domain'] && preg_match('/^www\.(.*)$/is', $_SERVER['HTTP_HOST'], $match)){
			$this->redirect($this->vars['router']['default_scheme'].'://'.$match[1].$_SERVER['REQUEST_URI']);
		}


		// getting all langs
		list($this->_lang_tbl, ) = $this->Service('oLanguages')->getLangs(array('native'));

		// init cookies
		$this->cookie = new cCookie($this);
		// setting timestamp
        // make short aliases
        $this->time = time();
        $this->vars['timestamp']['unix'] = $this->time;
		$this->vars['timestamp']['date'] = date('Y-m-d', $this->time);
		$this->vars['timestamp']['time'] = date('Y-m-d H:i:s', $this->time);
		$this->vars['timestamp']['micro'] = microtime(1);
		$this->vars['today']['unix'] = mktime(0, 0, 0, date('m', $this->time), date('d', $this->time), date('y', $this->time));
		$this->vars['today']['date'] = date('Y-m-d', $this->vars['today']['unix']);
		$this->vars['today']['time'] = date('Y-m-d H:i:s', $this->vars['today']['unix']);

        /**
         * TODO: remove autoparsing
         */
        if (isset($_SERVER['REQUEST_URI']))
		    $this->query->parse($_SERVER['REQUEST_URI']);
        else
            $this->query->parse('');

		/*if (preg_match('/^\/('.join('|', array_keys($this->_lang_tbl)).')\//is', $_SERVER['REQUEST_URI'], $match)){

			// cleanup all session cookies
			//$this->cookie->cleanup();
			
			$storage->set('lang_id', $this->_lang_tbl[$match[1]]);
			
			$this->initLangs($match[1]);
			$this->redirect($this->vars['router']['default_scheme'].'://'.$_SERVER['SERVER_NAME'].str_replace($match[1].'/', '', $_SERVER['REQUEST_URI']));
		} else {
			// getting lang from storage
			if ($lang_id = $storage->get('lang_id')){
				$this->initLangs($this->vars['langs'][$lang_id]['nick']);
			}
		} */

		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After parse query");
			
		// determine server name
		/*$server = $this->query->parseServerName();
        if ($server['domain']){
            $this->query->setDomain($server['domain']);
            $this->query->setSubdomain($server['subdomain']);
        }
        unset($server); */

		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After parse query");

		// moving session to all subdomains
		ini_set("session.cookie_domain", ".".$this->query->get()->getDomain());

		$found = false;
		foreach ($this->vars['bots'] as $v){
			if (strpos($_SERVER['HTTP_USER_AGENT'], $v) !== false){ $found = true; break;} 
					
		}
		//session_cache_limiter('private');
		// if not found in bots array - start session
		if ($found){
			// because bots dont use cookies - turn off use_trans_sid for clearing url in the search results
			ini_set('session.use_only_cookies', true);
			ini_set('session.use_trans_sid', false);
		} else
			ini_set('session.use_only_cookies', false);


		// open user session
		if (!ini_get('session.auto_start') && session_id() == '' && $this->vars['user']['session_autostart']){
			// setting session on all subdomains
			session_name($this->vars['user']['session_name']);
			session_cache_limiter('nocache');
			if ($this->vars['is_debug'])
				$this->debug->add("CORE: Before core session_start");
			
			session_start();
			//session_write_close();
			if ($this->vars['is_debug'])
				$this->debug->add("CORE: After init session");
	
		}

		// connect languages
		if ($this->vars['language'] == '')
			$this->initLangs();
		
		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After initLangs");
				
		// setting up default cookies
		if (isset($this->vars['cookies']) && is_array($this->vars['cookies'])){
            $clife = $this->getTime() + 60*60*24*$this->vars['user']['cookie_timeout'];
			foreach ($this->vars['cookies'] as $k => $v){

				if (!isset($_COOKIE[$k])){
					$this->cookie->set($k, $v, $clife, "/");
	
				}
			} 
		}
		
		// setting up cookies variable
		$this->vars['cookies'] = $_COOKIE;
		
		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After parse cookies");

		// setting portal page title
		$this->vars['portal_title'] = ''; 
		
		if ($this->vars['is_debug'])
			$this->debug->add("CORE: After portal initialize");

        // connect user
        $this->user = new cProxy($this, array('instance' => 'webtUser', 'include' => $INFO['FW_DIR'].$INFO['lib_dir'].$INFO['core_dir'].'webtUser.inc.php'));

        if ($this->vars['is_debug']){

            $this->debug->add("CORE: After connect user");

        }

        // add event listeners
        if (isset($INFO['__dispatch__']) && $INFO['__dispatch__']){

            foreach ($INFO['__dispatch__'] as $event => $functions){
                foreach ($functions as $function){
                    $this->events->addEventListener($event, $function);
                }
            }

        }
        unset($INFO['__dispatch__']);

        // dispath oninit event
        $this->events->dispatch(new oEvent(WEBT_CORE_INIT, $this));

        // set initialization flag
        $this->_is_init = true;

	}

    /**
     * return initialization flag
     * @return bool
     */
    public function getIsInit(){

        return $this->_is_init;

    }

    /**
     * generate tables hash (integer representative of table name)
     * @param string|oModel $source
     * @return mixed
     * @throws \Exception
     */
    public function getTableHash($source){

        if ($source instanceof oModel)
            $table = $source->getModelTable();
        else
            $table = $source;

        if (!($this->vars['tables_hash'] = $this->cache->getSerial('tables_hash'))){
            foreach ($this->vars['tables'] as $v){
                $this->vars['tables_hash'][$v] = abs((int)crc16($v));
            }
            $this->cache->saveSerial('tables_hash', $this->vars['tables_hash']);
        }

        if (!isset($this->vars['tables_hash'][$table]) && isset($this->vars['tables'][$table])){

            $this->vars['tables_hash'][$table] = abs((int)crc16($this->vars['tables'][$table]));
            $this->cache->saveSerial('tables_hash', $this->vars['tables_hash']);

        } elseif (!isset($this->vars['tables_hash'][$table])){

            if (!isset($this->_temp['inv_tables'])){
                $this->_temp['inv_tables'] = array_flip($this->vars['tables']);
            }

            if (isset($this->_temp['inv_tables'][$table])){
                $this->vars['tables_hash'][$table] = abs((int)crc16($table));
                $this->cache->saveSerial('tables_hash', $this->vars['tables_hash']);
            }

        }

        if (isset($this->vars['tables_hash'][$table])){
            return $this->vars['tables_hash'][$table];
        } else {
            throw new \Exception($this->trans('error.no_table_hash').' '.$table);
        }

    }

    /**
     * method find table name by hash
     * @param $hash
     * @return mixed
     * @throws \Exception
     */
    public function getTableNameByHash($hash){

        if (!($this->vars['tables_hash'] = $this->cache->getSerial('tables_hash'))){
            foreach ($this->vars['tables'] as $v){
                $this->vars['tables_hash'][$v] = abs((int)crc16($v));
            }
            $this->cache->saveSerial('tables_hash', $this->vars['tables_hash']);
        }

        $this->_temp['inv_tbl'] = array_flip($this->vars['tables_hash']);

        if (isset($this->_temp['inv_tbl'][$hash]))
            return $this->_temp['inv_tbl'][$hash];
        else
            throw new \Exception($this->trans('error.no_hash_for_table').' '.$hash);


    }

	
	/** 
	* method connect languages to the core
	*
	* @param string $lang_nick[option] nick of the connected language
	*/
	public function initLangs($lang_nick = null){

		// don't set 'language' property by itself - get do it much better
        $this->m = $this->Service('oLanguages')->get($lang_nick, $this->getApplication());
        $this->mess = &$this->m;

		// adding info into the link
		if ($this->vars['is_multilang']){
			$this->query->setPart('lang', $this->getLangNick()); // TODO - fix doubled info about language
		}
		
	}


    /**
     * helper for translate messages
     *
     * @param string $phrase
     * @return null|string
     */
    public function trans($phrase = null){

        return $this->Service('oLanguages')->trans($phrase);

    }

    /**
     * getter for document root directory
     * @return mixed
     */
    public function getDocDir(){

        return $this->vars['DOC_DIR'];

    }


    /**
     * method get snapshot of request unixtime
     * @return int|null
     */
    public function getTime(){

        return $this->time;

    }

    /**
     * get current language id
     * @return int|null
     */
    public function getLangId(){

        return $this->lang_id;

    }

    /**
     * set language id
     * @param null $lang_id
     * @return $this
     */
    public function setLangId($lang_id = null){

        if ($lang_id && is_numeric($lang_id))
            $this->lang_id = $lang_id;

        return $this;

    }


    /**
     * get language nick
     * @return null|string
     */
    public function getLangNick(){

        return $this->_lang_nick;

    }

    /**
     * set languange nick
     * @param $nick
     * @return $this
     */
    public function setLangNick($nick){

        $this->_lang_nick = $nick;
        $this->vars['language'] = $nick;

        return $this;

    }

    /**
     * get table of languages
     * @return array
     */
    public function getLangTbl(){

        return $this->_lang_tbl;

    }

    /**
     * set table of languages
     * @param $lang_tbl
     * @return $this
     */
    public function setLangTbl($lang_tbl){

        if ($lang_tbl){
            $this->_lang_tbl = $lang_tbl;
        }

        return $this;

    }

    /**
     * get languages list
     * @return mixed
     */
    public function getLangs(){

        return $this->vars['langs'];

    }

    /**
     * set languages list
     * @param $langs
     * @return $this
     */
    public function setLangs($langs){

        if ($langs){
            $this->vars['langs'] = $langs;
        }

        return $this;

    }



    /**
     * extract var from vars list
     * @param $var
     * @return null
     */
    public function getVar($var){

        if ($var && isset($this->vars[$var])){
            return $this->vars[$var];
        } else {
            return null;
        }

    }

    /**
     * get all vars
     * @return array
     */
    public function getVars(){
        return $this->vars;
    }

    /**
     * set var
     * @param string $var
     * @param mixed $value
     * @param bool $combine set to true, when you need to combine variables (suchas array)
     * @return null
     */
    public function setVar($var, $value, $combine = false){

        if ($var){
            if ($combine && is_array($value) && isset($this->vars[$var])){

                $this->vars[$var] = array_merge_recursive_distinct((array)$this->vars[$var], $value);

            } else
                $this->vars[$var] = $value;
        }

        return $this;

    }

    /**
     * get current application
     * @return string
     */
    public function getApplication(){
        return $this->_application;
    }

    /**
     * set current application
     * @param string $application
     * @return $this
     */
    public function setApplication($application){

        $this->_application = $application;

        return $this;
    }


    /**
     * initialize selected bundle
     * @param $application
     * @throws \Exception
     */
    public function initApplication($application){

        // init application and all configs
        if (file_exists($this->vars['APP_DIR'].$this->vars['bundles_dir'].$application) && is_dir($this->vars['APP_DIR'].$this->vars['bundles_dir'].$application)){

            $this->setApplication($application);

            // create local stamp of the object
            $p = &$this;

            // read config
            foreach ($this->vars['config_files'] as $config => $config_file){

                // load config
                if (file_exists($this->vars['APP_DIR'].$this->vars['bundles_dir'].$application.WEBT_DS.$this->vars['config_dir'].$config_file)){

                    require($this->vars['APP_DIR'].$this->vars['bundles_dir'].$application.WEBT_DS.$this->vars['config_dir'].$config_file);

                    switch ($config){

                        case 'routes':

                            if (isset($INFO) && isset($INFO['ROUTES']) && is_array($INFO['ROUTES']) && !empty($INFO['ROUTES'])){
                                foreach ($INFO['ROUTES'] as $route_nick => $route_value){
                                    if (!isset($route_value['path']))
                                        throw new \Exception('error.router.no_path');
                                    $this->query->addRoute($route_nick, new oRoute(
                                        $route_value['path'],
                                        isset($route_value['defaults']) ? $route_value['defaults'] : array(),
                                        isset($route_value['requirements']) ? $route_value['requirements'] : array(),
                                        isset($route_value['options']) ? $route_value['options'] : array(),
                                        isset($route_value['host']) ? $route_value['host'] : '',
                                        isset($route_value['schemes']) ? $route_value['schemes'] : array(),
                                        isset($route_value['methods']) ? $route_value['methods'] : array(),
                                        isset($route_value['callback']) ? $route_value['callback'] : null
                                    ));
                                }
                            }
                            break;


                        case 'config':
                        default:
                            if (isset($INFO) && is_array($INFO)){
                                foreach ($INFO as $nick => $value){

                                    if ($nick != 'ROUTES'){
                                        if (is_array($value) && isset($this->vars[$nick]))
                                            $this->vars[$nick] = array_merge_recursive_distinct($this->vars[$nick], $value);
                                        else
                                            $this->vars[$nick] = $value;

                                        if ($nick == 'tables'){
                                            // parsing tables
                                            foreach ($value as $z => $x){
                                                $this->vars[$z] = $x;
                                            }

                                        }
                                    }

                                }

                            }

                            break;


                    }


                }

            }

            // add bundles resources dir to the directory list

            if (!isset($this->vars['templator']['template_dir'])){
                $this->vars['templator']['template_dir'] = array();
            }
            $this->vars['templator']['template_dir'][] = $this->getVar('BASE_APP_DIR').$this->getVar('bundles_dir').$application.WEBT_DS.$this->getVar('templator')['dir'].WEBT_DS;

            $this->tpl->initTemplator($this->vars['templator']);

            // delete local vars
            unset($INFO);

        } else {
            throw new \Exception('error.core.no_application_found');
        }
    }

    /**
     * method return config path for application
     * @param string $filename
     * @return string
     * @throws \Exception
     */
    public function getApplicationConfigPath($filename = ''){

        if ($this->_application && file_exists($this->vars['bundles_dir'].$this->_application) &&
                is_dir($this->vars['bundles_dir'].$this->_application) &&
                file_exists($this->vars['bundles_dir'].$this->_application) &&
                is_dir($this->vars['bundles_dir'].$this->_application.WEBT_DS.$this->getVar('config_dir'))){

            return $this->vars['bundles_dir'].$this->_application.WEBT_DS.$this->getVar('config_dir').$filename;

        } else {

            throw new \Exception('error.core_application_not_defined');

        }

    }


    /**
     * Domain crosser for resources
     *
     * @param string $path string full path to the resource
     * @param int $res_id identifier
     * @param string [$protocol] string protocol string (like "http://", "ftp://")
     * @return string
     */
    public function getAssetDomain($path, $res_id, $protocol = null){

        if (!$protocol)
            $protocol = ($this->vars['router']['default_scheme'] ? $this->vars['router']['default_scheme'] : 'http').'://';

        // fix domains list
        if (empty($this->vars['resource_domains']) && $this->query->get() && $this->query->get()->getDomain()){
            $this->vars['resource_domains'][] = $this->query->get()->getDomain();

            $this->vars['resource_domains_cnt'] = count($this->vars['resource_domains']);

        }

        $domain = '';

        if ($this->vars['resource_domains_cnt'])
            $domain = $this->vars['resource_domains'][(int)($res_id) % $this->vars['resource_domains_cnt']];

        $port = $this->query->getRequest() && $this->query->getRequest()->getPort() != 80 ? ':'.$this->query->getRequest()->getPort() : '';

        return ($domain != '' ? $protocol.$domain : '').$port.($path[0] != "/" ? "/" : "").$path;
    }

    /**
     * resource files version generator
     * @param string $file
     * @return mixed
     */
    public function getAssetVersion($file){

        $version = $this->filesystem->getFileMtime('./'.$this->vars['DOC_DIR'].$file);

        return preg_replace('!.([a-z]+?)$!', ".v$version.$1", $file);

    }


    /**
	 * Lock file for timeout
	 *
	 * @param string $filename name of file
	 * @param int $timeout timeout for lock file in seconds
     * @return bool
	 */
    public function lockFile($filename, $timeout = 0){

		if (!$filename)
			return false;

        $dir = $this->vars['BASE_APP_DIR'].$this->vars['cron_files_dir'];

        if (!$this->filesystem->rmkdir($dir))
            return false;

		$filename = $dir.$filename.'.lock';
		$fexists = file_exists($filename);

		if (!$fexists || ($fexists && (trim(file_get_contents($filename)) != '1')) || ($fexists && (trim(file_get_contents($filename)) == '1') && $timeout && (time() - filemtime($filename)) > $timeout)){

            $this->filesystem->writeData($filename, '1', 'w', 0644);

			return true;
		} else {
			return false;
		}
	}

    /**
     * unlock previously locked file
     * @param $filename
     * @return bool
     */
    public function unlockFile($filename){

		if (!$filename)
			return false;

		$filename = $this->vars['BASE_APP_DIR'].$this->vars['cron_files_dir'].$filename.'.lock';
		if (file_exists($filename) && trim(file_get_contents($filename)) == '1'){

            $this->filesystem->writeData($filename, '0', 'w', 0644);

			return true;
		} else {
			return false;
		}


	}


    /**
     * makes outer redirect
     * it can detect ajax mode and use another method for redirect response
     *
     * @param mixed $href
     * @param bool $http_response
     * @return null|array
     */
    public function redirect($href, $http_response = false){

        // checking for Ajax mode
        if ($this->getVar('is_ajax')){

            $this->redirectAjax($href, $http_response);

        } else {

            if (is_array($href) || $href instanceof oQuery){
                $href = $this->query->build($href);
            }
            if ($http_response && is_numeric($http_response) && $http_response == 404)
                Header("Refresh: 0; url=".$href, false, $http_response);
            else
                Header('Location: '.$href, $http_response && is_numeric($http_response) ? true : false, $http_response && is_numeric($http_response) ? $http_response : null);

            exit;
        }
	}


    /**
     * makes ajax redirect
     * @param string|array|oQuery $href  url for redirecting
     * @param string $redirect_type redirect mode, can be 'normal' or 'ajax'
     * @access public
     * @deprecated
     */
    public function redirectAjax($href, $redirect_type = 'normal'){

        global $_RESULT;

        if (is_array($href) || $href instanceof oQuery){
            $href = $this->query->build($href);
        }

        $_RESULT['status'] = 200;
        $_RESULT['redirect'] = $href;
        $_RESULT['redirect_type'] = $redirect_type;

        exit;

    }

    /**
     * method initialize ajax core
     * @return \JsHttpRequest|null
     * @deprecated
     */
    public function initAjaxCore(){

        if (!$this->AJAX){
            // Load JsHttpRequest backend.
            require_once $this->vars['DOC_DIR']."share/js/JsHttpRequest/JsHttpRequest.php";

            // Create main library object. You MUST specify page encoding!
            $this->AJAX = new \JsHttpRequest($this->vars['codepage'], $this);
        }

        return $this->AJAX;
    }

    /**
     * detect if current application instance is main
     * @return bool
     */
    public function isMainInstance(){
        return INSTANCE_NAME == 'main';
    }

    /**
     * parse callabale string like 'Frontend:news:default' to normalized array array('application' => xx, 'controller' => yy, 'method' => yy)
     * @param $callable
     * @return array|callable
     * @throws \Exception
     */
    public function parseCallable($callable){

        if (isset($callable)){

            if (is_callable($callable))
                return $callable;
            elseif (is_array($callable) && (isset($callable['method']) || isset($callable['application'])))
                return $callable;

            $result = array();

            $callable = explode(':', $callable);
            if (count($callable) == 3){
                $result['application'] = $callable[0];
                $result['controller'] = $callable[1];
                $result['method'] = $callable[2];
            } elseif (count($callable) == 2){
                $result['application'] = $callable[0];
                $result['controller'] = $callable[1];
            } elseif (count($callable) == 1){
                $result['method'] = $callable[0];
            }

            return $result;

        } else {
            throw new \Exception('error.callable_not_set');
        }

    }

    /**
     * loads application controller
     * @param mixed $app you can handle apps from another bundles,like 'SomeBundle:Appname'
     * @param array $params
     * @return \Frontend\Interfaces\oClip|\webtFramework\Interfaces\oApp|bool application object, or false if any error
     * @throws \Exception
     */
    public function App($app = null, $params = array()){
		
		try {

			$app_id = $app_name = null;
            $bundle = 'Frontend';

			if (is_array($app) && isset($app['real_id'])){
			// for completed array with data
				$app_id = $app['real_id'];
				$app_name = $app['parser_name'];

			} elseif (is_string($app)){
                // loading app by app name (!), not by nick, but always save nicks

                // check for bundle (like 'Frontend:News_tape')
                if (strpos($app, ':') !== false){
                    $app = explode(':', $app);
                    // cleanup bundle
                    $bundle_base = preg_replace('/[^0-9a-zA-Z_]/is', '', $app[0]);
                    if (file_exists($this->vars['bundles_dir'].$bundle_base)){
                        $bundle = $bundle_base;
                    } else {
                        $bundle = mb_strtolower($bundle_base);
                    }
                    $app = mb_strtolower($app[1]);
                }

                $app_id = $app_name = $app;

			}

            if (strpos($app_name, ':') !== false){
                $app_name = explode(':', $app_name);
                // cleanup bundle
                $bundle_base = preg_replace('/[^0-9a-zA-Z_]/is', '', $app_name[0]);
                if (file_exists($this->vars['bundles_dir'].$bundle_base)){
                    $bundle = $bundle_base;
                } else {
                    $bundle = mb_strtolower($bundle_base);
                }

                $app_name = mb_strtolower($app_name[1]);
            }

            if ($app_id && isset($this->_cached_apps[$bundle.':'.$app_id]) && $this->_cached_apps[$bundle.':'.$app_id]){
				return ($params ? $this->_cached_apps[$bundle.':'.$app_id]->extend($params) : $this->_cached_apps[$bundle.':'.$app_id]);
            }

            // detect apps dir
            if (file_exists($this->getVar('bundles_dir').$bundle.WEBT_DS.$this->getVar('apps_dir'))){
                $apps_dir = $this->getVar('bundles_dir').$bundle.WEBT_DS.$this->getVar('apps_dir');
            } elseif (file_exists($this->getVar('bundles_dir').$bundle.WEBT_DS.ucfirst($this->getVar('apps_dir')))){
                $apps_dir = $this->getVar('bundles_dir').$bundle.WEBT_DS.ucfirst($this->getVar('apps_dir'));
            } else
                $apps_dir = null;


            if ($app_name &&
                    file_exists($apps_dir.$app_name.'.app.php')){

				require_once($apps_dir.$app_name.'.app.php');

				$app = $params ? array_merge((array)$app, $params) : (array)$app;
                $app_name = ucfirst($bundle).'\Apps\\'.$app_name;
				$this->_cached_apps[$bundle.':'.$app_id] = new $app_name($this, $app);

                return $this->_cached_apps[$bundle.':'.$app_id];
			
			} else {

                throw new \Exception($this->trans('errors.core.no_app_found').': '.$app);

            }

		} catch (\Exception $e){
			
			if (is_object($this->debug)){
                $message = "CORE: Error loading app : ".$app.". Details: ".$e->getMessage();
                if ($this->vars['is_debug'])
                    $this->debug->add($message, array('error' => true));

                $this->debug->log($message, 'error');
			}
			
			throw new \Exception($e->getMessage(), $e->getCode());
			
		}

	}


    /**
     * loads console controller
     * @param mixed $app you can handle apps from another bundles,like 'SomeBundle:Appname'
     * @param array $params some parameters, like 'is_init_bundle'
     * @return \webtFramework\Interfaces\oConsole|bool console application object, or false if any error
     * @throws \Exception
     */
    public function Console($app = null, $params = array()){

        try {

            $app_name = null;
            $bundle = 'core';
            $bundle_base = 'core';

            if (is_string($app)){

                // check for bundle (like 'Frontend:cleanup')
                if (strpos($app, ':') !== false){
                    $app = explode(':', $app);
                    // cleanup bundle

                    $bundle_base = preg_replace('/[^0-9a-zA-Z_]/is', '', $app[0]);
                    $bundle = mb_strtolower($bundle_base);
                    $app_name = $app = preg_replace('/[^0-9a-zA-Z_]/is', '', $app[1]);
                    /*if (file_exists($this->vars['bundles_dir'].$bundle)){
                        $app_file = $this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['models_dir'].(string)$model.'.model.php';
                    } else {
                        $app_file = $this->vars['bundles_dir'].$base_bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['models_dir'].(string)$model.'.model.php';
                        $bundle = $base_bundle;
                    }*/

                }

            } else {
                $app_name = $app = mb_strtolower($app);
            }

            if ($app && isset($this->_cached_consoles[$bundle.':'.$app]) && $this->_cached_consoles[$bundle.':'.$app]){
                return ($params ? $this->_cached_consoles[$bundle.':'.$app]->extend($params) : $this->_cached_consoles[$bundle.':'.$app]);
            }

            // for core bundle use another strategy
            if ($bundle == 'core'){

                $app_dir = $this->vars['FW_DIR'].$this->vars['lib_dir'].WEBT_DS.ucfirst($this->vars['console_dir']);
                $app_name = $app_class = ucfirst($app_name);
                $namespace = '\webtFramework\Console\\';

            } else {

                if (file_exists($this->vars['bundles_dir'].$bundle_base)){

                    // init bundle if you need
                    if (isset($params['is_init_bundle']) && $params['is_init_bundle']){
                        $this->initApplication($bundle_base);
                    }

                    $app_dir = $this->vars['bundles_dir'].$bundle_base.WEBT_DS.$this->vars['console_dir'];
                    $namespace = $bundle_base.'\Console\\';
                } else {

                    // init bundle if you need
                    if (isset($params['is_init_bundle']) && $params['is_init_bundle']){
                        $this->initApplication($bundle);
                    }

                    $app_dir = $this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['console_dir'];
                    $namespace = ucfirst($bundle).'\Console\\';
                }

                $app_class = $app_name;

            }

            if ($app_name && file_exists($app_dir) && is_dir($app_dir) && file_exists($app_dir.$app_name.'.console.php')){

                require_once($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['interfaces_dir'].'iConsole.inc.php');
                require_once($app_dir.$app_name.'.console.php');

                $app_name = $namespace.$app_class;

                $this->_cached_consoles[$bundle.':'.$app] = new $app_name($this, $params);

                return $this->_cached_consoles[$bundle.':'.$app];

            } else {
                throw new \Exception('error.core.no_console_app');
            }

        } catch (\Exception $e){

            if (is_object($this->debug)){
                $message = "CORE: Error loading console app : ".$app.". Details: ".$e->getMessage();
                if ($this->vars['is_debug'])
                    $this->debug->add($message, array('error' => true));

                $this->debug->log($message, 'error');
            }

            // re-throw exception
            throw new \Exception($e->getMessage(), $e->getCode());

        }

    }


    /**
     * loads Api controller
     * @param mixed $app you can handle apps from another bundles,like 'SomeBundle:Appname'
     * @param array $params some parameters, like 'is_init_bundle'
     * @return \webtFramework\Interfaces\oApi|bool console application object, or false if any error
     * @throws \Exception
     */
    public function ApiApp($app = null, $params = array()){

        try {

            $app_name = null;
            $bundle = 'core';
            $bundle_base = 'core';

            if (is_string($app)){

                // check for bundle (like 'Frontend:cleanup')
                if (strpos($app, ':') !== false){
                    $app = explode(':', $app);
                    // cleanup bundle
                    $bundle_base = preg_replace('/[^0-9a-zA-Z]/is', '', $app[0]);
                    $bundle = mb_strtolower($bundle_base);
                    $app_name = $app = mb_strtolower($app[1]);

                }

            } else {
                $app_name = $app = mb_strtolower($app);
            }

            if ($app && isset($this->_cached_apis[$bundle.':'.$app]) && $this->_cached_apis[$bundle.':'.$app]){
                return ($params ? $this->_cached_apis[$bundle.':'.$app]->extend($params) : $this->_cached_apis[$bundle.':'.$app]);
            }

            // for core bundle use another strategy
            if ($bundle == 'core'){

                $app_dir = $this->vars['FW_DIR'].$this->vars['lib_dir'].WEBT_DS.ucfirst($this->vars['api_dir']);
                $app_name = $app_class = ucfirst($app_name);
                $namespace = '\webtFramework\Api\\';

            } else {

                if (file_exists($this->vars['bundles_dir'].$bundle_base)){

                    // init bundle if you need
                    if (isset($params['is_init_bundle']) && $params['is_init_bundle']){
                        $this->initApplication($bundle_base);
                    }

                    $app_dir = $this->vars['bundles_dir'].$bundle_base.WEBT_DS.$this->vars['api_dir'];
                    $namespace = $bundle_base.'\Api\\';
                } else {

                    // init bundle if you need
                    if (isset($params['is_init_bundle']) && $params['is_init_bundle']){
                        $this->initApplication($bundle);
                    }

                    $app_dir = $this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['api_dir'];
                    $namespace = ucfirst($bundle).'\Api\\';
                }

                $app_class = $app_name;

            }

            if ($app_name && file_exists($app_dir) && is_dir($app_dir) && file_exists($app_dir.$app_name.'.api.php')){

                require_once($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['interfaces_dir'].'iApi.inc.php');
                require_once($app_dir.$app_name.'.api.php');

                $app_name = $namespace.$app_class;

                $this->_cached_apis[$bundle.':'.$app] = new $app_name($this, $params);

                return $this->_cached_apis[$bundle.':'.$app];

            } else {
                throw new \Exception('error.core.no_api_app', ERROR_NO_API_FOUND);
            }

        } catch (\Exception $e){

            if (is_object($this->debug)){
                $message = "CORE: Error loading api app : ".$app.". Details: ".$e->getMessage();
                if ($this->vars['is_debug'])
                    $this->debug->add($message, array('error' => true));

                $this->debug->log($message, 'error');
            }

            // re-throw exception
            throw new \Exception($e->getMessage(), $e->getCode());

        }

    }



    /**
     * loades mixed module
     * @param string $module
     * @param array $params
     * @return \webtFramework\Interfaces\oModule|\webtFramework\Modules\oLinker|\webtCMS\Modules\oMailer|\webtCMS\Modules\oUploader|\webtCMS\Modules\oImages|\webtCMS\Modules\oVideo|\webtCMS\Modules\oAudio|\webtCMS\Modules\oSeoOptimizer|\webtCMS\Modules\oCharts|\webtCMS\Modules\oGeo|\webtFramework\Modules\oSearch|\webtCMS\Modules\oPager|\webtCMS\Modules\oModerator|\webtCMS\Modules\oPayments|\webtCMS\Modules\oComments|\webtCMS\Modules\oMaps|\webtShop\Modules\oShop|\webtCMS\Modules\oSocial|\webtCMS\Modules\oTags|\webtFramework\Modules\oWeb|\webtCMS\Modules\oXML|\webtCMS\Modules\oStats|\webtCMS\Modules\oPortfolio|\webtCMS\Modules\oTextLinker|\webtBackend\Modules\oAdminStats|\webtCMS\Modules\oForum|\webtCMS\Modules\oSitemap|\webtCMS\Modules\oRules|\webtCMS\Modules\oMessenger|\webtCMS\Modules\oRating object
     * @throws \Exception
     */
    public function Module($module = null, $params = array()){

        // determine base class
        if (isset($params['class'])){
            $class = $params['class'];
        } else {
            $class = $module;
        }

        // check for bundle (like 'Frontend:News_tape')
        $bundle = null;
        $bundle_str = '';
        $base_bundle = null;
        if (is_string($module) && strpos($module, ':') !== false){
            $module = explode(':', $module);
            // cleanup bundle
            $base_bundle = preg_replace('/[^0-9a-zA-Z]/is', '', $module[0]);
            $bundle = mb_strtolower($base_bundle);
            $bundle_str = $bundle.':';
            $module = preg_replace('/[^0-9a-zA-Z]/is', '', $module[1]);
            if (!$params['class']){
                $class = $module;
            }
        }

        $non_cached = array('oLinker');

		if (isset($this->_cached_modules[$bundle_str.$class]) && $this->_cached_modules[$bundle_str.$class] && !$params['no_cache'] && !in_array($bundle_str.$class, $non_cached))
			return $this->_cached_modules[$bundle_str.$class];

		try {

			$app_name = $app_file = null;

			if (is_string($module)){

				if (isset($this->vars[$module.'_dir'])){

					$app_name = $class;

					if (strpos($this->vars[$module.'_dir'], 'lib'.WEBT_DS) === false)
						$app_file = $this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['modules_dir'].$this->vars[$module.'_dir'].$class.'.inc.php';
					else
						$app_file = $this->vars[$module.'_dir'].$class.'.inc.php';

                } elseif ($base_bundle &&
                    file_exists($this->vars['bundles_dir'].$base_bundle) &&
                    is_dir($this->vars['bundles_dir'].$base_bundle) &&
                    file_exists($this->vars['bundles_dir'].$base_bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['modules_dir']) &&
                    file_exists($this->vars['bundles_dir'].$base_bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['modules_dir'].$module)){

                    $bundle = $base_bundle;
                    $app_name = $class;
                    $app_file = $this->vars['bundles_dir'].$base_bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['modules_dir'].$module.WEBT_DS.$class.'.inc.php';

                } elseif ($bundle &&
                    file_exists($this->vars['bundles_dir'].$bundle) &&
                    is_dir($this->vars['bundles_dir'].$bundle) &&
                    file_exists($this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['modules_dir']) &&
                    file_exists($this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['modules_dir'].$module)
                ) {
                    $app_name = $class;
                    $app_file = $this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['modules_dir'].$module.WEBT_DS.$class.'.inc.php';

				} elseif (file_exists($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['modules_dir']) &&
                    file_exists($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['modules_dir'].$module) &&
                    is_dir($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['modules_dir'].$module)
                ){
                    $app_name = $class;
                    $app_file = $this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['modules_dir'].$module.WEBT_DS.$class.'.inc.php';
                }

			} elseif (is_array($module)){
				// for completed array with data
				$app_name = $module['name'];
				$app_file = $module['parser_name'];
			}

			if ($app_name && $app_file){

                if (!class_exists('\webtFramework\Interfaces\oModule'))
				    require_once($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['interfaces_dir'].'iModule.inc.php');

				require_once($app_file);

                if (!$params['no_construct']){
                    $app_name = ($bundle ? '\\'.ucfirst($bundle).'\\' : '\webtFramework\\').'Modules\\'.$app_name;

                    // prepare parameters for module
                    $module_params = array();
                    if (isset($params['params'])){
                        $module_params = $params['params'];
                    }

                    $module_app = new $app_name($this, $module_params);
                    unset($module_params);

                    if (!$params['no_cache'])
					    $this->_cached_modules[$bundle_str.$class] = &$module_app;

					return $module_app;
				}

			}

		} catch (\Exception $e){

			if (is_object($this->debug)){

				$message = "CORE: Error loading module : ".$module.".{".$class."} Details: ".$e->getMessage();
                if ($this->vars['is_debug'])
				    $this->debug->add($message, array('error' => true));

                $this->debug->log($message, 'error');

			}

            throw new \Exception($e->getMessage(), $e->getCode());

		}

        return true;
	}

    /**
     * loades model
     * @param string|\webtFramework\Interfaces\oModel $model
     * @param array $params
     * @return \webtFramework\Interfaces\oModel
     * @throws \Exception
     */
    public function Model($model, $params = array()){

        // check if we send model
        if ($model && $model instanceof \webtFramework\Interfaces\oModel){
            return $model;
        }

        try {

            $bundle = null;
            $base_bundle = null;
            if (is_string($model) && strpos($model, ':') !== false){
                $model = explode(':', $model);
                // cleanup bundle
                $base_bundle = preg_replace('/[^0-9a-zA-Z]/is', '', $model[0]);
                $bundle = mb_strtolower($base_bundle);
                $app_name = preg_replace('/[^0-9a-zA-Z]/is', '', $model[1]);
                $model = $model[1];
                if (file_exists($this->vars['bundles_dir'].$bundle)){
                    $app_file = $this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['models_dir'].(string)$model.'.model.php';
                } else {
                    $app_file = $this->vars['bundles_dir'].$base_bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['models_dir'].(string)$model.'.model.php';
                    $bundle = $base_bundle;
                }

            } else {
                $app_name = (string)$model;
                $app_file = $this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['models_dir'].(string)$model.'.model.php';
            }

            if ($app_name && $app_file && file_exists($app_file)){

                if (!class_exists('\webtFramework\Interfaces\oModel'))
                    require_once($this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['interfaces_dir'].'iModel.inc.php');

                $app_name = ($bundle ? ucfirst($bundle).'\\' : '\webtFramework\\').'Models\\'.$app_name;

                if (!class_exists($app_name))
                    require_once($app_file);

                return new $app_name($this);

            } else {
                throw new \Exception($this->trans('error.core.no_model_exists').': '.$model);
            }

        } catch (\Exception $e){

            if (is_object($this->debug)){

                $message = "CORE: Error loading model : ".(string)$model.". Details: ".$e->getMessage();
                if ($this->vars['is_debug'])
                    $this->debug->add($message, array('error' => true));

                $this->debug->log($message, 'error');

            }
            throw new \Exception($e->getMessage(), $e->getCode());

        }
    }

    /**
     * loades control
     * @param string $controller
     * @param array $params
     * @return \webtBackend\Services\oAdmin|\webtFramework\Services\oMail|\webtFramework\Components\Mail\oMailAbstract|\webtFramework\Services\oForms|\webtFramework\Services\oConvert|\webtFramework\Services\oKeys|\webtCMS\Services\oImagesUploader|\webtFramework\Services\oDev|\webtFramework\Services\oLanguages|\webtFramework\Services\oImage|\webtFramework\Components\Image\oImageManagerAbstract|\webtFramework\Services\oAsset|\webtFramework\Services\oList|\webtFramework\Services\oTree|\webtCMS\Services\Core|\webtCMS\Services\Syslog|\webtCMS\Services\Query|\webtCMS\Services\User|boolean|\webtShop\Services\Core|\webtShop\Services\Import|boolean
     * @throws \Exception
     */
    public function Service($controller = null, $params = array()){

        // non cacheable controlls
        $non_cached = array('oForms', 'oTree', 'oList', 'oImage');

		if (!$params['no_cache'] && isset($this->_cached_controls[$controller]) && $this->_cached_controls[$controller] && !in_array($controller, $non_cached))
			return $this->_cached_controls[$controller];

		try {

			$app_name = $app_file = $bundle_base = $bundle = $controller_dir = $namespace = null;
			if (is_string($controller)){

                // check for bundle (like 'Frontend:News_tape')
                if (is_string($controller) && strpos($controller, ':') !== false){
                    $class = explode(':', $controller);
                    // cleanup bundle
                    $bundle_base = preg_replace('/[^0-9a-zA-Z]/is', '', $class[0]);
                    $bundle = mb_strtolower($bundle_base);
                    $app_name = preg_replace('/[^0-9a-zA-Z]/is', '', $class[1]);
                } else
                    $app_name = $controller;

                if ($params['services_dir']){

                    $controller_dir = $params['services_dir'];

                } elseif ($bundle) {

                    if (file_exists($this->vars['bundles_dir'].$bundle_base)){

                        $controller_dir = $this->vars['bundles_dir'].$bundle_base.WEBT_DS.$this->vars['lib_dir'].$this->vars['services_dir'];
                        $namespace = $bundle_base.'\Services\\';

                    } else {

                        $controller_dir = $this->vars['bundles_dir'].$bundle.WEBT_DS.$this->vars['lib_dir'].$this->vars['services_dir'];
                        $namespace = ucfirst($bundle).'\Services\\';

                    }
                }

                if (!$namespace){
                    $namespace = '\webtFramework\Services\\';
                }

				if ($controller_dir){

					if (strpos($controller_dir, 'lib'.WEBT_DS) === false)
						$app_file = $this->vars['FW_DIR'].$this->vars['lib_dir'].$controller_dir.$app_name.'.inc.php';
					else
						$app_file = $controller_dir.$app_name.'.inc.php';
				} else {
					$app_file = $this->vars['FW_DIR'].$this->vars['lib_dir'].$this->vars['services_dir'].$app_name.'.inc.php';
				}

			} elseif (is_array($controller)){
				// for completed array with data
				$app_name = $controller['name'];
				$app_file = $controller['parser_name'];
			}

			if ($app_name && $app_file){

				require_once($app_file);

				if (!$params['no_construct']){
                    $namespace = isset($params['namespace']) ? $params['namespace'] : $namespace;
                    if (!$params['no_cache']){
                        $app_name = $namespace.$app_name;
                        $this->_cached_controls[$controller] = new $app_name($this);
                        return $this->_cached_controls[$controller];
                    } else {
                        $app_name = $namespace.$app_name;
                        return new $app_name($this);
                    }
				}

			}

		} catch (\Exception $e){

			if (is_object($this->debug)){
				$message = "CORE: Error loading controller : ".$controller.". Details: ".$e->getMessage();
                if ($this->vars['is_debug'])
                    $this->debug->add($message, array('error' => true));

                $this->debug->log($message, 'error');
			}

            throw new \Exception($e->getMessage(), $e->getCode());

		}

        return true;
	}



}
