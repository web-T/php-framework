<?php

/** 
* Search module for web-T::CMS
* @version 0.80
* @author goshi
* @package web-T[share]
*
* Changelog:
 *  0.80    10.01.15/goshi  refactoring - use models
 *  0.70    24.11.13/goshi  now _compileQuery return array, add is_author column with 1 by default
 *  0.67    22.09.13/goshi  fix bug with text_fields
*	0.66	13.12.11/goshi	fix virtual fields
*	0.65	16.08.11/goshi	add update method
*	0.62	29.06.11/goshi	use config templates
*	0.61	07.05.11/goshi	fix determine text fields
*	0.6		25.04.11/goshi	add search in foreign keys!!!, fix filters
*	0.52	01.02.11/goshi	fix text search for non optimized 
*	0.51	19.01.11/goshi	add title_hash field
*	0.5		11.01.11/goshi	fix reindex for large tables
*	0.47	09.01.11/goshi	add some project fields
*	0.46	05.01.11/goshi	update save method to save all data 
*	0.45	20.12.10/goshi	fix bug with use standart driver
*	0.44	07.12.10/goshi	fix save quoting
*	0.42	01.12.10/goshi	fix search bug for non optimized query
*	0.41	24.11.10/goshi	add is_spec field
*	0.4	20.11.10/goshi	add last_modified field
*	0.3	17.11.10/goshi	full refactoring, add category field, add getCount method
*	0.2	05.02.10/goshi	use find method to determine order, working on not fast table
*	0.1	17.11.09
*
*/

namespace webtFramework\Modules;

use webtFramework\Interfaces\oModule;
use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;
use webtFramework\Interfaces\oModel;
use webtFramework\Helpers\Text;
use webtFramework\Models\Search;


/**
* declare base driver search class 
* @package web-T[share]
*/
class oSearch_Common extends oBase{

	// root dir for this module
	protected $_ROOT_DIR = '';
	protected $_SKIN_DIR = '';
	protected $_CSS_DIR = '';
	protected $_JS_DIR = '';
	
	protected $_def_count;

	protected $_temp = null;

    /**
     * @var array array of default search fields
     */
    protected $_sfields = array('title', 'descr', 'tags');
	
	// range for reindexing
	protected $_index_range = 1000;
		
	protected $_source_fields = null;
	
	public function __construct(oPortal &$p, $params = array()){

        parent::__construct($p, $params);

		// customizing search table
		$this->_work_tbl = $params['work_tbl'] && $p->getVar($params['work_tbl']) ? $p->getVar($params['work_tbl']) : $p->getVar('tbl_search');

		// prepare default count
		$this->_def_count = 2147483647;
		
		// copy fields
		if ($params['fields'])
			$this->_source_fields = &$params['fields'];
	}

	/**
	* function find text fiedls
	*/
	protected function _prepareSearchFields($fields){
		$sfields = array();
		foreach ($fields as $k => $v){
			if (isset($v['search']) && $v['search']){
				if ($v['type'] != 'virtual')
					$sfields[] = $k;
			}
			
			// check for linked source data
			if (isset($v['visual']) && isset($v['visual']['source']['tbl_name']) && $v['visual']['source']['search']){
				// hint!! describing base table and find text fields
				$tbl_fields = describe_table($this->_p, $this->_p->getVar($v['visual']['source']['tbl_name']));
				if ($tbl_fields && !empty($tbl_fields)){
				
					foreach ($tbl_fields as $field){
						if (in_array($field, $this->_p->getVar('search')['fields'])){
							$sfields[] = $this->_p->getVar($v['visual']['source']['tbl_name']).'.'.$field;
						}
					}		
				}
			
				if ($v['type'] != 'virtual')
					$sfields[] = $k;
			}			
			
		}
		return $sfields;
	}

    protected function _getSearchSource($params = array(), $is_optimized = false){

        $source = '';

        if (!$is_optimized){

            if (isset($params['model']) && $params['model'] instanceof oModel)
                $source = $params['model'];
            elseif (isset($params['tbl_name'])){
                $source = $params['tbl_name'];
                $source = $this->_p->db->getQueryBuilder()->createModel($source);

            } elseif (isset($params['tbl_id'])){

                $source = $this->_p->getTableNameByHash(is_array($params['tbl_id']) ? $params['tbl_id'][0] : $params['tbl_id']);
                $source = $this->_p->db->getQueryBuilder()->createModel($source);
            }

        } else
            $source = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

        return $source;

    }

	
	/**
	* compile text search
	*/
	protected function _compileTextQuery($params, &$is_optimized, $source = null){
	
		///$sfields_str = '';

        $result = array();

		if (isset($params['text']) && trim($params['text']) != ''){

            if ($is_optimized){
                $sfields = $this->_sfields;
            } elseif ($params['text_fields'] && !empty($params['text_fields'])){
				$sfields = (array)$params['text_fields'];
            } elseif (/*!$is_optimized && */isset($params['model']) && $params['model'] instanceof oModel){
                $sfields = $this->_prepareSearchFields($params['model']->getModelFields());
            } elseif (/*!$is_optimized && */isset($params['fields']) && !empty($params['fields'])){
				$sfields = $this->_prepareSearchFields($params['fields']);
            } else {
                $sfields = array();
            }

            // final checking for text fields
            if (empty($sfields)){

                if ($source && $source instanceof oModel){
                    $tbl_fields = array_keys($source->getModelFields());
                } elseif ($source && is_string($source)){
                    $tbl_fields = $this->_p->db->getQueryBuilder()->describeTable($source);
                } else {
                    $tbl_fields = array_keys($this->_p->Model($this->_p->getVar('search')['indexing_model'])->getModelFields());
                }

				if (!empty($tbl_fields)){
					foreach ($tbl_fields as $field){
						if (in_array($field, $this->_p->getVar('search')['fields'])){
							$sfields[$field] = $field;
						}
					}
				}
				
			}

			// going throw fields and check for optimized
            $search_model = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

            if ($is_optimized){
                foreach ($sfields as $v){
                    if (!$this->_p->db->getQueryBuilder($search_model->getModelStorage())->isFieldExists($search_model, $v)){
                        $is_optimized = false;
                    }
                }
            }

			$keyarray = array(trim($params['text']));
			$sfields_virtual = array();


			if (((isset($params['fields']) && is_array($params['fields'])) || isset($params['model'])) && !$is_optimized){

                // for new code
                if (isset($params['model'])){

                    foreach ($params['model']->getModelFields() as $k => $v){
                        // trying to find virtual fields
                        if ($v['type'] == 'virtual' && isset($v['handlerNodes'])){
                            $sfields_virtual = array_merge($sfields_virtual, $v['handlerNodes']);
                        }
                    }

                } else {
                    /**
                     * for old code
                     * @deprecated
                     **/
                    foreach ($params['fields'] as $k => $v){
                        // trying to find virtual fields
                        $f_id = is_array($v) ? $k : $v;
                        if ($params['fields'][$f_id]['type'] == 'virtual' && isset($params['fields'][$f_id]['handlerNodes'])){
                            $sfields_virtual = array_merge($sfields_virtual, $params['fields'][$f_id]['handlerNodes']);
                        }

                    }
                }

			}

            /*if ($is_optimized){
                $sfields_str = $this->_p->db->getQueryBuilder($search_model->getModelStorage())->compileSearch($search_model, $sfields, $keyarray, 'or', 'a', false, 'or');
            } elseif ($params['model']){
                $sfields_str = $this->_p->db->getQueryBuilder($params['model']->getModelStorage())->compileSearch($params['model'], $sfields, $keyarray, 'or', 'a', false, 'or');
            } else {
			    $sfields_str = make_search_fields($this->_p, $sfields, $keyarray, 'OR', 'a', false, 'OR', $params['fields']);
            }*/

			if (!empty($sfields_virtual)){
				// now we must to break keyarray
				$new_keyarray = array();
				foreach ((array)$keyarray as $v){
					$new_keyarray = array_merge($new_keyarray, (array)explode(' ', $v));
				}
                $keyarray = $new_keyarray;

                $sfields = array_merge($sfields, $sfields_virtual);

                /*if ($is_optimized){
                    $sfields_str2 = $this->_p->db->getQueryBuilder($search_model->getModelStorage())->compileSearch($search_model, $sfields_virtual, $new_keyarray, 'and', 'a', false, 'or');
                } elseif ($params['model']){
                    $sfields_str2 = $this->_p->db->getQueryBuilder($params['model']->getModelStorage())->compileSearch($params['model'], $sfields_virtual, $new_keyarray, 'and', 'a', false, 'or');
                } else {
                    $sfields_str2 = make_search_fields($this->_p, $sfields_virtual, $new_keyarray, 'AND', 'a', false, 'OR');
                }

                if ($sfields_str){
                    if ($is_optimized){
                        $sfields_str = $this->_p->db->getQueryBuilder($search_model->getModelStorage())->compileOr($search_model, array($sfields_str, $sfields_str2));
                    } elseif ($params['model']){
                        $sfields_str = $this->_p->db->getQueryBuilder($params['model']->getModelStorage())->compileOr($params['model'], array($sfields_str, $sfields_str2));
                    }

                } else{
                    $sfields_str = $sfields_str2;
                }*/

			}

            $result['op'] = 'search';
            $result['sfields'] = $sfields;
            $result['value'] = $keyarray;
            $result['wmode'] = 'or';
            $result['prefix'] = 'a';
            $result['fulltext'] = false;
            $result['innermode'] = 'or';

            unset($search_model);
			
		}
	
		//return $sfields_str;
		return $result;

	}
	
	protected function _extractFieldsFromWhere($where){

        $fields = array();
        if ($where && is_array($where)){
            foreach ($where as $k => $v){

                // detect key
                if (!is_numeric($k) && in_array($k, array('$or', '$and'))){
                    $fields = array_merge($fields, $this->_extractFieldsFromWhere($v));
                } elseif (!is_numeric($k)){
                    $fields[] = $k;
                } else {
                    $fields[] = isset($v['key']) ? $v['key'] : $v['field'];
                }
            }

        }

        return $fields;

    }
	
	/**
	* method compile where query for current database and optimize it
	*/
	protected function _compileQuery(&$params){
	
        $model = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

		$is_optimized = (isset($params['is_optimized']) ? $params['is_optimized'] : true) &&
                $this->_p->getVar('search')['is_indexing'] &&
                (isset($params['model']) ? !$params['model']->getIsNoIndex() : (isset($params['noindex']) ? !$params['noindex'] : true))
                ;

		// prepare where sql
        $inner_conditions = array('where' => array(), 'order' => array(), 'join' => array());

		if (!empty($params['conditions']) && is_array($params['conditions']) && isset($params['conditions']['where'])){

            $fields = $this->_extractFieldsFromWhere($params['conditions']['where']);

			foreach ($fields as $v){

				if (!isset($model->getModelFields()[$v])){
					$is_optimized = false;
				}
			}

            $inner_conditions['where'] = $params['conditions']['where'];

			// if optimized - then change fields
			/*$where_sql = $params['where_sql'];
			if ($is_optimized){
				foreach ($params['where_sql'] as $k => $v){
					//$where_sql[$k]['key'] = $this->_fields[$v['key']];
					$where_sql[$k]['key'] = $v['key'];
				}
			}
			//echo dump($params['where_sql'], false);
			$sql_add .= compile_where_string($where_sql); */

		}

		/** compile join table **/
		if (!isset($params['conditions']['join'])){
			$params['conditions']['join'] = array();
			if (!is_array($params['fields']))
                $params['fields'] = array();

            $fields_source = isset($params['model']) && $params['model'] ? $params['model']->getModelFields() : $params['fields'];
			
			foreach ($fields_source as $k => $v){

				if (isset($v['visual']) && (isset($v['visual']['source']['tbl_name']) || isset($v['visual']['source']['model']))&& $v['visual']['source']['search']){

                    if (isset($v['visual']['source']['model'])){

                        $tmp_model = $this->_p->Model($v['visual']['source']['model']);
                        $conds = array(
                            $k => array(
                                'table' => 'a',
                                'op' => '=',
                                'value' => $tmp_model->getModelTable().'.'.$tmp_model->getPrimaryKey(),
                                'type' => 'foreign_key'
                            )
                        );

                        if ($tmp_model->getIsMultilang()){
                            $conds['lang_id'] = array(
                                'table' => $tmp_model->getModelTable(),
                                'op' => '=',
                                'value' => $this->_p->getLangId(),
                            );
                        }

                        $params['conditions']['join'][] = array(
                            'model' => $tmp_model->getModelName(),
                            'conditions' => $conds);

                    } else {

                        $conds = array(
                            $k => array(
                                'table' => 'a',
                                'op' => '=',
                                'value' => $this->_p->getVar($v['visual']['source']['tbl_name']).'.'.($v['visual']['source']['multilang'] ? 'real_id' : 'id'),
                                'type' => 'foreign_key'
                            ));

                        if ($v['visual']['source']['multilang']){

                            $conds['lang_id'] = array(
                                'table' => $this->_p->getVar($v['visual']['source']['tbl_name']),
                                'op' => '=',
                                'value' => $this->_p->getLangId(),
                            );
                        }

                        $params['conditions']['join'][] = array(
                            'tbl_name' => $this->_p->getVar($v['visual']['source']['tbl_name']),
                            'conditions' => $conds);

                    }

				}
			}

            unset($tmp_model);
            unset($fields_source);
		}

        $inner_conditions['join'] = $params['conditions']['join'];

		/* prepare sorting */

		if (isset($params['conditions']['order']) && (isset($params['model']) || isset($params['fields']))){
			
			if (is_array($params['conditions']['order'])){
				if ($is_optimized){
					foreach ($params['conditions']['order'] as $k => $v){
						// check for enabled fields
						if (!isset($model->getModelFields()[$k]))
							$is_optimized = false;
					}
				}

				foreach ($params['conditions']['order'] as $k => $v){
					
					// checking for pseudo relevant field
					if ($k == 'rank') continue;
				
					// check for enabled fields
                    $inner_conditions['order'][$k] = $v;
				}

			}
		}

        // adding final fields if have optimized query

		if (isset($params['tbl_id']) && $is_optimized){
			if (is_array($params['tbl_id']))
                $inner_conditions['where']['tbl_id'] = array('op' => 'in', 'value' => $params['tbl_id']);
			else
                $inner_conditions['where']['tbl_id'] = $params['tbl_id'];
		}
		if (isset($params['tbl_name']) && $is_optimized)
            $inner_conditions['where']['tbl_name'] = $params['tbl_name'];

        if (isset($params['model']) && $is_optimized)
            $inner_conditions['where']['tbl_name'] = $params['model']->getModelTable();

		if (isset($params['elem_id']) && $is_optimized)
            $inner_conditions['where']['elem_id'] = $params['elem_id'];

        if (isset($params['conditions']['begin']) || isset($params['conditions']['limit'])){
            $inner_conditions['begin'] = (int)$params['conditions']['begin'];
            $inner_conditions['limit'] = isset($params['conditions']['limit']) ? $params['conditions']['limit'] : $this->_def_count;
        }

		$params['is_optimized'] = $is_optimized;

        $source = $this->_getSearchSource($params, $is_optimized);

        if (isset($params['text']) && trim($params['text']) != ''){

            $inner_conditions['where'][] = $this->_compileTextQuery($params, $is_optimized, $source);

        }

        return array(
            'is_optimized' => $is_optimized,
            'source' => $source,
            'conditions' => $inner_conditions
        );
	}

    /**
     * find field in selected table
     * @param $field
     * @param $source
     * @return bool
     */
    protected function _findFieldInTable($field, $source){

        if ($field && $source){

            if ($source instanceof oModel){
                $table = $this->_p->db->getQueryBuilder($source->getModelStorage())->describeTable($source);
            } else {
                $table = describe_table($this->_p, $source instanceof oModel ? $source->getModelTable() : $source);
            }

            if ($table && in_array($field, $table)){
                return true;
            }

        }
        return false;

    }
		
	
    /**
     * @param $params
     * @return array|null|void set of results
     *
     *
     * params consists of:
     *	text		string	text to find
     *	[tbl_name]	string	optional table name for searching
     *	[join]		array optional array of joined tables like array('tbl_name' => '...', 'method' => 'join,left,right|default:join', ['alias' => 'alias of the table'], 'conditions' => array of standart conditions)
     *	[elem_id]	int	optional element id for searching
     *	[begin]		int	optional parameter for starting looking
     *	[count]		int	optional parameter for count of searching elements
     *	[sort]		mixed	optional parameter with sort info and fields
     *	[fields]	array	optional parameter with fields information (see @iAdminController)
     *	[data_source]	array	optional parameter with current query
     *	[where_sql]	string	optional WHERE statement - if set - then no fast table used
     *
     * other drivers can decorate this method
     */
    public function find($params){

        $compiled = $this->_compileQuery($params);

        if (!isset($compiled['conditions']['select']))
            $compiled['conditions']['select'] = array('a' => array('*'));

        if (!$compiled['is_optimized'] &&
            $compiled['tbl_name'] != $this->_p->getVar('tbl_search') &&
            $this->_findFieldInTable('tbl_name', $compiled['source'])){
            $compiled['conditions']['select']['a'][] = array('field' => 'tbl_name', 'nick' => 'base_tbl_name');
        }

        if (!$compiled['is_optimized'] && !($compiled['source'] instanceof Search)){

            if ($compiled['source'] instanceof oModel){
                $tbl_name = $compiled['source']->getModelTable();
            } else {
                $tbl_name = $compiled['source'];
            }

            $compiled['conditions']['select']['a'][] = array('value' => $tbl_name, 'nick' => 'base_tbl_name');
            $compiled['conditions']['select']['a'][] = array('value' => $this->_p->getTableHash($tbl_name), 'nick' => 'tbl_id');

        }

        if (!($compiled['is_optimized'] || $compiled['source'] instanceof oModel)){
            // create model
            $compiled['source'] = $this->_p->db->getQueryBuilder()->createModel($compiled['source']);

        }
        $sql = $this->_p->db->getQueryBuilder($compiled['source']->getModelStorage())->compile($compiled['source'], $compiled['conditions']);

        $data = $this->_p->db->select($sql, $compiled['source']->getModelStorage());

		// check for optimized query and full data
		if (isset($params['full_data']) && $data){

            if ($params['is_optimized']){

                $primary = null;
			
                $ids = array();
                foreach ($data as $v){
                    $ids[] = $v['elem_id'];
                }

                $params['is_optimized'] = false;
                // detecting primary key
                if (isset($params['fields'])){
                    if (isset($params['fields']['real_id'])){
                        $primary = 'real_id';
                    } elseif (isset($params['fields']['id'])){
                        $primary = 'id';
                    }
                }

                if ($primary)
                    $params['conditions']['where'][] = array('field' => $primary, 'op' => 'IN', 'value' => $ids);

                unset($params['text']);
                unset($params['count']);
                unset($params['begin']);

                $where = $this->_compileQuery($params);

                $sql = $this->_p->db->getQueryBuilder($where['source']->getModelStorage())->compile($where['source'], $where['conditions']);

                $data = $this->_p->db->select($sql, $where['source']->getModelStorage());

            }
		
		}

		return $data;
	
	}
	
	
	/**
	* method getting count of the query
	* for all params see
     * @param array $params
	* 
	* @return array set of results in form 
	* other drivers can decorate this method
	*/	
	public function count($params){
	
		$compiled = $this->_compileQuery($params);

        if (!($compiled['is_optimized'] || $compiled['source'] instanceof oModel)){
            // create model
            $compiled['source'] = $this->_p->db->getQueryBuilder()->createModel($compiled['source']);

        }

        $sql = $this->_p->db->getQueryBuilder($compiled['source']->getModelStorage())->compileCount($compiled['source'], $compiled['conditions']);

        $data = $this->_p->db->selectCell($sql, $compiled['source']->getModelStorage());

		return $data;
	}
	

    /**
     * @param $params
     * params consists of:
     *	@param string $params[tbl_name]		optional table name for searching
     *	@param oModel $params['model'] optional model parameter
     *	@param int $params[elem_id]		optional element id for searching
     *	@param int $params[weight]		optional weight of element
     *	@param array $params[data]		data for saving
     * @return bool
     */
    public function save($params){

		if (!((isset($params['model']) && $params['model'] instanceof oModel) || (isset($params['fields']) && isset($params['tbl_name'])))) return false;

        // because more of fields are standart
        $descr = $searched = array();

        // for model - simply extract necessary data
        if (isset($params['model'])){

            if (isset($params['data']))
                $params['model']->setModelData($params['data']);

            // update primary key
            if (isset($params['elem_id']))
                $params['model']->setModelData($params['elem_id']);

            $this->remove(array(
                'model' => $params['model'],
                'elem_id' => $params['model']->getPrimaryValue(),
                'lang_id' => isset($params['model']->getModelFields()['lang_id']) ? $params['model']->getLang_id() : null)
            );

            $primary_value = $params['model']->getPrimaryValue();

            foreach ($params['model']->getModelFields() as $k => $v){
                if (isset($v['search']) && $v['search']){
                    $searched[] = $k;
                    if ($k != 'title')
                        $descr[] = $params['model']->getModelData($k);
                }
            }

        } else {

    		$this->remove(array(
                'tbl_name' => $params['tbl_name'],
                'elem_id' => $params['elem_id'] ? $params['elem_id'] : ($params['data']['real_id'] ? $params['data']['real_id'] : $params['data']['id']),
                'lang_id' => $params['data']['lang_id'])
            );

            $primary_value = $params['data']['real_id'] ? $params['data']['real_id'] : $params['data']['id'];

            foreach ($params['fields'] as $k => $v){
                if (isset($v['search']) && $v['search']){
                    $searched[] = $k;
                    if ($k != 'title')
                        $descr[] = $params['data'][$k];
                }
            }

        }

		// now it is not neccessary - because we are using search for speed indexing
		//if (!empty($searched)){
            $data = array(
                'tbl_name' => isset($params['model']) && $params['model'] ? $params['model']->getModelTable() : $params['tbl_name'],
                'tbl_id' => isset($params['model']) && $params['model'] ? $this->_p->getTableHash($params['model']->getModelTable()) : $this->_p->getTableHash($params['tbl_name']),
                'model' => isset($params['model']) && $params['model'] ? $params['model']->getModelName() : '',
                'elem_id' => $primary_value,
                'weight' => isset($params['model']) ? $params['model']->getModelData('weight') : (isset($params['data']['weight']) ? $params['data']['weight'] : 0),
                'category' => isset($params['model']) ? $params['model']->getModelData('category') : (isset($params['data']['category']) ? $params['data']['category'] : 0),
                'cats' => isset($params['model']) ? $params['model']->getModelData('cats') : (isset($params['data']['cats']) ? $params['data']['cats'] : ''),
                'is_spec' => isset($params['model']) ? $params['model']->getModelData('is_specproject') : (isset($params['data']['is_specproject']) ? $params['data']['is_specproject'] : 0),
                'title' => Text::cleanupRepeat(str_replace("\r\n", " ", get_row_title(isset($params['model']) ? $params['model']->getModelData() : $params['data']))),
                'title_hash' => isset($params['model']) ? $params['model']->getModelData('title_hash') :  (isset($params['data']['title_hash']) ? $params['data']['title_hash'] : 0),
                'descr' => Text::cleanupTags(join(' ', $descr)),
                'date_add' => isset($params['model']) ? $params['model']->getModelData('date_add') : (isset($params['data']['date_add']) ? $params['data']['date_add'] : 0),
                'date_post' => isset($params['model']) ? $params['model']->getModelData('date_post') : (isset($params['data']['date_post']) ? $params['data']['date_post'] : 0),
                'last_modified' => isset($params['model']) ? ($params['model']->getModelData('last_modified') ? $params['model']->getModelData('last_modified') : $this->_p->getTime()) : (isset($params['data']['last_modified']) ? $params['data']['last_modified'] : $this->_p->getTime()) ,
                'is_on' => isset($params['model']) ? (isset($params['model']->getModelFields()['is_on']) ? $params['model']->getModelData('is_on') : 1 ) : (isset($params['data']['is_on']) ? $params['data']['is_on'] : 1 ),
                'lang_id' => isset($params['model']) ? $params['model']->getModelData('lang_id') : (isset($params['data']['lang_id']) ? $params['data']['lang_id'] : 0 ) ,
                'is_photo' => isset($params['model']) ? $params['model']->getModelData('is_photo') : (isset($params['data']['is_photo']) ? $params['data']['is_photo'] : 0 )  ,
                'is_video' => isset($params['model']) ? $params['model']->getModelData('is_video') :  (isset($params['data']['is_video']) ? $params['data']['is_video'] : 0 )  ,
                'is_top' => isset($params['model']) ? $params['model']->getModelData('is_top') :  (isset($params['data']['is_top']) ? $params['data']['is_top'] : 0 )  ,
                'cost_usd' => isset($params['model']) ? $params['model']->getModelData('cost_usd') :  (isset($params['data']['cost_usd']) ? $params['data']['cost_usd'] : 0 )  ,
                'type_id' => isset($params['model']) ? $params['model']->getModelData('type_id') :  (isset($params['data']['type_id']) ? $params['data']['type_id'] : 0 )  ,
                'region_id' => isset($params['model']) ? $params['model']->getModelData('region_id') :  (isset($params['data']['region_id']) ? $params['data']['region_id'] : 0 )  ,
                'user_id' => isset($params['model']) ? $params['model']->getModelData('user_id') : (isset($params['data']['user_id']) ? $params['data']['user_id'] : 0 ),
                'tags' => isset($params['model']) ? $params['model']->getModelData('tags') : (isset($params['data']['tags']) ? $params['data']['tags'] : ''),
                'club_id' => isset($params['model']) ? $params['model']->getModelData('club_id') : (isset($params['data']['club_id']) ? $params['data']['club_id'] : 0 ),
                'car_id' => isset($params['model']) ? $params['model']->getModelData('car_id') : (isset($params['data']['car_id']) ? $params['data']['car_id'] : 0 ),
                'comments_today' => isset($params['model']) ? $params['model']->getModelData('comments_today') : (isset($params['data']['comments_today']) ? $params['data']['comments_today'] : 0 ),
                'rating_today' => isset($params['model']) ? $params['model']->getModelData('rating_today') : (isset($params['data']['rating_today']) ? $params['data']['rating_today'] : 0 ),
                'comments' => isset($params['model']) ? $params['model']->getModelData('comments') : (isset($params['data']['comments']) ? $params['data']['comments'] : 0 ),
                'popularity' => isset($params['model']) ? $params['model']->getModelData('popularity') : (isset($params['data']['popularity']) ? $params['data']['popularity'] : 0 ),
            );

            $model = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileInsert($model, $data);

			return $this->_p->db->query($sql, $model->getModelStorage());
		
		//}

			
	}
	

    /**
     * @param $params
     * params consists of:
     *  [model]	oModel	optional Model
     *  [tbl_name]	string	optional table name for searching
     *	[elems]		array of elements with updated parameters
     * @return bool
     */
    public function update($params){

		if (!(isset($params['tbl_name']) || isset($params['model']))) return false;
		
		$return = false;
		
		if (!empty($params['elems'])){

            $model = $this->_p->Model($this->_p->getVar('search')['indexing_model']);
            $qb = $this->_p->db->getQueryBuilder($model->getModelStorage());

			foreach ($params['elems'] as $id => $v){

                $conditions = array('where' => array(
                    'elem_id' => $id,
                    'tbl_id' => isset($params['model']) && $params['model'] instanceof oModel ? $this->_p->getTableHash($params['model']->getModelTable()) : $this->_p->getTableHash($params['tbl_name'])
                ));
                $data = array('last_modified' => $this->_p->getTime());

                foreach ($v as $field => $value){
                    $data[$field] = $value;
                }

                $sql = $qb->compileUpdate($model, $data, $conditions);

				$return = $return || $this->_p->db->query($sql, $model->getModelStorage());
			
			}

            unset($qb);
            unset($conditions);
            unset($data);
            unset($model);
			
		}
		return $return;		
	}
		

    /**
     * @param array $params
     * params consists of:
     *	[tbl_name]	string	optional table name for searching
     *	[elem_id]	int	optional element id for searching
     *	[weight]	int	optional weight of element
     *  [clear_all] bool flag for clear all in search index
     *  [remove_all_langs] bool flag for remove all lang versions of the item
     *
     * @return bool
     */
    public function remove($params){

        $conditions = array('where' => array());

        if (isset($params['model']) && $params['model'] instanceOf oModel){

            $conditions['where']['tbl_name'] = $params['model']->getModelTable();

            if ($params['model']->getPrimaryValue()){
                $conditions['where']['elem_id'] = $params['model']->getPrimaryValue();
            }

            if ($params['model']->getModelData('lang_id') && !(isset($params['remove_all_langs']) && $params['remove_all_langs'])){
                $conditions['where']['lang_id'] = $params['model']->getModelData('lang_id');
            }

            if (isset($params['model']->getModelFields()['weight']) && $params['model']->getModelData('weight') !== 0){
                $conditions['where']['weight'] = $params['model']->getModelData('weight');
            }

        }

        // for old code
        if (isset($params['tbl_name']) && $params['tbl_name'])
            $conditions['where']['tbl_name'] = $params['tbl_name'];
	
		if (isset($params['elem_id']) && $params['elem_id']){
            $conditions['where']['elem_id'] = $params['elem_id'];
		}
		
		if (isset($params['lang_id']) && $params['lang_id']){
            $conditions['where']['lang_id'] = $params['lang_id'];
		}

		if (isset($params['weight']) && $params['weight']){
            $conditions['where']['weight'] = $params['weight'];
        }

        $model = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

		// protect from succesful  cleaning
		if (isset($params['clear_all']) && $params['clear_all']){

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileTruncate($model);

        } elseif (!empty($conditions['where'])){

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileDelete($model, $conditions);

        } else
            return false;

		return $this->_p->db->query($sql, $model->getModelStorage());
		 	
	}
	
	/**
	* reindexing all tables in database
	*/
	public function index($params = array()){

        // 1. get list of models from core
        $files = scandir($this->_p->getVar('FW_DIR').$this->_p->getVar('lib_dir').$this->_p->getVar('models_dir'));

        $models_list = array('webtFramework' => array());

        if ($files){

            foreach ($files as $file){
                if ($file != '.' && $file != '..'){
                    $models_list['webtFramework'][] = str_replace('.model.php', '', $file);
                }
            }
        }

        // 2. bundles
        $bundles = scandir($this->_p->getVar('bundles_dir'));

        if ($bundles){
            foreach ($bundles as $bundle){
                if ($bundle != '.' && $bundle != '..' && file_exists($this->_p->getVar('bundles_dir').$bundle.WEBT_DS.$this->_p->getVar('lib_dir').$this->_p->getVar('models_dir'))){

                    $files = scandir($this->_p->getVar('bundles_dir').$bundle.WEBT_DS.$this->_p->getVar('lib_dir').$this->_p->getVar('models_dir'));
                    if ($files){

                        foreach ($files as $file){
                            if ($file != '.' && $file != '..'){
                                if (!isset($models_list[$bundle])){
                                    $models_list[$bundle] = array();
                                }
                                $models_list[$bundle][] = $bundle.':'.str_replace('.model.php', '', $file);
                            }
                        }
                    }
                }
            }
        }

		if ($models_list){

            $searchModel = $this->_p->Model($this->_p->getVar('search')['indexing_model']);

            $qb = $this->_p->db->getQueryBuilder($searchModel->getModelStorage());

			// empty search table
            $sql = $qb->compileTruncate($searchModel);
			$this->_p->db->query($sql, $searchModel->getModelStorage());
			
			ini_set('memory_limit', '2000M');
			set_time_limit(0);
			ini_set('max_execution_time', 0);
			
			foreach ($models_list as $bundle => $models){

                // we need to load application, because there can be addtional settings for models
                if ($bundle != 'webtFramework')
                    $this->_p->initApplication($bundle);

                foreach ($models as $model_name){

                    $model = $this->_p->Model($model_name);
                    $qb = $this->_p->db->getQueryBuilder($model->getModelStorage());

                    // getting all fields
                    $fields = $model->getModelFields();

                    // because more of fields are standart
                    $searched = array();
                    foreach ($fields as $k => $v){
                        if (isset($v['search']) && $v['search']){
                            $searched[] = $k;
                        }
                    }

                    // check for empty search fields and noindex flag
                    if (!$model->getIsNoindex() && !empty($searched)){

                        $this->_p->response->send("* ".$model_name);
                        // getting range of the items
                        $sql = $qb->compile($model, array(
                            'no_array_key' => true,
                            'select' => array(
                                'a' => array(
                                    array(
                                        'function' => 'max()',
                                        'field' => $model->getPrimaryKey()
                                    )
                                )
                            ),
                            'limit' => 1,
                            'group' => array(
                                $model->getPrimaryKey()
                            )
                        ));

                        $count = $this->_p->db->selectCell($sql, $model->getModelStorage());

                        if ($count > 0){

                            $ncount = 0;

                            while ($ncount < $count){

                                $sql = $qb->compile($model, array(
                                    'no_array_key' => true,
                                    'where' => array('[PRIMARY]' => array('op' => 'between', 'value' => array($ncount, ($ncount + $this->_index_range))))
                                ));

                                $res2 = $this->_p->db->select($sql, $model->getModelStorage());

                                if ($res2 && !empty($res2)){

                                    foreach ($res2 as $arr2){

                                        $this->save(array(
                                            'model' => $model,
                                            'data' => $arr2
                                            ));
                                    }
                                }
                                unset($res2);

                                $ncount += $this->_index_range;
                            }
                        }

                    }

                }

			}
		
		}
		
	}
	
}

/** 
* declare decorator 
* @package web-T[share]
*/
class oSearchDecorator extends oBase{

    /**
     * @var oSearch_Common
     */
    protected $_oSearch_Common;
	
	public function __construct(oPortal &$p, oSearch_Common &$oSearch_Common, $params = array()){

        parent::__construct($p, $params);

		$this->_oSearch_Common = $oSearch_Common;
	}
	
	public function find($params){
		return $this->_oSearch_Common->find($params);
	}
	
	public function count($params){
		return $this->_oSearch_Common->count($params);
	}

	public function remove($params){
		return $this->_oSearch_Common->remove($params);
	}

	public function update($params){
		return $this->_oSearch_Common->update($params);
	}

	public function save($params){
		return $this->_oSearch_Common->save($params);
	}
	
	public function index($params = array()){
		return $this->_oSearch_Common->index($params);
	}
}
	
	
	
/** 
* declare base extended class for interfacing with outer world 
* @package web-T[share]
*/
class oSearch extends oModule{

	/* @var object search driver (getting from base variables)
	*	can be 'mysql_standart', 'sphinx'
	*/
	private $_oSearchDriver;
	
	protected static $_drivers = array('mysql_standart', 'sphinx');

	// constructor
	public function __construct(oPortal &$p, $params = array()){
	
		parent::__construct($p, $params);
		$this->_work_tbl = $p->Model($this->_p->getVar('search')['indexing_model'])->getModelTable();
		
		$oSearch_Common = new oSearch_Common($p, array_merge(array('ROOT_DIR' => $this->_ROOT_DIR,
					'SKIN_DIR' => $this->_SKIN_DIR,
					'CSS_DIR' => $this->_CSS_DIR,
					'JS_DIR' => $this->_JS_DIR),
					(array)$params));
				
		// prepare driver for fulltext search
        if (isset($params['driver']) && self::$_drivers[$params['driver']] != 'mysql_standart' && file_exists($this->_ROOT_DIR.'/drivers/'.escapeshellcmd($params['driver']).'.driver.php')){

            require_once($this->_ROOT_DIR.'/drivers/'.escapeshellcmd($params['driver']).'.driver.php');
            $decorator = '\webtFramework\Modules\Drivers\oSearchDecorator_'.$params['driver'];
            $this->_oSearchDriver = new $decorator($p, $oSearch_Common);

        } elseif ($p->getVar('search')['indexing_driver'] && self::$_drivers[$p->getVar('search')['indexing_driver']] != 'mysql_standart' && file_exists($this->_ROOT_DIR.'/drivers/'.escapeshellcmd($p->getVar('search')['indexing_driver']).'.driver.php')){

            require_once($this->_ROOT_DIR.'/drivers/'.escapeshellcmd($p->getVar('search')['indexing_driver']).'.driver.php');
            $decorator = '\webtFramework\Modules\Drivers\oSearchDecorator_'.$p->getVar('search')['indexing_driver'];
            $this->_oSearchDriver = new $decorator($p, $oSearch_Common);

			// now do nothing
		} else {
			$this->_oSearchDriver = new oSearchDecorator($p, $oSearch_Common);
		}
				
	}

    /**
     * get search drivers list
     * @return array
     */
    static public function getDrivers(){
	
		$drivers = array();
		foreach (self::$_drivers as $v){
			$drivers[$v] = $v;
		}
		return $drivers;
	
	}

    /**
     * index storage
     * @param array $params
     */
    public function index($params = array()){
		return $this->_oSearchDriver->index($params);
	}
	

	/* override saving method */
	public function saveData($params){
		
		return $this->_oSearchDriver->save($params);
		
	}

	/* override update method method */
	public function updateData($params){
		
		return $this->_oSearchDriver->update($params);
		
	}

    /**
     * get items count
     * @param bool|array $params
     * @return array|bool|null|void
     */
    public function getCount($params = false){
		return $this->_oSearchDriver->count($params);
	
	}

    /**
     * find items by conditions
     * @param array $params
     * @return array|bool|null|void
     */
    public function getData($params = array()){

		return $this->_oSearchDriver->find($params);
	
	}
	
	public function removeData($params = array()){
	
		return $this->_oSearchDriver->remove($params);
	
	}

    /**
     * build text snippets
     * @param $docs
     * @param $text
     * @return bool
     */
    public function buildSnippets(&$docs, $text){
		if (method_exists($this->_oSearchDriver, 'buildSnippets'))
			return $this->_oSearchDriver->buildSnippets($docs, $text);
		else
			return false;
		
	}

    /**
     * get module config
     * @return array|null
     */
    public function getDriverConfig(){

        if (method_exists($this->_oSearchDriver, 'getConfig'))
            return $this->_oSearchDriver->getConfig();
        else
            return array();

    }


    /**
     * @param $elem_ids
     * @param bool $params
     * @return array|bool|null
     */
    public function getElems($elem_ids, $params = false){
						
		return null;
	
	}


}
