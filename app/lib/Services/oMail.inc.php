<?php
/**
 * web-T::CMS mail sender classes
 *
 * Date: 18.12.12
 * Time: 08:40
 *  @author Ash, goshi
 *	@package web-T[share]
 *	@version 2.0
 *
 *	Changelog:
 *      2.0 12.01.13/goshi  full refactor
 *		1.1	24.05.10/goshi	fix bugs with headers
 *		1.0	29.11.08/ash	...
 *
 */

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;

/**
*	@package web-T[share]
*/
class oMail extends oBase{


    /**
     * concrete decorators
     * @var null|array
     */
    protected $__instance = array();

    /**
     * current driver
     * @var null
     */
    protected $_driver = null;

    public function __construct(oPortal &$p, $params = array()){

        parent::__construct($p, $params);

    }

    protected function _initObject($driver = null){

        if (!$driver){
            $driver = $this->_driver ? $this->_driver : $this->_p->getVar('mail')['driver'];
        }

        if ($driver &&
            class_exists('\webtFramework\Components\Mail\oMail'.ucfirst(strtolower($driver)))){

            $this->_driver = strtolower($driver);
            $class = '\webtFramework\Components\Mail\oMail'.ucfirst(strtolower($driver));

            $this->__instance[$this->_driver] = new $class($this->_p);

            // setup settings
            $this->__instance[$this->_driver]->
                setTransport($this->_p->getVar('mail')['transport'])->
                setTransportHost($this->_p->getVar('mail')['transport_host'])->
                setTransportPort($this->_p->getVar('mail')['transport_port'])->
                setTransportSecure($this->_p->getVar('mail')['transport_secure'])->
                setTransportCredentials($this->_p->getVar('mail')['transport_login'], $this->_p->getVar('mail')['transport_password'])->
                setIsEmbedImages($this->_p->getVar('mail')['embed_images'])->
                setIsTrackLinks($this->_p->getVar('mail')['track_links'])->
                setIsUseMessageID($this->_p->getVar('mail')['use_message_id'])->
                setMailType($this->_p->getVar('mail')['type'])->
                setMailEncoding($this->_p->getVar('mail')['encoding'])
            ;

        } else {

            throw new \Exception('error.mail.cannot_detect_driver');

        }

    }

    /**
     * magic method
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {

        if (!$this->_driver || !$this->__instance[$this->_driver]){

            $this->_initObject();

        }

        return call_user_func_array(array($this->__instance[$this->_driver], $name), $arguments);
    }




}
