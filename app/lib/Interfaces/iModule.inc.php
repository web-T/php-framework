<?php

namespace webtFramework\Interfaces;

/**
 * Interface for web-T::CMS modules
 *
 * @version 2.1
 * @author goshi
 * @package web-T[CMS]
 *
 */

/**
 * TODO: refactor to universal storage
 */

use webtFramework\Core\oPortal;
use webtFramework\Core\webtSharedMemory;


/**
* @package web-T[CMS]
*/
interface iModuleInterface{

	public function AddParams($params = array());
	public function saveData($data);
	public function removeData($params = false);
	public function getElems($elem_ids, $params = false);
	public function getElemsAjx($elem_ids, $params = false);
	public function getData($params = false);
	public function getCount($params = false);
	
}

/**
* @package web-T[CMS]
*/
class oModule extends oBase implements iModuleInterface{

    /**
     * @var string root dir for this module
     */
    protected $_ROOT_DIR = '';

    /**
     * @var string resources diretory
     */
    protected $_RES_DIR = '';

    /**
     * @var string templates directory
     */
    protected $_SKIN_DIR = '';

    /**
     * templates directory
     * @var string
     */
    protected $_TPLS_DIR = '';

    /**
     * @var string css's directory
     */
    protected $_CSS_DIR = '';

    /**
     * @var string javascripts directory
     */
    protected $_JS_DIR = '';

    /**
     * @var string module's plugins directory
     */
    protected $_PLUGINS_DIR = '';

    /**
     * @var string cache directory
     */
    protected $_CACHE_DIR			=	'';

    /**
     * @var null|string table name of linked element
     */
    protected $_tbl_name			=	'';

    /**
     * @var string|integer entity identifier
     */
    protected $_elem_id			=	'';

    /**
     * @var null|string links table name
     */
    protected $_lnk_table			=	'';

    /**
     * @var string entities table name
     */
    protected $_work_tbl			=	'';

    /**
     * @var string entitie's model name
     */
    protected $_model			=	'';

    /**
     * @var null config array for module's plugins
     */
    protected $_config				=	null;

    /**
     * @var bool flag for compressing cache file
     */
    protected $_is_cache_compress	=	true;

	/*
	* plugins rights in format
	*	'name_of_plugin' => array('apply' => array('all' => 1, 'auth' => 1),
	*			'prepare' => array('all' => 0, 'auth' => 1))
	*/
	protected $_allowed_plugins = array();


	public function __construct(oPortal &$p, $params = array()){

        if ($p->getVar('is_debug'))
            $p->debug->add(get_class($this).": before construct");

        $this->_lnk_table = $p->getVar('tbl_linker');

        parent::__construct($p, $params);

        $cname = $this->extractClassname();

        $this->_ROOT_DIR = $this->_getRootDirByClassName(get_class($this));

        $this->_TPLS_DIR = $this->_ROOT_DIR.$this->_p->getVar('templator')['dir'].WEBT_DS;

		$skin_dir = 'skin/';
        $this->_RES_DIR = $skin_dir.'/'.$p->getVar('modules_dir').$cname.'/'.$skin_dir;
		$this->_SKIN_DIR = $p->getVar('DOC_DIR').$this->_RES_DIR;
		$this->_CSS_DIR = $skin_dir.'/'.$p->getVar('modules_dir').$cname.'/'.$p->getVar('css_dir');
		$this->_JS_DIR = $p->getVar('DOC_DIR').$skin_dir.'/'.$p->getVar('modules_dir').$p->getVar('lib_js_dir');
		$this->_CACHE_DIR = $p->getVar('cache')['modules_dir'].$cname.WEBT_DS;

		// if not setted plugin directory in module - setup standart
		if ($this->_PLUGINS_DIR == '')
			$this->_PLUGINS_DIR = $p->getVar('lib_dir').$p->getVar('plugins_dir');

		if ($this->_p->getVar('is_debug'))
			$this->_p->debug->add(get_class($this).": before loadConfig");

        // checking for model and get it parameters to themself
        if ($this->_model && !$this->_tbl_name){
            /**
             * @var $tmp_model oModel
             */
            $tmp_model = $this->_p->Model($this->_model);
            if ($tmp_model){
                $this->_tbl_name = $tmp_model->getModelTable();
                if (isset($this->_multilang))
                    $this->_multilang = $tmp_model->getIsMultilang();
            }
            unset($tmp_model);
        }

		// load config file
		$this->loadConfig();

		if ($this->_p->getVar('is_debug'))
			$this->_p->debug->add(get_class($this).": after loadConfig");

	}

    /**
     * method create path from classname
     * @param null $class_name
     * @return null|string
     */
    protected function _getRootDirByClassName($class_name = null){

        $result = null;

        if ($class_name){

            $class_name = explode('\\', $class_name);

            if ($class_name[0] == 'webtFramework'){
                $result = $this->_p->getVar('FW_DIR').$this->_p->getVar('lib_dir').$this->_p->getVar('modules_dir').$class_name[count($class_name)-1].WEBT_DS;
            } else {

                if (file_exists($this->_p->getVar('bundles_dir').$class_name[0]))
                    $result = $this->_p->getVar('bundles_dir').$class_name[0];
                else
                    $result = $this->_p->getVar('bundles_dir').mb_strtolower($class_name[0]);

                $result .= WEBT_DS.$this->_p->getVar('lib_dir').$this->_p->getVar('modules_dir').$class_name[count($class_name)-1].WEBT_DS;
            }

        }

        return $result;

    }

    /**
     * method removes data of selected entity
     * @param array $params
     * @return bool
     */
    public function removeData($params = array()){

		if ($params)
			$this->AddParams($params);

		if (!$this->_elem_id || !$this->_tbl_name)
			return false;

        $qb = $this->_p->db->getQueryBuilder();
        $model = $qb->createModel($this->_lnk_table);

        $sql = $qb->compileDelete($model, array('where' => array(
            'elem_id' => $this->_elem_id,
            'tbl_name' => $this->_tbl_name
        )));
        $this->_p->db->query($sql);

        unset($qb);
        unset($model);
        unset($sql);

        return true;

	}

	/**
	* gets additional conditions for collect entities
	*/
	protected function _getSqlIds(){

		if (is_array($this->_elem_id))
            return array('op' => 'in', 'value' => $this->_elem_id);
		else
            return array('op' => '=', 'value' => $this->_elem_id);

	}

    /**
     * methods save modules data
     * @param $data
     * @return bool
     */
    public function saveData($data){

		if (!$this->_elem_id || !$this->_tbl_name)
			return false;

		$this->removeData();

		// saving linked elems
		if ($data){

			// unescape all chars
			$arr_elems = array();
            if (!is_array($data)){
			    parse_str($data, $arr_elems);
            } else {
                $arr_elems = $data;
            }

			if ($arr_elems && !empty($arr_elems)){

                $qb = $this->_p->db->getQueryBuilder();
                $model = $qb->createModel($this->_lnk_table);

                $datas = array();

				foreach ($arr_elems as $k => $v){

                    unset($v[$model->getPrimaryKey()]);
                    $v['this_id'] = $k;
                    $v['elem_id'] = $this->_elem_id;
                    $v['tbl_name'] = $this->_tbl_name;

                    $datas[] = $v;

				}

                $sql = $qb->compileInsert($model, $datas, true);

                $this->_p->db->query($sql, $model->getModelStorage());

                unset($qb);
                unset($model);
                unset($datas);
                unset($sql);

			}

		}

        return true;

	}

    /**
     * connect plugins with selected method
     * @param string $method plugin method for using
     * @param null|array $data data for plugin
     * @param bool $virtual
     * @param null $params some parameters for plugin, you can use it for add some callback to caller
     * @return array|null
     */
    protected function _connectPlugins($method, $data = null, $virtual = false, &$params = null){

		$is_auth = $this->_p->user->isAuth();

		if ($data === null)
			$data = array();

		require_once($this->_p->getVar('FW_DIR').$this->_p->getVar('lib_dir').$this->_p->getVar('interfaces_dir').'iPlugin.inc.php');

        if (!is_array($this->_PLUGINS_DIR))
            $pdirs = array($this->_PLUGINS_DIR);
        else
            $pdirs = $this->_PLUGINS_DIR;

        foreach ($this->_allowed_plugins as $k => $v){

            // try to find namespace
            $namespace = 'webtCMS';
            $app = $k;
            if (strpos($k, ':') !== false){
                $app = explode(':', $app);
                $namespace = $app[0];
                $app = $app[1];
            }

            // try to find plugin
            $found = false;
            foreach ($pdirs as $pdir){
                if (file_exists($pdir.$app.".plugin.php")){
                    $found = $pdir.$app.".plugin.php";
                    break;
                }
            }

			if ($found){

				require_once($found);
				// check method rules
				if (!($v[$method]['all'] || ($is_auth && $v[$method]['auth'])))
					$method = '_dis'.$method;
				//  for virtual testing - remove method name
				elseif ($virtual)
					$method = '';

				// check - if method callable
				if (is_callable(array('\\'.$namespace.'\Plugins\\'.$app."Plugin", $method))){
					$plugin_name = '\\'.$namespace.'\Plugins\\'.$app."Plugin";
					$plugin = new $plugin_name($this->_p, $params, $this);
					if ($data === null)
						$data[$k] = $plugin->$method($data, $params);
					else
						$data = $plugin->$method($data, $params);
					unset($plugin);
				}

			}

		}

		return $data;

	}


    /**
     * method duplicates linked data
     * @param $data
     * @param array $params
     * @return bool
     */
    public function duplicateData($data, $params = array()){

		if ($params)
			$this->AddParams($params);

		if (!$this->_elem_id || !$this->_tbl_name || !$data['to'])
			return false;

		$ids = $this->getElemLinkedIds(array_merge((array)$params, array('full_data' => true)));

		if (!empty($ids)){
			// turn new id
			$this->_elem_id = $data['to'];

            foreach ($ids as $k => $v){
                unset($ids[$k]['id']);
            }

			$this->saveData($ids);

            unset($result);
            unset($ids);

			return true;
		}

		return false;

	}


    /**
     * method extract modules entities into view
     * you need to overwrite it
     * @param $elem_ids
     * @param array $params
     * @return bool|array
     */
    public function getElems($elem_ids, $params = array()){

		if (!$elem_ids)
			return false;

		if ($params)
			$this->AddParams($params);

		// you must override this method

        return false;

	}


	/**
	 * method extracts modules entities view and return it with ajax response
	 *
	 * input parameters - see getElems
	 */
	public function getElemsAjx($elem_id, $params = array()){

        $this->_p->response->send(array(
			"content"   => $this->getElems($elem_id, $params)
		), null, CT_AJAX);

	}
	
    /**
     * get linked ids for one entity
     * @param bool|array $params
     * @return array
     */
    public function getElemLinkedIds($params = false){

        $ids = $this->getLinkedIds($params);

        if ($ids){
            foreach ($ids as $elem_id => $weights){
                foreach ($weights as $weight => $id){
                    $ids[$elem_id][$weight] = is_array($id) ? current($id) : $id;
                }
            }
        }

        return $ids;

    }

    /**
     * method return linked ids by _tbl_name and _elem_id
     * @param array $params
     * @return array
     */
    public function getLinkedIds($params = array()){

		if ($params)
			$this->AddParams($params);

        $tmp = array();

		if ($this->_tbl_name && $this->_elem_id){

            $qb = $this->_p->db->getQueryBuilder();
            $model = $qb->createModel($this->_lnk_table);

            $conditions = array(
                'where' => array(
                    'elem_id' => $this->_getSqlIds(),
                    'tbl_name' => $this->_tbl_name,
                ),
                'order' => array('weight' => 'asc'),
                'no_array_key' => true,
            );

            if (!$params['full_data']){
                $conditions['select']['a'] = array('elem_id', 'this_id', 'weight');
            }

			if ($this->_lnk_table == $this->_p->getVar('tbl_linker')){
                $conditions['where']['this_tbl_name'] = $this->_work_tbl;
			}

            $sql = $qb->compile($model, $conditions);

			$res = $this->_p->db->select($sql, $model->getModelStorage());

			$tmp = array();

			if ($res){

				foreach ($res as $arr){
					// making reverse array
					if ($params['full_data']){

						$tmp[$arr['this_id']] = $arr;

					} else {

						if ($params['reverse'])
							$tmp[$arr['elem_id']][$arr['weight']][] = $arr['this_id'];
						else {
							$tmp[$arr['this_id']][$arr['weight']][] = $arr['elem_id'];
                        }
					}

				}

			}

            unset($qb);
            unset($model);

		}

		return $tmp;

	}

    /**
     * method must be called after get entities from database
     * it is makes post process of the entities
     * @param $elem
     * @return mixed
     */
    protected function _prepareElem($elem){

        return $elem;

    }


	/**
	 * main enter point for the module
     * you need to write rounting logic in it
     *
     * @param array $params
	 * @return array|boolean
	 * @access public
	*/
	public function getData($params = array()){

		$ids = $this->getLinkedIds($params);
		if ($ids){

			return $this->getElems($ids, $params);

		}

		return false;

	}

    /**
     * get view of the selected entity
     * @param array $params
     * @return bool
     */
    public function getTemplate($params = array()){

		if ($params)
			$this->AddParams($params);

		return false;

	}

    /**
     * get count of the linked entities
     * @param array $params
     * @return array|bool|null|void array of elem_id => count elements
     */
    public function getCount($params = array()){

		if ($params)
			$this->AddParams($params);

		if ($this->_tbl_name && $this->_elem_id){

            $qb = $this->_p->db->getQueryBuilder();

            $model = $qb->createModel($this->_lnk_table);

            $sql = $qb->compile($model, array(
                'select' => array('__groupkey__' => 'elem_id', 'a' => array(array('nick' => 'count', 'function' => 'count()', 'field' => '*'))),
                'where' => array('elem_id' => $this->_getSqlIds(), 'tbl_name' => $this->_tbl_name),
                'group' => array('elem_id'),
            ));

			$res = $this->_p->db->select($sql, $model->getModelStorage());

			if ($res){

				return $res;

			} else return false;

		}

        return false;

	}

    /**
     * method returns entities by their ids
     *
     * @param array|int $ids
     * @param array $params
     * @return array|null
     */
    public function getById($ids, $params = array()){

        $elems = $this->getElems($ids, $params);

        $result = null;

        if ($elems){

            $result = array();

            // transform data
            foreach ($elems as $k => $v){
                $result[$k] = $v[0];
            }

            unset($elems);

            if (!is_array($ids)){
                $result = $result[$ids];
            }

        }

        return $result;

    }



    /**
     * method returns admin form of the module
     * @param bool|array $params
     * @return bool
     */
    public function getAdminForm($params = false){

		if ($params)
			$this->AddParams($params);

		return false;

	}


	/**
	* method loads config file
	*/
	public function loadConfig(){

		if (file_exists($this->_ROOT_DIR.$this->_p->getVar('config_dir').'config.inc.php')){
			require_once($this->_ROOT_DIR.$this->_p->getVar('config_dir').'config.inc.php');
			$conf = 'config_'.$this->extractClassname();
			$this->_config = $$conf;
		}
	}

    /**
     * getter for current driver config
     * @param null $driver
     * @return null
     */
    public function getConfig($driver = null){

		if ($driver)
			return $this->_config[$driver];
		else
			return $this->_config;

	}

    /**
     * method saves module data into cache
     *
     * @param array $target directories for saving - may be parsed URL
     * @param string $filename name of the result file at directory tree
     * @param mixed $content content for saving
     * @param bool $is_append
     * @return bool|string string with path to created file
     */
    public function saveCache($target, $filename, $content, $is_append = false){

		if (!$target)
			$target = array('tbl_name' => $this->_tbl_name, 'id' => $this->_elem_id);

		if (isset($target['tbl_name']))
			$target['tbl_name'] = str_replace($this->_p->getVar('tbl_prefix'), '', $target['tbl_name']);
        elseif (isset($params['model']) && ($params['model'] = $this->_p->Model($params['model']))){
            $params['tbl_name'] = str_replace($this->_p->getVar('tbl_prefix'), '', $params['model']->getModelTable());
            unset($params['model']);
        }

		if (!$filename) $filename = 'index.html';

		// walk through directory tree
		// protection
		if (!is_array($target))
			return false;

		//making cache dir
		if (!is_dir($this->_CACHE_DIR))
            $this->_p->filesystem->rmkdir($this->_CACHE_DIR);

		// base string for walking
		$base = join(WEBT_DS, $target).WEBT_DS;

        if (!$this->_p->filesystem->rmkdir($base))
            return false;

		// adding last slash to the cache dir
		if ($base == '')
			$base = WEBT_DS;

		// save content in file
		$fh = fopen($this->_CACHE_DIR.$base.$filename, $is_append ? 'a' : 'w');
		if ($fh){
			fwrite($fh, !is_string($content) ? json_encode($content) : $content);
			fclose($fh);
			chmod($this->_CACHE_DIR.WEBT_DS.$base.$filename, PERM_FILES);
		}

		// gzip content
		if ($this->_is_cache_compress){
			$this->_p->filesystem->gzip(
                $this->_CACHE_DIR.$base.$filename,
				$this->_CACHE_DIR.$base.$filename.'.gz'
            );
		}
		return $base;

	}


    /**
     * method clears cached data
     * @param array $params
     * @param string $filename
     * @return bool|string
     */
    public function getCache($params = array(), $filename = 'index.html'){

		empty($params) ? $params = array('tbl_name' => $this->_tbl_name, 'id' => $this->_elem_id) : null;

		if (!empty($params['tbl_name']) && !empty($params['id'])){

			if (isset($params['tbl_name']))
				$params['tbl_name'] = str_replace($this->_p->getVar('tbl_prefix'), '', $params['tbl_name']);
            elseif (isset($params['model']) && ($params['model'] = $this->_p->Model($params['model']))){
                $params['tbl_name'] = str_replace($this->_p->getVar('tbl_prefix'), '', $params['model']->getModelTable());
                unset($params['model']);
            }

			// preparing alias
			$alias = join(WEBT_DS, array_values($params)).WEBT_DS;

			// first - checking in sharedmemory
			$shmem = webtSharedMemory::factory($this->_p, 'memcache');

			if ($shmem->isConnected() && ($content = $shmem->get($this->_p->query->get()->getDomain().$this->_CACHE_DIR.WEBT_DS.$alias.$filename))){
				Header('X-Memcache: get');
				return $content;
			} elseif (file_exists($this->_CACHE_DIR.WEBT_DS.$alias.$filename)) {
				return file_get_contents($this->_CACHE_DIR.WEBT_DS.$alias.$filename);

			}

		} return false;
	}

    /**
     * clear cache data
     * @param array|null $params
     */
    public function removeCache($params = null){

		if ($params && is_array($params)){

			if (isset($params['tbl_name']))
				$params['tbl_name'] = str_replace($this->_p->getVar('tbl_prefix'), '', $params['tbl_name']);
            elseif (isset($params['model']) && ($params['model'] = $this->_p->Model($params['model']))){
                $params['tbl_name'] = str_replace($this->_p->getVar('tbl_prefix'), '', $params['model']->getModelTable());
                unset($params['model']);
            }

			// preparing alias
			$alias = join(WEBT_DS, array_values($params)).WEBT_DS;
			// delete only from directory with this alias
			if (file_exists($this->_CACHE_DIR.$alias))
                $this->_p->filesystem->removeFilesFromDir($this->_CACHE_DIR.$alias, true);
			@rmdir($this->_CACHE_DIR.$alias);

		} else {

            $this->_p->filesystem->removeFilesFromDir($this->_CACHE_DIR, true);

		}

	}


}
