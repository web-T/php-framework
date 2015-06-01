<?php
/**
 * Debug Helper
 *
 * Date: 21.02.15
 * Time: 21:17
 * @version 1.0
 * @author goshi
 * @package web-T[Helpers]
 * 
 * Changelog:
 *	1.0	21.02.2015/goshi 
 */

namespace webtFramework\Helpers;


class Debug {


    static public function startTimer(){

        global $starttime, $lastendtime;
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $lastendtime = $starttime = $mtime;

    }

    static public function endTimer(){

        global $starttime, $lastendtime;
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $ontime = round (($mtime - $lastendtime), 6);
        $lastendtime = $endtime = $mtime;
        $totaltime = round (($endtime - $starttime), 6);
        return $totaltime." (".$ontime.")";

    }


    static public function add($string, &$DEBUG){

        if (!isset($INFO['is_debug']) || (isset($DEBUG['is_debug']) && $DEBUG['is_debug'] == 0)) return false;
        $DEBUG['_debug']['common']['data'][] = $string;

        $DEBUG['_debug']['time']['data'][] = self::endTimer();
        // adding raw data from memory
        $DEBUG['_debug']['memory']['rawdata'][] = memory_get_usage();
        $DEBUG['_debug']['memory']['data'][] = memory_get_usage();
        $cnt = count($DEBUG['_debug']['common']['data']) - 1;
        $DEBUG['_debug']['common']['time'][$cnt] = $DEBUG['_debug']['common']['memory'][$cnt] = $cnt;

    }


} 