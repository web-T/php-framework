<?php
/**
 * ...
 *
 * Date: 21.11.14
 * Time: 14:01
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	21.11.2014/goshi 
 */

namespace webtFramework\Components\Storage;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oModel;

abstract class oQueryBuilderAbstract implements iQueryBuilder{

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){

        $this->_p = $p;

    }

    /**
     * method create model from source
     * @param string|oModel $source
     * @return oModel
     * @throws \Exception
     */
    public function createModel($source){

        if (!$source){
            throw new \Exception('error.queryBuilder.no_source_defined');
        }

        if ($source instanceof oModel){
            return $source;
        } else {

            // try to get schema of the table
            $fields = $this->getSchema($source);

            if (!$fields){
                throw new \Exception('error.queryBuilder.cannot_build_model_schema');
            }
            $model = new oModel($this->_p);
            $model->setModelFields($fields);
            // set noindex mode for custom models
            $model->setIsNoindex(true);
            // set no clear cache for custom models
            $model->setIsNoCacheClean(true);

            if (is_string($source)){
                $model->setModelTable($this->_p->getVar($source) ? $this->_p->getVar($source) : $source);
            }

            // reinit the model
            $model->init();

            // try to find multilang keys
            $keys = array();
            foreach ($fields as $k => $v){
                if ($k == 'real_id' || $k == 'lang_id'){
                    $keys[] = $k;
                }

                if (count($keys) == 2){
                    $model->setIsMultilang(true);
                    break;
                }
            }
            unset($keys);

            return $model;

        }

    }

    /**
     * quote value
     * @param $s
     * @return mixed
     */
    abstract public function quoteString($s);

    /**
     * compile query for database
     *
     * @param oModel|string $source
     * @param array $conditions
     * @param null|string $where precompiled where string
     * @return string
     * @throws \Exception
     */
    abstract public function compile($source, $conditions = array(), $where = null);

    /**
     * query conditions compiler
     * conditions consists of 'where', 'limit', 'index', 'select', 'join', 'order', 'group', 'no_def_fields', 'no_array_key'
     * @param oModel|string $source
     * @param array $conditions
     * @return string
     * @throws \Exception
     */
    abstract public function compileConditions($source, $conditions = array());

    /**
     * compile count query
     * @param $source
     * @param array $conditions
     * @param null $where
     * @return mixed
     */
    abstract public function compileCount($source, $conditions = array(), $where = null);

    /**
     * compile update query
     * @param $source
     * @param array $data
     * @param array $conditions
     * @param null $where
     * @return mixed
     */
    abstract public function compileUpdate($source, $data = array(), $conditions = array(), $where = null);

    /**
     * compile delete query
     * @param $source
     * @param array $conditions
     * @param null $where
     * @return mixed
     */
    abstract public function compileDelete($source, $conditions = array(), $where = null);

    /**
     * @param oModel|string $source
     * @param array $data
     * @param bool $is_multi_insert flag for multiinsert mode, when we insert more then one row in one query
     * @return string
     * @throws \Exception
     */
    abstract public function compileInsert($source, $data = array(), $is_multi_insert = false);

    /**
     * compile truncate query
     * @param $source
     * @return mixed
     */
    abstract public function compileTruncate($source);

    /**
     * compile optimize query
     * @param $source
     * @return mixed
     */
    abstract public function compileOptimize($source);


    /**
     * compile query of OR operator
     * @param $source
     * @param array $conditions
     * @return mixed
     */
    abstract public function compileOr($source, $conditions = array());

    /**
     * compile search query
     * @param $source
     * @param $sfields
     * @param $keywords
     * @param string $wmode
     * @param bool $prefix
     * @param bool $fulltext
     * @param string $innermode
     * @return mixed
     */
    abstract public function compileSearch($source, $sfields, $keywords, $wmode = '', $prefix = false, $fulltext = false, $innermode = 'or');



    /**
     * method return all fields of the table/collection (if possible)
     * @param oModel|array|string $source you can send string, model, or array of fields
     * @param bool $no_cache flag for don't use cache
     * @return mixed or array for the field names, or
     */
    abstract public function describeTable($source, $no_cache = false);

    /**
     * method try to find field in the specified source
     * @param mixed $source can be oModel, array of fields, table name or table nickname
     * @param string $field
     * @return mixed
     */
    abstract public function isFieldExists($source, $field);

    /**
     * detect primary field in SOMETHING
     * @param mixed $source can be oModel, array of fields, table name or table nickname
     * @return mixed
     */
    abstract public function getPrimary($source);

    /**
     * extract fields schema in standart array format
     * @param mixed $source can be oModel, array of fields, table name or table nickname
     * @return mixed
     */
    abstract public function getSchema($source);

} 
