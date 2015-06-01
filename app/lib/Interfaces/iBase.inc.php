<?php

/**
* web-T::CMS Base class
* @version 0.2
* @author goshi
* @package web-T[share]
*		
*
* Changelog:
 *  0.2 28.07.14/goshi  refactoring, move to another directory
*	0.1	30.11.11/goshi	...
*/

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;

interface iBase {
    public function AddParams($params = array());
    public function getParam($param);
}


/**
* Class oBase definition
* @package web-T[share]
*/
class oBase implements iBase{

	/**
	 * @var oPortal
	 */
	protected $_p;

    /**
     * base constructor
     * @param oPortal $p
     * @param array $params
     */
    public function __construct(oPortal &$p, $params = array()){

        $this->_p = $p;

        if (!(isset($params['no_addparams_on_init']) && $params['no_addparams_on_init']))
		    $this->AddParams($params);
		
	}

    /**
     * methods adds property values to the class
     * property must exists with '_'
     * @param array $params
     * @return $this|oBase|oModule|\Frontend\Interfaces\oClip|\webtBackend\Interfaces\oAdminController|\webtFramework\Modules\oLinker|$this|\webtCMS\Modules\oMailer|\webtCMS\Modules\oUploader|\webtCMS\Modules\oImages|\webtCMS\Modules\oVideo|\webtCMS\Modules\oAudio|\webtCMS\Modules\oSeoOptimizer|\webtCMS\Modules\oCharts|\webtCMS\Modules\oGeo|\webtFramework\Modules\oSearch|\webtCMS\Modules\oPager|\webtCMS\Modules\oModerator|\webtCMS\Modules\oPayments|\webtCMS\Modules\oComments|\webtCMS\Modules\oMaps|\webtShop\Modules\oShop|\webtCMS\Modules\oSocial|\webtCMS\Modules\oTags|\webtFramework\Modules\oWeb|\webtCMS\Modules\oXML|\webtCMS\Modules\oStats|\webtCMS\Modules\oPortfolio|\webtCMS\Modules\oTextLinker|\webtCMS\Modules\oRules|\webtBackend\Modules\oAdminStats
     */
    public function AddParams($params = array()){
		
		if ($params && is_array($params)){
			foreach ($params as $k => $v){
				if (property_exists($this, '_'.$k)){
				
					$prop = '_'.$k;
					$this->$prop = $v;

				}		
			}
		}

		return $this;
	}

    /**
     * get property value from the class
     * @param $param
     * @return null
     */
    public function getParam($param){

        if (property_exists($this, '_'.$param)){
            $param = '_'.$param;
            return $this->$param;
        } else
            return null;
    }

    /**
     * extend internal protected parameters
     * @param array $params
     * @return $this
     */
    public function extend($params = array()){

        if (is_array($params) && !empty($params))
            foreach ($params as $k => $v){
                $k = '_'.$k;
                if (isset($this->$k)){
                    $this->$k = is_array($v) ? array_merge_recursive_distinct($this->$k, $v) : $v;
                }
            }

        return $this;
    }



    /**
     * extracts classname
     * @param oPortal $p
     * @return mixed
     */
    public function extractClassname(oPortal &$p = null){

        if (!$this->_p)
            $this->_p = $p;

        return extractClassname($this->_p, $this);

    }


}
