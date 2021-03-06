<?php
/**
 *
 * The Shmop driver for SharedMemory
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
 * The methods PEAR SharedMemory uses to interact with PHP's Shmop extension
 * for interacting with Shmop shared memory
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


class SharedMemory_Shmop extends SharedMemory_Common{


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
            'size' => 1048576,
            'tmp'  => '/tmp',
            'project' => 's'
        ));
        $this->_h = $this->_ftok($this->_options['project']);
        $this->_connected = true;
    }


    /**
     * returns value of variable in shared mem
     *
     * @param mixed $name name of variable or false if all variables needs
     *
     * @return mixed PEAR_error or value of the variable
     * @access public
     */
    public function get($name){
        $id = shmop_open($this->_h, 'c', 0600, $this->_options['size']);

        if ($id !== false) {
            $ret = unserialize(shmop_read($id, 0, shmop_size($id)));
            shmop_close($id);

            if (!isset($name)) {
                return $ret;
            }
            return isset($ret[$name]) ? $ret[$name] : null;
        }

        return false;
    }


    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return bool true on success
     * @access public
     */
    public function set($name, $value, $ttl = 0){
        parent::set($name, $value, $ttl);
        $lh = $this->_lock();
        $val = $this->get($name);
        if (!is_array($val)) {
            $val = array();
        }

        $val[$name] = $value;
        $val = serialize($val);
        return $this->_write($val, $lh);
    }

    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return bool true on success
     * @access public
     */
    public function rm($name, $ttl = false){
        parent::rm($name, $ttl);
        $lh = $this->_lock();

        $val = $this->get($name);
        if (!is_array($val)) {
            $val = array();
        }
        unset($val[$name]);
        $val = serialize($val);

        return $this->_write($val, $lh);
    }


    /**
     * ftok emulation for Windows
     *
     * @param string $project project ID
     *
     * @access private
     */
    protected function _ftok($project)
    {
        if (function_exists('ftok')) {
            return ftok(__FILE__, $project);
        }

        $s = stat(__FILE__);
        return sprintf("%u", (($s['ino'] & 0xffff) | (($s['dev'] & 0xff) << 16) |
            (($project & 0xff) << 24)));
    }


    /**
     * write to the shared memory
     *
     * @param string $val values of all variables
     * @param resource $lh lock handler
     *
     * @return mixed PEAR_error or true on success
     * @access private
     */
    protected function _write(&$val, &$lh){
        $id = shmop_open($this->_h, 'c', 0600, $this->_options['size']);
        if ($id) {
            $ret = shmop_write($id, $val, 0) == strlen($val);
            shmop_close($id);
            $this->_unlock($lh);
            return $ret;
        }

        $this->_unlock($lh);
        return false;
    }


    /**
     * access locking function
     *
     * @return resource lock handler
     * @access private
     */
    protected function &_lock(){
        if (function_exists('sem_get')) {
            $fp = PHP_VERSION < 4.3 ? sem_get($this->_h, 1, 0600) : sem_get($this->_h, 1, 0600, 1);
            sem_acquire ($fp);
        } else {
            $fp = fopen($this->_options['tmp'].'/sm_'.md5($this->_h), 'w');
            flock($fp, LOCK_EX);
        }

        return $fp;
    }


    /**
     * access unlocking function
     *
     * @param resource $fp lock handler
     *
     * @access private
     */
    protected function _unlock(&$fp){
        if (function_exists('sem_get')) {
            sem_release($fp);
        } else {
            fclose($fp);
        }
    }

}
