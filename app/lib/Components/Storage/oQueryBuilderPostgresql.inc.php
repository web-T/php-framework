<?php
/**
 * Postgres Query builder
 *
 * Date: 07.12.14
 * Time: 19:20
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	07.12.2014/goshi 
 */

namespace webtFramework\Components\Storage;

use webtFramework\Interfaces\oModel;

class oQueryBuilderPostgresql extends oQueryBuilderMysql{

    /**
     * override field quoter
     * @var string
     */
    protected $_f_quote = '"';

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
                $f = array_map(function(&$value) use ($_this, $storage) {

                    return $_this->quoteField($value, $storage);

                }, $f);

                $field_name[$k] = join('.', $f);

            } else {

                $field_name[$k] = '"'.$this->quoteString($f, $storage).'"';

            }

        }

        if ($not_array)
            return current($field_name);
        else
            return $field_name;

    }


    public function quoteString($s, $storage = null){

        $magic_quotes = get_magic_quotes_gpc();

        if (function_exists("pg_escape_string") && $this->_p->db && $this->_p->db->instance != null) {

            if (is_array($s)){
                foreach ($s as $k => $v){
                    $s[$k] = $this->_cleanQuoteString($v, $magic_quotes);
                }

            } else {
                //undo any magic quote effects so pg_escape_string can do the work
                if ($magic_quotes) {
                    $s = stripslashes($s);
                }

                if (!is_numeric($s) && !is_object($s)) {
                    $before = $s;
                    $s = $storage ? $this->_p->db->getStorage($storage)->cleanupEscape($s) : pg_escape_string($s);
                    // another check for HHVM right support (it is not supports this function yet)
                    if ($s === null && $before !== null){
                        $s = $this->_deprecatedQuoteString($before, $magic_quotes);
                    }
                }
            }

            return $s;

        } else { // before PHP v4.3.0

            return $this->_deprecatedQuoteString($s, $magic_quotes);

        }

    }

    public function compileInsert($source, $data = array(), $is_multi_insert = false){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        if (!is_array($data)/* || ($data && empty($data))*/)
            throw new \Exception('error.queryBuilder.no_model_data_to_insert');

        $model = $this->createModel($source);

        $base_table = ' '.$this->quoteField($model->getModelTable()).' ';

        $fields = $rows = array();
        $is_fields_defined = false;

        if (!$is_multi_insert)
            $data = array($data);

        foreach ($data as $row){
            $values = array();
            foreach ($row as $field => $value){
                if (isset($model->getModelFields()[$field])){

                    if (!$is_fields_defined)
                        $fields[$field] = $this->quoteField($field);

                    if (is_array($value)){
                        if (isset($value['subquery'])){

                            $sub = $this->compile($model, $value['subquery']);

                            $values[$field] = '('.(is_array($sub) ? $sub['query'] : $sub).')';
                        }
                    } else {
                        $value = $this->quote($model->getModelFields()[$field], $value);
                        $values[$field] = !in_array($model->getModelFields()[$field]['type'], $this->_native_types) ?  "'".$value."'" : $value;
                    }

                }
            }

            if (!empty($fields) && isset($model->getModelFields()['id']) && isset($values['id']) && $values['id'] == 0){
                /*if (!$is_fields_defined)
                    $fields['id'] = $this->quoteField('id');
                */
                $values['id'] = 'DEFAULT';
            }

            $is_fields_defined = true;
            $rows[] = '('.join(',', $values).')';
        }


        if (empty($fields))
            $sql = $this->_queryReplace($model, 'INSERT INTO '.$base_table.' DEFAULT VALUES');
        else
            $sql = $this->_queryReplace($model, 'INSERT INTO '.$base_table.'('.join(',', $fields).') VALUES '.join(',', $rows));

        unset($rows);
        unset($fields);
        unset($values);

        return $sql;

    }

    /**
     * return nothing due to postgresql does not support optimize
     * @param $source
     * @return string
     */
    public function compileOptimize($source){

        return '';

    }


    protected function _compileFunctionBitsearch($field, $field_value, $storage = null){

        return '(('.(isset($field_value['table'])  ? $this->_f_quote.$this->quoteString($field_value['table'], $storage).$this->_f_quote.'.' : '').$field.' & '.pow(2, (int)$field_value['value']).')>0)';

    }

    protected function _compileFunctionField($field, $field_value, $storage = null){

        if (!is_array($field_value['value']))
            $field_value['value'] = array($field_value['value']);

        $sql = 'CASE ';
        foreach ($field_value['value'] as $k => $v){
            $sql .= 'WHEN '.$this->quoteField($field).'='."'".$this->quoteString($v, $storage)."'".' THEN '.($k+1).' ';
        }
        $sql .= 'END';

        return $sql;

    }



    public function compileSearch($source, $sfields, $keywords, $wmode = '', $prefix = false, $fulltext = false, $innermode = 'or'){

        $query = false;

        $model = $this->createModel($source);

        // making table prefix
        if ($prefix)
            $prefix = $this->quoteField($prefix).".";
        else
            $prefix = "";

        if (!is_array($sfields))
            $sfields = array($sfields);

        foreach ($sfields as $k => $v){
            if (!isset($model->getModelFields()[$v]))
                unset($sfields[$k]);
            else
                $sfields[$k] = (strpos($v, '.') === false ? $prefix : '').$this->quoteField($v);
        }

        // TODO: make normal fullsearch index
        /*if ($fulltext){

            $tmp_keys = $keywords;

            if (!is_array($tmp_keys))
                $tmp_keys = array($tmp_keys);

            foreach ($tmp_keys as $k => $v){
                $tmp_keys[$k] = "+".$this->quoteString($v);
            }

            $query = 'MATCH('.join(',', $sfields).') AGAINST(\''.join(' ', $tmp_keys).'\' IN BOOLEAN MODE)';

        } else { */

            $op = 'ILIKE';

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

        //}

        if ($query)
            $query = "(".$query.")";

        return $query;

    }



    public function compileCount($source, $conditions = array(), $where = null){

        if (!$source)
            throw new \Exception('error.queryBuilder.no_model_exists');

        $model = $this->createModel($source);

        if (!$where)
            $where = $this->compileConditions($model, $conditions);

        $base_table = 'FROM '.$this->quoteField($model->getModelTable()).($this->_add_alias_to_base_table ? ' a ' : ' ');

        // parse conditions
        $index = '';
        if (isset($conditions['index_count']) && !empty($conditions['index_count'])){

            // do nothing

        } elseif (isset($conditions['index']) && !empty($conditions['index'])){

            // do nothing

        }

        return $this->_queryReplace($model, 'SELECT COUNT('.($conditions['count_distinct'] ? 'DISTINCT '.($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '').(!is_string($conditions['count_distinct']) ? $model->getPrimaryKey() : $this->quoteString($conditions['count_distinct'], $model->getModelStorage())) : '*').') as ncount '.$base_table.$index.$where);

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

            // do nothing, due PG does not supports index hinting

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
            /*if ($model->getModelFields()){
                foreach ($model->getModelFields() as $k => $v){
                    if (isset($v['default_sort']) && $v['default_sort']){
                        $order .= ($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '').$this->quoteField($k).' '.(mb_strtolower($v['order']) == 'desc' ? 'DESC' : 'ASC');
                        $found_order = true;
                        break;
                    }
                }
            }*/
            if (!$found_order)
                $order = ' ';
            //$order .= ' NULL';

        }

        if (isset($conditions['limit'])){
            if (!isset($conditions['begin'])){
                $conditions['begin'] = 0;
            }
            $limit = ' LIMIT '.(int)$conditions['limit'].' OFFSET '.(int)$conditions['begin'];
        } else {
            $limit = '';
        }

        return $this->_queryReplace($model, 'SELECT '.(!$conditions['no_array_key'] ? ($this->_add_alias_to_base_table ? $this->_base_table_alias.'.' : '').$model->getPrimaryKey().' AS ARRAY_KEY,' : '').$select.' '.$base_table.$index.$where.$order.$limit);


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
            $table_exists = true;

            if ($table_exists){

                // describe table
                $sql = 'select column_name, data_type, character_maximum_length
from INFORMATION_SCHEMA.COLUMNS where table_name = \''.$this->quoteString($table, $source->getModelStorage()).'\';';
                $res = $this->_p->db->select($sql);

                if ($res){
                    foreach ($res as $data){
                        $table_fields[] = $data['column_name'];
                    }
                }
            }

            $this->_p->cache->saveSerial('describe.'.get_class().'.'.(string)$table, $table_fields);

        }
        return $table_fields;


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
            $sql = 'SELECT * FROM pg_catalog.pg_tables where tablename=\''.$this->quoteString($source, 'base').'\'';
            $table_exists = $this->_p->db->selectRow($sql);

            if ($table_exists){

                // describe table
                $sql = 'select column_name, data_type, character_maximum_length
from INFORMATION_SCHEMA.COLUMNS where table_name = \''.$this->quoteString($source, 'base').'\';';
                $res = $this->_p->db->select($sql);

                if ($res){

                    $primary = null;
                    foreach ($res as $data){

                        $type = explode(' ', $data['data_type']);
                        $maxlength = null;
                        if (preg_match('/(.*?)\((\d+)\)/is', $type[0], $match)){
                            $type = $match[1];
                            $maxlength = $match[2];
                        } else {
                            $type = $type[0];
                        }

                        if ($data['character_maximum_length']){
                            $maxlength = $data['character_maximum_length'];
                        }

                        switch (mb_strtolower($type)){

                            case 'text':
                                $type = 'text';
                                if (!$maxlength)
                                    $maxlength = 65535;
                                break;

                            case 'int':
                            case 'bigint':
                            case 'serial':
                            case 'bigserial':
                            case 'smallserial':
                            case 'smallint':
                            case 'integer':
                                if (preg_match('/^is_.*$/is', $data['column_name']))
                                    $type = 'boolean';
                                else
                                    $type = 'int';
                                break;

                            case 'bool':
                            case 'boolean':
                                $type = 'boolean';
                                break;


                            case 'decimal':
                            case 'numeric':
                            case 'real':
                            case 'double precision':
                                $type = 'float';
                                break;

                            case 'date':
                                $type = 'date';
                                break;

                            case 'timestamp':
                                $type = 'datetime';
                                break;

                            case 'time':
                                $type = 'time';
                                break;

                            case 'json':
                                $type = 'json';
                                break;


                            case 'varchar':
                            case 'character varying':
                            case 'character':
                            case 'char':
                            default:
                                $type = 'varchar';
                                if (!$maxlength)
                                    $maxlength = 255;
                                break;

                        }

                        $table_fields[$data['column_name']] = array(
                            'type' => $type
                        );

                        if ($maxlength){
                            $table_fields[$data['column_name']]['maxlength'] = $maxlength;
                        }

                        if ($data['Default'] !== '' && $data['Default'] !== null){
                            $table_fields[$data['column_name']]['default_value'] = $data['Default'];
                        }

                        if (mb_strtolower($data['Key']) == 'pri'){
                            $primary = $data['column_name'];
                        }

                    }

                    if (isset($table_fields['real_id']) && isset($table_fields['lang_id'])){
                        $table_fields['real_id']['primary'] = true;
                    } elseif (isset($table_fields['id'])){
                        $table_fields['id']['primary'] = true;
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
