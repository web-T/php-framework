<?php
/**
 * MySQL Query builder
 *
 * Date: 16.08.14
 * Time: 22:57
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 *
 * Changelog:
 *	1.0	16.08.2014/goshi
 */

namespace webtFramework\Components\Storage;

use webtFramework\Interfaces\oModel;

class oQueryBuilderMysql extends oQueryBuilderAbstract{

    /**
     * basic table alias
     * @var string
     */
    protected $_base_table_alias = 'a';

    /**
     * flag for add alias to the base table
     * @var bool
     */
    protected $_add_alias_to_base_table = true;

    /**
     * native data types, which don't need quotes embracing
     * @var array
     */
    protected $_native_types = array('boolean', 'bool', 'int', 'integer', 'float', 'double', 'unixtimestamp');

    /**
     * symbol for fields quoting
     * @var string
     */
    protected $_f_quote = '`';

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

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $base_table = 'FROM '.$this->quoteField($model->getModelTable(), $model->getModelStorage()).($this->_add_alias_to_base_table ? ' a ' : ' ');

        $order = 'ORDER BY ';

        $index = '';
        if (isset($conditions['index']) && !empty($conditions['index'])){

            if (is_array($conditions['index']))
                $index .= ' USE INDEX ('.join(',', $conditions['index']).') ';
            else
                $index .= ' USE INDEX ('.$conditions['index'].') ';

        }

        if (isset($conditions['select']) && !empty($conditions['select'])){

            $select = $this->compileSelect($model, $conditions);

        } else {
            $select = ($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '') .'*';
        }


        if (isset($conditions['order']) && is_array($conditions['order']) && !empty($conditions['order'])){

            $order .= $this->compileOrderValue($model, $conditions['order']);

        } else {
            // getting sort by fields
            $found_order = false;
            if ($model->getModelFields()){
                foreach ($model->getModelFields() as $k => $v){
                    if (isset($v['default_sort']) && $v['default_sort']){
                        $order .= ($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '').$this->quoteField($k, $model->getModelStorage()).' '.(mb_strtolower($v['order']) == 'desc' ? 'DESC' : 'ASC');
                        $found_order = true;
                        break;
                    }
                }
            }
            if (!$found_order)
                $order = ' ';
                //$order .= ' NULL';

        }

        if (isset($conditions['limit'])){
            if (!isset($conditions['begin'])){
                $conditions['begin'] = 0;
            }
            $limit = ' LIMIT '.(int)$conditions['begin'].','.(int)$conditions['limit'];
        } else {
            $limit = '';
        }

        return $this->_queryReplace($model, 'SELECT '.(!$conditions['no_array_key'] ? ($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '').$model->getPrimaryKey().' AS ARRAY_KEY,' : '').$select.' '.$base_table.$index.$where.$order.$limit);


    }



    public function compileInsert($source, $data = array(), $is_multi_insert = false){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!is_array($data)/* || ($data && empty($data))*/)
            throw new \Exception('error.queryBuilder.no_model_data_to_insert');

        $model = $this->createModel($source);

        $base_table = ' '.$this->quoteField($model->getModelTable(), $model->getModelStorage()).' ';

        $fields = $rows = array();
        $is_fields_defined = false;

        if (!$is_multi_insert)
            $data = array($data);

        foreach ($data as $row){
            $values = array();
            foreach ($row as $field => $value){
                if (isset($model->getModelFields()[$field])){

                    if (!$is_fields_defined)
                        $fields[$field] = $this->quoteField($field, $model->getModelStorage());

                    if (is_array($value)){
                        if (isset($value['subquery'])){

                            $sub = $this->compile($model, $value['subquery']);

                            $values[$field] = '('.(is_array($sub) ? $sub['query'] : $sub).')';
                        }
                    } else {
                        $value = $this->quote($model->getModelFields()[$field], $value, $model->getModelStorage());
                        $values[$field] = !in_array($model->getModelFields()[$field]['type'], $this->_native_types) ?  "'".$value."'" : $value;
                    }

                }
            }
            $is_fields_defined = true;
            $rows[] = '('.join(',', $values).')';
        }


        $sql = $this->_queryReplace($model, 'INSERT INTO '.$base_table.'('.join(',', $fields).') VALUES '.join(',', $rows));
        unset($rows);
        unset($fields);
        unset($values);

        return $sql;

    }

    public function compileUpdate($source, $data = array(), $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!$data || !is_array($data) || ($data && empty($data)))
            throw new \Exception('error.queryBuilder.no_model_data_to_update');

        $model = $this->createModel($source);

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $base_table = ' '.$this->quoteField($model->getModelTable(), $model->getModelStorage()).($this->_add_alias_to_base_table ? ' a ' : ' ');

        $updates = array();
        foreach ($data as $field => $value){
            if (isset($model->getModelFields()[$field])){

                $f = $this->quoteField($field, $model->getModelStorage());

                if (preg_match('/^(\++|\--)(.*)$/is', $value, $match)){
                    $value = $f.($match[1] == '--' ? '-' : '+')."'".$this->quote($model->getModelFields()[$field], $match[2], $model->getModelStorage())."'";
                    //$value = $f.$match[1].(float)$match[2];
                } else {
                    $value = $this->quote($model->getModelFields()[$field], $value, $model->getModelStorage());
                    $value = !in_array($model->getModelFields()[$field]['type'], $this->_native_types) ? "'".$value."'" : $value;
                }

                $updates[] = $f.'='.$value;
            }
        }

        if (isset($conditions['limit'])){
            if (!isset($conditions['begin'])){
                $conditions['begin'] = 0;
            }
            $limit = ' LIMIT '.(int)$conditions['begin'].','.(int)$conditions['limit'];
        } else {
            $limit = '';
        }

        return $this->_queryReplace($model, 'UPDATE '.$base_table.' SET '.join(',', $updates).$where.$limit);

    }


    public function compileDelete($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!is_array($conditions) || ($conditions && empty($conditions)))
            throw new \Exception('error.queryBuilder.no_model_data_to_delete');

        $model = $this->createModel($source);

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $base_table = ' '.$this->quoteField($model->getModelTable(), $model->getModelStorage()).' ';

        if (isset($conditions['limit'])){
            $limit = ' LIMIT '.(int)$conditions['limit'];
        } else {
            $limit = '';
        }

        return $this->_queryReplace($model, 'DELETE FROM '.$base_table.' '.$where.$limit);

    }

    public function compileTruncate($source){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        return 'TRUNCATE '.$model->getModelTable();

    }

    public function compileOptimize($source){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        return 'OPTIMIZE TABLE '.$model->getModelTable();

    }

    public function compileCount($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $base_table = 'FROM '.$this->quoteField($model->getModelTable(), $model->getModelStorage()).($this->_add_alias_to_base_table ? ' a ' : ' ');

        // parse conditions
        $index = '';
        if (isset($conditions['index_count']) && !empty($conditions['index_count'])){

            if (is_array($conditions['index_count']))
                $index .= ' USE INDEX ('.join(',', $conditions['index_count']).') ';
            else
                $index .= ' USE INDEX ('.$conditions['index_count'].') ';

        } elseif (isset($conditions['index']) && !empty($conditions['index'])){

            if (is_array($conditions['index']))
                $index .= ' USE INDEX ('.join(',', $conditions['index']).') ';
            else
                $index .= ' USE INDEX ('.$conditions['index'].') ';

        }

        return $this->_queryReplace($model, 'SELECT COUNT('.($conditions['count_distinct'] ? 'DISTINCT '.($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '').(!is_string($conditions['count_distinct']) ? $model->getPrimaryKey() : $this->quoteString($conditions['count_distinct'], $model->getModelStorage())) : '*').') as ncount '.$base_table.$index.$where);

    }


    public function compileConditions($source, $conditions = array()){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        // prepare query
        $table = ' ';
        $where = '';

        // default fastest sort
        $group = '';

        if (isset($conditions['where'])){

            if (!is_array($conditions['where']))
                $conditions['where'] = array($conditions['where']);

            $where .= $this->compileWhere($model, $conditions['where']);

        }

        if (trim($where) != '')
            $where = ' WHERE '.preg_replace('/^(?:AND|OR|NOT)\s(.*)$/is', '$1', trim($where));

        if (isset($conditions['join']) && is_array($conditions['join'])){

            // check for composite join
            reset($conditions['join']);
            if (is_array(current($conditions['join']))){

                $table .= join(' ', $this->compileJoin($conditions['join']));

            } else {
                /**
                 * simply SQL join
                 * @deprecated
                 */
                foreach ($conditions['join'] as $v){
                    $table .= $v.' ';
                }

            }

        } elseif (!empty($conditions['join'])){
            /**
             * string join
             * @deprecated
             */
            $table .= $conditions['join'].' ';

        }


        if (isset($conditions['group']) && !empty($conditions['group'])){
            $conditions['group'] = (array)$conditions['group'];
            foreach ($conditions['group'] as $k => $v){
                if (!trim($v)){
                    unset($conditions['group'][$k]);
                } else {
                    if (strpos($v, '.') !== false){
                        $v = explode('.', $v);
                        $v = array_map(array($this, 'quoteField'), $v);
                        $v = join('.', $v);

                    } else {
                        $v = $this->quoteField($v, $model->getModelStorage());
                    }
                    $conditions['group'][$k] = $v;
                }
            }
            if (!empty($conditions['group']))
                $group = 'GROUP BY '.join(',', (array)$conditions['group']);
        }

        // add some spaces before and after query for protection
        return ' '.$table.' '.$where.' '.$group.' ';


    }

    public function compileSelect(oModel $source, &$conditions){

        if (is_array($conditions['select'])){
            // adding selecting for a
            if (!isset($conditions['select']['a'])){
                $conditions['select']['a'] = '*';
            }
            foreach ($conditions['select'] as $k => $v){
                if ($v !== '' && $v !== null){

                    $conditions['no_array_key'] = true;
                    // virtualize primary for results
                    if ($k == '__groupkey__'){

                        if ($v){
                            $conditions['select'][$k] = $this->quoteField($v, $source->getModelStorage()).' AS ARRAY_KEY';
                        }

                    } else {

                        //$v = str_replace(' ', '', $v);
                        if (!is_array($v))
                            $v = explode(',', $v);
                        foreach ($v as $i => $z){
                            if ($z !== null){

                                $no_add_prefix = !$this->_add_alias_to_base_table && $k == $this->_base_table_alias;
                                if (is_array($z)){
                                    $tmp_z = array();

                                    if (isset($z['field'])){
                                        $no_add_prefix = true;
                                        $tmp_field = (!($k == '*' || $z['field'] == '*') && !(!$this->_add_alias_to_base_table && $k == $this->_base_table_alias) ? $this->quoteField($k, $source->getModelStorage()).'.' : '').($z['field'] != '*' ? $this->quoteField($z['field'], $source->getModelStorage()) : $z['field']);
                                        // check for function
                                        if (isset($z['function'])){
                                            switch (mb_strtolower($z['function'])){

                                                case 'max()':
                                                    $tmp_field = 'MAX('.$tmp_field.')';
                                                    break;

                                                case 'min()':
                                                    $tmp_field = 'MIN('.$tmp_field.')';
                                                    break;

                                                case 'count()':
                                                    $tmp_field = 'COUNT('.$tmp_field.')';
                                                    break;

                                                case 'lower()':
                                                    $tmp_field = 'LOWER('.$tmp_field.')';
                                                    break;
                                            }
                                            if (isset($z['equation'])){
                                                $tmp_field .= $this->quoteString($z['equation'], $source->getModelStorage());
                                            }

                                        }
                                        $tmp_z[] = $tmp_field;
                                    } elseif (isset($z['value'])){
                                        $tmp_z[] = '\''.$this->quoteString($z['value'], $source->getModelStorage()).'\'';
                                        $no_add_prefix = true;
                                    }

                                    if (isset($z['nick'])){
                                        $tmp_z[] = 'as';
                                        $tmp_z[] = $z['nick'];
                                    }
                                    $tmp_z = join(' ', $tmp_z);

                                    $v[$i] = $k != '*' ? (!$no_add_prefix ? $this->quoteField($k, $source->getModelStorage()).'.' : '').$tmp_z : $this->quoteString($tmp_z, $source->getModelStorage());
                                } else {
                                    $v[$i] = $k != '*' ? (!$no_add_prefix ? $this->quoteField($k, $source->getModelStorage()).'.' : '').($z != '*' && strpos(strtolower($z), ' as ') === false ? $this->quoteField(trim($z), $source->getModelStorage()) : $z) : $this->quoteString($z, $source->getModelStorage());
                                }
                            }
                        }
                        if (!empty($v))
                            $conditions['select'][$k] = join(',', $v);
                        else
                            unset($conditions['select'][$k]);

                    }
                } else {
                    unset($conditions['select'][$k]);
                }
            }
            $select = join(',', $conditions['select']);
        } else {
            $select = $conditions['select'];
        }

        return $select;
    }


    public function compileOrderValue(oModel $source, $value = null){

        $order = '';
        if (isset($value) && is_array($value) && !empty($value)){

            // if we have set of arrays
            foreach ($value as $k => $v){

                // check for complex value
                if (is_array($v)){

                    if (isset($v['function'])){
                        switch (mb_strtolower($v['function'])){

                            case 'field()':
                                $value[$k] = $this->_compileFunctionField($k, $v, $source->getModelStorage());
                                break;

                            case 'rand()':
                                $value[$k] = 'RAND() ASC';
                                break;

                        }
                    } else {
                        $value[$k] = (isset($v['table']) && !(!$this->_add_alias_to_base_table && $v['table'] == $this->_base_table_alias) ? $this->quoteField($v['table'], $source->getModelStorage()).'.' : '').$this->quoteField($k, $source->getModelStorage()).' '.(strtolower($v['order']) == 'desc' ? ' DESC' : ($v['order'] != '' ? $this->quoteString($v['order'], $source->getModelStorage()) :' ASC'));
                    }

                } else {

                    if (strpos($k, '.') !== false){
                        $karr = explode('.', $k);
                        $karr = array_map(array($this, 'quoteField'), $karr);
                        $karr = join('.', $karr);

                    } else {
                        $karr = $this->quoteField($k, $source->getModelStorage());
                    }

                    $value[$k] = $karr.' '.(strtolower($v) == 'desc' ? ' DESC' : ($v != '' ? $this->quoteString($v, $source->getModelStorage()) :' ASC'));

                }
            }
            unset($karr);
            $order .= join(',', $value);

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
            if (!is_numeric($k) && (is_scalar($v) || $v === null) && !preg_match('/^(AND|OR|NOT|IN)\s/is', $v)){

                /*if (!isset($model->getModelFields()[$k]))
                    continue; */

                // we have simple value

                $z = $this->compileWhereValue($model, $k, array('value' => trim($v), 'op' => '='));

                if ($z != '')
                    $where[] = $z;

                //$where[] = $this->quoteField($k).'=\''.$this->quote($model->getModelFields()[$k], trim($v)).'\'';

            } elseif (!is_numeric($k) && is_array($v)){

                if ($k == '$or'){

                    $v = $this->compileOr($model, $v);

                    if ($v != '')
                        $where[] = $v;

                } elseif ($k == '$and'){

                    $v = $this->compileAnd($model, $v);

                    if ($v != '')
                        $where[] = $v;

                } else {

                    $v = $this->compileWhereValue($model, $k, $v);

                    //if ($v != '' && !preg_match('/^(AND|OR|NOT)\s/is', $v))
                    if ($v != '')
                        $where[] = $v;

                }


            } elseif (is_numeric($k) && is_array($v)){

                $field = isset($v['key']) ? $v['key'] : $v['field'];
                $v = $this->compileWhereValue($model, $field, $v);

                if ($v != '' /*&& !preg_match('/^(AND|OR|NOT)\s/is', $v)*/)
                    $where[] = $v;

            } else {

                $v = trim($v);
                if ($v != '' /*&& !preg_match('/^(AND|OR|NOT)\s/is', $v)*/)
                    $where[] = $v;
            }

        }

        unset($model);

        return $where;

    }


    public function compileWhere($source, $conditions, $op = 'AND'){

        $where = '';

        if (!$conditions)
            return $where;

        $model = $this->createModel($source);

        $where_arr = $this->_compileWhereArray($model, $conditions);

        if ($where_arr){

            foreach ($where_arr as $w){
                if (!preg_match('/^(AND|OR|NOT)\s/is', $w)){
                    $where .= ' AND '.$w;
                } else {
                    $where .= ' '.$w;
                }
            }

        }

        unset($model);
        unset($where_arr);

        return $where;

    }

    public function compileJoin($conditions){

        $sql_add = array();
        if (is_array($conditions) && !empty($conditions)){

            foreach ($conditions as $v){

                $item = '';

                switch ($v['method']){

                    case 'left':

                        $item .= 'LEFT JOIN';
                        break;

                    case 'right':

                        $item .= 'RIGHT JOIN';
                        break;

                    case 'join':
                    default:

                        $item .= 'INNER JOIN';
                        break;

                }

                if (isset($v['model']) && ($tmp_model = $this->_p->Model($v['model']))){
                    $item .= ' '.$tmp_model->getModelTable().(isset($v['alias']) ? ' AS '.$v['alias'] : '');
                } else {
                    $tbl_name = isset($v['tbl_name']) ? $v['tbl_name'] : $v['table'];
                    $tmp_model = $this->createModel($tbl_name);
                    $item .= ' '.$tmp_model->getModelTable().(isset($v['alias']) ? ' AS '.$v['alias'] : '');
                }

                if ($on_cond = $this->compileWhere($tmp_model, $v['conditions'])){
                    // delete first AND
                    $on_cond = preg_replace('/^\sAND\s(.*)$/is', '$1', $on_cond);
                    $item .= ' ON '.$on_cond;
                }

                unset($on_cond);

                $sql_add[] = $item;

                unset($tmp_model);

            }
        }

        return $sql_add;

    }


    protected function _compileFunctionBitsearch($field, $field_value, $storage = null){

        return '('.(isset($field_value['table'])  ? $this->_f_quote.$this->quoteString($field_value['table'], $storage).$this->_f_quote.'.' : '').$field.' & POWER(2, '.(int)$field_value['value'].'))';

    }

    protected function _compileFunctionField($field, $field_value, $storage = null){

        return 'FIELD ('.$this->quoteField($field, $storage).','.(is_array($field_value['value']) ? join(',', array_map(function($val){return "'".$val."'";}, $this->quoteString($field_value['value'], $storage))) : "'".$this->quoteString($field_value['value'], $storage)."'").')';

    }


    public function compileWhereValue(oModel $model, $field_name, $field_value){

        // checking for subquery
        reset($field_value);

        if (!(isset($field_value['value']) || isset($field_value['subquery'])))
            return null;

        $cond = isset($field_value['op']) ? $cond = $this->quoteString($field_value['op'], $model->getModelStorage()) : '=';
        $value = '';

        if (isset($field_value['key']) && $field_value['key'])
            $key = $field_value['key'];
        elseif (isset($field_value['field']) && $field_value['field']){
            $key = $field_value['field'];
        } else {
            $key = $field_name;
        }

        // check key exists
        /*if (!isset($model->getModelFields()[$key]))
            return null; */

        $native_keys = $key;
        $field = $model->getModelFields()[is_array($key) ? current($key) : $key];
        $key = $this->quoteField($key, $model->getModelStorage());

        if (isset($field_value['subquery']) && $field_value['subquery']){

            $sub = $this->compile($model, $field_value['subquery']);

            $value = '('.(is_array($sub) ? $sub['query'] : $sub).')';

        } elseif ((trim(mb_strtolower($cond)) == 'in' || trim(mb_strtolower($cond)) == 'not in')){

            if (!is_array($field_value['value']))
                $field_value['value'] = array($field_value['value']);

            $self = &$this;
            array_walk($field_value['value'], function(&$value) use (&$self, &$field, &$model){

                $value = $self->quote($field, $value, $model->getModelStorage());
                $value = !in_array($field['type'], $this->_native_types) ? '\''.$value.'\'' : $value;

            });
            $value = '('.join(',', $field_value['value']).')';

        } elseif ((trim(mb_strtolower($cond)) == 'is' || trim(mb_strtolower($cond)) == 'not is')){

            $value = !is_numeric($field_value['value']) ? trim($field_value['value']) : $field_value['value'];
            $value = mb_strtolower($value) == 'null' || $value === null? 'NULL' : (!in_array($field['type'], $this->_native_types) ? '\''.$value.'\'' : $value);

        } elseif (is_array($field_value['value']) && trim(mb_strtolower($cond)) == 'mva_in'){

            $value = array();
            foreach ($field_value['value'] as $z){
                $value[] = ' '.$key.' LIKE \'%;'.$this->quote($field, $z, $model->getModelStorage()).';%\' ';
            }

            $cond = '';
            $key = '';
            unset($field_value['table']);
            $value = '('.join(' OR ', $value).')';

        } elseif (is_array($field_value['value']) && trim(mb_strtolower($cond)) == 'between'){

            $value = ' \''.$this->quote($field, $field_value['value'][0], $model->getModelStorage()).'\' AND \''.$this->quote($field, $field_value['value'][1], $model->getModelStorage()).'\'';

        } elseif ((trim(mb_strtolower($cond)) == 'like' || trim(mb_strtolower($cond)) == 'not like')) {

            $value = "'".$this->quoteString(str_replace(array('.*', '?'), array('%', '_'), $field_value['value']), $model->getModelStorage())."'";

        } elseif (trim(mb_strtolower($cond)) == 'search') {

            $value = $this->compileSearch($model,
                isset($field_value['sfields']) ? $field_value['sfields'] : $native_keys,
                $field_value['value'],
                isset($field_value['wmode']) ? $field_value['wmode'] : 'or',
                isset($field_value['prefix']) ? $field_value['prefix'] : false,
                isset($field_value['fulltext']) ? $field_value['fulltext'] : true,
                isset($field_value['innermode']) ? $field_value['innermode'] : 'or');

            $key = $cond = '';

        } elseif (isset($field_value['function']) && $field_value['function']){

            // cleanup key and operator
            switch (mb_strtolower($field_value['function'])){

                case 'bitsearch()':
                    $value = $this->_compileFunctionBitsearch($key, $field_value, $model->getModelStorage());
                    break;

            }
            $field_value['table'] = $key = $cond = '';

        } elseif (isset($field_value['type']) && $field_value['type'] == 'foreign_key'){

            $value = $this->quoteString($field_value['value'], $model->getModelStorage());

        } else {

            $field_value['value'] = $this->quote($field, $field_value['value'], $model->getModelStorage());
            $value = !in_array($field['type'], $this->_native_types) ? '\''.$field_value['value'].'\'' : $field_value['value'];
        }

        $sql_add = (isset($field_value['table']) && $field_value['table'] && !(!$this->_add_alias_to_base_table && $field_value['table'] == $this->_base_table_alias)  ? $this->_f_quote.$this->quoteString($field_value['table'], $model->getModelStorage()).$this->_f_quote.'.' : '').($key != '' ? $key : '').' '.$cond.' '.$value;


        return $sql_add;

    }


    public function compileOr($source, $conditions = array()){

        if ($conditions){

            $final = $this->_compileWhereArray($source, $conditions);

            unset($conditions);

            return '('.join(' OR ', $final).')';
        } else
            return '';

    }

    public function compileAnd($source, $conditions = array()){

        if ($conditions){

            $final = $this->_compileWhereArray($source, $conditions);

            unset($conditions);

            return '('.join(' AND ', $final).')';
        } else
            return '';

    }


    public function compileSearch($source, $sfields, $keywords, $wmode = '', $prefix = false, $fulltext = false, $innermode = 'or'){

        $query = false;

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
            else
                $sfields[$k] = (strpos($v, '.') === false ? $prefix : '').$this->quoteField($v, $model->getModelStorage());
        }

        if ($fulltext){

            $tmp_keys = $keywords;

            if (!is_array($tmp_keys))
                $tmp_keys = array($tmp_keys);

            foreach ($tmp_keys as $k => $v){
                $tmp_keys[$k] = "+".$this->quoteString($v, $model->getModelStorage());
            }

            $query = 'MATCH('.join(',', $sfields).') AGAINST(\''.join(' ', $tmp_keys).'\' IN BOOLEAN MODE)';

        } else {

            $op = 'LIKE';

            $i = 1;

            if (!is_array($keywords))
                $keywords = array($keywords);

            $keywords = $this->quoteString($keywords, $model->getModelStorage());
            foreach ($keywords as $k){
                $tmp_query = array();
                if (!empty($k)){
                    if ($i == 1){
                        $query .= "(";

                    } else {

                        $query .= " $wmode (";
                    }

                    foreach ($sfields as $v){
                        // some optimization for integer fields
                        if (in_array($model->getModelFields()[$v]['type'], $this->_native_types)){
                            if (is_numeric($k))
                                $tmp_query[] = "(".$v."=".(int)$k.")";
                        } else
                            $tmp_query[] = "(".$v." $op '%".$k."%')";
                    }

                    $query .= join(" ".$innermode." ", $tmp_query).")";

                }

                $i++;

            }

        }

        if ($query)
            $query = "(".$query.")";

        return $query;

    }

    public function quoteField($field_name, $storage = null){

        if (!is_array($field_name)){
            $not_array = true;
            $field_name = array($field_name);
        } else {
            $not_array = false;
        }

        foreach ($field_name as $k => $f){

            if (strpos($f, '.') !== false){

                $f = explode('.', $f);
                $_this = $this;

                $f = array_map(function($value) use ($_this, $storage) {

                    return $_this->quoteField($value, $storage);

                }, $f);

                $field_name[$k] = join('.', $f);

            } else {

                $field_name[$k] = $this->_f_quote.$this->quoteString($f, $storage).$this->_f_quote;

            }

        }

        if ($not_array)
            return current($field_name);
        else
            return $field_name;

    }

    public function quote($field, $value, $storage = null){

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

                default:
                    return $this->quoteString($value, $storage);
                    break;

            }

        } else {
            return $this->quoteString($value, $storage);
        }

    }


    public function _cleanQuoteString($data, $magic_quotes_active){

        if (is_array($data)){
            foreach ($data as $k => $v){
                $data[$k] = $this->_cleanQuoteString($v, $magic_quotes_active);
            }

        } else {
            //undo any magic quote effects so mysql_real_escape_string can do the work
            if ($magic_quotes_active) {
                $data = stripslashes($data);
            }

            if (!is_numeric($data) && !is_object($data)) {
                $data = $this->_p->db->cleanupEscape($data);
            }
        }

        return $data;

    }

    protected function  _deprecatedQuoteString($s, $magic_quotes = null){

        $replaceQuote = "\\'";

        if (!$magic_quotes) {

            if ($replaceQuote[0] == '\\'){
                // only since php 4.0.5
                $s = arr_replace(array('\\',"\0"), array('\\\\',"\\\0"), $s);
                //$s = str_replace("\0","\\\0", str_replace('\\','\\\\',$s));
            }
            $s = arr_replace('"','\\"',$s);

            //print_r( arr_replace("'", $replaceQuote, array('adsds' => "sadasd d''' '  \" ''' \"' ''''sad's'ad", array('grp' => "sdasd'sds '' sasd'' "))));
            //print_r(arr_replace("'", $replaceQuote, $s));
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


    public function quoteString($s, $storage = null){

        // i.e PHP >= v4.3.0
        $magic_quotes = get_magic_quotes_gpc();
        if (function_exists("mysql_real_escape_string") && $this->_p->db && $this->_p->db->instance != null) {

            if (is_array($s)){
                foreach ($s as $k => $v){
                    $s[$k] = $this->_cleanQuoteString($v, $magic_quotes);
                }

            } else {
                //undo any magic quote effects so mysql_real_escape_string can do the work
                if ($magic_quotes) {
                    $s = stripslashes($s);
                }

                if (!is_numeric($s) && !is_object($s)) {
                    $s = mysql_real_escape_string($s);
                }
            }

            return $s;

        } else { // before PHP v4.3.0

            return $this->_deprecatedQuoteString($s, $magic_quotes);

        }

    }


    public function describeTable($source, $no_cache = false){

        if (!$source)
            throw new \Exception('error.queryBuilder.table_name_not_defined');

        // detect table source
        if (is_array($source)) {
            // if it is array - then it is always array of fields
            return $source;

        } elseif (!(is_object($source) && $source instanceof oModel)){

            $source = $this->createModel($source);

        }

        $table = $source->getModelTable();

        // additional checking for nickname
        if (isset($this->_p->getVar('tables')[$table])){
            $table = $this->_p->getVar('tables')[$table];
        }

        $table_fields = array();

        if ($no_cache || !($table_fields = $this->_p->cache->getSerial('describe.'.get_class().'.'.(string)$table))){

            // check if table exists
            //$sql = 'SHOW TABLES FROM '.$this->_f_quote.$this->_p->getVar('storages')['base']['db_name'].$this->_f_quote.' LIKE \''.$this->quoteString($table).'\'';
            //$table_exists = $this->_p->db->selectRow($sql);
            $table_exists = true;

            if ($table_exists){

                // describe table
                $sql = 'DESCRIBE '.$this->quoteString($table, $source->getModelStorage());
                $res = $this->_p->db->select($sql, $source->getModelStorage());

                if ($res){
                    foreach ($res as $data){
                        $table_fields[] = $data['Field'];
                    }
                }
            }

            $this->_p->cache->saveSerial('describe.'.get_class().'.'.(string)$table, $table_fields);

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
            $sql = 'SHOW TABLES FROM '.$this->_f_quote.$this->_p->getVar('storages')['base']['db_name'].$this->_f_quote.' LIKE \''.$this->quoteString($source).'\'';
            $table_exists = $this->_p->db->selectRow($sql);

            if ($table_exists){

                // describe table
                $sql = 'DESCRIBE '.$this->quoteString($source);
                $res = $this->_p->db->select($sql);

                if ($res){

                    $primary = null;
                    foreach ($res as $data){

                        $type = explode(' ', $data['Type']);
                        $maxlength = null;
                        if (preg_match('/(.*?)\((\d+)\)/is', $type[0], $match)){
                            $type = $match[1];
                            $maxlength = $match[2];
                        } else {
                            $type = $type[0];
                        }

                        switch (mb_strtolower($type)){

                            case 'tinytext':
                            case 'text':
                            case 'mediumtext':
                            case 'longtext':
                                $type = 'text';
                                if (!$maxlength)
                                    $maxlength = 65535;
                                break;

                            case 'int':
                            case 'bigint':
                            case 'mediumint':
                            case 'smallint':
                            case 'tinyint':
                                if (preg_match('/^is_.*$/is', $data['Field']))
                                    $type = 'boolean';
                                else
                                    $type = 'int';
                                break;

                            case 'bool':
                            case 'boolean':
                                $type = 'boolean';
                                break;


                            case 'float':
                            case 'double':
                            case 'real':
                            case 'decimal':
                                $type = 'float';
                                break;

                            case 'date':
                                $type = 'date';
                                break;

                            case 'datetime':
                                $type = 'datetime';
                                break;

                            case 'time':
                                $type = 'time';
                                break;

                            case 'timestamp':
                                $type = 'unixtimestamp';
                                break;

                            case 'varchar':
                            case 'char':
                            default:
                                $type = 'varchar';
                                if (!$maxlength)
                                    $maxlength = 255;
                                break;

                        }

                        $table_fields[$data['Field']] = array(
                            'type' => $type
                        );

                        if ($maxlength){
                            $table_fields[$data['Field']]['maxlength'] = $maxlength;
                        }

                        if ($data['Default'] !== '' && $data['Default'] !== null){
                            $table_fields[$data['Field']]['default_value'] = $data['Default'];
                        }

                        if (mb_strtolower($data['Key']) == 'pri'){
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
