<?php
/**
 *
 * The System V driver for SharedMemory
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
 * The methods PEAR SharedMemory uses to interact with PHP's System V extension
 * for interacting with System V shared memory
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


class SharedMemory_Systemv extends SharedMemory_Common{

    /**
     * Constructor. Init all variables.
     *
     * @param array $options
     *
     * @access public
     */
    public function __construct($options){
        $options = $this->_default($options, array
        (
            'size'    => false,
            'project' => 's',
        ));

        if ($options['size'] === false) {
            $this->_h = shm_attach($this->_ftok($options['project']));
        } else {
            if ($options['size'] < SHMMIN || $options['size'] > SHMMAX) {
                return $this->_connection = false;
            }

            $this->_h = shm_attach($this->_ftok($options['project']), $options['size']);
        }

        $this->_connection = true;
    }



    /**
     * returns value of variable in shared mem
     *
     * @param int $name name of variable
     *
     * @return mixed value of the variable
     * @access public
     */
    public function get($name){
        return shm_get_var($this->_h, $this->_s2i($name));
    }

    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return bool true on success, false on fail
     * @access public
     */
    public function set($name, $value, $ttl = 0){
        parent::set($name, $value, $ttl);
        return shm_put_var($this->_h, $this->_s2i($name), $value);
    }


    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return bool true on success, false on fail
     * @access public
     */
    public function rm($name, $ttl = false){
        parent::rm($name, $ttl);
        return shm_remove_var($this->_h, $this->_s2i($name));
    }


    /**
     * ftok emulation for Windows
     *
     * @param string $project project ID
     *
     * @access private
     */
    protected function _ftok($project){
        if (function_exists('ftok')) {
            return ftok(__FILE__, $project);
        }

        $s = stat(__FILE__);
        return sprintf("%u", (($s['ino'] & 0xffff) | (($s['dev'] & 0xff) << 16) |
            (($project & 0xff) << 24)));
    }

    /**
     * convert string to int
     *
     * @param string $name string to conversion
     *
     * @access private
     */
    protected function _s2i($name){
        return unpack('N', str_pad($name, 4, "\0", STR_PAD_LEFT));
    }

}
