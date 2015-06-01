<?php

/**
* shared memory module
* @version 0.3
* @author goshi
* @package web-T[CORE]
*
* Changelog:
*	0.3	08.06.09/goshi	remove all list property - because it is not need - it is cleaning, when script stop working
*	0.2	02.06.09/goshi	add all_list array for anm of all variables, which we have in cache
*	0.1	01.06.09/goshi	...
*/

namespace webtFramework\Core;

/**
* @package web-T[CORE]
*/
class webtSharedMemory{
    
    /**
     * Create a new shared mem object
     *
     * @param oPortal $p
     * @param bool|string $type the shared mem type (or false on autodetect)
     * @param array $options an associative array of option names and values
     * @return bool|object new System_Shared object
     */
    public static function &factory(oPortal &$p, $type = false, $options = array()){

        if ($type === false){
            $type = webtSharedMemory::getAvailableTypes(true);

            if (!$type || (is_array($type) && empty($type)))
            	return false;
        } else {
            $type = ucfirst(strtolower($type));
        }
        
        $class = 'webtFramework\Components\SharedMemory\SharedMemory_' . $type;

        $ref = new $class(array_merge($options, array('type' => $type)));
        return $ref;
    }

    /**
     * Get available types or first one
     *
     * @param bool $only_first false if need all types and true if only first one
     * @return mixed list of available types (array) or first one (string)
     */
	public static function getAvailableTypes($only_first = false){

        $detect = array(
            '\eaccelerator_get' => 'Eaccelerator',   // Eaccelerator (Turck MMcache fork)
            '\mmcache'      => 'Mmcache',        // Turck MMCache
            '\Memcache'     => 'Memcache',      // Memched
            //'shmop_open'   => 'Shmop',          // Shmop
            '\apc_fetch'    => 'Apc',            // APC
            /*'shm_get_var'  => 'Systemv',        // System V
            'apache_note'  => 'Apachenote',     // Apache note - dont use it! it is only for one request!!!
            'sqlite_open'  => 'Sqlite',         // SQLite*/
            'file'         => 'File',           // Plain text
            'fsockopen'    => 'Sharedance',     // Sharedance
        );

        $types = array();

        foreach ($detect as $func=>$val) {
            if (function_exists($func) || class_exists($func)) {
                if ($only_first) {
                    return $val;
                }

                $types[] = $val;
            }
    	}

        return $types;
     }

}
