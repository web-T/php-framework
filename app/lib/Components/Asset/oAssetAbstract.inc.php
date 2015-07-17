<?php
/**
 * Abstract asset class
 *
 * Date: 31.01.15
 * Time: 14:46
 * @version 1.0
 * @author goshi
 * @package web-T[Asset]
 * 
 * Changelog:
 *	1.0	31.01.2015/goshi 
 */

namespace webtFramework\Components\Asset;

use webtFramework\Core\oPortal;

abstract class oAssetAbstract implements iAsset{

    /**
     * assets namespace
     * @var string
     */
    protected $_namespace;


    /**
     * asset sources list
     * @var array
     */
    protected $_sources = array();

    /**
     * target asset file
     * @var
     */
    protected $_target;

    /**
     * filters list for applying to the asset
     * @var array
     */
    protected $_filters = array();

    /**
     * @var \webtFramework\Core\oPortal
     */
    protected $_p;


    public function __construct(oPortal &$p){

        $this->_p = &$p;

        $this->cleanup();
    }

    public function cleanup(){

        $this->_filters = array();
        $this->_sources = array();
        $this->_target = null;

        return $this;

    }

    /**
     * add source asset file to string
     * @param string $source
     * @return $this
     */
    public function addSource($source){

        if ($source){

            $filename = is_array($source) ? $source['filename'] : $source;

            $this->_sources[$filename] = $source;
        }

        return $this;

    }

    /**
     * add multiple source
     * @param $sources
     * @return $this
     */
    public function addSources($sources){

        if ($sources && is_array($sources)){
            foreach ($sources as $source){
                $this->addSource($source);
            }
        }

        return $this;

    }

    /**
     * method adds target asset filename
     * @param $target
     * @return $this
     */
    public function addTarget($target){

        if ($target && is_string($target)){
            $this->_target = $target;
        }

        return $this;
    }

    /**
     * add filters to the list
     * @param array|string $filters concrete filters list
     * @return $this
     */
    public function addFilters($filters){

        if ($filters){
            if (!is_array($filters)){
                $filters = array($filters);
            }
            foreach ($filters as $filter){
                $this->_filters[] = $filter;
            }
        }

        return $this;

    }

    /**
     * method builds asset with defined version
     * @param null|integer $version fileversion, which added to the filename
     * @return mixed
     */
    public function build($version = null){

        // add default filters
        if (empty($this->_filters) && $this->_namespace && isset($this->_p->getVar('assets')[$this->_namespace]['default_filters'])){
            $this->addFilters($this->_p->getVar('assets')[$this->_namespace]['default_filters']);
        }



    }

} 