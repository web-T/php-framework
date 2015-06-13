<?php

/**
 * DB abstract layer
 *
 * Date: 16.12.13
 * Time: 09:50
 * @version 1.0
 * @author goshi
 * @package web-T[core]
 *
 * Changelog:
 *    1.0    16.12.2013/goshi
 */

namespace webtFramework\Core;

use webtFramework\Interfaces\oModel;
use webtFramework\Interfaces\oModelManager;

/**
 * @package web-T[CORE]
 */
class webtDB{

    /**
     * @var null|oPortal
     */
    protected $_p = null;

    /**
     * @var array of \webtFramework\Components\Storage\Database\oDatabaseAbstract
     */
    protected $_DB = array();

    /**
     * @var oModelManager
     */
    protected $_modelManager;

    /**
     * query builders caches
     * @var array
     */
    protected $_queryBuilders = array();

    /**
     * last storage name, that was used
     * @var null
     */
    protected $_lastStorage = null;

    /**
     * special storages scheme
     * if not set - then module will use Default
     * @var array
     */
    protected $_storageScheme = array(
        'postgresql' => 'Postgresql',
        'mongodb' => 'Mongodb',
        'sphinx' => 'Sphinx',
    );

    public function __construct(oPortal &$p){
        $this->_p = $p;

        // be careful when using Proxy object - PHP 5.xx is not stable with new features!!!
        $this->init();
        return $this;

    }

    /*public function getInstance(){

        return $this->_DB;

    }*/

    /**
     * method init new connection with DB
     */
    public function init($storage = 'base'){

        if (!isset($this->_p->getVar('storages')[$storage]))
            throw new \Exception($this->_p->trans('error.db.storage_not_found'));

        // try to close old connection
        if ($this->_DB[$storage]){
            $this->close($storage);
        }

        $class = '\webtFramework\Components\Storage\Database\\oDatabase';

        if (isset($this->_storageScheme[$this->_p->getVar('storages')[$storage]['db_type']])){

            $class .= ucfirst($this->_storageScheme[$this->_p->getVar('storages')[$storage]['db_type']]);

        } else {

            $class .= 'Default';

        }

        $this->_DB[$storage] = new $class($this->_p, $this->_p->getVar('storages')[$storage]);

        $this->_DB[$storage]->init();

    }

    /**
     * return storage instance
     * @param string $storage
     * @return mixed
     * @throws \Exception
     */
    public function getStorage($storage = 'base'){

        if (!isset($this->_p->getVar('storages')[$storage]))
            throw new \Exception($this->_p->trans('error.db.storage_not_found'));

        if (!$this->_DB[$storage])
            $this->init($storage);

        if (!$this->_DB[$storage]){
            throw new \Exception($this->_p->trans('error.db.storage_not_initialized'));
        }

        return $this->_DB[$storage];

    }

    /**
     * init model's manager
     * @param array $params
     * @return oModelManager
     */
    public function getManager($params = array()){

        /*if (is_string($model)){
            $model = new oModel($this->_p);
        }*/

        if (!$this->_modelManager)
            $this->_modelManager = new oModelManager($this->_p, $params);

        if (!empty($params)){
            $this->_modelManager->AddParams($params);
        }

        return $this->_modelManager;

    }

    /**
     * @param string|oModel $storage
     * @return null|\webtFramework\Components\Storage\oQueryBuilderAbstract
     * @throws \Exception
     */
    public function getQueryBuilder($storage = null){

        if ($storage instanceof oModel)
            $inner_storage = $storage->getModelStorage();
        else
            $inner_storage = $storage;

        if (!$inner_storage)
            $inner_storage = 'base';

        if (!isset($this->_p->getVar('storages')[$inner_storage]))
            throw new \Exception($this->_p->trans('error.db.storage_not_found'));

        $driver = $this->_p->getVar('storages')[$inner_storage]['db_type'];

        if (!isset($this->_queryBuilders[$driver])){
            $class = 'webtFramework\Components\Storage\oQueryBuilder'.ucfirst($driver);
            $this->_queryBuilders[$driver] = new $class($this->_p);
        }

        return $this->_queryBuilders[$driver];

    }

    /**
     * override close method
     */
    public function close($storage = null){

        if ($this->_DB && isset($this->_DB[$storage])){
            $this->_DB[$storage]->close();
            $this->_DB[$storage] = null;
        }
    }

    public function getLastError($storage = null){

        if (!$storage){
            $storage = $this->_lastStorage;
        }

        if (!$storage || !isset($this->_DB[$storage]))
            throw new \Exception($this->_p->trans('error.db.storage_not_selected'));

        return $this->_DB[$storage]->getLastError();

    }

    /**
     * magick method to oDatabaseAbstract concrete decorator
     * @param string $name
     * @param array $args
     * @return mixed|null
     * @throws \Exception
     */
    public function __call($name, $args){

        // get count of the args
        $storage = null;
        if (count($args) <= 1)
            $storage = 'base';
        else  {
            $storage = $args[count($args) - 1];
            if ($storage instanceof oModel)
                $storage = $storage->getModelStorage();
        }

        if (!isset($this->_DB[$storage])){
            // try to init storage
            $this->init($storage);
        }

        if (!isset($this->_DB[$storage]))
            throw new \Exception($this->_p->trans('error.db.storage_not_selected').": ".$name);

        // save current storage
        $this->_lastStorage = $storage;

        return call_user_func_array(array($this->_DB[$storage], $name), $args);

    }

}

