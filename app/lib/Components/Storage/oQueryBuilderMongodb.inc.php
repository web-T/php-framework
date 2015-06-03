<?php
/**
 * MongoDB query builder
 * Need >= 2.6 version
 *
 * Date: 17.01.15
 * Time: 18:06
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 * 
 * Changelog:
 *	1.0	17.01.2015/goshi 
 */

namespace webtFramework\Components\Storage;

use webtFramework\Interfaces\oModel;

class oQueryBuilderMongodb extends oQueryBuilderAbstract{

    /**
     * native data types, which don't need quotes embracing
     * @var array
     */
    protected $_native_types = array('boolean', 'bool', 'int', 'integer', 'float', 'double', 'unixtimestamp');

    /**
     * replacing in query with changing coded substring to normal values
     *
     * @param oModel|string $source
     * @param string $query
     * @return string
     */
    protected function _queryReplace($source, $query){

        $model = $this->createModel($source);

        $from = array('[PRIMARY]');
        $to = array($model->getPrimaryKey());

        return str_replace($from, $to, $query);

    }

    public function compile($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        //dump_file(array('compile' => $conditions));

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $query = $where;

        $table = $this->quoteField($model->getModelTable());

        if (!isset($conditions['type']))
            $conditions['type'] = 'aggregate';

        if (isset($conditions['index']) && !empty($conditions['index'])){

            throw new \Exception('error.queryBuilder.index_not_supported');

        }

        if (isset($conditions['select']) && !empty($conditions['select'])){

            $query = array_merge_recursive_distinct($query, $this->compileSelect($model, $conditions), 'combine');

        } else {

            // no selection made, so we get all fields

        }

        if (!isset($query['$sort'])){
            $query['$sort'] = array();
        }

        if (isset($conditions['order']) && is_array($conditions['order']) && !empty($conditions['order'])){

            $query['$sort'] = array_merge($query['$sort'], $this->compileOrderValue($model, $conditions['order']));

        } else {
            // getting sort by fields
            if ($model->getModelFields()){
                foreach ($model->getModelFields() as $k => $v){
                    if (isset($v['default_sort']) && $v['default_sort']){

                        $query['$sort'][$this->quoteField($k)] = mb_strtolower($v['order']) == 'desc' ? -1 : 1;
                        break;
                    }
                }
            }

        }

        if (isset($conditions['limit'])){
            if (!isset($conditions['begin'])){
                $conditions['begin'] = 0;
            }
            $query['$limit'] = (int)$conditions['limit'];
            $query['$skip'] = (int)$conditions['begin'];

        }

        // now fix all keys
        if (isset($query['$group']) && !empty($query['$group'])){
            foreach ($query['$group'] as $k => $v){
                $query['$group'][$this->_queryReplace($model, $k)] = $v;
            }
        }

        if (isset($query['$project']) && !empty($query['$project'])){
            foreach ($query['$project'] as $k => $v){
                $query['$project'][$this->_queryReplace($model, $k)] = $v;
            }
        }

        // check if $grouping is not empty and add fields to the $project
        if (!empty($query['$group']) && !empty($query['$project'])){

            foreach ($query['$group'] as $k => $v){

                $k = $this->_queryReplace($model, $k);

                if ($k != '_id' && !isset($query['$project'][$k]) && isset($model->getModelFields()[$k])){
                    $query['$project'][$k] = 1;
                }

            }

        }

        if (!$conditions['no_array_key']){
            $query['$group_key'] = $model->getPrimaryKey();
        }


        return array('query' => $query, 'table' => $table, 'type' => $conditions['type']);


    }


    /**
     * @param string|oModel $source
     * @param array $data
     * @param bool $is_multi_insert
     * @return array|string
     * @throws \Exception
     */
    public function compileInsert($source, $data = array(), $is_multi_insert = false){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!is_array($data)/* || ($data && empty($data))*/)
            throw new \Exception('error.queryBuilder.no_model_data_to_insert');

        $model = $this->createModel($source);

        $table = $this->quoteField($model->getModelTable());

        $rows = array();

        $add = array();

        if (!$is_multi_insert)
            $data = array($data);

        $inc = 0;

        // check for empty data
        foreach ($data as $row){

            if ($model->getIsMultilang()){

                // check for subquery - it is not supported by Mongo
                if (isset($row[$model->getPrimaryKey()]) && isset($row[$model->getPrimaryKey()]['subquery'])){

                    $eq = null;

                    $sql = $this->compile($model, $row[$model->getPrimaryKey()]['subquery']);

                    $row[$model->getPrimaryKey()] = $this->_p->db->selectCell($sql, $model->getModelStorage()) + $inc;

                    //dump(array('new_max_id' => $row[$model->getPrimaryKey()]), false);

                    $inc++;

                }

                // check for subquery - it is not supported by Sphinx
                if (isset($row[$model->getPrimaryKey()]) && (!isset($row['id']) || !$row['id'])){

                    $row['id'] = $row[$model->getPrimaryKey()];

                }

            } elseif (!isset($row[$model->getPrimaryKey()]) || !$row[$model->getPrimaryKey()]){

                $sql = $this->compile($model, array(
                    'no_array_key' => true,
                    'select' => array(
                        'a' => array(
                            array(
                                'function' => 'max()',
                                'field' => $model->getPrimaryKey(),
                                'equation' => '+1'
                            )
                        )
                    )
                ));

                $row[$model->getPrimaryKey()] = $this->_p->db->selectCell($sql, $model->getModelStorage()) + $inc;

                // fix for first row
                if ($row[$model->getPrimaryKey()] == 0)
                    $row[$model->getPrimaryKey()] = 1;

                //dump(array('new_max_id' => $row[$model->getPrimaryKey()]), false);

                $inc++;

            }

            if (isset($row[$model->getPrimaryKey()])){
                if (!isset($add['primary']))
                    $add['primary'] = array();

                $add['primary'][] = $row[$model->getPrimaryKey()];
            }

            foreach ($row as $field => $value){
                if (!isset($model->getModelFields()[$field])){

                    unset($row[$field]);

                } else {
                    $row[$field] = $this->quote($model->getModelFields()[$field], $value);
                }
            }
            $rows[] = $row;
        }

        if (!$is_multi_insert && isset($add['primary'])){
            $add['primary'] = current($add['primary']);
        }

        return array_merge(array('value' => $rows, 'table' => $table, 'type' => 'insert'), $add);

    }

    public function compileUpdate($source, $data = array(), $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!$data || !is_array($data) || ($data && empty($data)))
            throw new \Exception('error.queryBuilder.no_model_data_to_update');

        $model = $this->createModel($source);

        $result = array('type' => 'update');

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $result['query'] = $where;

        $result['table'] = $model->getModelTable();

        $inc = $set = array();

        foreach ($data as $field => $value){

            $f = $this->_queryReplace($model, $this->quoteField($field));

            if (isset($model->getModelFields()[$f])){

                if (preg_match('/^(\++|\--)(.*)$/is', $value, $match)){

                    $inc[$f] = $match[1] == '--' ? 0 - $this->quote($model->getModelFields()[$f], $match[2]) : $this->quote($model->getModelFields()[$f], $match[2]);

                } else {

                    $set[$f] = $this->quote($model->getModelFields()[$f], $value);

                }
            }
        }

        $result['value'] = array();

        if (!empty($inc)){

            $result['value']['$inc'] = $inc;

        }

        if (!empty($set)){

            $result['value']['$set'] = $set;

        }

        if (isset($conditions['limit'])){

            $result['multi'] = false;

        } else {

            $result['multi'] = true;

        }

        return $result;

    }


    public function compileDelete($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!is_array($conditions) || ($conditions && empty($conditions)))
            throw new \Exception('error.queryBuilder.no_model_data_to_delete');

        $model = $this->createModel($source);

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $table = $this->quoteField($model->getModelTable());

        if (isset($conditions['limit'])){
            throw new \Exception('error.queryBuilder.delete_limit_not_supported');
        }

        return array('query' => $where, 'table' => $table, 'type' => 'remove');

    }

    public function compileTruncate($source){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        return array('type' => 'remove', 'table' => $model->getModelTable());

    }

    public function compileOptimize($source){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        return array('type' => 'find', 'table' => $this->createModel($source)->getModelTable());


    }

    public function compileCount($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        // parse conditions
        if (isset($conditions['index_count']) && !empty($conditions['index_count'])){

            throw new \Exception('error.queryBuilder.index_count_not_supported');

        } elseif (isset($conditions['index']) && !empty($conditions['index'])){

            throw new \Exception('error.queryBuilder.index_not_supported');

        }

        $result = array('query' => $where, 'table' => $this->quoteField($model->getModelTable()), 'type' => 'count');

        if ($conditions['count_distinct']){
            $result['type'] = 'distinct';
            if (!is_string($conditions['count_distinct'])){
                $result['value'] = $model->getPrimaryKey();
            } else {
                $result['value'] = $this->quoteString($conditions['count_distinct']);
            }
        }

        $result['count'] = 'ncount';

        return $result;

    }


    public function compileConditions($source, $conditions = array()){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        $query = $group = $where = array();

        if (isset($conditions['where'])){

            if (!is_array($conditions['where']))
                $conditions['where'] = array($conditions['where']);

            $where = array_merge($this->compileWhere($model, $conditions['where']));
            if ($where){
                $conditions = array_merge($conditions, $where);
            }

        }

        // grouping especially by mongo rules
        if (isset($conditions['group']) && !empty($conditions['group'])){
            $conditions['group'] = (array)$conditions['group'];

            foreach ($conditions['group'] as $k => $v){
                if (!trim($v)){
                    unset($conditions['group'][$k]);
                } else {
                    $v = $this->quoteField($v);
                    $group[$v] = '$'.$v;
                }
            }
            if (!empty($group['_id']))
                $group = array('_id' => $group);
        }

        if (!empty($where)){
            $query['$match'] = $where;
        }

        if (!empty($group)){
            $query['$group'] = $group;
        }

        // add some spaces before and after query for protection
        return $query;


    }

    /**
     * parse equations and prepare $project structure
     * supports only simplest equations, like (+|-|*|/|)
     * @param string|null $field
     * @param string $base_equation
     * @param array $parsed
     * @return array
     */
    protected function _parseEquation($field, $base_equation, $parsed = array()){

        if (($eq = explode('+', $base_equation)) && count($eq) > 1){

            $f = trim($eq[0]) != '' ? trim($eq[0]) : $field;
            $parsed[$f] = array('$add' => array('$'.$f, (float)trim($eq[1])));

        } elseif (($eq = explode('-', $base_equation)) && count($eq) > 1) {

            $f = trim($eq[0]) != '' ? trim($eq[0]) : $field;
            $parsed[$f] = array('$subtract' => array('$'.$f, (float)trim($eq[1])));

        } elseif (($eq = explode('/', $base_equation)) && count($eq) > 1) {

            $f = trim($eq[0]) != '' ? trim($eq[0]) : $field;
            $parsed[$f] = array('$divide' => array('$'.$f, (float)trim($eq[1])));

        } elseif (($eq = explode('*', $base_equation)) && count($eq) > 1) {

            $f = trim($eq[0]) != '' ? trim($eq[0]) : $field;
            $parsed[$f] = array('$multiply' => array('$'.$f, (float)trim($eq[1])));

        }

        return $parsed;

    }

    public function compileSelect(oModel $source, &$conditions){

        $query = array();

        if (is_array($conditions['select'])){

            $selected = array();
            $cut_fields = array();
            $add_values = array();
            $project = array();

            $select_all = false;

            foreach ($conditions['select'] as $k => $v){

                if ($v !== '' && $v !== null){

                    $conditions['no_array_key'] = true;
                    // virtualize primary for results
                    if ($k == '__groupkey__'){

                        if ($v){
                            $query['$group_key'] = $this->quoteField($v);
                        }

                    } else {

                        if (!is_array($v))
                            $v = explode(',', $v);

                        foreach ($v as $i => $z){

                            if ($z == '*'){
                                $select_all = true;
                                continue;
                            }

                            if ($z !== null){

                                if (is_array($z)){

                                    if (isset($z['field'])){

                                        $tmp_field = $this->quoteField($z['field']);

                                        // check for function
                                        if (isset($z['function'])){
                                            switch (mb_strtolower($z['function'])){

                                                case 'max()':
                                                    $selected[isset($z['nick']) ? $z['nick'] : 'count'] = array('$max' => '$'.$tmp_field);
                                                    break;

                                                case 'min()':
                                                    $selected[isset($z['nick']) ? $z['nick'] : 'count'] = array('$min' => '$'.$tmp_field);
                                                    break;

                                                case 'count()':
                                                    $selected[isset($z['nick']) ? $z['nick'] : 'count'] = array('$sum' => 1);
                                                    break;

                                                case 'lower()':
                                                    $project[$tmp_field] = array('$toLower' => '$'.$tmp_field);
                                                    $add_fields[] = $tmp_field;
                                                    //$selected[isset($z['nick']) ? $z['nick'] : $tmp_field] = array('$first' => '$'.$tmp_field);
                                                    break;
                                            }
                                            if (isset($z['equation'])){

                                                $p_eq = $this->_parseEquation($z['field'], $z['equation']);

                                                if (!empty($p_eq)){
                                                    $project = array_merge($project, $p_eq);
                                                }
                                            }

                                        } else {
                                            $project[isset($z['nick']) ? $z['nick'] : $tmp_field] = 1;
                                            //$add_fields[] = isset($z['nick']) ? $z['nick'] : $tmp_field;
                                            //$selected[isset($z['nick']) ? $z['nick'] : $tmp_field] = array('$first' => '$'.$tmp_field);
                                        }

                                    } elseif (isset($z['value']) && isset($z['nick'])){
                                        //$cut_fields[] = $z['nick'];
                                        $project[$z['nick']] = array('$literal' => $this->quoteString($z['value']));
                                        //$selected[$z['nick']] = array('$first' => $this->quoteString($z['value']));

                                    }

                                } else {

                                    $tmp_field = $this->quoteField(trim($z));
                                    //$cut_fields[] = $tmp_field;
                                    $project[$tmp_field] = 1;
                                    //$selected[$tmp_field] = array('$first' => '$'.$tmp_field);

                                }
                            }
                        }

                    }
                } else {
                    unset($conditions['select'][$k]);
                }
            }

            if ($select_all && !empty($project)){

                $model = $this->createModel($source);

                if ($model->getModelFields()){
                    foreach ($model->getModelFields() as $field => $f){
                        if ($f['type'] != 'virtual' && !isset($project[$field])){
                            $project[$field] = 1;
                        }
                    }
                }

            }

            if (!empty($project)){

                if (!isset($query['$project'])){
                    $query['$project'] = array();
                }

                $query['$project'] = array_merge($query['$project'], $project);

            }

            if (!empty($selected)){

                if (!isset($query['$group'])){
                    $query['$group'] = array();
                }

                $query['$group'] = array_merge($query['$group'], $selected);

            }

        } else {
            // now selection
            // maybe yo want who had made this quesries
        }

        // we couldnot return anything, because we are patching base conditions
        return $query;
    }


    public function compileOrderValue($source, $value = null){

        $order = array();
        if (isset($value) && is_array($value) && !empty($value)){

            // if we have set of arrays
            foreach ($value as $k => $v){

                // check for complex value
                if (is_array($v)){

                    if (isset($v['function'])){
                        throw new \Exception('error.queryBuilder.order_functions_not_supported');
                    } else {
                        $order[$this->quoteField($k)] = strtolower($v['order']) == 'desc' ? -1 : 1;
                    }

                } else {

                    $order[$this->quoteField($k)] = strtolower($v) == 'desc' ? -1 : 1;

                }
            }

        }

        return $order;

    }

    /**
     * compile where array, which you can join by any operator
     *
     * @param $source
     * @param $conditions
     * @return array
     */
    protected function _compileWhereArray($source, $conditions){

        $where = array();

        if (!$conditions)
            return $where;

        $model = $this->createModel($source);

        foreach ($conditions as $k => $v){

            // detect if we have array
            if (!is_numeric($k) && (is_scalar($v) || $v === null)){

                // we have simple value
                $z = $this->compileWhereValue($model, $k, array('value' => trim($v), 'op' => '='));

                if ($z != '')
                    $where = array_merge($where, $z);

            } elseif (!is_numeric($k) && is_array($v)){

                if ($k == '$or'){

                    $v = $this->compileOr($model, $v);

                    if ($v != '')
                        $where = array_merge($where, $v);

                } elseif ($k == '$and'){

                    $v = $this->compileAnd($model, $v);

                    if ($v != '')
                        $where = array_merge($where, $v);

                } else {

                    $v = $this->compileWhereValue($model, $k, $v);

                    if ($v != '')
                        $where = array_merge($where, $v);

                }


            } elseif (is_numeric($k) && is_array($v)){

                $field = isset($v['key']) ? $v['key'] : $v['field'];
                $v = $this->compileWhereValue($model, $field, $v);

                if ($v != '')
                    $where = array_merge($where, $v);

            }/* else {

                $v = trim($v);
                if ($v != '')
                    $where[] = $v;
            }*/

        }

        unset($model);

        return $where;

    }


    public function compileWhere($source, $conditions, $op = 'AND'){

        $where = array();

        if (!$conditions)
            return $where;

        $model = $this->createModel($source);

        $where = $this->_compileWhereArray($model, $conditions);

        unset($model);

        return $where;

    }

    public function compileJoin($conditions){

        throw new \Exception('error.queryBuilder.join_not_supported');


    }

    public function compileWhereValue(oModel $model, $field_name, $field_value){

        // checking for subquery
        reset($field_value);

        if (!(isset($field_value['value']) || isset($field_value['subquery'])))
            return null;

        $cond = isset($field_value['op']) ? $cond = $this->quoteString($field_value['op']) : '=';

        if (isset($field_value['key']) && $field_value['key'])
            $key = $field_value['key'];
        elseif (isset($field_value['field']) && $field_value['field']){
            $key = $field_value['field'];
        } else {
            $key = $field_name;
        }

        $native_keys = $key;
        //dump(array($key, $field_name), false);
        $key = $this->_queryReplace($model, $this->quoteField($key));
        $field = $model->getModelFields()[is_array($key) ? current($key) : $key];

        if (isset($field_value['subquery']) && $field_value['subquery']){

            throw new \Exception('error.queryBuilder.subquery_not_supported');

        } elseif ((trim(mb_strtolower($cond)) == 'in' || trim(mb_strtolower($cond)) == 'not in')){

            $op = trim(mb_strtolower($cond));

            if (!is_array($field_value['value']))
                $field_value['value'] = array($field_value['value']);

            $self = &$this;
            array_walk($field_value['value'], function(&$value) use (&$self, &$field, &$model){

                $value = $self->quote($field, $value);

            });

            $value = array($op == 'in' ? '$in' : '$nin' => array_values($field_value['value']));

        } elseif (is_array($field_value['value']) && trim(mb_strtolower($cond)) == 'mva_in'){

            $value = array();
            foreach ($field_value['value'] as $z){
                $value[] = array($key => $this->quote($field, $z));
            }

            $value = array('$or' => $value);

        } elseif (is_array($field_value['value']) && trim(mb_strtolower($cond)) == 'between'){

            $value = array('$gt' => $this->quote($field, $field_value['value'][0]), '$lt'=> $this->quote($field, $field_value['value'][1]));

        } elseif (trim(mb_strtolower($cond)) == '>='){

            $value = array('$gte' => $this->quote($field, $field_value['value']));

        } elseif (trim(mb_strtolower($cond)) == '>'){

            $value = array('$gt' => $this->quote($field, $field_value['value']));

        } elseif (trim(mb_strtolower($cond)) == '<='){

            $value = array('$lte' => $this->quote($field, $field_value['value']));

        } elseif (trim(mb_strtolower($cond)) == '<'){

            $value = array('$lt' => $this->quote($field, $field_value['value']));

        } elseif (trim(mb_strtolower($cond)) == '<>'){

            $value = array('$ne' => $this->quote($field, $field_value['value']));

        } elseif ((trim(mb_strtolower($cond)) == 'like' || trim(mb_strtolower($cond)) == 'not like')) {

            $value = '/^'.$this->quoteString(str_replace(array('.*', '/'), array('', '\/'), $field_value['value']))."$/";

        } elseif (trim(mb_strtolower($cond)) == 'search') {

            return $this->compileSearch($model,
                isset($field_value['sfields']) ? $field_value['sfields'] : $native_keys,
                $field_value['value'],
                isset($field_value['wmode']) ? $field_value['wmode'] : 'or',
                isset($field_value['prefix']) ? $field_value['prefix'] : false,
                isset($field_value['fulltext']) ? $field_value['fulltext'] : false, // fullsearch index works very badly
                isset($field_value['innermode']) ? $field_value['innermode'] : 'or');

        } elseif (isset($field_value['function']) && $field_value['function']){

            throw new \Exception('error.queryBuilder.functions_not_supported');

        } elseif (isset($field_value['type']) && $field_value['type'] == 'foreign_key'){

            // TODO: maybe in the future make search in embedded documents $elemMatch
            throw new \Exception('error.queryBuilder.foreign_keys_not_supported');

        } else {
            //dump(array($field, $field_value['value']), false);
            $value = $this->quote($field, $field_value['value']);
        }

        $sql_add = array($key => $value);

        return $sql_add;

    }


    public function compileOr($source, $conditions = array()){

        if ($conditions){

            $final = $this->_compileWhereArray($source, $conditions);

            unset($conditions);

            return array('$or' => $final);

        } else
            return '';

    }

    public function compileAnd($source, $conditions = array()){

        if ($conditions){

            $final = $this->_compileWhereArray($source, $conditions);

            unset($conditions);

            return array('$and' => $final);

        } else
            return '';

    }


    public function compileSearch($source, $sfields, $keywords, $wmode = '', $prefix = false, $fulltext = false, $innermode = 'or'){

        $model = $this->createModel($source);


        if (!is_array($sfields))
            $sfields = array($sfields);

        foreach ($sfields as $k => $v){
            if (!isset($model->getModelFields()[$v]))
                unset($sfields[$k]);
            else
                $sfields[$k] = $this->quoteField($v);
        }

        $tmp_keys = $keywords;

        if (!is_array($tmp_keys))
            $tmp_keys = array($tmp_keys);

        foreach ($tmp_keys as $k => $v){
            $tmp_keys[$k] = $this->quoteString($v);
        }

        if ($fulltext){
            $query = array('$text' => array('$search' => join(' ', $tmp_keys), '$language' => 'ru'));
        } else {
            $query = array();
            foreach ($sfields as $field){
                $query[] = array($field => array('$regex' => join(' ', $tmp_keys), '$options' => 'i'));
            }
            $query = array('$or' => $query);
        }


        return $query;

    }

    public function quoteField($field_name){

        if (!is_array($field_name)){
            $not_array = true;
            $field_name = array($field_name);
        } else {
            $not_array = false;
        }

        foreach ($field_name as $k => $f){

            $field_name[$k] = $this->quoteString($f);

        }

        if ($not_array)
            return current($field_name);
        else
            return $field_name;

    }

    public function quote($field, $value){

        if (is_array($field)){

            switch ($field['type']){

                case 'integer':
                case 'int':
                case 'boolean':
                case 'bool':
                case 'unixtimestamp':
                    return (int)$value;
                    break;

                case 'float':
                case 'double':
                    return (float)$value;
                    break;

                case 'date':
                case 'datetime':
                    return new \MongoDate(strtotime($value));
                    break;

                default:
                    return $value;//$this->quoteString($value);
                    break;

            }

        } else {
            return $this->quoteString($value);
        }

    }


    public function quoteString($s){

        $replaceQuote = "\\'";

        if (!get_magic_quotes_gpc()) {

            if ($replaceQuote[0] == '\\'){
                // only since php 4.0.5
                $s = arr_replace(array('\\',"\0"), array('\\\\',"\\\0"), $s);
            }
            $s = arr_replace('"','\\"',$s);

            return  arr_replace("'", $replaceQuote, $s);
        }

        // undo magic quotes for "
        $s = arr_replace('\\"','"',$s);

        if ($replaceQuote == "\\'")  // ' already quoted, no need to change anything
            return $s;
        else {// change \' to '' for sybase/mssql
            $s = arr_replace('\\\\','\\',$s);
            return arr_replace("\\'", $replaceQuote, $s);
        }


    }


    public function describeTable($source, $no_cache = false){

        if (!$source)
            throw new \Exception('error.queryBuilder.table_name_not_defined');

        // detect table source
        if (is_array($source)) {
            // if it is array - then it is always array of fields
            return $source;

        } elseif (!($source instanceof oModel)){

            throw new \Exception('error.queryBuilder.describe_not_supported');

        }

        $table_fields = array();

        if ($no_cache || !($table_fields = $this->_p->cache->getSerial('describe.'.get_class().'.'.(string)$source->getModelTable()))){

            foreach ($source->getModelFields() as $field => $v){
                $table_fields[] = $field;
            }

            $this->_p->cache->saveSerial('describe.'.get_class().'.'.(string)$source->getModelTable(), $table_fields);

        }
        return $table_fields;

    }

    public function isFieldExists($source, $field){

        $table_fields = $this->describeTable($source);

        return is_array($table_fields) && in_array($field, $table_fields);

    }


    public function getPrimary($source){

        // detect source
        $table_fields = null;
        if (is_object($source) && $source instanceof oModel){

            return $source->getPrimaryKey();

        } elseif (is_array($source)) {

            $table_fields = $source;

        } elseif (is_string($source)){

            $table_fields = $this->describeTable($source);

        }

        if ($table_fields){
            if (in_array('real_id', $table_fields))
                return 'real_id';
            elseif (in_array('id', $table_fields))
                return 'id';
        }

        throw new \Exception('error.queryBuilder.no_primary');

    }

    public function getSchema($source, $no_cache = false){

        throw new \Exception('error.queryBuilder.schema_not_supported');

    }


} 