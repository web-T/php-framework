<?php
/**
 * Mongo database adapter
 *
 * Date: 16.01.15
 * Time: 01:07
 * @version 1.0
 * @author goshi
 * @package web-T[Storage]
 *
 * Changelog:
 *	1.0	16.01.2015/goshi
 */

namespace webtFramework\Components\Storage\Database;

class oDatabaseMongodb extends oDatabaseAbstract {

    /**
     * @var \MongoClient
     */
    protected $_instance;

    /**
     * selected database
     * @var \MongoDB
     */
    protected $_db;

    public function init(){

        $this->_instance = new \MongoClient($this->_settings['db_type']."://".($this->_settings['db_user'] ? $this->_settings['db_user'].($this->_settings['db_pass'] ? ":".$this->_settings['db_pass'] : '')."@" : '').$this->_settings['db_host']);

        if ($this->_instance){

            // select database
            if ($this->_settings['db_name']){
                $this->_db = $this->_instance->selectDB($this->_settings['db_name']);
            }

            /*
            $this->_instance->setErrorHandler(array($this->_p->debug, 'logDBError'));
            if ($this->_p->getVar('is_debug') == 1 || $this->_p->getVar('is_dev_env') == 1)
                $this->_instance->setLogger(array($this->_p->debug, 'addSQL'));
            */

        } else {
            throw new \Exception($this->_p->trans('error.db.no_connection'));
        }

    }

    public function close(){

        if ($this->_instance){

            $connections = $this->_instance->getConnections();

            foreach ( $connections as $con )
            {
                $this->_instance->close( $con['hash'] );
            }

            $this->_instance = null;
            $this->_db = null;
        }

    }

    public function getLastError(){

        /*if ($this->_instance){
            return $this->_instance->error;
        } else {
            return null;
        }*/
        return null;

    }

    /**
     * convert result to normal values
     * @param $result
     * @return array
     */
    protected function _convertResults($result){

        if ($result && is_array($result)){

            foreach ($result as $k => $v){

                if ($v instanceof \MongoDate){
                    $result[$k] = date('Y-m-d', $v->sec);
                } elseif (is_array($v)){
                    $result[$k] = $this->_convertResults($v);
                }
            }

        }

        return $result;

    }


    public function query($query){

        //dump_file(array('base' => $query), false);

        if (is_array($query) && isset($query['table'])){

            $collection = $this->_db->$query['table'];
            $cursor = null;
            $result = null;

            if (isset($query['type'])){
                $type = $query['type'];
            } else {
                $type = 'aggregate';
            }

            $stage = array();

            if ($query['query']){

                if ($type == 'aggregate'){
                    $stage = array();
                    if (isset($query['query']['$project']) && !empty($query['query']['$project'])){
                        $stage[]['$project'] = $query['query']['$project'];
                    }
                    if (isset($query['query']['$match']) && !empty($query['query']['$match'])){
                        $stage[]['$match'] = $query['query']['$match'];
                    }
                    if (isset($query['query']['$group']) && !empty($query['query']['$group'])){
                        if (!isset($query['query']['$group']['_id']))
                            $query['query']['$group']['_id'] = null;
                        $stage[]['$group'] = $query['query']['$group'];
                    }
                    if (isset($query['query']['$sort']) && !empty($query['query']['$sort'])){
                        $stage[]['$sort'] = $query['query']['$sort'];
                    }
                    if (isset($query['query']['$skip'])){
                        $stage[]['$skip'] = $query['query']['$skip'];
                    }

                    if (isset($query['query']['$limit'])){
                        $stage[]['$limit'] = $query['query']['$limit'];
                    }

                } else {

                    if (isset($query['query']['$match'])){
                        $stage = $query['query']['$match'];
                    }

                }

            }

            //dump_file(array('type' => $type, 'stage' => $stage), false);

            switch ($type){

                case 'update':
                    //dump_file(array($stage, $query['value'], array('multi' => isset($query['multi']) ? $query['multi'] : true)));
                    $result = $collection->$type($stage, $query['value'], array('multi' => isset($query['multi']) ? $query['multi'] : true, 'multiple' => isset($query['multi']) ? $query['multi'] : true));
                    break;

                case 'insert':
                    $result = $collection->batchInsert($query['value'], array('multi' => isset($query['multi']) ? $query['multi'] : true, 'multiple' => isset($query['multi']) ? $query['multi'] : true));
                    break;


                default:
                    $cursor = $collection->$type($stage);
                    break;

            }
            //dump(array($type => $stage), false);


            if ($type == 'find' && isset($query['query']['$limit']) && $cursor){
                $result = $cursor->limit($query['query']['$limit']);
            }

            if ($type == 'find' && isset($query['query']['$skip']) && $cursor){
                $cursor->skip($query['query']['$skip']);
            }

            if ($type == 'find' && isset($query['count']) && $cursor){
                $cursor->count();
            }

            //dump_file(array('cursor' => $cursor), false);

            if ($cursor){
                $num = 0;

                if ($cursor instanceof \MongoCursor){
                    while ($cursor->hasNext()){
                        $value = $cursor->getNext();
                        $result[isset($query['query']['$group_key']) && isset($value[$query['query']['$group_key']]) ? $value[$query['query']['$group_key']] : $num] = $value;
                        $num++;
                    }
                } elseif (is_array($cursor) && isset($cursor['result'])){

                    foreach ($cursor['result'] as $value){

                        $result[isset($query['query']['$group_key']) && isset($value[$query['query']['$group_key']]) ? $value[$query['query']['$group_key']] : $num] = $value;
                        $num++;
                    }

                } elseif (is_scalar($cursor)) {

                    $result = $cursor;

                }

                unset($value);
            }

            //dump_file(array($type, $result, $query['primary']), false);

            // emulate insert_id property of mysqli
            if ($type == 'insert' && isset($query['primary']))
                return is_array($query['primary']) ? array_pop($query['primary']) : $query['primary'];
            else
                return $this->_convertResults($result);

        } else {
            throw new \Exception('error.mongo_bad_command');
        }

    }

    /**
     * call selected command on the mongo driver
     * each command - is an array:
     * {
     *   "command" : "find",
     *   "table" : table_name
     *   "conditions": array of parameters for the command
     *   }
     *
     * @param $query
     * @return mixed
     * @throws \Exception
     */
    public function api($query){

        if (is_array($query) && isset($query['command'])){
            return call_user_func_array(array($this->_db, $query), $query['conditions']);
        } else {
            throw new \Exception('error.mongo_bad_command');
        }

    }

    /**
     * general overwrited selector
     *
     * @param $query
     * @return \MongoCursor|null
     * @throws \Exception
     */
    public function select($query){

        return $this->query($query);

    }

    public function selectCell($query){

        $data = $this->select($query);

        if ($data && is_array($data)){

            $data = array_slice($data, 0, 1);

            reset($data[0]);

            // remove first id
            unset($data[0]['_id']);

            if (isset($query['count']))
                $data = (int)$data[0]['count'];
            else
                $data = current($data[0]);

        } elseif ($query['count'] && !$data){
            $data = 0;
        }

        //dump_file(array('cell_res' => $data), false);

        return $data;

    }

    public function selectRow($query) {

        if (!(is_array($query) && isset($query['table']))){
            throw new \Exception('error.mongo_bad_command');
        }

        // modify command
        $query['query']['$limit'] = 1;

        $data = $this->select($query);

        return $data ? current($data) : $data;

    }

    public function selectCol($query){

        $data = $this->select($query);

        if ($data){

            foreach ($data as $k => $v){
                reset($v);
                $data[$k] = current($v);
            }

        }

        return $data;

    }

    public function escape($data){

        return $data;

    }

    public function cleanupEscape($data){

        return $data;

    }

    /**
     * unfortunatelly, MongoDB on this date don't support normal transaction mechanism,
     * you need to read about two-phase commits http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/
     * or use $isolated mechanism directly in the QueryBuilder
     * @param null $mode
     * @return mixed
     */
    public function transaction($mode = null){

        return true;

    }

    public function commit(){

        return true;

    }

    public function rollback(){

        return true;

    }

    /**
     * TODO: develop method or remove it
     * @param $conditions
     */
    public function createTable($conditions){

    }

    /**
     * TODO: develop method or remove it
     * @param $conditions
     */
    public function createIndex($conditions){

    }

}