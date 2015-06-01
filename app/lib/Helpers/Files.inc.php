<?php
/**
 * Files helper
 *
 * Date: 25.02.15
 * Time: 07:33
 * @version 1.0
 * @author goshi
 * @package web-T[Helpers]
 * 
 * Changelog:
 *	1.0	25.02.2015/goshi 
 */

namespace webtFramework\Helpers;


class Files {

    /**
     * method normalize _FILES array
     *
     * @param array $files standart $_FILES array
     * @return array normalized files array
     */
    static public function normalizeFilesArray($files){

        $new_files = array();
        if (!empty($files) && isset($files['name'])){
            foreach($files as $k => $l) {
                foreach($l as $i => $v) {
                    if(!array_key_exists($i,$new_files)) $new_files[$i] = array();
                    if (is_array($v))
                        foreach ($v as $z => $x){
                            $new_files[$i][$z][$k] = $x;
                        }
                    else
                        $new_files[$i][$k] = $v;
                }
            }

        } else
            $new_files = $files;
        return $new_files;

    }


} 