<?php
/**
 * Models repoitory base class
 *
 * Date: 16.08.14
 * Time: 21:30
 * @version 1.0
 * @author goshi
 * @package web-T[Interfaces]
 * 
 * Changelog:
 *	1.0	16.08.2014/goshi 
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;

interface iModelRepository {

    public function find($ids);
    public function findOne($ids);
    public function findBy($conditions = array());
    public function findOneBy($conditions = array());

}

class oModelRepository extends oBase implements iModelRepository {

    /**
     * hydration types
     */
    const ML_HYDRATION_MODEL = 1;
    const ML_HYDRATION_ARRAY = 2;

    /**
     * current model name
     * @var null|string|oModel
     */
    protected $_model = null;

    /**
     * model instance
     * @var oModel
     */
    protected $_modelInstance;

    public function __construct(oPortal $p, $model){

        $this->_model = $model;

        return parent::__construct($p);

    }

    /**
     * getter for model instance
     * @return oModel
     * @throws \Exception
     */
    protected function _getInstance(){

        if (!($this->_modelInstance && is_object($this->_modelInstance))){
            $this->_modelInstance = $this->_p->Model($this->_model);
        }

        if (!$this->_modelInstance)
            throw new \Exception($this->_p->trans('error.no_model_instance'), ERROR_NO_MODEL_FOUND);

        return $this->_modelInstance;

    }

    /**
     * method finds entities in the storage by their IDs
     * @param array|int $ids
     * @param array $conditions
     * @param int $hydration
     * @return array|null|void
     */
    public function find($ids, $conditions = array(), $hydration = self::ML_HYDRATION_MODEL){

        $result = null;

        if ($ids){

            if (is_array($ids)){
                array_walk($ids, 'intval');
            } else {
                $ids = (int)$ids;
            }

            // checking for model
            if ($this->_getInstance()){

                if (!isset($conditions['where'])){
                    $conditions['where'] = array();
                }

                if (is_array($ids))
                    $conditions['where'][] = array('key' => $this->_getInstance()->getPrimaryKey(), 'op' => 'in', 'value' => $ids);
                else
                    $conditions['where'][$this->_getInstance()->getPrimaryKey()] = $ids;

                $result = $this->findBy($conditions, $hydration);

            }

        }

        return $result;

    }

    /**
     * method finds entities in the storage by condition
     * for condition - @see \webtFramework\Components\Storage\oQueryBuilder class
     * @param array $conditions
     * @param int $hydration hydration type
     * @return array|oModel|null|void
     */
    public function findBy($conditions = array(), $hydration = self::ML_HYDRATION_MODEL){

        $result = null;

        // checking for model
        if ($this->_getInstance()){

            $query = $this->_p->db->getQueryBuilder($this->_modelInstance->getModelStorage())->compile($this->_modelInstance, $conditions);

            if ($query){

                $result = $this->_p->db->select($query, $this->_modelInstance->getModelStorage());

                if ($result){

                    // check for multilang option
                    if (isset($conditions['multilang']) && $conditions['multilang'] && $this->_modelInstance->getIsMultilang()){

                        $ml_fields = array();

                        // collect multilang fields
                        foreach ($this->_modelInstance->getModelFields() as $k => $v){

                            if (isset($v['multilang']) && $v['multilang']){
                                $ml_fields[] = $k;
                            }

                        }

                        // check if any multilang field realy exists
                        if (!empty($ml_fields)){

                            $ids = array();

                            $prim = $this->_modelInstance->getPrimaryKey();

                            foreach ($result as $k => $v){

                                $ids[$k] = $v[$prim];

                            }

                            $tmp_res = $this->find($ids, array('no_array_key' => true), self::ML_HYDRATION_ARRAY);

                            if ($tmp_res){

                                foreach ($tmp_res as $v){

                                    foreach ($ml_fields as $f){

                                        if (!is_array($result[$ids[$v[$prim]]][$f])){

                                            $result[$ids[$v[$prim]]][$f] = array($result[$ids[$v[$prim]]]['lang_id'] => $result[$ids[$v[$prim]]][$f]);

                                        }

                                        $result[$ids[$v[$prim]]][$f][$v['lang_id']] = $v[$f];

                                    }

                                }

                            }

                            unset($ids);
                            unset($tmp_res);

                        }

                        unset($ml_fields);

                    }

                    if ($hydration == self::ML_HYDRATION_MODEL){
                        $tmp_res = array();
                        foreach ($result as $k => $v){
                            /**
                             * @var \webtFramework\Interfaces\oModel $tmp_res[$k]
                             */
                            $tmp_res[$k] = $this->_p->Model($this->_model);
                            $tmp_res[$k]->setModelData($v);
                        }
                        $result = $tmp_res;
                    }

                }

            }


        }

        return $result;

    }

    /**
     * method finds one entity in the storage by its id
     * @param $ids
     * @param array $conditions
     * @param int $hydration
     * @return array|null|void|oModel
     */
    public function findOne($ids, $conditions = array(), $hydration = self::ML_HYDRATION_MODEL){

        $conditions['limit'] = 1;

        $res = $this->find($ids, $conditions, $hydration);
        return $res ? current($res) : null;

    }

    /**
     * method finds one entity in the storage by conditions
     * @param array $conditions
     * @param int $hydration
     * @return array|null|void|oModel
     */
    public function findOneBy($conditions = array(), $hydration = self::ML_HYDRATION_MODEL){

        $conditions['limit'] = 1;

        $res = $this->findBy($conditions, $hydration);
        return $res ? current($res) : null;

    }

    /**
     * method updates model data
     * @param oModel $model
     * @param array $data
     * @param array $conditions
     * @return array|null
     */
    public function update(oModel $model, $data = array(), $conditions = array()){

        $result = null;

        // checking for model
        if ($model && is_object($model) && $model instanceof oModel){

            // add primary field
            if ((!isset($conditions['where']) || (isset($conditions['where']) && empty($conditions['where']))) && $model->getPrimaryKey()){
                if (!isset($conditions['where']))
                    $conditions['where'] = array();

                $conditions['where'][$model->getPrimaryKey()] = $model->getPrimaryValue();
            }

            $model->setModelOldData($model->getModelData());
            $query = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileUpdate($model, $data, $conditions);

            if ($query){

                $result = $this->_p->db->query($query, $model->getModelStorage());
                if ($result){
                    // update model data
                    $model->postUpdate($data);
                }

            }
        }

        return $result;

    }



}

