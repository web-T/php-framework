<?php

/** 
* Sphinx search driver for web-T::CMS
* @version 0.31
* @author goshi
* @package web-T[share]
*
* Changelog:
*	0.31	17.07.11/goshi	add tbl_id like params for compile query
*	0.30	29.06.11/goshi	use config templates
*	0.29	19.01.11/goshi	add title_hash field
*	0.28	18.01.11/goshi	fix bug with find linked elems
*	0.27	17.01.11/goshi	add multisorting orders
*	0.26	09.01.11/goshi	add _fields_defs and fixing filterranges for float fields
*	0.25	22.12.10/goshi	update sorting, add rank field
*	0.24	04.12.10/goshi	fix max_matches value
*	0.23	02.12.10/goshi	add connect protection
*	0.22	01.12.10/goshi	fix sort option for text fields
*	0.21	25.11.10/goshi	add support for between operator
*	0.2	23.11.10/goshi	now tbl_name can handle array, add to the find method parameter 'natural_data'
*	0.1	18.11.10/goshi	...
* TODO: Do refactor
*/

namespace webtFramework\Modules\Drivers;

use webtFramework\Modules\oSearch_Common;
use webtFramework\Interfaces\oBase;
use webtFramework\Core\oPortal;

/**
* declare sphinx driver search class 
* @package web-T[share]
*/
class oSearchDecorator_sphinx extends oBase{

    /**
     * @var oSearch_Common
     */
    private $_oSearch_Common;
	
	protected $_def_count;
	protected $_temp;

	protected $_config = array();

	// parameters
	private $_params = null;

	// sphinx core constant	
	private $_max_sort_keys = 5;	
	private $_max_matches = 200000;	
	
	/**
	* focing all text search by sphinx
	* set to 'false', if some cant find
	*/
	private $_force_sphinx = true;  
	
	/**
	* working environment
	*/
	protected $_environment = 'test';

    /**
     * TODO remove fields list and get it from the oSearch model
     * @var array
     */
    protected $_fields = array(
		'tbl_name' => 'tbl_name',
		'tbl_id' => 'tbl_id',
		'this_id' => 'tbl_name',
		'elem_id' => 'elem_id',
		'real_id' => 'elem_id',
		'weight' => 'weight',
		'category' => 'category',
		'cats' => 'cats',
		'is_specproject' => 'is_spec',
		'is_spec' => 'is_spec',
		'cost' => 'cost',
		'picture' => 'picture',
		'title' => 'title',
		'title_hash' => 'title_hash',
		'descr' => 'descr',
		'date_add' => 'date_add',
		'date_post' => 'date_add',
		'is_on' => 'is_on',
		'lang_id' => 'lang_id',
		'is_photo' => 'is_photo',
		'is_video' => 'is_video',
		'is_top' => 'is_top',
		'cost_usd' => 'cost_usd',
		'type_id' => 'type_id',
		'region_id' => 'region_id',
		'user_id' => 'user_id',
		'tags' => 'tags',
		'rank' => '@rank',
		/** project fields */
		'club_id' => 'club_id',
		'car_id' => 'car_id',
		'comments_today' => 'comments_today',
		'rating_today' => 'rating_today',
		);
	
	/**
	* fields definitions
	*/
	protected $_fields_defs = array(
		'cost_usd' => array(
			'type' => 'float'));
		
		
	private $_snippet_opts = array(
		'chunk_separator' => '', 
		'around' => 0, 
		'single_passage' => true, 
		'limit_passages' => 1);	
	
	public function __construct(oPortal &$p, oSearch_Common &$oSearch_Common, $params = array()){
	
		parent::__construct($p, $params);
		$this->_oSearch_Common = $oSearch_Common;
		
		$this->_def_count = $this->_max_matches;
		$this->_environment = WEBT_ENV;
		
		// include configure files
		require_once($oSearch_Common->getParam('ROOT_DIR').'etc/osearch_sphinx.config.php');
		$this->_config = \osearch_sphinx_confClass::getConfig($this->_environment);
				

	}

    public function getConfig(){

        return $this->_config;

    }
	
	protected function _callbackUpdate(){
	
		/*if ($this->_environment == 'production')
			exec('/usr/local/bin/sudo /usr/local/etc/rc.d/sphinxsearch restart'); */
	}
	
	private function _inverseFieldsValues($client, $arr){
	
		$normalize = array();
		if (is_array($arr)){
			foreach ($arr as $k => $v){
				$normalize[$v['attrs']['elem_id']] = $v['attrs'];
				// adding excerpts
				if ($this->_params['text'] != '')
					$normalize[$v['attrs']['elem_id']]['snippet'] = $client->BuildExcerpts(array($v['attrs']['title']), $this->_config['indexes']['main'], $this->_params['text'], $this->_snippet_opts);
				unset($v['attrs']);
				
			}
		
		}
		return $normalize;
	}
	
	
	/**
	* compile where for sphinx
	*/
	private function _compile_where_array($conditions, &$client){

		if (is_array($conditions) && !empty($conditions)){
			foreach ($conditions as $k => $v){
				
				// some patches
				if ($v['key'] == 'tbl_name'){
					$v['key'] = 'tbl_id';
					$v['value'] = $this->_p->getTableHash($v['value']);
				}
				
				// checing for field type
				if (isset($this->_fields_defs[$v['key']]) && $this->_fields_defs[$v['key']]['type'] == 'float')
					$range_method = 'SetFilterFloatRange';
				else
					$range_method = 'SetFilterRange';
					
				$max_int = PHP_INT_SIZE >= 8 ? intval(pow(2, 62)) : PHP_INT_SIZE;

				switch (strtolower(trim($v['op']))){
				case '>=':
					$client->$range_method($this->_fields[$v['key']], (int)$v['value'], $max_int);
					break;
					
				case '>':
					$client->$range_method($this->_fields[$v['key']], (int)$v['value']+1, $max_int);
					break;

				case '<=':
					$client->$range_method($this->_fields[$v['key']], 0, (int)$v['value']); 
					break;
					
				case '<':
					$client->$range_method($this->_fields[$v['key']], 0, (int)$v['value']-1); 
					break;

				case 'between':
					$client->$range_method($this->_fields[$v['key']], $v['value'][0], $v['value'][1]); 
					break;

				case '<>':
					$client->SetFilter($this->_fields[$v['key']], array((int)$v['value']), true); 
					break;
				
				// for multi value attribute
				case 'mva_in':
				case 'in':
					//dump($this->_fields[$v['key']], false);
					//dump(is_array($v['value']) ? $v['value'] : array($v['value']));
					$client->SetFilter($this->_fields[$v['key']], is_array($v['value']) ? $v['value'] : array($v['value'])); 
					break;

				case 'not in':
					//dump($this->_fields[$v['key']], false);
					//dump(is_array($v['value']) ? $v['value'] : array($v['value']));
					$client->SetFilter($this->_fields[$v['key']], is_array($v['value']) ? $v['value'] : array($v['value']), true); 
					break;
			
				case '=':
				default:
					$client->SetFilter($this->_fields[$v['key']], array((int)$v['value'])); 
					break;
				
				}					
			}
		}
	}
	
	/**
	* simply checking fields for exists
	*/
	protected function _compileTextQuery($params){
	
		// continue - if no search fields external detected
		if (is_array($params['text_fields']) && !empty($params['text_fields']))
			$sfields = $params['text_fields'];
		else 
			return true;
		
		if (!is_array($sfields))
			$sfields = array($sfields);
			
		$sfields_virtual = array();
		foreach ($sfields as $k => $v){
			if (!isset($this->_fields[$v]))
				return false;

			// trying to find virtual fields 
			if (isset($params['fields']) && !empty($params['fields']) && $params['fields'][$v]['type'] == 'virtual' &&
                isset($params['fields'][$v]['handlerNodes']))
				$sfields_virtual = array_merge($sfields_virtual, $params['fields'][$v]['handlerNodes']);
				
		}
		
		if (!empty($sfields_virtual)){
			foreach ($sfields_virtual as $v){
				if (!isset($this->_fields[$v]))
					return false;
			}				
		}
	}
	
	
	/**
	* method compile where query for current database and optimize it
	*/
	protected function _compileQuery(&$params, &$client){
	
		if (!$client)
			return false;

		// check for query mode
		if (isset($params['query_mode']) && $client){
			switch ($params['query_mode']){

			case 'any':
				$client->SetMatchMode(SPH_MATCH_ANY);
				break;
			
			case 'phrase':
				$client->SetMatchMode(SPH_MATCH_PHRASE);
				break;

			case 'boolean':
				$client->SetMatchMode(SPH_MATCH_BOOLEAN);
				break;
				
			case 'extended':
				$client->SetMatchMode(SPH_MATCH_EXTENDED);
				break;
				
			case 'extended2':
				$client->SetMatchMode(SPH_MATCH_EXTENDED2);
				break;

			case 'fullscan':
				$client->SetMatchMode(SPH_MATCH_FULLSCAN);
				break;

			case 'all':
			default:
				$client->SetMatchMode(SPH_MATCH_ALL);
			} 
		} else
			$client->SetMatchMode(SPH_MATCH_ALL);

		$is_optimized = (isset($params['is_optimized']) ? $params['is_optimized'] : true) && $this->_p->getVar('search')['is_indexing'] && (isset($params['noindex']) ? !$params['noindex'] : true);
		// if not optimized - return common results
		if (!$is_optimized)
			return false;
		// prepare where sql 
		if (!empty($params['where_sql']) && is_array($params['where_sql'])){

			foreach ($params['where_sql'] as $k => $v){
				if (!isset($this->_fields[$v['key']])){
					//dump($v['key']);
					return false;
				}
			}
										
			$this->_compile_where_array($params['where_sql'], $client);
		}
	

		/* prepare sorting */
		/*
		SPH_SORT_RELEVANCE mode, that sorts by relevance in descending order (best matches first);
		SPH_SORT_ATTR_DESC mode, that sorts by an attribute in descending order (bigger attribute values first);
		SPH_SORT_ATTR_ASC mode, that sorts by an attribute in ascending order (smaller attribute values first);
		SPH_SORT_TIME_SEGMENTS mode, that sorts by time segments (last hour/day/week/month) in descending order, and then by relevance in descending order;
		SPH_SORT_EXTENDED mode, that sorts by SQL-like combination of columns in ASC/DESC order (only to 5 attributes);
		SPH_SORT_EXPR mode, that sorts by an arithmetic expression.
		*/		

		if (isset($params['sort'])/* && isset($params['fields'])*/){
		
			if (is_array($params['sort'])){
				$tmp_s = '';
				if ($is_optimized){
					foreach ($params['sort'] as $v){
						// check for enabled fields
						if (!isset($this->_fields[$v]) || $v == 'title'){
							return false;
						}
					}
				}
				$count = 0;
				foreach ($params['sort'] as $v){
					// check for enabled fields
					$v = $this->_fields[$v];
					
					// determine sort order for field
					$tmp_s[] = $v.' '.($params['data_source']['sort'] && is_array($params['data_source']['sort']) && $params['data_source']['sort'][$v] ? qstr($params['data_source']['sort'][$v]) : ($params['data_source']['sort'] && !is_array($params['data_source']['sort']) ? qstr($params['data_source']['sort']) : ($params['fields'][$v]['order'] ? qstr($params['fields'][$v]['order']) : ' DESC ')));
					$count++;
					if ($count >= $this->_max_sort_keys) break;
				}
				
				$client->SetSortMode(SPH_SORT_EXTENDED, join(',', $tmp_s));
				
			} elseif (isset($this->_fields[$params['sort']])){

				// check for enabled fields
				if (!isset($this->_fields[$params['sort']]) || $params['sort'] == 'title'){
					return false;
				}
				
				$sort = $this->_fields[$params['sort']];
				
				// determine sort order for field
				$client->SetSortMode(SPH_SORT_EXTENDED, qstr($sort).' '.($params['data_source']['sort'] && is_array($params['data_source']['sort']) && $params['data_source']['sort'][$sort] ? qstr($params['data_source']['sort'][$sort]) : ($params['data_source']['sort'] && !is_array($params['data_source']['sort']) ? qstr($params['data_source']['sort']) : ($params['fields'][$sort]['order'] ? qstr($params['fields'][$sort]['order']) : ' DESC '))));
				//echo qstr($sort).' '.($params['data_source']['sort'] ? qstr($params['data_source']['sort']) : ($params['fields'][$sort]['order'] ? qstr($params['fields'][$sort]['order']) : ' DESC '));
			}
		} else
			$client->SetSortMode(SPH_SORT_RELEVANCE);
			 
		// adding final fields if have optimized query
		if (isset($params['elem_id']) && $is_optimized)
			$client->SetFilter('elem_id', array(qstr($params['elem_id']))); 

		if (isset($params['tbl_name']) && $params['tbl_name'] != ''){
			if (is_array($params['tbl_name'])){
				$ids = array();
				foreach ($params['tbl_name'] as $v){
					$ids[] = (int)$this->_p->getVar('tables_hash')[$v];
				}
				$client->SetFilter('tbl_id', $ids);
			} else
				$client->SetFilter('tbl_id', array((int)$this->_p->getVar('tables_hash')[$params['tbl_name']]));
				
		} elseif (isset($params['tbl_id'])){
			//dump((array)$params['tbl_id']);
			$client->SetFilter('tbl_id', is_array($params['tbl_id']) ? $params['tbl_id'] : array($params['tbl_id']));

		}
		
		//echo $params['begin']." || ".$params['count']."<br/>"; 
		if ($params['begin'] || $params['count']){
			$client->SetLimits($params['begin'] ? (int)$params['begin'] : 0, $params['count'] ? (int)$params['count'] : $this->_def_count, $this->_max_matches);
		}
		
		
		$params['is_optimized'] = $is_optimized;
		return true;
	
	}
		
	
	/**
	* params consists of:
	*	text		string	text to find
	*	[tbl_name]	string	optional table name for searching
	*	[tbl_id]	int	optional table id for searching
	*	[elem_id]	int	optional element id for searching
	*	[begin]		int	optional parameter for starting looking
	*	[count]		int	optional parameter for count of searching elements
	*	[sort]		mixed	optional parameter with sort info and fields
	*	[fields]	array	optional parameter with fields information (see @iAdminController)
	*	[data_source]	array	optional parameter with current query
	*	[where_sql]	string	optional WHERE statement - if set - then no fast table used
	*
	* @return array set of results in form 
	* other drivers can decorate this method
	*/	
	public function find($params){

		$this->_params = $params;

		require_once(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/sphinxapi.php');
	    $cl = new \SphinxClient();
	    $cl->SetServer($this->_config['server'], $this->_config['port']);
				
		// checking - if we can compile query
		if ($params['noindex'] || !$this->_compileQuery($params, $cl)){
			return $this->_oSearch_Common->find($params);
		}
		
		$cl->SetArrayResult(true);	
		$cl->SetSelect('*');
		
		if (isset($params['text']) && !empty($params['text'])){
			if (!$this->_force_sphinx && !$this->_compileTextQuery($params))
				return $this->_oSearch_Common->find($params);
			$data = $cl->Query(qstr($params['text']), join(' ', $this->_config['indexes']));
		} else {
			$data = $cl->Query('', join(' ', $this->_config['indexes']));
		}

		//dump($cl->GetLastError(), false);
		// adding protection from connection error	
		if ($cl->IsConnectError())
			return $this->_oSearch_Common->find($params);

		if (is_array($data)){
			
			// check for optimized query and full data
			// WARNING!!! does not support now for multitable data types (yet)
			if (isset($params['full_data']) && $params['is_optimized'] && $data){

				$ids = array();

				foreach ($data['matches'] as $v){
					$ids[] = $v['attrs']['elem_id'];
				}
				$params['is_optimized'] = false;
				// detecting primary key
				if (!isset($params['fields'])){
					if ($params['tbl_id']){
						if (!$this->_temp['inv_tbl'])
							$this->_temp['inv_tbl'] = array_flip($this->_p->getVar('tables_hash'));
						$params['tbl_name'] = $this->_temp['inv_tbl'][(is_array($params['tbl_id']) ? $params['tbl_id'][0] : $params['tbl_id'])];
					}
					$params['fields'] = describe_table($this->_p, $params['tbl_name']);
					if ($params['fields'])
						$params['fields'] = array_flip($params['fields']);
				}
				$primary = isset($params['fields']['real_id']) ? 'real_id' : 'id';

				$params['where_sql'][] = array('key' => $primary, 'op' => 'IN', 'value' => $ids);
				unset($params['text']);
				unset($params['count']);
				unset($params['begin']);
				unset($ids);
				
				//dump($params);
				
				return $this->_oSearch_Common->find($params);
			
			} else if (isset($params['natural_data']) && $params['natural_data'])
				return $data;
			else
				return $this->_inverseFieldsValues($cl, $data['matches']);
				
		} else
			return false;
	
	}
	
	
	/**
	* method getting count of the query
	* for all params see 
	* 
	* @return array set of results in form 
	* other drivers can decorate this method
	*/	
	public function count($params){

		require_once(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/sphinxapi.php');
	    $cl = new \SphinxClient();

	    $cl->SetServer($this->_config['server'], $this->_config['port']);

		// checking - if we can compile query
		if ($params['noindex'] || !$this->_compileQuery($params, $cl)){
			return $this->_oSearch_Common->count($params);
		}

		$cl->SetLimits(0, 1);
		
		if (isset($params['text'])){
			if (!$this->_force_sphinx && !$this->_compileTextQuery($params))
				return $this->_oSearch_Common->count($params);

			$data = $cl->Query(qstr($params['text']), join(' ', $this->_config['indexes']));
			
		} else
			$data = $cl->Query('', join(' ', $this->_config['indexes']));

		//dump($cl->GetLastError(), false);

		// adding protection from connection error	
		if ($cl->IsConnectError())
			return $this->_oSearch_Common->count($params);
		//dump($data['total_found']);
		//dump($data);
		//print_r($data);
		return $data ? $data['total_found'] : 0;
	}
	
	/**
	* params consists of:
	*	[tbl_name]	string	optional table name for searching
	*	[elem_id]	int	optional element id for searching
	*	[weight]	int	optional weight of element
	*
	* @return boolean if succeed - return true 
	* other drivers can decorate this method
	*/
	public function save($params){

		if (!(isset($params['fields']) && isset($params['tbl_name'])) || isset($params['model'])) return false;
		
		// saving common driver
		$this->_oSearch_Common->save($params);
		// update delta index
		//echo $this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['delta'].' --rotate ';
		exec($this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['delta'].' --rotate ', $return);
		//dump_file($return, false);
		//dump($return, false);
		$this->_callbackUpdate();
	}
	
	
	/**
	* params consists of:
	*	[tbl_name]	string	optional table name for searching
	*	[elem_id]	int	optional element id for searching
	*	[weight]	int	optional weight of element
	*
	* @return boolean if succeed - return true 
	* other drivers can decorate this method
	*/
	public function update($params){

		if (!(isset($params['tbl_name']) || isset($params['model']))) return false;
		
		// saving common driver
		$this->_oSearch_Common->update($params);
		// update delta index
		//echo $this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['delta'].' --rotate ';
		exec($this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['delta'].' --rotate ', $return);
		//dump_file($return, false);
		//dump_file($return, false);
		$this->_callbackUpdate();
	}	
	
	/**
	* params consists of:
	*	[tbl_name]	string	optional table name for searching
	*	[elem_id]	int	optional element id for searching
	*	[weight]	int	optional weight of element
	*
	* @return boolean if succeed - return true 
	* other drivers can decorate this method
	*/
	public function remove($params){

		// remove from common driver
		$this->_oSearch_Common->remove($params);
		// update delta index
		
		exec($this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['delta'].' --rotate');
		$this->_callbackUpdate();					
		 	
	}
	
	
	public function buildSnippets(&$docs, $text){

		// adding snippets
		if ($docs && $text != ''){
			$opts = array('before_match' => '<span class="keyword">', 
				'after_match' => '</span>');
			require_once(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/sphinxapi.php');
		    $cl = new \SphinxClient();
		    $cl->SetServer($this->_config['server'], $this->_config['port']);
			//dump($docs);
			reset($this->_config['indexes']);
			$index = current($this->_config['indexes']);
			foreach ($docs as $k => $v){
				
				if (empty($v['snippet']) || (!empty($v['snippet']) && count($v['snippet']) == 1 && $v['snippet'][0] == '')){
					//dump(array_values($this->_config['indexes']));
					$docs[$k]['snippet'] = $cl->BuildExcerpts (array((string)$v['title'], (string)$v['descr'], (string)$v['sname'], (string)$v['usernick']), $index, $text, $opts);
					
					// join snippets
					if ($docs[$k]['snippet'])
						$docs[$k]['snippet'] = join('', $docs[$k]['snippet']);
				}			
			}
			
			unset($cl);
		
		}

	
	}
	
	/**
	* reindexing all tables in database
	* emulate base admin controller
	* support only new (2009) admin modules (based on iAdminController)
	*/
	public function index($params = array()){
	
		// index common driver if we not block it from method call
		if (!$params['no_common_index'])
			$this->_oSearch_Common->index($params);

		// now reindex delta
		exec($this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['main'].' --rotate', $return);
		//dump_file($this->_config['sphinx_dir'].'indexer --config '.$this->_config['config_file'].' '.$this->_config['indexes']['main'].' --rotate', false);
		$this->_callbackUpdate();

	}
	
}

