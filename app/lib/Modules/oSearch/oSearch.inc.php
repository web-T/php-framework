<?php

/** 
* Search module for web-T::CMS
* @version 0.90
* @author goshi
* @package web-T[share]
*
*/

namespace webtFramework\Modules;

use webtFramework\Interfaces\oModule;
use webtFramework\Core\oPortal;
use webtFramework\Modules\oSearch\Components\oSearchCommon;
use webtFramework\Modules\oSearch\Components\oSearchDecorator;


/** 
* declare base extended class for interfacing with outer world 
* @package web-T[share]
*/
class oSearch extends oModule{

	/* @var object search driver (getting from base variables)
	*	can be 'mysql_standart', 'sphinx'
	*/
	private $_oSearchDriver;
	
	protected static $_drivers = array('mysql_standart', 'sphinx');

	// constructor
	public function __construct(oPortal &$p, $params = array()){
	
		parent::__construct($p, $params);
		$this->_work_tbl = $p->Model($this->_p->getVar('search')['indexing_model'])->getModelTable();
		
		$oSearchCommon = new oSearchCommon($p, array_merge(
            array(
                'ROOT_DIR' => $this->_ROOT_DIR,
                'SKIN_DIR' => $this->_SKIN_DIR,
                'CSS_DIR' => $this->_CSS_DIR,
                'JS_DIR' => $this->_JS_DIR
            ),
            (array)$params));
				
		// prepare driver for fulltext search
        if (isset($params['driver']) && self::$_drivers[$params['driver']] != 'mysql_standart' && file_exists($this->_ROOT_DIR.'/Drivers/oSearch_'.escapeshellcmd($params['driver']).'.driver.php')){

            require_once($this->_ROOT_DIR.'/Drivers/oSearch_'.escapeshellcmd($params['driver']).'.driver.php');
            $decorator = '\webtFramework\Modules\Drivers\oSearchDecorator_'.$params['driver'];
            $this->_oSearchDriver = new $decorator($p, $oSearchCommon);

        } elseif ($p->getVar('search')['indexing_driver'] && self::$_drivers[$p->getVar('search')['indexing_driver']] != 'mysql_standart' && file_exists($this->_ROOT_DIR.'/Drivers/oSearch_'.escapeshellcmd($p->getVar('search')['indexing_driver']).'.driver.php')){

            require_once($this->_ROOT_DIR.'/Drivers/oSearch_'.escapeshellcmd($p->getVar('search')['indexing_driver']).'.driver.php');
            $decorator = '\webtFramework\Modules\Drivers\oSearchDecorator_'.$p->getVar('search')['indexing_driver'];
            $this->_oSearchDriver = new $decorator($p, $oSearchCommon);

		} else {

			$this->_oSearchDriver = new oSearchDecorator($p, $oSearchCommon);

		}
				
	}

    /**
     * get search drivers list
     * @return array
     */
    static public function getDrivers(){
	
		$drivers = array();
		foreach (self::$_drivers as $v){
			$drivers[$v] = $v;
		}
		return $drivers;
	
	}

    /**
     * index storage
     * @param array $params
     */
    public function index($params = array()){

		return $this->_oSearchDriver->index($params);

	}
	

	/* override saving method */
	public function saveData($params){
		
		return $this->_oSearchDriver->save($params);
		
	}

	/* override update method method */
	public function updateData($params){
		
		return $this->_oSearchDriver->update($params);
		
	}

    /**
     * get items count
     * @param bool|array $params
     * @return array|bool|null|void
     */
    public function getCount($params = false){

		return $this->_oSearchDriver->count($params);
	
	}

    /**
     * find items by conditions
     * @param array $params
     * @return array|bool|null|void
     */
    public function getData($params = array()){

		return $this->_oSearchDriver->find($params);
	
	}
	
	public function removeData($params = array()){
	
		return $this->_oSearchDriver->remove($params);
	
	}

    /**
     * build text snippets
     * @param $docs
     * @param $text
     * @return bool
     */
    public function buildSnippets(&$docs, $text){

		if (method_exists($this->_oSearchDriver, 'buildSnippets'))
			return $this->_oSearchDriver->buildSnippets($docs, $text);
		else
			return false;
		
	}

    /**
     * get module config
     * @return array|null
     */
    public function getDriverConfig(){

        if (method_exists($this->_oSearchDriver, 'getConfig'))
            return $this->_oSearchDriver->getConfig();
        else
            return array();

    }


    /**
     * @param $elem_ids
     * @param bool $params
     * @return array|bool|null
     */
    public function getElems($elem_ids, $params = false){
						
		return null;
	
	}


}
