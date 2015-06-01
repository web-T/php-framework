<?php
/**
 * Base class for all controllers
 *
 * Date: 30.07.14
 * Time: 06:33
 * @version 1.0
 * @author goshi
 * @package web-T[framework]
 * 
 * Changelog:
 *	1.0	30.07.2014/goshi 
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;

interface iApp {

    public function useModel($model);
    public function getModel();

}

abstract class oApp extends oBase implements iApp{

    /**
     * app fields list
     * @var array
     */
    protected $_fields = array();

    /**
     * main work table for app
     * @var string
     */
    protected $_work_tbl = '';
    /**
     * linker table with links to this app
     * @var string
     */
    protected $_link_tbl = '';

    /**
     * links of the app
     * @var array
     */
    protected $_links = array();

    /**
     * reversed links
     * @var array
     */
    protected $_reverse_links = array();

    /**
     * upload files dir
     * @var string
     */
    protected $_upload_dir = '';

    /**
     * primary fields key
     * @var null|string
     */
    protected $_primary = null;

    /**
     * multilang property
     * @var null
     */
    protected $_multilang = null;

    /**
     * key for non-indexing data
     * @var bool
     */
    protected $_noindex = false;

    /**
     * linked serialized structures
     * @var array
     */
    protected $_linked_serialized = array();


    /**
     * model name property
     * @var null
     */
    protected $_model = null;

    /**
     * current model instance
     * @var null|oModel
     */
    protected $_modelInstance = null;


    public function __construct(oPortal $p, $params = array()){

        parent::__construct($p, $params);

        if ($this->_model){
            $this->useModel($this->_model);
        }

    }

    /**
     * method connect model to controller
     * @param $model
     * @return bool
     * @throws \Exception
     */
    public function useModel($model){

        // hack for the future
        if (true){

            // create new model
            if ($model){
                $this->_modelInstance = $this->_p->Model($model);
            } else {
                // for backward compatibility - create new model and patch it with all properties
                $this->_modelInstance = new oModel($this->_p);

                if (!$this->_work_tbl){
                    $class = $this->extractClassname();
                    $this->_work_tbl = $this->_p->getVar('tbl_prefix').$class;
                }

                $this->_modelInstance->setModelTable($this->_work_tbl);
                $this->_modelInstance->setModelLinkTable($this->_link_tbl);
                $this->_modelInstance->setModelFields($this->_fields);
                $this->_modelInstance->setModelLinks($this->_links);
                $this->_modelInstance->setModelReverseLinks($this->_reverse_links);
                $this->_modelInstance->setUploadDir($this->_upload_dir);
                $this->_modelInstance->setPrimaryKey($this->_primary);
                $this->_modelInstance->getIsMultilang($this->_multilang);
                $this->_modelInstance->getLinkedSerialized($this->_linked_serialized);

                // initialize model
                $this->_modelInstance->init();
            }

            if ($this->_modelInstance){

                // copy all properties
                // this is backward compatibility
                // ToDo: make all calls to the Model directly
                $this->_work_tbl = $this->_modelInstance->getModelTable();
                $this->_link_tbl = $this->_modelInstance->getModelLinkTable();
                $this->_fields = $this->_modelInstance->getModelFields();
                $this->_links = $this->_modelInstance->getModelLinks();
                $this->_reverse_links = $this->_modelInstance->getModelReverseLinks();
                $this->_upload_dir = $this->_modelInstance->getUploadDir();
                $this->_primary = $this->_modelInstance->getPrimaryKey();
                $this->_multilang = $this->_modelInstance->getIsMultilang();
                $this->_linked_serialized = $this->_modelInstance->getLinkedSerialized();

                return true;

            }
        }

        throw new \Exception($this->_p->trans('errors.no_model_defined'));

    }

    /**
     * get connected model name
     * @return null
     */
    public function getModel(){

        return $this->_model;

    }

    /**
     * get connected model's instance
     * @return null|oModel
     */
    public function getModelInstance(){

        return $this->_modelInstance;

    }

    /**
     * determines is selected method overrided in the current controller
     * @param null $method
     * @param array|string $exclude excluded object names for searching
     * @return bool
     */
    public function isOverrided($method = null, $exclude = array()){

        if (!$method)
            return false;

        // try to find overrided method
        $overrided = false;
        $class_name = get_class($this);

        $ref = new \ReflectionClass($class_name);
        if ($ref){
            $parentMethods = $ref->getMethods(
                \ReflectionMethod::IS_PUBLIC ^ \ReflectionMethod::IS_PROTECTED
            );

            foreach ($parentMethods as $v){
                if ($v->name == $method && $class_name == $v->class && !in_array($v->class, $exclude)){
                    $overrided = true;
                    break;
                }
            }
        }

        return $overrided;

    }

    abstract public function get($conditions = array());

    abstract public function getById($ids);

}