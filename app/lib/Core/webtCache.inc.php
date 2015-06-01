<?php

/**
* Core class for cache system
* @version 3.0
* @author goshi
* @package web-T[CORE]
*/

namespace webtFramework\Core;

use webtFramework\Components\Event\oEvent;
use webtFramework\Components\Request\oQuery;

/**
* @package web-T[CORE]
*/
class webtCache{

	/**
	 * @var oPortal
	 */
	protected $_p;


	/*protected $_metadata = array(
		'expire' => 0,
		'tags' => array());
	*/

    /**
     * global tags list for caching
     * @var array
     */
    protected $_tags = array();

    /**
     * directory permissions
     * @var int
     */
    protected $_rules_mod = PERM_DIRS;


    /**
     * serializers cache
     * @var array
     */
    protected $_serializers = array();

    /**
     * cachestorages
     * @var array
     */
    protected $_storages = array();
	
	public function __construct(oPortal &$p){

		$this->_p = $p;

	}


    /**
     * get cache storage instance
     * @param $storage
     * @return \webtFramework\Components\Cache\Storage\oCacheStorageAbstract
     * @throws \Exception
     */
    protected function _getCacheStorageInstance($storage){

        if ($storage && isset($this->_storages[$storage])){

            return $this->_storages[$storage];

        } elseif ($storage && class_exists('\\webtFramework\\Components\\Cache\\Storage\oCacheStorage'.ucfirst($storage))) {

            $class = '\\webtFramework\\Components\\Cache\\Storage\oCacheStorage'.ucfirst($storage);

            try {

                $this->_storages[$storage] = new $class($this->_p);

            } catch (\Exception $e){

                // fallback to filebased storage
                $class = '\\webtFramework\\Components\\Cache\\Storage\oCacheStorageFiles';
                $this->_storages[$storage] = new $class($this->_p);

            }

            return $this->_storages[$storage];


        } else {

            throw new \Exception('errors.cache.cache_storage_not_found');

        }

    }

		
    /**
     * function find query page in cache
     *
     * @param array|oQuery $query
     * @param array $params consists of 'cache_type' (type of the cache (1 - static, 2 - with timeout, 3 - with definite time of update))
     *                      'timeout' timeout for this page for selected type of caching
     *                      'use_sess_id' flag to get session id (if not set flag for anonymous user)
     * @return bool
     */
    public function find($query, $params = array()){
			
		$params['cache_type'] = $params['cache_type'] ? (int)$params['cache_type'] : WEBT_CACHE_TYPE_STATIC;

		$str = $this->_query2str($query, $params['use_sess_id']);
		$tmp_name = $this->_str2hash($str);
		$filename = $this->_hash2path($tmp_name);

		if ($params['cache_type'] == WEBT_CACHE_TYPE_UPDATETIME){
			$tmp_time = getdate_new($params['timeout']);
			$cache_update = mktime($tmp_time['hours'], $tmp_time['minutes'], 0, date("m", $this->_p->getTime()), date("d", $this->_p->getTime()), date("Y", $this->_p->getTime()));
		} else
            $cache_update = 0;

		// checking for shmem using
		$mtime = $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->exists($filename);

		if ($mtime){
			if (!$params['timeout'] || 
				($params['cache_type'] == WEBT_CACHE_TYPE_LIFETIME && $params['timeout'] && ($this->_p->getTime() - $mtime <= $params['timeout'])) ||
				($params['cache_type'] == WEBT_CACHE_TYPE_UPDATETIME && $params['timeout'] && !($this->_p->getTime() >= $cache_update && $mtime < $cache_update))){

            // if we have found page
				return true;
			
			} else {
			// if timeout - delete page
                $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->remove($filename);

                $m = $this->_p->Model($this->_p->getVar('cache')['cache_model']);
				$this->_p->db->query($this->_p->db->getQueryBuilder($m->getModelStorage())->compileDelete($m, array('where' => array('filename' => $tmp_name))), $m->getModelStorage());

                unset($m);
				return false;
			
			}			

		} else {

			return false;
		
		}
		
				
	}
	
    /**
     * save query page in cache
     *
     * @param array|oQuery $query saving query
     * @param string $data body of the page
     * @param array $params
     * @return array|bool|null|void
     */
    public function save($query, $data, $params = array()){
			
		$str = $this->_query2str($query, $params['use_sess_id']);
		$tmp_name = $this->_str2hash($str);
		$filename = $this->_hash2path($tmp_name);
		
        $is_skeleton_exists = (int)($this->_p->tpl->getMainTemplate() && strlen($this->_p->tpl->getSrc($this->_p->tpl->getMainTemplate()))>0);

		$cache = array(
			'query' => $str,
			'page_content' => $data,
			'filename' => $tmp_name,
			'smphr' => (int)$this->_p->query->get()->get('smphr'),
			'is_skeleton' => $is_skeleton_exists,
            'skeleton' => $is_skeleton_exists ? $this->_p->tpl->getMainTemplate() : null,
			'static' => $params['static'],
			'date_add' => $this->_p->getTime(),
			'find_title' => $this->_p->getVar('find_title'),
			'page_title' => !empty($params['find_title']) ? $params['find_title'] : $params['page_title'],
			'find_descr' => !empty($params['find_descr']) ? $params['find_descr'] : '',
			'find_keywords' => !empty($params['find_keywords']) ? $params['find_keywords'] : '',
			'is_find_index' => $params['is_find_index'],
			'is_find_follow' => $params['is_find_follow'],
			'is_find_archive' => $params['is_find_archive'],
			'last_modified' => !empty($params['last_modified']) ? $params['last_modified'] : $this->_p->getTime(),
			'og' => $this->_p->getVar('og'),	// saving OpenGraph data
			);

        $model = $this->_p->Model($this->_p->getVar('cache')['cache_model']);
        $this->_p->db->query($this->_p->db->getQueryBuilder($model->getModelStorage())->compileDelete($model, array('where' => array('filename' => $tmp_name))), $model->getModelStorage());

        $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->save($filename, $cache);

		// adding tags
		if (!empty($params['tags']) || !empty($this->_add_cache_tag))
			$this->_setTags(array_merge((array)$params['tags'], (array)$this->_tags), basename($filename));

		// add protection from ob handlers, when global object DB is not visible (it is using in JsHttpRequest caching)
		if ($this->_p->db != null){

            // saving in DB
            $model->setModelData(array(
                'query' => $str,
                'filename' => $tmp_name,
                'page_type' => $this->_p->query->get()->getContentType(),
                'date_add' => $this->_p->getTime(),
                'last_modified' => $this->_p->getTime(),
                'hits' => 1
            ));
            $em = $this->_p->db->getManager();

            $id = $em->initPrimaryValue($model);
            $em->save($model);

            return $id;

        } else
			return false;
		
		
	}
	
    /**
     * saving page in static cache
     * @param array $target directories for saving - may be parsed URL
     * @param string $filename name of the result file at directory tree
     * @param string $content content for saving
     * @param array $params
     * @return bool|string string with path to created file
     */
    public function saveStatic($target, $filename, $content, $params = array()){
	
		if (!is_array($target))
			$target = explode('/', $target);

	
		// walk through directory tree
		// protection
		if (!is_array($target))
			return false;
		
		// base string for walking
		$base = '';

		// adding subdomain and domain part
		if ($this->_p->query->get()->getSubdomain()){
			if (!is_dir($this->_p->getVar('cache')['static_dir'].$this->_p->query->get()->getServerName()) &&
                !mkdir($this->_p->getVar('cache')['static_dir'].$this->_p->query->get()->getServerName(), $this->_rules_mod)){
				return false;
			}
			$base = $this->_p->query->get()->getServerName().'/'.$base;
			
		} elseif ($this->_p->getVar('server_name')){
			if (!$this->_p->filesystem->rmkdir($this->_p->getVar('cache')['static_dir'].$this->_p->getVar('server_name'))){
				return false;
			}
			$base = $this->_p->getVar('server_name').'/'.$base;
		
		}
		if ($base != '')
			@chmod($this->_p->getVar('cache')['static_dir'].$base, $this->_rules_mod);

		
		foreach ($target as $k) {
			
			if ($k != ''){
				if (!in_array($k, $this->_p->getVar('doc_types'))){
					$base .= $k.'/';
		        }
			}	
		}

        if (!$this->_p->filesystem->rmkdir($base, $this->_rules_mod)){
            return false;
        }
		
		// save content into a file

		$ext = $this->_p->filesystem->getFileExtension($filename);
		$file = $this->_p->getVar('cache')['static_dir'].$base.$filename.(isset($params['use_sess_id']) && $this->_p->user->isAuth() && $params['use_sess_id'] && $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']) ? '-'.$this->_p->cookie->get($this->_p->getVar('user')['session_cookie']).'.'.$ext : '');
		
		$this->_p->filesystem->writeData($file, $content, "wb", $this->_rules_mod);

        // copy file to master server
        $this->_p->api->callMaster('filesPut', array('path' => $file), array('file' => $file ? '@'.realpath($file) : null), 'post');


        // saving gzipped version
        if ($this->_p->getVar('cache')['gzip_static_cache']){

            $gz_file = './'.$this->_p->getVar('cache')['static_dir'].$base.$filename.(isset($params['use_sess_id']) && $this->_p->user->isAuth() && $params['use_sess_id'] && $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']) ? '.gz'.'-'.$this->_p->cookie->get($this->_p->getVar('user')['session_cookie']).'.'.$ext : '').'.gz';
            $this->_p->filesystem->gzip(null, $gz_file, $content);

            // copy file to master server
            $this->_p->api->callMaster('filesPut', array('path' => $gz_file), array('file' => $gz_file ? '@'.realpath($gz_file) : null), 'post');

        }




        return $base;
	
	}


    /**
     * remove static page from cache
     * @param $page
     */
    public function removeStaticPage($page){

        $this->_p->filesystem->removeFilesFromDir($this->_p->getVar('cache')['static_dir'].$page, ($page != $this->_p->getVar('server_name').'/' ? true : false));

        // recursively delete from other instances
        if ($this->_p->isMainInstance()){

            $this->_p->api->callExceptMe('cacheRemoveStaticPage', array('value' => $page), null, 'post', 'json', array('async' => true));

        }

	}

    /**
     * get queried page from cache
     * Because we can't parse page again and don't know what page title we must get it from cache
     * @param array|oQuery $query query page
     * @param array $params[option] consists of 'get_headers' (flag to get page headers in array) and 'use_sess_id' (flag to get session id (if not set flag for anonymous user))
     * @return bool|mixed
     */
    public function get($query, $params = array()){
		
		$str = $this->_query2str($query, $params['use_sess_id']);
		$tmp_name = $this->_str2hash($str);
		$filename = $this->_hash2path($tmp_name);

        $h_arr = $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->get($filename);

        if ($h_arr){
            if (isset($params['get_headers']) && !empty($params['get_headers'])){
                $h_arr['last_modified'] = $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->exists($filename);
            } else {
                $h_arr['last_modified'] = $this->_p->getTime();
            }
        }

		return $h_arr;
				
	}

    /**
     * clear pages cache from selected page and clip (it means that this element have changed)
     * page can be removed recursively - if yo set highest level (only page name) - it will remove all subpages
     *
     * @param array|oQuery $query array of query for cleaning
     * @param array $params consists of:
     *      bool $is_strict[option] flag, that means the query is strict and no regular expression needed (but it clear _clip and _page!!!)
     *      bool $no_link_add[option] when removing - no add link_add parameters
     *      bool $use_sess_id[option] when removing - use session id of the user
     * @return $this;
     */
    public function removeData($query, $params = array()){

		// first of all remove by tags - because it is more important to remove operative this cache
		if (!empty($params['tags'])){
			$this->_removeByTag($params['tags']);
		}

        $model = $this->_p->Model($this->_p->getVar('cache')['cache_model']);
        $qb = $this->_p->db->getQueryBuilder($model->getModelStorage());

		// making query string (RLIKE in use)
		if (!$params['is_strict']){
		
			$sfields = array('query');
			
			// check type of the query
            if (is_array($query) || $query instanceof oQuery)
				$str = $this->_p->query->buildStat($query, true, $params['no_link_add']);
			else {
				$str = $query;
				$this->_p->query->parseStat($query);
			}
			
			$keyarray = array($str);
			
			$maked_fields = $qb->compileSearch($model, $sfields, $keyarray);

			
		} else {
		
			// check type of the query
            if (is_array($query) || $query instanceof oQuery)
				$str = $this->_p->query->buildStat($query, false, $params['no_link_add']);
			else {
				$str = $query;
				$this->_p->query->parseStat($query);
			}
			
			// check anonymous session and flag for using session id
			if ($params['use_sess_id'] && $this->_p->user->isAuth()){
				
				$str .= '?'.$this->_p->user->getId();
				
			}
			
			$maked_fields = array('field' => 'query', 'value' => $str);

		}

		// first parse stat query
        if ($maked_fields){

            $sql = $qb->compile($model, array('where' => array($maked_fields)));

            $result = $this->_p->db->select($sql, $model->getModelStorage());
        } else
            $result = null;

		// if we have found some pages - delete them and clear from DB
		if ($result){
		
			$filenames = array();
			
			// always cleared array
			$always_cleared = array('static' => array());
			foreach ($result as $arr){
				
				$filename = $this->_hash2path($arr['filename']);
				$filenames[] = $filename;

                $data = $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->get($filename);
                $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->remove($filename);

				if (!empty($data['static']) && !isset($always_cleared['static'][$data['static']])){
					$this->removeStaticPage($data['static']);
					$always_cleared['static'][$data['static']] = 1;
				}
				unset($data);
			}
			
			$this->_p->db->query($qb->compileDelete($model, array('where' => array('filename' => array('op' => 'in', 'value' => $filenames)))), $model->getModelStorage());

		}

        unset($model);
        unset($qb);

        return $this;

	}

    /**
     * remove page/clips from cache
     * @param array|string $nick_arr array of arrays of nicks
     * @param array	$params consists of
     *  boolean [directly] flag for deleting cache directly, default = false
     *  boolean [is_strict] flag for deleting cache strictly, default = false
     *  boolean	[no_link_add] flag for deleting cache with no adding link_add such lang_id
     *  boolean	[use_sess_id] flag for use sesssion id
     * @return bool
     */
    public function remove($nick_arr, $params = array()){
		
		// check for empty nick array
		if (empty($nick_arr)) return false;		
		
		// if array - making foreach statement
		$is_array = false;
		if (is_array($nick_arr)){
			reset($nick_arr);
			list(, $first) = each($nick_arr);
			if (is_array($first))
				$is_array = true;
		}	
		
		if ($is_array){
			foreach($nick_arr as $v){
				
				$this->removeData($v['query'], array_merge($params, array('no_link_add' => true)));
				
			}			
		} else {
		
			$this->removeData(is_array($nick_arr) ? $nick_arr : array($nick_arr => ''), $params);
		
		}

        // recursively delete from other instances
        if ($this->_p->isMainInstance()){

            $this->_p->api->callExceptMe('cacheRemovePage', array('value' => $nick_arr, 'params' => $params), null, 'post', 'json', array('async' => true));

        }

        return true;
	}
	
	/**
	 * function clear page cache
	 *
 	 * @param array $modes array of modes for clearing cache - can consists of 'data', 'serial', 'queries', 'static', 'meta'
	*/
	public function clear($modes = array('data', 'static', 'queries', 'serial', 'meta')){
	
		if (in_array('data', $modes)){
	
            $cm = $this->_p->Model($this->_p->getVar('cache')['cache_model']);
            $cmt = $this->_p->Model($this->_p->getVar('cache')['cache_tag_model']);

			$this->_p->db->query($this->_p->db->getQueryBuilder($cm->getModelStorage())->compileTruncate($cm), $cm->getModelStorage());
			$this->_p->db->query($this->_p->db->getQueryBuilder($cmt->getModelStorage())->compileTruncate($cmt), $cmt->getModelStorage());

            $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->removeAll($this->_p->getVar('cache')['data_dir']);

            $this->_p->events->dispatch(new oEvent(
                    WEBT_CORE_CACHE_DATA_CLEAR,
                    $this)
            );

		}
		
		if (in_array('static', $modes)){
					
			// foreach directory in cache dir - make delete recursive
			$dir = './'.$this->_p->getVar('cache')['static_dir'];
            $this->_p->filesystem->removeFilesFromDir($dir, true);

            $this->_p->events->dispatch(new oEvent(
                    WEBT_CORE_CACHE_STATIC_CLEAR,
                    $this)
            );

		}
		
		if (in_array('queries', $modes)){
					
			// foreach directory in cache dir - make delete recursive
			$dir = $this->_p->getVar('cache')['queries_dir'];
            $this->_p->filesystem->removeFilesFromDir($dir, true);

            $this->_p->events->dispatch(new oEvent(
                    WEBT_CORE_CACHE_QUERIES_CLEAR,
                    $this)
            );

		}
		
		if (in_array('serial', $modes)){
					
			// removing serialization
			$this->removeSerial();

            $this->_p->events->dispatch(new oEvent(
                    WEBT_CORE_CACHE_SERIAL_CLEAR,
                    $this)
            );

		}

		if (in_array('meta', $modes)){

			// removing meta cache
			$this->removeMeta();

            $this->_p->events->dispatch(new oEvent(
                    WEBT_CORE_CACHE_META_CLEAR,
                    $this)
            );

		}

        if (in_array('modules', $modes)){

            // removing modules cache
            $this->_p->filesystem->removeFilesFromDir($this->_p->getVar('cache')['modules_dir'], true);

            $this->_p->events->dispatch(new oEvent(
                    WEBT_CORE_CACHE_MODULES_CLEAR,
                    $this)
            );

        }

        // recursively delete from other instances
        if ($this->_p->isMainInstance()){

            $this->_p->api->callExceptMe('cacheClear', array('value' => $modes), null, 'post', 'json', array('async' => true));


        }
		
	}
	
	
	/**
	* getting info about cache state
	*
	* @param array $modes array of modes for clearing cache - can consists of 'data', 'shmem', 'serial', 'queries', 'static',  'modules'
	* @return array return array with selected cache types	
	*/
	public function info($modes = array('data', 'static', 'queries', 'serial', 'meta', 'modules')){
	
		$info = array();
	
		if (in_array('data', $modes)){
			$info['data'] = $this->_getCacheStorageInstance($this->_p->getVar('cache')['data_storage'])->getInfo($this->_p->getVar('cache')['data_dir']);
			
		}
		
		if (in_array('static', $modes)){
					
			$info['static'] = $this->_p->filesystem->getFilesCount($this->_p->getVar('cache')['static_dir'], true, true);
		}
		
		if (in_array('queries', $modes)){
					
			$info['queries'] = $this->_p->filesystem->getFilesCount($this->_p->getVar('cache')['queries_dir'], true, true);
			
		}
		
		if (in_array('serial', $modes)){
		
			$info['serial'] = $this->_getCacheStorageInstance($this->_p->getVar('cache')['serial_storage'])->getInfo($this->_p->getVar('cache')['serial_dir']);
		
		}

		if (in_array('meta', $modes)){

			$info['meta'] = $this->_getCacheStorageInstance($this->_p->getVar('cache')['meta_storage'])->getInfo($this->_p->getVar('cache')['meta_dir']);

		}

        if (in_array('modules', $modes)){

            $info['modules'] = $this->_p->filesystem->getFilesCount($this->_p->getVar('cache')['modules_dir'], true, true);

        }
		
		return $info;
		
	}

    /**
     * get serializer instance
     * @param $serializer
     * @return \webtFramework\Components\Cache\Serialize\oCacheSerializeAbstract
     * @throws \Exception
     */
    protected function _getSerializerInstance($serializer){

        if (!$serializer)
            $serializer = $this->_p->getVar('cache')['serialize_method'];

        if ($serializer && isset($this->_serializers[$serializer])){

            return $this->_serializers[$serializer];

        } elseif ($serializer && class_exists('\\webtFramework\\Components\\Cache\\Serialize\oCacheSerialize'.ucfirst($serializer))) {

            $class = '\\webtFramework\\Components\\Cache\\Serialize\oCacheSerialize'.ucfirst($serializer);
            $this->_serializers[$serializer] = new $class($this->_p);

            return $this->_serializers[$serializer];

        } else {

            throw new \Exception('errors.cache.serializer_not_found');

        }

    }

    /**
     * return serialized data with selected method
     *
     * @param array $data
     * @param string $method
     * @return mixed|string
     */
    public function serialize($data, $method = ''){

        return $this->_getSerializerInstance($method)->serialize($data);

	}
	
    /**
     * return serialized data by selected methtod
     * @param $data
     * @param string $method
     * @return mixed
     */
    public function unserialize($data, $method = ''){

        return $this->_getSerializerInstance($method)->unserialize($data);

	}
	

    /**
     * save serialized data to cache
     *
     * @param string $fname filename of the file
     * @param string $content content for converting and saving
     * @param string $dir
     * @return bool
     */
    public function saveSerial($fname, $content, $dir = null){

        if (!$dir){
            $dir = $this->_p->getVar('cache')['serial_dir'];
        }

		$tmp_name = $this->_str2hash($fname);

        $filename = '.'.WEBT_DS.$dir.$tmp_name;

        return $this->_getCacheStorageInstance($this->_p->getVar('cache')['serial_storage'])->save($filename, $content);

	}

    /**
     * return serial data from cache
     *
     * @param string $fname
     * @param string $dir[option] directory, in which cache can be found
     * @param int $time seconds after cache expired
     * @return bool|mixed
     */
    public function getSerial($fname, $dir = null, $time = 0){

        if (!$dir){
            $dir = $this->_p->getVar('cache')['serial_dir'];
        }

        $tmp_name = $this->_str2hash($fname);
		$filename = './'.$dir.$tmp_name;
		
		// check for shared memory
        $exists = $this->_getCacheStorageInstance($this->_p->getVar('cache')['serial_storage'])->exists($filename);

        if ($exists){

            if ($time && $this->_p->getTime() - $time > $exists){
                return false;
            }

            return $this->_getCacheStorageInstance($this->_p->getVar('cache')['serial_storage'])->get($filename);

        } else {

            return false;

        }

	}

    /**
     * remove serialized data. If file fname defined - output data, else remove all files from directory
     *
     * @param string|null $fname[option] filename for removing
     * @param string $dir[option] directory name for saving
     * @return webtCache;
     */
    public function removeSerial($fname = null, $dir = null){

        if (!$dir){
            $dir = $this->_p->getVar('cache')['serial_dir'];
        }

		if ($fname){
			// check for shared memory
			$filename = './'.$dir.$this->_str2hash($fname);
            $this->_getCacheStorageInstance($this->_p->getVar('cache')['serial_storage'])->remove($filename);

		} else {

            $this->_getCacheStorageInstance($this->_p->getVar('cache')['serial_storage'])->removeAll('./'.$dir);

		}

        // recursively delete from other instances
        if ($this->_p->isMainInstance()){

            $this->_p->api->callExceptMe('cacheRemoveSerial', array('value' => $fname, 'dir' => $dir), null, 'post', 'json', array('async' => true));

        }

        return $this;

	}
	

    /**
     * add tags to the cached list
     *
     * @param $tag
     */
    public function addTags($tag){
    	if (is_array($tag))
    		$this->_tags = array_merge($this->_tags, $tag);
    	else
	    	$this->_tags[] = $tag;
    }

    /**
     * save tags in cache
     *
     * @param $tags
     * @param $filename
     * @return bool
     */
    protected function _setTags($tags, $filename){
    
    	if (empty($tags)) return false;
    	if (!is_array($tags))
    		$tags = array($tags);
		
		$sql  = array();

        $datas = array();
        foreach ($tags as $v){
            $datas[] = array(
                'tag_crc' => crc16($v),
                'filename_crc' => crc16($filename),
                'tag' => $v,
                'filename' => $filename
            );
        }

        $m = $this->_p->Model($this->_p->getVar('cache')['cache_tag_model']);
        $this->_p->db->query($this->_p->db->getQueryBuilder($m->getModelStorage())->compileInsert($m, $datas, true), $m->getModelStorage());

        unset($tags);    
        unset($sql);

        return true;
    }

    /**
     * remove tags from cache table
     *
     * @param $tags
     */
    protected function _removeByTag($tags){
    
		if (!empty($tags)){
			// converting tags
			if (!is_array($tags))
				$tags = array($tags);
			
            $tags_crc = array();

            foreach ($tags as $v){
                $tags_crc[] = crc16($v);
            }

            $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('cache')['cache_tag_model']);
            $res = $repo->findBy(array(
                'no_array_key' => true,
                'select' => 'filename',
                'where' => array('tag_crc' => array('op' => 'in', 'value' => $tags_crc))
                ),
                $repo::ML_HYDRATION_ARRAY
            );

            if (!empty($res)){
                $always_deleted = array();
                foreach ($res as $v){
                    if (!isset($always_deleted['dynamic'][$v['filename']])){
                        // read data from file
                        $path = $this->_hash2path($v['filename']);

                        $data = unserialize(file_get_contents($path));

                        @unlink($path);
                        if (!empty($data['static']) && !isset($always_deleted[$data['static']])) {
                            $this->removeStaticPage($data['static']);
                            $always_deleted['static'][$data['static']] = 1;
                        }
                        $always_deleted['dynamic'][$v['filename']] = 1;
                        unset($path);
                        unset($data);
                    }
                }
            }
            unset($res);

            $m = $this->_p->Model($this->_p->getVar('cache')['cache_tag_model']);
            $this->_p->db->query($this->_p->db->getQueryBuilder($m->getModelStorage())->compileDelete($m, array('where' => array('tag_crc' => array('op' => 'in', 'value' => $tags_crc)))), $m->getModelStorage());
            unset($tags_crc);
            unset($m);

			unset($res);
			
		}

    }

    

    /**
     * extract meta data from cache
     *
     * @param $tbl_name
     * @param $elem_id
     * @param null $lang_id
     * @return array|bool
     */
    public function getMeta($tbl_name, $elem_id, $lang_id = null){

        $path = $this->_p->getVar('cache')['meta_dir'].$tbl_name.'/'.calc_item_path($elem_id).$tbl_name.'_'.$elem_id.($lang_id ? '_'.$lang_id : '');

        return $this->_getCacheStorageInstance($this->_p->getVar('cache')['meta_storage'])->get($path);

    }

    /**
     * save meta data to cache
     *
     * @param string $tbl_name
     * @param int $elem_id
     * @param string $metadata
     * @param null $lang_id
     * @return bool
     */
    public function saveMeta($tbl_name, $elem_id, $metadata = '', $lang_id = null){

        if ($tbl_name && $elem_id){

            $path = $this->_p->getVar('cache')['meta_dir'].$tbl_name.'/'.calc_item_path($elem_id).$tbl_name.'_'.$elem_id.($lang_id ? '_'.$lang_id : '');

            return $this->_getCacheStorageInstance($this->_p->getVar('cache')['meta_storage'])->save($path, $metadata);

        }

        return true;
    }


    /**
     * remove metadata. If file filename defined - output data, else remove all files from directory
     *
     * @param string|null $tbl_name[option] table name for removing
     * @param int|array|null $elem_id[option] elem for removing
     */
    public function removeMeta($tbl_name = null, $elem_id = null){

        $path = $this->_p->getVar('cache')['meta_dir'];
        $remove_all = false;
        if ($tbl_name){
            // check for shared memory

            $path .= $tbl_name.'/';
            if ($elem_id){
                // checking for array in elem_id
                if (is_array($elem_id)){
                    $primary = get_primary(array_keys($elem_id));
                    if ($primary)
                        $elem_id = array((int)$elem_id[$primary]);
                    else
                        $elem_id = array_map('intval', $elem_id);
                } else
                    $elem_id = array((int)$elem_id);

                foreach ($elem_id as $id){

                    $path .= calc_item_path($id).$tbl_name.'_'.$id;

                    $this->_getCacheStorageInstance($this->_p->getVar('cache')['meta_storage'])->remove($path);
                }

            } else {
                $remove_all = true;
            }

        } else {
            $remove_all = true;
        }

        if ($remove_all){

            $this->_getCacheStorageInstance($this->_p->getVar('cache')['meta_storage'])->removeAll($path);

        }

    }


    /**
     * convert data to internal variables
     *
     * @param mixed $query
     * @param bool $use_sess_id
     * @return string
     */
    protected function _query2str($query, $use_sess_id = false){

        if (is_array($query) || $query instanceof oQuery)
            $str = $this->_p->query->buildStat($query);
        else
            $str = $query;

        // check anonymous session and flag for using session id
        if ($use_sess_id && $this->_p->user->isAuth()){

            $str .= '?'.$this->_p->user->getId();

        }

        return $str;

    }

    /**
     * convert string to hash
     * @param $str
     * @return string
     */
    protected function _str2hash($str){
        return md5($str);
    }

    /**
     * convert hash to path
     * @param $hash
     * @return string
     */
    protected function _hash2path($hash){
        return $this->_p->getVar('cache')['data_dir'].calc_item_path(abs(crc16($hash))).$hash;
    }


}
