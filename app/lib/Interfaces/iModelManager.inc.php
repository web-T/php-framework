<?php
/**
 * Model's manager for base functions
 *
 * Date: 10.08.14
 * Time: 09:37
 * @version 1.0
 * @author goshi
 * @package web-T[Interfaces]
 * 
 * Changelog:
 *	1.0	10.08.2014/goshi 
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;

class oModelManager extends oBase  {

    /**
     * backend application ID
     * @var null|int
     * @deprecated
     * TODO: refactor to make another link to the custom fields
     */
    //protected $_adm_app_id = null;

    /**
     * cached model repositories
     * @var array
     */
    protected $_modelRepositories = array();

    public function __construct(oPortal &$p, $params = array()){

        parent::__construct($p, $params);

    }

    /**
     * method initialize (create) primary value of the model
     * @param oModel $model
     * @return array|bool|mixed|null|void
     */
    public function initPrimaryValue(oModel &$model){

        $pk = null;

        // check for model fields
        if (!($model->getModelFields() && ($pk = $model->getPrimaryKey()))){
            return false;
        }

        $method = 'get'.ucfirst($pk);
        $pv = $model->$method();

        // check if we update info
        if ($pv){
            // saving old data

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, array(
                'no_array_key' => true,
                'select' => array('__groupkey__' => $model->getIsMultilang() ? 'lang_id' : null, 'a' => '*'),
                'where' => array($model->getPrimaryKey() => $pv)
            ));
            $old_data = $this->_p->db->select($sql, $model->getModelStorage());

            if ($old_data){

                //$old_id = $pv;
                //
                reset($old_data);
                list(, $curr_row) = each($old_data);

                // if not multilang - get one row fro old
                if (!$model->getIsMultilang())
                    $old_data = $curr_row;

            }

            // set old data to the model
            $model->setModelOldData($old_data);

            $id = $pv;

        } else {

            $id = $model->generatePrimaryValue();

        }

        $method = 'set'.ucfirst($pk);
        $model->$method($id);

        return $id;

    }


    /**
     * get model repository class
     * @param string|oModel $model
     * @return null|oModelRepository
     */
    public function getRepository($model = null){

        if ($model instanceof oModel){
            $modelname = $model->getModelName();
        } else
            $modelname = $model;

        if (!isset($this->_modelRepositories[$modelname])){
            $this->_modelRepositories[$modelname] = new oModelRepository($this->_p, $model instanceof oModel ? $model : $modelname);
        }

        return $this->_modelRepositories[$modelname];

    }

    /**
     * method duplicates model
     * @param null $model
     */
    public function duplicate($model = null){
        // TODO: make duplicate method
    }


    /**
     * method save model to storage
     *
     * @param oModel $model
     * @return bool|null|int
     * @throws \Exception
     */
    public function save(oModel &$model){

        // check if model has data
        if (!$model->getModelData()){
            return false;
        }

        // check if model has id
        if (!($model->getPrimaryKey() && $model->getModelData($model->getPrimaryKey()))){
            throw new \Exception($this->_p->trans('errors.model_no_primary'));
        }

        // fix model multilang fields data
        if ($model->getIsMultilang() && ($f = $model->getModelFields())){
            foreach ($f as $k => $v){
                $tv = $model->getModelData($k);
                if ($v['multilang'] && !is_array($tv) && $tv !== ''){

                    $nv = array();
                    foreach ($this->_p->getVar('langs') as $lk => $lv){
                        $nv[$lk] = $tv;
                    }
                    $method = 'set'.ucfirst($k);
                    $model->$method($nv);
                }
            }
        }

        $fc = $this->_p->Service('oForms')->AddParams(array(
            'data' => $model->getModelData(),
            'caller' => $this,
            'callbacks' => array(
                'unique' => array(&$this, 'checkValueExists')
            ),
            'model' => $model,
        ));

        if ($model->getUploadDir()){

            if (!file_exists($this->_p->getDocDir().$model->getUploadDir()))
                if(!@mkdir($this->_p->getDocDir().$model->getUploadDir(), PERM_DIRS, true)){
                    throw new \Exception($this->_p->Service('oLanguages')->trans('err_cant_create_dir').":".$this->_p->getDocDir().$model->getUploadDir());
                }
        }

        $model->preSave();

        $data = $model->getModelData();

        $old_data = $model->getModelOldData();

        $fs = $model->getModelFields();

        // checking dirs
        foreach ($fs as $v){
            if (isset($v['visual']) && $v['visual']['type'] == 'picture'){
                if (!file_exists($this->_p->getDocDir().$v['visual']['img_dir']))
                    if(!mkdir($this->_p->getDocDir().$v['visual']['img_dir'], PERM_DIRS, true)){
                        throw new \Exception($this->_p->trans('err_cant_create_dir').":".$this->_p->getDocDir().$v['visual']['img_dir']);
                    }
            }

        }

        $external_data = array();
        // statuses for external multifields already saved
        $external_data_saved = false;
        // statuses for external multifields on language dependence
        $multi_field_multilang_status = array();

        if ($model->getIsMultilang()){

            $first = true;

            // non multifields cache
            $non_multi_cache = array();

            // 2. for each language save info in database and save with old real_id
            foreach ($this->_p->getLangTbl() as $lang_id){
                /*$fields = */$custom_fields = $values = $custom_values = array();

                // update lang_id field
                $data['lang_id'] = $lang_id;

                foreach ($fs as $k => $v){

                    // remove virtual type from saving
                    if ($v['type'] == 'virtual') continue;

                    if ($k == 'last_modified' && $model->getModelOldData($lang_id))
                        $old_data[$lang_id]['last_modified'] = $this->_p->getTime();

                    // check for unique field and add id to exclude from search
                    if (isset($v['unique'])){
                        $fs[$k]['unique']['exclude'] = $model->getPrimaryValue();
                        $fc->AddParams(array('fields' => $fs));
                    }

                    if (isset($non_multi_cache[$k])){
                        $tmp_val = $non_multi_cache[$k];
                    } else {
                        $tmp_val = $fc->getSaveField($k, $data, $old_data, $lang_id);
                        if (!$v['multilang'] && $k != 'lang_id' && $k != 'id'){
                            $non_multi_cache[$k] = $tmp_val;
                        }
                    }

                    if ($v['custom']){
                        $custom_fields[] = $k;
                        $custom_values[$k] = $tmp_val;
                        //echo "out"."---".$k."---".var_dump($tmp_val)."<br>";
                    } else {
                        //echo $k."---".var_dump($data[$k])."---".$tmp_val."<br>";
                        // check if method return array
                        if (is_array($tmp_val)){
                            foreach ($tmp_val as $z => $x){
                                //$fields[] = $z;
                                $values[$z] = $x;
                            }
                        } else {
                            //$fields[] = $k;
                            $values[$k] = $tmp_val;
                        }
                    }

                    // check for multifield with foreign table
                    if (isset($v['visual']) && $v['visual']['type'] == 'multi' &&
                        isset($v['visual']['source']) &&
                        isset($v['visual']['source']['tbl_name']) &&
                        isset($v['visual']['source']['foreign_key'])
                    ){

                        // check, if multifield is language dependent
                        if (!isset($multi_field_multilang_status[$k])){
                            foreach ($v['children'] as $ch_v){
                                if (isset($ch_v['multilang']) && $ch_v['multilang']){
                                    $multi_field_multilang_status[$k] = true;
                                    break;
                                }
                            }
                            $multi_field_multilang_status[$k] = false;
                        }

                        // extract data from multifield
                        $ext_data = unserialize($tmp_val);
                        if ($ext_data && isset($ext_data['items'])){
                            foreach ($ext_data['items'] as $z => $x){
                                if (!isset($external_data[$k])){
                                    $external_data[$k] = array();
                                }
                                if (isset($v['children'][$z]['multilang']) && $v['children'][$z]['multilang'] && is_array($x))
                                    $external_data[$k][] = $x[$lang_id];
                                else
                                    $external_data[$k][] = $x;
                            }
                        }
                    }
                }

                // dispatch event after we got all fields
                $model->postPrepareSaveFields($values);

                // when founded all new fields - duplicate it
                //dump(array($model->getPrimaryValue(), $old_data[$lang_id]));

                if ($model->getPrimaryValue() && $old_data[$lang_id]){

                    unset($values['id']);
                    $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileUpdate($model, $values, array('where' => array(
                        $model->getPrimaryKey() => $model->getPrimaryValue(),
                        'lang_id' => $lang_id
                    )));

                } else {
                    if ($first && $old_data[$lang_id]){

                        // remove unecessary id field
                        unset($values['id']);
                        //dump($values, false);
                        $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileUpdate($model, $values, array('where' => array(
                            $model->getPrimaryKey() => $model->getPrimaryValue(),
                            'lang_id' => $lang_id
                        )));

                    } else {

                        $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileInsert($model, $values);

                    }

                }

                if ($this->_p->db->query($sql, $model->getModelStorage())){

                    // check for external data exists
                    if ($external_data && !empty($external_data)){

                        // first of all cleanup all data from external tables
                        foreach ($external_data as $multi_field => $ext_data){

                            // check for non multilang external field or multilang and not saved yet
                            if ($multi_field_multilang_status[$multi_field] || (!$multi_field_multilang_status[$multi_field] && !isset($external_data_saved))){

                                // create virtual model
                                $external_model = new oModel($this->_p);
                                $external_model->setModelFields($model->getModelFields()[$multi_field]['children']);
                                $external_model->setModelStorage($model->getModelStorage());

                                // add primary
                                if ($multi_field_multilang_status[$multi_field]){
                                    $external_model->addModelField('real_id', array('primary' => true, 'type' => 'int'));
                                    $external_model->addModelField('lang_id', array('type' => 'int'));
                                    $external_model->addModelField('id', array('type' => 'int'));
                                } else {
                                    $external_model->addModelField('id', array('primary' => true, 'type' => 'int'));
                                }

                                $is_on_exists = false;
                                if ($this->_p->db->getQueryBuilder($external_model->getModelStorage())->isFieldExists($model->getModelFields()[$multi_field]['visual']['source']['tbl_name'], 'is_on')){
                                    $external_model->addModelField('is_on', array('type' => 'boolean'));
                                    $is_on_exists = true;
                                }

                                // add foreign key
                                $external_model->addModelField($model->getModelFields()[$multi_field]['visual']['source']['foreign_key'], array('type' => 'int'));
                                $external_model->setModelTable($this->_p->getVar($model->getModelFields()[$multi_field]['visual']['source']['tbl_name']));

                                if (isset($this->_p->getVar('tables')[$model->getModelFields()[$multi_field]['visual']['source']['tbl_name']])){

                                    // remove old values
                                    $sql = $this->_p->db->getQueryBuilder($external_model->getModelStorage())->compileDelete($external_model, array('where' => array(
                                        $model->getModelFields()[$multi_field]['visual']['source']['foreign_key'] => $model->getPrimaryValue()
                                    )));
                                    $this->_p->db->query($sql, $external_model->getModelStorage());

                                    // insert new values
                                    foreach ($ext_data as $x){
                                        $x[$model->getModelFields()[$multi_field]['visual']['source']['foreign_key']] = $model->getPrimaryValue();
                                        if ($is_on_exists && isset($values['is_on'])){
                                            $x['is_on'] = $values['is_on'];
                                        }
                                        $sql = $this->_p->db->getQueryBuilder($external_model->getModelStorage())->compileInsert($external_model, $x);
                                        $this->_p->db->query($sql, $external_model->getModelStorage());
                                    }
                                }

                                // set flag for always save field
                                $external_data_saved = true;
                            }
                        }
                    }
                }

                $first = false;

                // saving custom fields
                //dump($this->_adm_app_id, false);
                //if ($this->_adm_app_id){
                    $fc->saveCustomValues(array(
                        'model' => $model,
                        //'fields' => $custom_fields,
                        'values' => $custom_values,
                        'static_values' => $data,
                        'weights' => $data['weight'],
                        'extended_descr' => $data['extended_descr'],
                        'is_unknown' => $data['is_unknown'],
                        'lang_id' => $lang_id,
                        'real_id' => $model->getPrimaryValue(),
                        //'link_id' => $this->_adm_app_id
                        )
                    );
                //}

            }

            // finally - delete non active language items
            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileDelete($model, array('where' => array(
                $model->getPrimaryKey() => $model->getPrimaryValue(),
                '$or' => array(
                    array('field' => 'lang_id', 'op' => 'not in', 'value' => $this->_p->getLangTbl()),
                    array('field' => 'lang_id', 'op' => 'is', 'value' => 'null'),
                )

            )));

            $this->_p->db->query($sql, $model->getModelStorage());

        } else {

            /*$fields = */$custom_fields = $values = $custom_values = array();
            foreach ($fs as $k => $v){

                // remove virtual type from saving
                if ($v['type'] != 'virtual'){

                    if ($k == 'last_modified' && $old_data)
                        $old_data['last_modified'] = $this->_p->getTime();

                    if (isset($v['unique'])){
                        $fs[$k]['unique']['exclude'] = $model->getPrimaryValue();
                        $fc->AddParams(array('fields' =>$fs));
                    }

                    $tmp_val = $fc->getSaveField($k, $data, $old_data);
                    if ($v['custom']){
                        $custom_fields[] = $k;
                        $custom_values[$k] = $tmp_val;
                    } else {

                        // check if method return array
                        if (is_array($tmp_val)){
                            foreach ($tmp_val as $z => $x){
                                //$fields[] = $z;
                                $values[$z] = $x;
                            }
                        } else {
                            //$fields[] = $k;
                            $values[$k] = $tmp_val;
                        }

                    }

                    // check for multifield with foreign table
                    if (isset($v['visual']) && $v['visual']['type'] == 'multi' &&
                        isset($v['visual']['source']) &&
                        isset($v['visual']['source']['tbl_name']) &&
                        isset($v['visual']['source']['foreign_key'])
                    ){
                        // extract data from multifield
                        $ext_data = unserialize($tmp_val);
                        if ($ext_data && isset($ext_data['items'])){
                            foreach ($ext_data['items'] as $x){
                                if (!isset($external_data[$k])){
                                    $external_data[$k] = array();
                                }
                                $external_data[$k][] = $x;
                            }
                        }


                    }
                }

            }

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileUpdate($model, $values, array('where' => array($model->getPrimaryKey() => $model->getPrimaryValue())));
            if ($this->_p->db->query($sql, $model->getModelStorage())){
            //dump_file($sql);

                // check for external data exists
                if ($external_data && !empty($external_data)){

                    // first of all cleanup all data from external tables
                    foreach ($external_data as $multi_field => $ext_data){

                        // create virtual model
                        $external_model = new oModel($this->_p);
                        $external_model->setModelFields($model->getModelFields()[$multi_field]['children']);
                        $external_model->setModelStorage($model->getModelStorage());
                        // add primary
                        $external_model->addModelField('id', array('primary' => true, 'type' => 'int'));

                        $is_on_exists = false;
                        if ($this->_p->db->getQueryBuilder($external_model->getModelStorage())->isFieldExists($model->getModelFields()[$multi_field]['visual']['source']['tbl_name'], 'is_on')){
                            $external_model->addModelField('is_on', array('type' => 'boolean'));
                            $is_on_exists = true;
                        }

                        // add foreign key
                        $external_model->addModelField($model->getModelFields()[$multi_field]['visual']['source']['foreign_key'], array('type' => 'int'));
                        $external_model->setModelTable($this->_p->getVar($model->getModelFields()[$multi_field]['visual']['source']['tbl_name']));

                        if (isset($this->_p->getVar('tables')[$model->getModelFields()[$multi_field]['visual']['source']['tbl_name']])){

                            // remove old values
                            $sql = $this->_p->db->getQueryBuilder($external_model->getModelStorage())->compileDelete($external_model, array('where' => array(
                                $model->getModelFields()[$multi_field]['visual']['source']['foreign_key'] => $model->getPrimaryValue()
                            )));
                            $this->_p->db->query($sql, $external_model->getModelStorage());

                            // insert new values
                            foreach ($ext_data as $x){
                                $x[$model->getModelFields()[$multi_field]['visual']['source']['foreign_key']] = $model->getPrimaryValue();
                                if ($is_on_exists && isset($values['is_on'])){
                                    $x['is_on'] = $values['is_on'];
                                }
                                $sql = $this->_p->db->getQueryBuilder($external_model->getModelStorage())->compileInsert($external_model, $x);
                                $this->_p->db->query($sql, $external_model->getModelStorage());
                            }
                        }
                    }
                }
            }

        }

        unset($fc);
        unset($fs);
        unset($old_data);
        unset($data);
        unset($external_data);

        // dispatch model event
        $model->postSave();


        return $model->getPrimaryValue();

    }

    /**
     * method delete model from storage
     * @param oModel $model
     * @return bool
     * @throws \Exception
     */
    public function remove(oModel $model){

        // check if model has data
        if (!$model->getModelData()){
            return false;
        }

        // check if model has id
        if (!($model->getPrimaryKey() && $model->getModelData($model->getPrimaryKey()))){
            throw new \Exception($this->_p->trans('errors.model_no_primary'));
        }

        //$data = $this->getRepository($model)->findOneBy($model->getPrimaryValue(), oModelRepository::ML_HYDRATION_ARRAY);

        // check if element with this id exists?
        if ($model->getModelData()){

            // dispatch model predelete event
            $status = $model->preDelete($model->getModelData());

            if ($status){

                // check for tree type
                if (isset($model->getModelFields()['owner_id'])){

                    $res = $this->getRepository($model)->findBy(array(
                        'select' => array('a' => '[PRIMARY]'),
                        'where' => array('owner_id' => $model->getPrimaryValue()),
                    ));

                    if ($res){

                        // delete nested items
                        foreach ($res as $z){
                            $this->remove($z);
                        }
                    }

                }



                /**
                 * collecting info about files
                 */
                if ($this->_p->getVar('upload')['service'] && ($oUploader = $this->_p->Module($this->_p->getVar('upload')['service']))){

                    $oUploader = $this->_p->Module($this->_p->getVar('upload')['service']);

                    $mf = $model->getModelFields();
                    $custom_fields = array();
                    foreach ($mf as $k => $v){
                        if ($v['visual'] && $v['visual']['type'] == 'file' && !$v['visual']['file_save_linked']){
                            $oUploader->removeFile(array('elem_id' => $model->getPrimaryValue(), 'filename' => $model->getModelData()[$k], 'filepath' => $model->getUploadDir()));
                        }

                        if ($v['visual'] && $v['visual']['type'] == 'picture' && $model->getModelData()[$k] != '' && $this->_p->getVar('image')['service']){

                            $imgLoader = $this->_p->Service($this->_p->getVar('image')['service']);
                            $img_params['elem_id'] = $model->getPrimaryValue();
                            $img_params['data'] = $model->getModelData()[$k];
                            $img_params['images'] = $v['visual']['picture_props'];
                            $img_params['img_dir'] = $v['visual']['img_dir'];

                            $imgLoader->removeData($img_params);
                            unset($imgLoader);

                        }

                        if (isset($v['custom']) && $v['custom']){
                            $custom_fields[] = $k;
                        }

                    }

                    unset($mf);
                }

                $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compileDelete($model, array(
                    'where' => array(
                        $model->getPrimaryKey() => $model->getPrimaryValue()
                    )
                ));

                $this->_p->db->query($sql, $model->getModelStorage());

                // delete external rows
                foreach ($model->getModelFields() as $k => $v){
                    // check for multifield with foreign table
                    if (isset($v['visual']) && $v['visual']['type'] == 'multi' &&
                        isset($v['visual']['source']) &&
                        isset($v['visual']['source']['tbl_name']) &&
                        isset($v['visual']['source']['foreign_key'])
                    ){
                        // create virtual model
                        $external_model = new oModel($this->_p);
                        $external_model->setModelFields($model->getModelFields()[$k]['children']);
                        $external_model->setModelStorage($model->getModelStorage());

                        // add foreign key
                        $external_model->addModelField($model->getModelFields()[$k]['visual']['source']['foreign_key'], array('type' => 'int'));
                        $external_model->setModelTable($this->_p->getVar($model->getModelFields()[$k]['visual']['source']['tbl_name']));

                        if (isset($this->_p->getVar('tables')[$model->getModelFields()[$k]['visual']['source']['tbl_name']])){

                            // remove old values
                            $sql = $this->_p->db->getQueryBuilder($external_model->getModelStorage())->compileDelete($external_model, array('where' => array(
                                $model->getModelFields()[$k]['visual']['source']['foreign_key'] => $model->getPrimaryValue()
                            )));
                            $this->_p->db->query($sql, $external_model->getModelStorage());
                        }
                        unset($external_model);
                    }
                }

                // delete linked data
                // connect linker
                $oLinker = $this->_p->Module($this->_p->getVar('linker')['service'])->AddParams(array(
                    'tbl_name'	=>	$model->getModelTable(),
                    'elem_id'	=>	$model->getPrimaryValue(),
                    'links'	=>	$model->getModelLinks()
                ));
                $oLinker->removeData();
                unset($oLinker);

                if ($model->getModelLinkTable()){

                    // hacking for universal linker
                    $model_linker = new oModel($this->_p);
                    $model_linker->setModelTable($model->getModelLinkTable());
                    $model_linker->addModelField('this_id', array('type' => 'int'));
                    $linker_cond = array(
                        'where' => array(
                            'this_id' => $model->getPrimaryValue()
                        )
                    );
                    if ($model->getModelLinkTable() == $this->_p->getVar('tbl_linker')){
                        $linker_cond['where']['this_tbl_name'] = $model->getModelTable();
                        $model_linker->addModelField('this_tbl_name', array('type' => 'varchar'));
                    }

                    $sql = $this->_p->db->getQueryBuilder()->compileDelete($model_linker, $linker_cond);
                    $this->_p->db->query($sql);
                    unset($model_linker);
                    unset($linker_cond);
                }

                // check for custom fields
                if ($custom_fields && !empty($custom_fields)){
                    $fc = $this->_p->Service('oForms')->AddParams(array(
                        'multilang' => $model->getIsMultilang(),
                        'caller' => $this
                    ));

                    $fc->deleteCustomValues(array('real_id' => $model->getPrimaryValue(), 'model' => $model->getModelName()));
                    unset($fc);
                }

                // call post method
                $model->postDelete($model->getModelData());

                return true;

            }

        }

        return false;

    }


    /**
     * method check if value exists in the model entities
     * @param oModel $model
     * @param array $data
     * @return bool|int
     */
    public function checkValueExists(oModel &$model, $data = array()){

        // check for type of the field
        if ($model->getModelFields()[$data['field']]['custom']){

            $fc = $this->_p->Service('oForms')->AddParams(array(
                'multilang' => $model->getIsMultilang(),
            ));
            //$status = $fc->checkCustomField(array_merge($data, array('link_id' => $data['adm_app_id'])));
            $status = $fc->checkCustomField(array_merge($data, array('model' => $model->getModelName())));
            unset($fc);

        } else {

            $conditions = array('where' => array());
            if (isset($data['exclude'])){

                if (is_array($data['exclude']))
                    $conditions['where'][$model->getPrimaryKey()] = array('op' => 'not in', 'value' => $data['exclude']);
                else
                    $conditions['where'][$model->getPrimaryKey()] = array('op' => '<>', 'value' => $data['exclude']);

            }

            if (isset($data['params']) && isset($data['params']['owner_id'])){
                foreach ($data['params'] as $k => $v){
                    $conditions['where'][$k] = $v;
                }
            }

            $conditions['where'][$data['field']] = $data['value'];
            $conditions['limit'] = 1;

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, $conditions);

            $res = $this->_p->db->select($sql, $model->getModelStorage());

            $status = $res ? true : false;

            unset($conditions);

        }

        return $status;
    }

}
