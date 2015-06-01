<?php
/**
 * Interface for all model's types
 *
 * Date: 13.08.14
 * Time: 22:52
 * @version 1.0
 * @author goshi
 * @package web-T[Interfaces]
 * 
 * Changelog:
 *	1.0	13.08.2014/goshi
 *
 * TODO: cleanup cache for each application (maybe iterate src directory)
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;
use webtFramework\Helpers\Images;

abstract class oModelType {

    /**
     * current modeltype parameters
     * @var array
     */
    protected static $_params = array();

    /**
     * method for update all hrefs of the modeltype entities
     * @param oPortal $p
     * @param $tree
     * @param $nicks
     * @param $deep
     * @param $child
     */
    protected static function updateHref(oPortal &$p, &$tree, &$nicks, &$deep, $child){}

    /**
     * method remove cache, for more info look at the `build` method
     * @param oPortal $p
     * @param mixed $params
     * @throws \Exception
     */
    public static function removeCache(oPortal &$p, $params){

        // detect model
        $primary = null;
        if ($params['model']){

            $model = $p->Model($params['model']);
            if ($model){
                $params['tbl_name'] = $model->getModelTable();
                $primary = $model->getPrimaryKey();
            } else {
                throw new \Exception($p->trans('oModelType :: Bad model name'));
            }

        } else {
            $params['tbl_name'] = $p->getVar($params['tbl_name']) ? $p->getVar($params['tbl_name']) : $params['tbl_name'];
        }

        if (!$primary){
            if (isset($params['fields'])){
                $primary = isset($params['fields']['real_id']) ? 'real_id' : 'id';
                //$table = array_keys($params['fields']);
            } else {
                $table = describe_table($p, $p->getVar($params['tbl_name']));
                $primary = in_array('real_id', $table) ? 'real_id' : 'id';
            }
        }

        if ($p->getVar('is_debug')){
            $p->debug->add("oModelType: after describe_table");
        }

        $p->cache->removeSerial($p->getApplication().'.'.$p->getVar($params['tbl_name']).($primary == 'real_id' ? '.'.$p->getLangNick() : ''));

    }

    /**
     * yo NEED to override it in instance of class
     * @param oPortal $p
     * @param string $primary
     * @param array $fields_list vector of fields
     * @param array $params
     *
     * @return mixed
     */
    protected static function collectData(oPortal &$p, $primary, $fields_list, $params = array()){}


    /**
     * initialize collection container
     * you need to override method if you need another container
     *
     * @param mixed $data
     * @return mixed collection container
     */
    protected static function initCollection(&$data){

        return array();

    }

    /**
     * method for post processing entities in collection
     * @param oPortal $p
     * @param mixed $tree
     * @return mixed
     */
    protected static function postCollect(oPortal &$p, $tree){

        return $tree;

    }

    /**
     * method for post build cached entities in collection
     * @param oPortal $p
     * @param mixed $tree
     * @return mixed
     */
    protected static function postBuild(oPortal &$p, $tree){

        return $tree;

    }

    /**
     * preparing tree array
     * @param oPortal $p
     * @param array $params
     * @param bool $force
     * @param callable $callbackElem callback function on generating entities list, get (&$p, &$entity, &$params) arguments
     * @param callable $callbackHref callback function on generating hrefs for entities, arguments you can get from updateHref method
     *
     * @return array|bool|mixed|array|iTree
     * @throws \Exception
     *
     * $params must consists of those parameters
     * @param	$params[fields]	 - fields for selected table
     * @param	$params[tbl_name]	 - fields for selected table
     * @param	$params[page_params]	 - page params array for generate queries
     * @param	$params[img_dir]	 - page params array for generate queries
     * @param	$params[lang_nick]	 - language nick of needed language
     */
    public static function build(oPortal &$p, $params = array(), $force = false, $callbackElem = null, $callbackHref = null){

        self::$_params = &$params;

        // detect model
        if ($params['model']){

            $params['modelInstance'] = $p->Model($params['model']);
            if ($params['modelInstance']){
                $params['tbl_name'] = $params['modelInstance']->getModelTable();
            } else {
                throw new \Exception($p->trans('oModelType :: Bad model name'));
            }

        } else {
            $params['modelInstance'] = $p->db->getQueryBuilder()->createModel($params['tbl_name']);
            $params['tbl_name'] = $p->getVar($params['tbl_name']) ? $p->getVar($params['tbl_name']) : $params['tbl_name'];
        }

        // getting fields
        if (isset($params['fields'])){
            $primary = isset($params['fields']['real_id']) ? 'real_id' : 'id';
            $table = array_keys($params['fields']);
        } else {
            $table = $p->db->getQueryBuilder($params['modelInstance']->getModelStorage())->describeTable($params['modelInstance']);//describe_table($p, $params['tbl_name']);
            if (!is_array($table))
                throw new \Exception($p->trans('oModelType :: Bad table name'));
            $primary = $params['modelInstance']->getPrimaryKey();//in_array('real_id', $table) ? 'real_id' : 'id';
        }

        if (!$table || empty($table))
            return false;

        if ($p->getVar('is_debug')){
            $p->debug->add("oModelType: after describe_table");
        }

        $tree = null;

        // check for language nick in parameters
        if (isset($params['lang_nick']) && $params['lang_nick']){
            $lang = $params['lang_nick'];
        } else {
            $lang = $p->getLangNick();
        }

        if ($force || !(($tree = $p->cache->getSerial($p->getApplication().'.'.$params['tbl_name'].($primary == 'real_id' ? '.'.$lang : ''))) && !empty($tree))){

            $result = static::collectData($p, $primary, $table, $params);

            $nicks = array();
            $keys = array();

            if ($result && !empty($result)){

                $tree = static::initCollection($result);

                $picture_fields = array();

                if ($params['model']){
                    foreach ($params['modelInstance']->getModelFields() as $k => $v){
                        if (isset($v['visual']) && isset($v['visual']['type']) && $v['visual']['type'] == 'picture'){
                            $picture_fields[$k] = $v['visual']['img_dir'];
                        }
                    }

                } else {
                    $picture_fields['picture'] = $params['img_dir'];
                }

                foreach ($result as $arr){

                    // generate pictures pictures
                    foreach ($picture_fields as $k => $v){
                        if (isset($arr[$k]) && $arr[$k] != ''){
                            $arr[$k] = Images::get($p, $arr[$k], $v, $arr[$primary]);
                        }
                    }

                    if (isset($arr['descr']) && $arr['header'] == '' && $arr['descr'] != ''){
                        $arr['header'] = get_teaser($arr['descr']);
                    }

                    $val = array();

                    foreach ($table as $v){
                        $val[$v] = $arr[$v];
                    }

                    $val['type'] = 'page';

                    $tree[$arr[$primary]] = $val;

                    // call callback function
                    if (is_callable($callbackElem)){
                        $callbackElem($p, $tree[$arr[$primary]], $params);
                    }

                    $nicks[$arr['nick']][] = $arr[$primary];
                    $keys[] = $arr[$primary];

                }
            }

            unset($result);

            $links = array();
            // now getting info from linker
            if (!empty($params['links']) && !empty($keys)){

                $LParams = array(
                    'elem_id' => $keys,
                    'tbl_name' => $params['tbl_name'],
                    'fulldata' => false/*$params['no_links_fulldata'] ? false : true*/
                );

                // get other linked elements
                $links = array_merge($links, (array)$p->Module($p->getVar('linker')['service'])->AddParams($LParams)->getAll($params));
            }

            unset($keys);

            // prepare children
            if (is_object($tree))
                $tree->rewind();
            elseif (is_array($tree))
                reset($tree);
            else
                return false;

            foreach ($tree as $k => $v){

                if (isset($v['owner_id']) && $v['owner_id'] != '') {
                    $tree[$v['owner_id']]['children'][] = $v['real_id'];
                    $tree[$v['owner_id']]['type'] = 'list';
                }
                $tree[$k]['links'] = array();

                if (!empty($links)){
                    foreach ($links as $x => $y){
                        $tree[$k]['links'][$x] = $y[$v[$primary]];
                    }
                }
            }

            // updating href for tree
            $deep = 0;

            if (is_callable($callbackHref)){
                $callbackHref($p, $tree, $nicks, $deep, 0);
            } else {
                static::updateHref($p, $tree, $nicks, $deep, 0);
            }

            $tree = static::postCollect($p, $tree);

            $p->cache->saveSerial($p->getApplication().'.'.$params['tbl_name'].($primary == 'real_id' ? '.'.$lang : ''), $tree);

        }

        if ($p->getVar('is_debug')){
            $p->debug->add(get_class().": after get serialized");
        }

        $tree = static::postBuild($p, $tree);

        if ($p->getVar('is_debug')){
            $p->debug->add(get_class().": after post build");
        }


        return $tree;


    }


}
