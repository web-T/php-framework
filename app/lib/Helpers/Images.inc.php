<?php
/**
 * Images helper
 *
 * Date: 25.02.15
 * Time: 07:37
 * @version 1.0
 * @author goshi
 * @package web-T[Helpers]
 * 
 * Changelog:
 *	1.0	25.02.2015/goshi 
 */

namespace webtFramework\Helpers;

use webtFramework\Core\oPortal;

class Images {


    /**
     * function generate array of the images of the selected module
     * now return title field
     */
    static public function extract($db_data, $img_params = array()){

        $images = array();

        if (is_string($db_data))
            $db_data = unserialize($db_data);

        if (is_array($db_data) && !empty($db_data) && isset($db_data['ext'])){

            $name = $db_data['name'];
            foreach ($db_data['ext'] as $k => $v){
                $images[$k] = $name.($db_data['info'][$k] ? $db_data['info'][$k] : ($img_params[$k]['secondary'] ? '_s' : '_p')).$k.$v;
            }
            if (isset($db_data['title']) && !empty($db_data['title'])){
                $images['title'] = unqstr($db_data['title']);
            }
            if (isset($db_data['width']) && !empty($db_data['width'])){
                $images['width'] = unqstr($db_data['width']);
            }
            if (isset($db_data['height']) && !empty($db_data['height'])){
                $images['height'] = unqstr($db_data['height']);
            }
            if (isset($db_data['size']) && !empty($db_data['size'])){
                $images['size'] = unqstr($db_data['size']);
            }

            /*if (isset($db_data['original']) && $db_data['original'] != '')
                $images['original'] = $db_data['original'];
            */
        }
        return $images;

    }


    /**
     * find picture with backward compatibility
     */
    static public function find(oPortal $p, $picture, $path, $id){

        if (file_exists($p->getDocDir().$path.$picture)){
            // do nothing
        } elseif (file_exists($p->getDocDir().$path.calc_item_path($id).$picture))
            $path = $path.calc_item_path($id);
        else
            $path = '';
        return $path;

    }


    /**
     * function get pictures from structure
     *
     * @param oPortal $p
     * @param string $curr_value serialized data of the images
     * @param string $path path to the images
     * @param int $id ID
     * @param bool $no_domain flag for no add domain to the picture link
     * @return array
     */
    static public function get(oPortal $p, $curr_value, $path, $id, $no_domain = false){

        $img = self::extract($curr_value);

        if (!empty($img)){
            $path = $path.calc_item_path($id);
            foreach ($img as $k => $v){
                if (!is_numeric($k))
                    continue;
                $img[$k] = $no_domain ? $path.$v : $p->getAssetDomain($path.$v, $id);
            }
        }
        if ($img && is_array($img['title']))
            $img['title'] = $img['title'][$p->getLangId()];

        return $img;
    }

}