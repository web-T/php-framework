<?php
/**
 *
 * The Plain File driver for SharedMemory
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   System
 * @package    System_Sharedmemory
 * @author     Evgeny Stepanischev <bolk@lixil.ru>
 * @copyright  2005 Evgeny Stepanischev
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/System_SharedMemory
 */

namespace webtFramework\Components\SharedMemory;


/**
 *
 * The methods PEAR SharedMemory uses to interact with plain file
 * for interacting with shared memory via plain files
 *
 * These methods overload the ones declared webtSharedMemory_Common
 *
 * @category   System
 * @package    System_Sharedmemory
 * @package    System_Sharedmemory
 * @author     Evgeny Stepanischev <bolk@lixil.ru>
 * @copyright  2005 Evgeny Stepanischev
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/System_SharedMemory
 */


class SharedMemory_File extends SharedMemory_Common{


    /**
     * Constructor. Init all variables.
     *
     * @param array $options
     *
     * @access public
     */
    public function __construct($options){
        $this->_options = $this->_default($options, array
        (
            'tmp'  => '/tmp',
        ));

        $this->_connected = is_writeable($this->_options['tmp']) && is_dir($this->_options['tmp']);
    }

    /**
     * returns value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
    public function get($name){

        $name = $this->_options['tmp'].'/smf_'.md5($name);

        if (!file_exists($name)) {
            return array();
        }

        $fp = fopen($name, 'rb');
        if (is_resource($fp)) {
            flock ($fp, LOCK_SH);

            $str = fread($fp, filesize($name));
            fclose($fp);
            return $str == '' ? array() : unserialize($str);
        }

        return false;
    }


    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
    public function set($name, $value, $ttl = 0){

        parent::set($name, $value, $ttl);
        $fp = fopen($this->_options['tmp'].'/smf_'.md5($name), 'ab');
        if (is_resource($fp)) {
            flock ($fp, LOCK_EX);
            ftruncate($fp, 0);
            fseek($fp, 0);

            fwrite($fp, serialize($value));
            fclose($fp);
            clearstatcache();
            return true;
        }

        return false;
    }


    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
    public function rm($name, $ttl = false){

        parent::rm($name, $ttl);
        $name = $this->_options['tmp'].'/smf_'.md5($name);

        if (file_exists($name)) {
            unlink($name);
        }
    }

}
