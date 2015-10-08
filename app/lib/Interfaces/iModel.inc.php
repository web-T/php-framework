<?php

/**
 * Interface for models for web-T::CMS
 * Date: 28.07.14
 * Time: 09:43
 * @version 1.0
 * @author goshi
 * @package web-T[framework]
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;
use webtFramework\Components\Event\oEvent;
use webtFramework\Helpers\Images;

/**
 * @package web-T[framework]
 */
interface iModel {

    public function preSave($data = null);
    public function postSave($data = null);
    public function postPrepareSaveFields(&$data = null);
    public function preDelete($data = null);
    public function postDelete($data = null);
    public function postUpdate($data = null);
    public function preUpdate($data = null);
    public function preDuplicate($data = null);
    public function postDuplicate($data = null);

}


class oModel extends oBase implements iModel {

    /**
     * fields declaration
     * @var array
     */
    protected $_fields = array();

    /**
     * links declaration
     * can be any nick of admin page, which can return linked data controller
     * from version 2.80 can consists of 'nick' => (array)options values
     * @var array
     */
    protected $_links = array();

    /**
     * reversed links
     * @var array
     */
    protected $_reverseLinks = array();

    /**
     * model's table
     * @var null|string
     */
    protected $_workTbl = null;

    /**
     * links table for model
     * @var null|string
     */
    protected $_linkTbl = null;

    /**
     * upload files dir
     * @var string
     */
    protected $_uploadDir = '';

    /**
     * primary fields key
     * @var null|string
     */
    protected $_primaryKey = null;


    /**
     * multilang property, determine are multilanguage fields presents
     * @var bool|null
     */
    protected $_isMultilang = null;

    /**
     * flag for disable indexing with hyper_search this module and table
     * @var bool
     */
    protected $_isNoindex = false;

    /**
     * flag for disable cache cleaning after model save/update/delete
     * @var bool
     */
    protected $_isNoCacheClean = false;


    /**
     * linked serialized structures
     * @var array
     */
    protected $_linkedSerialized = array();


    /**
     * current setted data of fields
     * @var array
     */
    protected $_data = array();

    /**
     * model data links
     * @var array
     */
    protected $_dataLinks = array();

    /**
     * old data of entity
     * @var array
     */
    protected $_oldData = array();


    /**
     * current model storage (@see common.conf.php)
     * @var string
     */
    protected $_storage = 'base';

    /**
     * @var oPortal
     */
    protected $_p;

    public function __construct(oPortal &$p){

        parent::__construct($p);

        $this->init();

        if ($this->_p->getVar('is_debug'))
            $this->_p->debug->add("oModel: ".get_class($this)." After construct");

    }


    public function __toString(){

        return $this->getModelName();

    }

    /**
     * initialize method
     * @throws \Exception
     */
    public function init(){

        $class = mb_strtolower($this->extractClassname());

        if (!$this->getModelTable())
            $this->_workTbl = $this->_p->getVar('tbl_'.$class);

        if ($this->getModelLinkTable() == ''){
            $this->_linkTbl = $this->_p->getVar('tbl_'.$class.'_lnk') ? $this->_p->getVar('tbl_'.$class.'_lnk') : $this->_p->getVar('tbl_linker');
        }

        if (!$this->getUploadDir()){

            $this->_uploadDir = $this->_p->getVar('files_dir').$class."/".$this->_p->getVar('uploads_dir');

        }

        // updating primary key
        if (!empty($this->_fields)){
            $multilang = false;
            foreach ($this->_fields as $k => $v){

                if ($v['primary']) $this->_primaryKey = $k;
                if ($v['multilang']) $multilang = true;
                if (!isset($v['empty'])) $this->_fields[$k]['empty'] = true;

                if (isset($v['visual']) && $v['visual']['type'] == 'picture'){
                    if (!$v['visual']['img_dir']){
                        $this->_fields[$k]['visual']['img_dir'] = $this->_p->getVar('files_dir').$class."/".$this->_p->getVar('images_dir');
                    }

                    if (!isset($v['visual']['picture_props'])){
                        $this->_fields[$k]['visual']['picture_props'] = array(
                            0 => array('src' => '',
                                'size' => array(),
                                'can_resize' => true,
                                'crop' => true),
                            1 => array('src' => '',
                                'size' => array('width' => 100),
                                'can_resize' => true,
                                'secondary' => 0,
                                'crop' => true),
                        );
                    }

                }

            }

            $this->_isMultilang = $multilang;

            !$this->_primaryKey ? ($this->_isMultilang ? $this->_primaryKey = 'real_id' : 'id') : null;

            if (!$this->_storage)
                $this->_storage = 'base';

            // now connect fields controller serialize caches
            if ($this->_isMultilang){
                foreach ($this->_p->getVar('langs') as $v){
                    $this->_linkedSerialized[] = $this->getModelTable().'_fields.'.$v['nick'];
                    $this->_linkedSerialized[] = $this->getModelTable().'.'.$v['nick'];
                }
            } else {
                $this->_linkedSerialized[] = $this->getModelTable().'_fields';
                $this->_linkedSerialized[] = $this->getModelTable();
            }

        }

    }

    /**
     * generate primary key value
     *
     * @return array|mixed|null|void
     */
    public function generatePrimaryValue(){

        $qb = $this->_p->db->getQueryBuilder($this->_storage);

        if ($this->getIsMultilang()){
            // 1. get new free identifier
            // reserving id
            // checking for empty key

            $sql = $qb->compile($this, array(
                'no_array_key' => true,
                'select' => array(
                    'a' => array(
                        array(
                            'field' => $this->getPrimaryKey(),
                            'function' => 'max()'
                        )
                    )
                ),
                'limit' => 1
            ));

            $max = $this->_p->db->selectCell($sql, $this->_storage);

            if (!$max){

                $sql = $qb->compileInsert($this, array($this->getPrimaryKey() => 1));
                $id = $this->_p->db->query($sql, $this->_storage);

            } else {

                // compile subquery
                $sql = $qb->compileInsert($this, array(
                    $this->getPrimaryKey() => array(
                        'subquery' => array(
                            'no_array_key' => true,
                            'select' => array(
                                'a' => array(
                                    array(
                                        'function' => 'max()',
                                        'field' => $this->getPrimaryKey(),
                                        'equation' => '+1'
                                    )
                                )
                            )
                        )
                    )
                ));

                $id = $this->_p->db->query($sql, $this->_storage);

            }

            $sql = $qb->compile($this, array(
                'no_array_key' => true,
                'select' => $this->getPrimaryKey(),
                'where' => array('id' => $id)
            ));

            $id = $this->_p->db->selectCell($sql, $this->_storage);

        } else {

            $sql = $qb->compileInsert($this);

            $id = $this->_p->db->query($sql, $this->_storage);
        }

        $this->_data[$this->getPrimaryKey()] = $id;

        unset($qb);

        return $id;

    }

    /**
     * extract value of primary key from the data collection
     * @return null|int
     */
    public function getPrimaryValue(){

        return $this->_data && $this->getPrimaryKey() && isset($this->_data[$this->getPrimaryKey()]) ? $this->_data[$this->getPrimaryKey()] : null;

    }

    /**
     * setter for primary key value
     * @param $value
     * @return $this
     */
    public function setPrimaryValue($value){

        if ($this->getPrimaryKey()){
            $this->_data[$this->getPrimaryKey()] = $value;
        }

        return $this;

    }


    /**
     * preprocces for save model
     * @param null $data
     * @return bool
     */
    public function preSave($data = null){

        $event = new oEvent(
            WEBT_MODEL_PRE_SAVE,
            $this,
            $data);

        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * postrocces for save model
     * @param null $data
     * @return bool
     */
    public function postSave($data = null){

        // saving linked data
        if ($this->_data['links'] || $this->_data['reverse_links']){

            /**
             * uncomment, if you want to save old links
             *
             * foreach ($this->_links as $k => $v){
            if ((is_array($v) && !isset($data['links'][$v['nick']])) ||
            (!is_array($v) && !isset($data['links'][$v]))){
            unset($this->_links[$k]);
            }
            }
             */

            $oLinker = $this->_p->Module($this->_p->getVar('linker')['service'])->AddParams(array(
                'tbl_name'		=>	$this->getModelTable(),
                'model'         =>  $this,
                'link_tbl'		=>	$this->getModelLinkTable(),
                'links'			=>	$this->_links,
                'reverseLinks'			=>	$this->_reverseLinks,
                'elem_id'		=>	(int)$this->_data[$this->getPrimaryKey()]
            ))->saveData($this->_data);
            unset($oLinker);
        }

        // insert into hyper search
        if ($this->_data[$this->getPrimaryKey()] && $this->_p->getVar('search')['is_indexing'] && !$this->getIsNoindex()){

            $oSearch = $this->_p->Module('oSearch');

            $sql = $this->_p->db->getQueryBuilder($this->_storage)->compile($this, array(
                'no_array_key' => true,
                'where' => array($this->getPrimaryKey() => $this->_data[$this->getPrimaryKey()])
            ));

            $res = $this->_p->db->select($sql, $this->_storage);

            if ($res){

                if ($this->getIsMultilang()){

                    foreach ($res as $arr)
                        $oSearch->saveData(array(
                            'model' => $this,
                            'data' => $arr
                        ));

                } else {

                    $oSearch->saveData(array(
                        'model' => $this,
                        'data' => current($res),
                    ));

                }
            }
            unset($oSearch);
        }

        if (!$this->_isNoCacheClean){

            // clear serialized linked data only NOW - because we have problems with reindex
            foreach ($this->getLinkedSerialized() as $v){
                $this->_p->jobs->add('$this->_p->cache->removeSerial(\''.$v.'\')');
            }
            // clear connected cached pages
            $this->_p->jobs->add('$this->_p->cache->removeMeta(\''.$this->getModelTable().'\', '.(int)$this->_data[$this->getPrimaryKey()].')');

            // remove user cache
            if ($this->getIsMultilang() && is_array($this->_data[$this->_p->getLangId()])){
                if ($this->_data[$this->_p->getLangId()]['user_id'])
                    $this->_p->jobs->add('$this->_p->cache->removeMeta(\''.$this->_p->getVar('tbl_users').'\', \''.(int)$this->_data[$this->_p->getLangId()]['user_id'].'\')');

            } elseif ($this->_data['user_id']) {
                $this->_p->jobs->add('$this->_p->cache->removeMeta(\''.$this->_p->getVar('tbl_users').'\', \''.(int)$this->_data['user_id'].'\')');
            }
        }

        // dispatching other functions
        $event = new oEvent(
            WEBT_MODEL_POST_SAVE,
            $this,
            $data);

        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * this event bubbles right after all fields ready for saving
     * @param array $data must be vector of fields => values
     * @return bool
     */
    public function postPrepareSaveFields(&$data = null){

        $event = new oEvent(
            WEBT_MODEL_POST_PREPARE_SAVE_FIELDS,
            $this,
            $data);
        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * preprocces for delete model
     * @param null $data
     * @return bool
     */
    public function preDelete($data = null){

        $this->_p->events->dispatch(new oEvent(
                WEBT_MODEL_PRE_DELETE,
                $this,
                $data)
        );

        return true;

    }

    /**
     * postprocces for delete model
     * @param null $data
     * @return bool
     */
    public function postDelete($data = null){

        // delete from hyper_search
        if ($this->_p->getVar('search')['is_indexing']){

            $oSearch = $this->_p->Module('oSearch');

            $oSearch->removeData(array(
                'model' => $this,
                'remove_all_langs' => true
            ));
            unset($oSearch);
        }

        // remove meta cache

        $this->_p->jobs->add('$this->_p->cache->removeMeta("'.$this->getModelTable().'", '.(int)$this->getPrimaryValue().');');
        // remove user cache
        if (isset($this->_data['user_id']) && $this->_data['user_id']) {
            $this->_p->jobs->add('$this->_p->cache->removeMeta("'.$this->_p->getVar('tbl_users').'", '.(int)$this->_data['user_id'].');');
        }

        $res = $this->_p->events->dispatch(new oEvent(
                WEBT_MODEL_POST_DELETE,
                $this,
                $data)
        );

        // cleanup current data
        //$this->_data = array();

        return $res;

    }

    /**
     * postprocess for update model
     * @param null $data
     * @return bool
     */
    public function postUpdate($data = null){

        // insert into hyper_search
        if ($this->_p->getVar('search')['is_indexing'] && !$this->_isNoindex){

            $oSearch = $this->_p->Module('oSearch');

            $sql = $this->_p->db->getQueryBuilder($this->_storage)->compile($this, array(
                'no_array_key' => true,
                'where' => array($this->getPrimaryKey() => $this->_data[$this->getPrimaryKey()])
            ));

            $res = $this->_p->db->select($sql, $this->_storage);

            if ($res){

                if ($this->getIsMultilang()){

                    foreach ($res as $arr)
                        $oSearch->saveData(array(
                            'model' => $this,
                            'data' => $arr
                        ));
                } else {

                    $oSearch->saveData(array(
                        'model' => $this,
                        'data' => current($res),
                    ));
                }
            }
            unset($oSearch);

        }

        if (!$this->_isNoCacheClean){

            // clear serialized linked data only NOW - because porblems with reindex
            foreach ($this->getLinkedSerialized() as $v){
                $this->_p->jobs->add('$this->_p->cache->removeSerial(\''.$v.'\')');
            }

            $this->_p->jobs->add('$this->_p->cache->removeMeta("'.$this->getModelTable().'", '.(int)$this->getPrimaryValue().');');

        }

        $event = new oEvent(
            WEBT_MODEL_POST_UPDATE,
            $this,
            $data);

        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * preprocess for update model
     * @param null $data
     * @return bool
     */
    public function preUpdate($data = null){

        $event = new oEvent(
            WEBT_MODEL_PRE_UPDATE,
            $this,
            $data);

        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * postprocess for duplicate model
     * @param null $data
     * @return bool
     */
    public function postDuplicate($data = null){

        if (!$this->_isNoCacheClean){

            // clear serialized linked data only NOW - because porblems with reindex
            foreach ($this->getLinkedSerialized() as $v){
                $this->_p->jobs->add('$this->_p->cache->removeSerial(\''.$v.'\')');
            }

        }

        $event = new oEvent(
            WEBT_MODEL_POST_DUPLICATE,
            $this,
            $data);

        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * preprocess for duplicate model
     * @param null $data
     * @return bool
     */
    public function preDuplicate($data = null){

        $event = new oEvent(
            WEBT_MODEL_PRE_DUPLICATE,
            $this,
            $data
        );

        $this->_p->events->dispatch($event);

        return $event->getContext();

    }

    /**
     * method extracts model's name from namespace
     * @return null|string
     */
    public function getModelName(){

        $modelname = get_class($this);
        $modelname = explode('\\', $modelname);
        //$modelname = $modelname[count($modelname) - 1];
        $modelname = (count($modelname) > 2 ? $modelname[0].':' : '').$modelname[count($modelname) - 1];

        return $modelname == 'oModel' && $this->_workTbl ? $this->_workTbl : $modelname;

    }


    /**
     * getter for model fields
     * @return array
     */
    public function getModelFields(){

        return $this->_fields;

    }

    /**
     * setter for model fields
     * @param $fields
     * @return $this
     */
    public function setModelFields($fields){

        $this->_fields = $fields;
        return $this;

    }

    /**
     * detect if model has this fields
     * @param string|array $fields keys to search
     * @return array|bool
     */
    public function hasModelFields($fields){

        if ($fields){

            if (!is_array($fields))
                $fields = array($fields);

            return array_intersect($fields, array_keys($this->_fields));

        }

        return true;

    }

    /**
     * updater for model's fields
     * @param $field
     * @param array $params
     * @param string $method method to manipulate with settings - 'rewrite' or 'combine'
     * @return $this
     */
    public function updateModelField($field, $params = array(), $method = 'rewrite'){

        if (isset($this->_fields[$field]) && $params){
            $this->_fields[$field] = array_merge_recursive_distinct($this->_fields[$field], $params, $method);
        }

        return $this;

    }

    /**
     * method adds new field to collection
     * @param $field
     * @param array $params
     * @return $this
     * @throws \Exception
     */
    public function addModelField($field, $params = array()){

        if (!isset($this->_fields[$field])){
            if ($params)
                $this->_fields[$field] = $params;
        } else {
            throw new \Exception('error.model.add_field_exists');
        }

        return $this;

    }

    /**
     * method removes model's field from collection
     * @param $field
     * @return $this
     */
    public function removeModelField($field){

        if (isset($this->_fields[$field])){
            unset($this->_fields[$field]);
        }

        return $this;

    }

    /**
     * getter for model's links to another entities
     * @return array
     */
    public function getModelLinks(){

        return $this->_links;

    }

    /**
     * setrer for model's links to another entities
     * @param $links
     * @return $this
     */
    public function setModelLinks($links){

        $this->_links = $links;
        return $this;

    }

    /**
     * getter for model's reversed links to another entities
     * @return array
     */
    public function getModelReverseLinks(){

        return $this->_reverseLinks;

    }

    /**
     * setter for model's reversed links to another entities
     * @param $reverse_links
     * @return $this
     */
    public function setModelReverseLinks($reverse_links){

        $this->_reverseLinks = $reverse_links;
        return $this;

    }

    /**
     * getter for model's table name
     * @deprecated
     * @return null|string
     */
    public function getWorkTbl(){

        return $this->_workTbl;

    }

    /**
     * setter for model's table name
     * @deprecated
     * @param $workTbl
     * @return $this
     */
    public function setWorkTbl($workTbl){

        $this->_workTbl = $workTbl;
        return $this;

    }

    /**
     * getter for model's table name
     * @return null|string
     */
    public function getModelTable(){

        return $this->_workTbl;

    }

    /**
     * setter for model's table name
     * @param $workTbl
     * @return $this
     */
    public function setModelTable($workTbl){

        $this->_workTbl = $workTbl;
        return $this;

    }

    /**
     * getter for model's links table name
     * @deprecated
     * @return null|string
     */
    public function getLinkTbl(){

        return $this->_linkTbl;

    }

    /**
     * setter for model's links table name
     * @deprecated
     * @param $linkTbl
     * @return $this
     */
    public function setLinkTbl($linkTbl){

        $this->_linkTbl = $linkTbl;
        return $this;

    }


    /**
     * getter for model's links table name
     * @return null|string
     */
    public function getModelLinkTable(){

        return $this->_linkTbl;

    }

    /**
     * setter for model's links table name
     * @param $linkTbl
     * @return $this
     */
    public function setModelLinkTable($linkTbl){

        $this->_linkTbl = $linkTbl;
        return $this;

    }

    /**
     * getter for model's upload directory
     * @return string
     */
    public function getUploadDir(){

        return $this->_uploadDir;

    }

    /**
     * setter for model's upload directory
     * @param $uploadDir
     * @return $this
     */
    public function setUploadDir($uploadDir){

        $this->_uploadDir = $uploadDir;
        return $this;

    }

    /**
     * getter for model's primary key name
     * @return null|string
     */
    public function getPrimaryKey(){

        if (!$this->_primaryKey){

            if ($this->_fields && !empty($this->_fields)){
                foreach ($this->_fields as $k => $v){
                    if ($v['primary']) $this->_primaryKey = $k;
                }
            }

        }

        return $this->_primaryKey;

    }

    /**
     * getter for model's multilanguage flag
     * @return bool|null
     */
    public function getIsMultilang(){

        if ($this->_isMultilang === null && $this->_fields){
            foreach ($this->_fields as $v){
                if (isset($v['multilang']) && $v['multilang']){
                    $this->_isMultilang = true;
                    break;
                }
            }
        }

        return $this->_isMultilang;

    }

    /**
     * getter for model's no indexing flag in universal search base
     * @return bool
     */
    public function getIsNoindex(){

        return $this->_isNoindex;

    }

    /**
     * setter for model's no indexing flag in universal search base
     * @param $isNoindex
     * @return $this
     */
    public function setIsNoindex($isNoindex){
        
        $this->_isNoindex = $isNoindex;
        return $this;
        
    }

    /**
     * getter for model's no clean cache flag
     * @return bool
     */
    public function getIsNoCacheClean(){

        return $this->_isNoCacheClean;

    }

    /**
     * setter for model's no clean cache flag
     * @param $isNoCacheClean
     * @return $this
     */
    public function setIsNoCacheClean($isNoCacheClean){

        $this->_isNoCacheClean = $isNoCacheClean;
        return $this;

    }


    /**
     * getter for list of the linked serialized names
     * @return array
     */
    public function getLinkedSerialized(){

        return $this->_linkedSerialized;

    }

    /**
     * getter for model's data
     * @param null $field
     * @return array|null
     */
    public function getModelData($field = null){

        return $field ? (isset($this->_data[$field]) ? $this->_data[$field] : null) : $this->_data;

    }

    /**
     * setter for model's data
     * @param $data
     * @return $this
     */
    public function setModelData($data){

        $this->_data = $data;
        return $this;

    }

    /**
     * update model data
     * instead of rewriting all model's data it merge
     * @param $data
     * @return $this
     */
    public function updateModelData($data){

        $this->extend(array(
            'data' => $data
        ));

        return $this;

    }

    /**
     * getter for model's links data
     * @return array
     */
    public function getModelDataLinks(){

        return $this->_dataLinks;

    }

    /**
     * setter for model's links data
     * @param $dataLinks
     * @return $this
     */
    public function setModelDataLinks($dataLinks){

        $this->_dataLinks = $dataLinks;
        return $this;

    }

    /**
     * getter for model's storage name
     * @return string
     */
    public function getModelStorage(){

        return $this->_storage;

    }

    /**
     * setter for model's storage
     * @param $storage
     * @return $this
     */
    public function setModelStorage($storage){

        $this->_storage = $storage;
        return $this;

    }

    /**
     * setter for old model's data
     * @param $oldData
     * @return $this
     */
    public function setModelOldData($oldData){

        $this->_oldData = $oldData;
        return $this;

    }

    /**
     * getter for ols model's data
     * @param null $field
     * @return array|null
     */
    public function getModelOldData($field = null){

        if ($this->_oldData){
            reset($this->_oldData);
        }

        return $field ? ($this->_oldData && isset(current($this->_oldData)[$field]) ? current($this->_oldData)[$field] : null) : $this->_oldData;

    }


    /*
     * setter for primary key of model
     * !WARNING! Use this method on your own risk
     */
    public function setPrimaryKey($primaryKey){

        $this->_primaryKey = $primaryKey;
        return $this;

    }

    /**
     * setter for multilanuage property
     * @param $isMultilang
     * @return $this
     */
    public function setIsMultilang($isMultilang){

        $this->_isMultilang = $isMultilang;
        return $this;

    }

    /**
     * setter for serialized links
     * @param $linkedSerialized
     * @return $this
     */
    public function setLinkedSerialized($linkedSerialized){

        $this->_linkedSerialized = $linkedSerialized;
        return $this;

    }

    /**
     * method generate pictures for selected field
     *
     * @param $field
     * @return array
     */
    public function getPictures($field){

        if (!isset($this->_fields[$field]))
            return null;

        if (!isset($this->_fields[$field]['visual']) || $this->_fields[$field]['visual']['type'] != 'picture' || !$this->_data[$field])
            return null;

        return Images::get($this->_p, $this->_data[$field], $this->_fields[$field]['visual']['img_dir'], $this->getPrimaryValue());

    }




    /**
     * getter and setter for fields data
     * @param string $name
     * @param array $arguments
     * @return $this|null
     * @throws \Exception
     */
    public function __call($name, array $arguments){

        if (preg_match('/^set(.+)$/', $name, $match) && count($arguments) > 0 && isset($this->_fields[lcfirst($match[1])])){

            $this->_data[lcfirst($match[1])] = $arguments[0];
            return $this;

        } elseif (preg_match('/^get(.+)$/', $name, $match) && isset($this->_fields[lcfirst($match[1])])){
            $n = lcfirst($match[1]);
            if (isset($this->_data[$n]))
                return $this->_data[$n];
            else
                return null;
        }

        throw new \Exception($this->_p->Service('oLanguages')->trans('error.no_getter_setter_for_field'));

    }

}
