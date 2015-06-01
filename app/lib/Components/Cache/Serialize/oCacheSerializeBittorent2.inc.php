<?php
/**
 * Bittorent serialization class
 *
 * Date: 15.02.15
 * Time: 14:24
 * @version 1.0
 * @author goshi
 * @package web-T[Cache]
 * 
 * Changelog:
 *	1.0	15.02.2015/goshi 
 */

namespace webtFramework\Components\Cache\Serialize;

class oCacheSerializeBittorent2 extends oCacheSerializeAbstract{

    /**
     * serialize encoder object
     * @var null|\StdClass
     */
    protected $_encode = null;

    /**
     * serialize decoder object
     * @var null
     */
    protected $_decode = null;


    public function serialize($data){

        if (!$this->_encode){

            include($this->_p->getVar('BASE_APP_DIR').$this->_p->getVar('vendor_dir').'Bittorrent2/Encode.php');
            $this->_encode = new \File_Bittorrent2_Encode;

        }

        return $this->_encode->encode($data);
    }

    public function unserialize($data){

        if (!$this->_decode){

            include($this->_p->getVar('BASE_APP_DIR').$this->_p->getVar('vendor_dir').'Bittorrent2/Decode.php');
            $this->_decode = new \File_Bittorrent2_Decode;

        }

        if (!is_array($data))
            $data = $this->_decode->decode($data);

        return $data;
    }

}