<?php
/**
* Interface for plugins for web-T::CMS
* @version 0.51
* @author goshi
* @package web-T[share]
*
* Changelog:
*	0.51	30.07.10/goshi	add property caller
*	0.4	27.07.10/goshi	add $_params property - for additional data
*	0.3	28.11.09/goshi	now chanhe property names, change file position
*	0.2	02.04.09/goshi	added skin_dir and skin_img_dir into abstract class
*
*/

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;

/**
* @package web-T[share]
*/
interface IPlugin{

    /**
     * method for applying plugin
     * @param $content
     * @return mixed
     */
    public function apply($content);
	
    /**
     * method for dis-applying plugin if no rules
     * @param $content
     * @return mixed
     */
    public function revoke($content);

    /**
     * method must return main prepared content for invoking _apply
     * @param null $type
     * @return mixed
     */
    public function prepare($type = null);

}

/**
* abstract clas for implements plugin interface
* @package web-T[share]
*/
abstract class PPlugin extends oBase implements IPlugin{

    /**
     * templates directory
     * @var string
     */
    protected $_SKIN_DIR = '';

    /**
     * images directory
     * @var string
     */
    protected $_SKIN_IMG_DIR = '';

    /**
     * parameters for plugin
     * @var array|null
     */
    protected $_params = null;

    /**
     * caller object
     * @var null|\stdClass
     */
    protected $_caller = null;
	
	// constructor		
	public function __construct(oPortal &$p, $params = array(), &$caller){

        parent::__construct($p, $params);

		$this->_SKIN_DIR = $p->getVar('DOC_DIR').$p->getVar('skin_dir')."/".$p->getVar('plugins_dir')."/";
		$this->_SKIN_IMG_DIR = $p->getVar('skin_dir')."/".$p->getVar('plugins_dir')."/".$p->getVar('img_dir')."/";
		if (is_object($caller))
			$this->_caller = $caller;
		if (!empty($params))
			$this->_params = $params;
	}
	
	public function apply($content){}
	
	public function revoke($content){}
	
	public function prepare($type = null){}

    /**
     * destructor
     */
    public function __destruct(){
		unset($this->_p);
        unset($this->_p);
        unset($this->_params);
        unset($this->_caller);
		unset($this->_p->db);
	}
}
