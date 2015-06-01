<?php
/**
 * Sphinx query builder
 * Based on MySQLi extension
 * Each text index field MUST have 'search_index' => true property in model, then transformer changes them
 * to the right search fields with '_search' postfix
 *
 * Date: 12.03.15
 * Time: 18:16
 * @version 0.4
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	0.1	12.03.2015/goshi
 */

namespace webtFramework\Components\Storage;

use webtFramework\Interfaces\oModel;

class oQueryBuilderSphinx extends oQueryBuilderMysqli {

    protected $_add_alias_to_base_table = false;

    protected function _getIndexSource($source){

        $model = $this->createModel($source);

        return $model->getModelTable();
    }

    public function compile($source, $conditions = array(), $where = null){

        if (isset($conditions['index']) && !empty($conditions['index'])){
            throw new \Exception('error.queryBuilder.sphinx_not_supports_index_attr');
        }

        /*$model = $this->createModel($source);
        $this->_base_table_alias = $model->getModelTable();
        unset($model);*/

        return array('query' => parent::compile($source, $conditions, $where), 'index' => $this->_getIndexSource($source));

    }

    public function compileSelect(oModel $source, &$conditions){

        if (is_array($conditions['select'])){
            foreach ($conditions['select'] as $k => $v){
                if ($v !== '' && $v !== null){

                    if (is_array($v)){
                        foreach ($v as $z => $x){
                            // sphinxQL don't support named text constants
                            if (is_array($x) && isset($x['value'])){
                                unset($conditions['select'][$k][$z]);
                            }
                        }
                    }
                }
            }
        }

        return parent::compileSelect($source, $conditions);
    }

    /**
     * scan insert data and detect spinx indexed values
     * all search fields must be named with "_search" postfix
     * @param $model
     * @return array of indexes
     */
    protected function _getModelSearchIndexes(oModel $model){

        $search_indexes = array();
        if ($model->getModelFields()){
            foreach ($model->getModelFields() as $k => $v){
                if (isset($v['search_index']) && $v['search_index']){
                    $search_indexes[$k] = 1;
                }
            }
        }

        return $search_indexes;

    }

    /**
     * @param string|oModel $source
     * @param array $data
     * @param bool $is_multi_insert
     * @return array|string
     */
    public function compileInsert($source, $data = array(), $is_multi_insert = false){

        // try to find index and change query
        $model = $this->createModel($source);

        $add = array();

        if (!$is_multi_insert)
            $data = array($data);

        // scan insert data and detect spinx indexed values
        $search_indexes = $this->_getModelSearchIndexes($model);

        $newmodel = null;

        foreach ($data as $k => $row){

            $primary_set = false;

            if (!empty($search_indexes)){

                // cloning model and update fields in it
                if (!$newmodel)
                    $newmodel = clone $model;

                foreach ($search_indexes as $index => $z){
                    if (isset($row[$index])){
                        $row[$index.'_search'] = $row[$index];
                        $newmodel->addModelField($k.'_search', $model->getModelFields()[$k]);
                    }
                }
            }

            if ($model->getIsMultilang()){

                // check for subquery - it is not supported by Sphinx
                if (isset($row[$model->getPrimaryKey()]) && isset($row[$model->getPrimaryKey()]['subquery'])){

                    $eq = null;
                    // there is a lot of primary hacks
                    if (isset($row[$model->getPrimaryKey()]['subquery']['select']['a'][0]['equation'])){
                        $eq = $row[$model->getPrimaryKey()]['subquery']['select']['a'][0]['equation'];
                        unset($row[$model->getPrimaryKey()]['subquery']['select']['a'][0]['equation']);
                    }

                    $sql = $this->compile($model, $row[$model->getPrimaryKey()]['subquery']);

                    if (!isset($add['primary'])){
                        $add['primary'] = array();
                    }

                    $add['primary'][] = $row[$model->getPrimaryKey()] = $this->_p->db->selectCell($sql, $model->getModelStorage());

                    if ($eq){
                        $x = null;
                        eval('$x='.$row[$model->getPrimaryKey()].$eq.';');
                        $row[$model->getPrimaryKey()] = $x;

                        $add['primary'][count($add['primary']) - 1] = $x;
                        $primary_set = true;
                        unset($x);
                    }

                }

            }

            if (isset($model->getModelFields()['id']) && (!isset($row['id']) || $row['id'] == 0)){
                $sql = $this->compile($model, array(
                    'no_array_key' => true,
                    'select' => array(
                        'a' => array(
                            array(
                                'field' => 'id',
                                'function' => 'max()'
                            )
                        )
                    ),
                    'limit' => 1
                ));

                $max = $this->_p->db->selectCell($sql, $model->getModelStorage());
                if (!$max)
                    $max = 0;

                $row['id'] = $max+1;

                if ($add['id'])
                    $add['id'] = array();

                $add['id'][] = $row['id'];
                if (!$primary_set){
                    if (!isset($add['primary']))
                        $add['primary'] = array();

                    $add['primary'][] = $row['id'];
                }

            }

            $data[$k] = $row;

        }

        if (!$is_multi_insert){
            $data = current($data);
            if (isset($add['id'])){
                $add['id'] = current($add['id']);
            }
            if (isset($add['primary'])){
                $add['primary'] = current($add['primary']);
            }
        }

        return array_merge(array('query' => parent::compileInsert($newmodel ? $newmodel : $source, $data, $is_multi_insert), 'type' => 'insert', 'index' => $this->_getIndexSource($source)), $add);

    }

    public function compileUpdate($source, $data = array(), $conditions = array(), $where = null){

        $model = $this->createModel($source);

        // restore id
        if (!isset($data['id']) || $data['id']){

            if (isset($conditions['where']['id']) && is_numeric($conditions['where']['id'])){
                $data['id'] = $conditions['where']['id'];
            } else {

                // extract id from row and inject it to the data
                $sql = $this->compile($source, $conditions);
                $row = $this->_p->db->selectRow($sql, $model->getModelStorage());
                if ($row){
                    $data['id'] = $row['id'];
                } else {
                    throw new \Exception('error.db.sphinx_cannot_update_without_id');
                }

            }

        }

        $search_indexes = $this->_getModelSearchIndexes($model);
        $newmodel = null;

        if ($search_indexes){

            // cloning model and update fields in it
            $newmodel = clone $model;

            foreach ($search_indexes as $k => $v){
                if (isset($data[$k])){
                    $data[$k.'_search'] = $data[$k];
                    $newmodel->addModelField($k.'_search', $model->getModelFields()[$k]);
                }
            }
        }

        //dump_file(array('query' => preg_replace('/^INSERT\s+(.*)$/', 'REPLACE $1', parent::compileInsert($newmodel ? $newmodel : $source, $data)), 'type' => 'update', 'index' => $this->_getIndexSource($source)));

        return array('query' => preg_replace('/^INSERT\s+(.*)$/', 'REPLACE $1', parent::compileInsert($newmodel ? $newmodel : $source, $data)), 'type' => 'update', 'index' => $this->_getIndexSource($source));

    }

    public function compileDelete($source, $conditions = array(), $where = null){

        return array('query' => parent::compileDelete($source, $conditions, $where), 'type' => 'delete', 'index' => $this->_getIndexSource($source));

    }

    /**
     * truncate supports only RT indexes now
     *
     * @param $source
     * @param array $conditions
     * @param null $where
     * @return array|string
     * @throws \Exception
     */
    public function compileTruncate($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        return array('query' => 'TRUNCATE RTINDEX '.$model->getModelTable(), 'type' => 'truncate', 'index' => $this->_getIndexSource($source));

    }

    /**
     * only for RT indexes
     * @param $source
     * @return array|string
     * @throws \Exception
     */
    public function compileOptimize($source){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        return array('query' => 'OPTIMIZE INDEX '.$model->getModelTable(), 'index' => $this->_getIndexSource($source));

    }

    public function compileCount($source, $conditions = array(), $where = null){

        if ((isset($conditions['index_count']) && !empty($conditions['index_count'])) || (isset($conditions['index']) && !empty($conditions['index']))){
            throw new \Exception('error.queryBuilder.sphinx_not_supports_index_attr');
        }

        /*$model = $this->createModel($source);
        $this->_base_table_alias = $model->getModelTable();
        unset($model); */

        return array('query' => parent::compileCount($source, $conditions, $where), 'index' => $this->_getIndexSource($source));

    }

    /**
     * @param $source
     * @param $sfields
     * @param $keywords
     * @param string $wmode
     * @param bool $prefix
     * @param bool $fulltext
     * @param string $innermode
     * @return bool|string
     *
     * TODO: look at the MAYBE operator in new versions of Sphinx
     */
    public function compileSearch($source, $sfields, $keywords, $wmode = '', $prefix = false, $fulltext = false, $innermode = 'or'){

        $model = $this->createModel($source);

        // making table prefix
        if ($prefix)
            $prefix = $this->quoteField($prefix, $model->getModelStorage()).".";
        else
            $prefix = "";

        if (!is_array($sfields))
            $sfields = array($sfields);

        foreach ($sfields as $k => $v){
            if (!isset($model->getModelFields()[$v]))
                unset($sfields[$k]);
            elseif (isset($model->getModelFields()[$v]['search_index']) && $model->getModelFields()[$v]['search_index']){

                $sfields[$k] = (strpos($v, '.') === false ? $prefix : '').$this->quoteField($v.'_search', $model->getModelStorage());

            } else
                $sfields[$k] = (strpos($v, '.') === false ? $prefix : '').$this->quoteField($v, $model->getModelStorage());
        }

        $tmp_keys = $keywords;

        if (!is_array($tmp_keys))
            $tmp_keys = array($tmp_keys);

        foreach ($tmp_keys as $k => $v){
            $tmp_keys[$k] = $this->quoteString($v, $model->getModelStorage());
        }

        $query = 'MATCH(\''.join(' ', $tmp_keys).'\')';

        if ($query)
            $query = "(".$query.")";

        //dump_file($query);

        return $query;

    }


    public function getSchema($source, $no_cache = false){

        if (!$source)
            throw new \Exception('error.queryBuilder.schema_name_not_defined');

        // detect source
        if (is_object($source) && $source instanceof oModel){

            return $source->getModelFields();

        } elseif (is_array($source)) {

            return $source;

        }

        // additional checking for nickname
        if (isset($this->_p->getVar('tables')[$source])){
            $source = $this->_p->getVar('tables')[$source];
        }

        $table_fields = array();

        if ($no_cache || !($table_fields = $this->_p->cache->getSerial('schema.'.get_class().'.'.(string)$source))){

            // check if table exists
            $sql = 'SHOW TABLES';
            $table = $this->_p->db->select($sql);
            $table_exists = false;

            if ($table){
                foreach ($table as $t){
                    if ($t['Index'] == $source){
                        $table_exists = true;
                        break;
                    }
                }
            }

            if ($table_exists){

                // describe table
                $sql = 'DESCRIBE '.$this->quoteString($source);
                $res = $this->_p->db->select($sql);

                if ($res){

                    $primary = null;
                    $i = 0;
                    foreach ($res as $data){

                        $type = trim($data['Type']);

                        switch (mb_strtolower($type)){

                            case 'string':
                            case 'json':
                                $type = 'text';
                                break;

                            case 'bool':
                                $type = 'boolean';
                                break;

                            case 'integer':
                            case 'uint':
                            case 'bigint':
                                if (preg_match('/^is_.*$/is', $data['Field']))
                                    $type = 'boolean';
                                else
                                    $type = 'int';
                                break;

                            case 'float':
                                $type = 'float';
                                break;

                            case 'timestamp':
                                $type = 'unixtimestamp';
                                break;

                            default:
                                $type = 'varchar';
                                break;

                        }

                        $table_fields[$data['Field']] = array(
                            'type' => $type
                        );


                        if ($i == 0){
                            $primary = $data['Field'];
                        }

                    }

                    if (isset($table_fields['real_id']) && isset($table_fields['lang_id'])){
                        $table_fields['real_id']['primary'] = true;
                    } else {
                        $table_fields[$primary]['primary'] = true;
                    }

                }
            }

            $this->_p->cache->saveSerial('schema.'.get_class().'.'.(string)$source, $table_fields);

        }

        return $table_fields;

    }

} 