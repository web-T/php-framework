<?php
/**
 * Core component for server tools
 *
 * Date: 22.11.14
 * Time: 18:41
 * @version 1.0
 * @author goshi
 * @package web-T[CORE]
 * 
 * Changelog:
 *	1.0	22.11.2014/goshi 
 */

namespace webtFramework\Core;


/**
 * @package web-T[CORE]
 */
class webtServer{

    /**
     * @var null|oPortal
     */
    protected $_p = null;

    public function __construct(oPortal &$p){
        $this->_p = $p;
    }


    /**
     * get server load average
     * @return array with 3 items - for last 15 minutes, 5 minutes, and current value
     */
    public function getLoadAverage(){

        if (function_exists('sys_getloadavg'))
            $uptime = sys_getloadavg();
        else
            $uptime = null;

        return $uptime;

    }

    /**
     * checking overload status of the server
     * @return bool
     */
    public function checkOverload(){

        if ((int)$this->_p->getVar('server')['overload_value'] > 0 && ($uptime = sys_getloadavg())){

            return round((int)$uptime[0]) > (int)$this->_p->getVar('server')['overload_value'];
        }

        return false;

    }

    /**
     * detect OS type
     * @return string possible values 'win' or 'nix'
     */
    public function getOsType(){

        if (function_exists('php_uname')){
            if (strtoupper(substr(php_uname(), 0, 3)) === 'WIN')
                return 'win';
            else
                return 'nix';
        } elseif (isset($_SERVER['WINDIR'])) {
            return 'win';
        } else {
            return 'nix';
        }

    }

    /**
     * detect memory usage
     * @return int|mixed
     */
    public function memoryGetUsage(){

        if (function_exists('memory_get_usage')){
            return memory_get_usage();
        }

        if (!function_exists('exec')){
            return 0;
        }

        //If its Windows
        //Tested on Win XP Pro SP2. Should work on Win 2003 Server too
        //Doesn't work for 2000
        //If you need it to work for 2000 look at http://us2.php.net/manual/en/function.memory-get-usage.php#54642
        if ($this->getOsType() == 'win'){

            $output = array();
            exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );

            return preg_replace( '/[\D]/', '', $output[5] ) * 1024;

        } else {

            //We now assume the OS is UNIX
            //Tested on Mac OS X 10.4.6 and Linux Red Hat Enterprise 4
            //This should work on most UNIX systems
            $pid = getmypid();
            exec("ps -eo%mem,rss,pid | grep $pid", $output);
            $output = explode("  ", $output[0]);
            //rss is given in 1024 byte units
            return $output[1] * 1024;

        }
    }

    /**
     * format upload max size
     * @param string $type
     * @param int $precission
     * @return float
     */
    public function getUploadMaxSize($type = 'M', $precission = 0){

        $types = array(
            'G' => 1073741824,
            'M' => 1048576,
            'K' => 1024);

        $value = !isset($types[$type]) ? 1 : $types[$type];

        if (get_real_size(ini_get('post_max_size')) > get_real_size(ini_get('upload_max_filesize'))){

            return round(get_real_size(ini_get('upload_max_filesize'))/$value, $precission);
        } else {

            return round(get_real_size(ini_get('post_max_size'))/$value, $precission);
        }

    }


    /**
     * get memory info of the server
     * @return array
     */
    public function getMemoryInfo(){

        $memory = array('free' => '0', 'total' => '0', 'used' => 0);
        if (file_exists('/proc/meminfo') && $data = shell_exec('cat /proc/meminfo')){
            // on debian/ubuntu
            $data = explode("\n", $data);
            if (is_array($data)){
                foreach ($data as $v){

                    if (stripos($v, 'MemTotal') !== false){
                        $memory['total'] = preg_replace('/.*?([0-9]+)\s+([a-z]+).*/is', '$1 $2', $v);
                    } elseif (stripos($v, 'MemFree') !== false) {
                        $memory['free'] = preg_replace('/.*?([0-9]+)\s+([a-z]+).*/is', '$1 $2', $v);
                    }
                }
            }
        } elseif ((file_exists('/sbin/sysctl') && ($data = shell_exec('/sbin/sysctl -a'))) || ($data = shell_exec('sysctl -a'))){
            // on BSD and MacOs X
            $data = explode("\n", $data);
            if (is_array($data)){
                foreach ($data as $v){

                    if (stripos($v, 'hw.memsize') !== false){
                        $memory['total'] = preg_replace('/.*?([0-9]+).*/is', '$1', $v);
                    } elseif (stripos($v, 'hw.usermem') !== false || stripos($v, 'hw.physmem') !== false) {
                        $memory['used'] += preg_replace('/.*?([0-9]+).*/is', '$1', $v);
                    }
                }
                if ($memory['used']){
                    $memory['free'] = $memory['total'] - $memory['used'];
                }
            }
        }
        // prepare memory color
        $memory['free'] = get_real_size($memory['free']);
        $memory['total'] = get_real_size($memory['total']);
        if ($memory['total']){
            //$memory['free'] = $memory['total'] - $memory['free'];
            $diff = ($memory['used']/$memory['total'])*100;
        } else {
            $diff = 0;
        }

        return array('value' => $memory, 'free' => $diff);

    }

    /**
     * get storage info
     * @return array
     */
    public function getStorageInfo(){

        $hdd = array();
        if ($this->getOsType() == 'win')
            $curr_disk = substr(__FILE__, 0, 2);
        else
            $curr_disk = '/';

        $hdd['total'] = function_exists('disk_total_space') ? disk_total_space($curr_disk) : 0;
        $hdd['free'] =  function_exists('disk_free_space') ? disk_free_space($curr_disk) : 0;

        if ($hdd['total']){
            $diff = ($hdd['free']/$hdd['total'])*100;
        } else {
            $diff = 0;
        }

        return array('value' => $hdd, 'free' => $diff);

    }

}

