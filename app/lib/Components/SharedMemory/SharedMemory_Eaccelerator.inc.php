<?php
/**
 *
 * The eAccelerator driver for SharedMemory
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
 * The methods PEAR SharedMemory uses to interact with PHP's eAccelerator extension
 * for interacting with APC shared memory
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

class SharedMemory_Eaccelerator extends SharedMemory_Common{

    /**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     *
     * @return mixed value of the variable
     * @access public
     */
    public function get($name){
        return \eaccelerator_get($name);
    }


    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     * @param int $ttl (optional) time to life of the variable
     *
     * @return bool true on success
     * @access public
     */
    public function set($name, $value, $ttl = 0){

        parent::set($name, $value, $ttl);
        \eaccelerator_lock($name);
        return \eaccelerator_put($name, $value, $ttl);
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
        return eaccelerator_rm($name);
    }

}
