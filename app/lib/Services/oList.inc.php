<?php

/**
* web-T::CMS List generate controller
* @version 1.0
* @author goshi
* @package web-T[Services]
*		
*
* Changelog:
 *  1.0 14.08.14/goshi  full refactor
 *  0.5 12.07.13/goshi  add removeCache
 *  0.4 10.07.13/goshi  fix default order for list
 *  0.3 24.04.12/goshi  add order parameter
 *	0.2	21.04.12/goshi	try to integrate splFixedArray (but found php 5.4.0 bug)
*	0.1	29.12.11/goshi	...
*/

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oModelType;

/**
* Class oList definition
* @package web-T[share]
*/
class oList extends oModelType{

	protected static function updateHref(oPortal &$p, &$tree, &$nicks, &$deep, $child = 0){
					
		/*foreach ($tree as $v){
		
			if ($v['href'] == ''){
			
				// getting href to top news
				$v['page_id'] = null;
				$cat_nick = $v['nick'];
				if (count($nicks[$cat_nick]) == 1){
				
					$v['page_id'] = $p->page_nicks[$cat_nick][0];
					//dump($p->page_nicks[$cat_nick][0], false);
				
				} elseif (count($nicks[$cat_nick]) > 1 && isset($p->page_nicks) && is_array($p->page_nicks)){
					
					foreach ($nicks[$cat_nick] as $z){
						if (!$p->page_nicks && !empty($p->page_nicks[$cat_nick])){
							foreach ($p->page_nicks[$cat_nick] as $x){
								if ($v == $z && isset($tree[$z]['owner_id']) && $tree[$tree[$z]['owner_id']]['nick'] == $p->ap[$p->ap[$x]['owner_id']]['nick']){
									$v['page_id'] = $x;
									//dump($x ." ---- " . $cat_nick, false);
									//dump($p->page_nicks[$cat_nick], false);
									break 2;
								}
							}
						}
					}
				}

				$query = array(self::$_params['page_params']['nick'] => '', 'sub' => $v['nick']);
			
				$v['href'] = $p->query->build($query, false, null, $v['page_id']);
				//dump($v['href'], false);
				$v['query'] = $p->query->parse($v['href'], true);
				$v['rss'] = $p->query->build(array($v['nick'] => "", 'rss' => ''), false, null, $v['page_id']);
			}
			
		} */
	
	}

    protected static function collectData(oPortal &$p, $primary, $fields_list, $params = array()){

        if (!(isset($params['order']) && $params['order'])){
            $params['order'] = array();
            if (in_array('weight', $fields_list))
                $params['order']['weight'] = 'desc';

            if (in_array('title', $fields_list))
                $params['order']['title'] = 'asc';
            else
                $params['order'][$primary] = 'desc';
        }

        $lang_id = isset($params['lang_nick']) && $params['lang_nick'] && isset($p->getLangTbl()[$params['lang_nick']]) ? $p->getLangTbl()[$params['lang_nick']] : $p->getLangId();

        $conditions = array(
            'where' => array(),
            'order' => $params['order']
        );

        if (isset($params['modelInstance']->getModelFields()['is_on'])){
            $conditions['where']['is_on'] = array('op' => '>', 'value' => 0);
        }
        if (!$params['all_langs'] && isset($params['modelInstance']->getModelFields()['lang_id'])){
            $conditions['where']['lang_id'] = $lang_id;
        }
        if (isset($params['modelInstance']->getModelFields()['title'])){
            $conditions['where']['title'] = array('op' => '<>', 'value' => '');
        }

        $sql = $p->db->getQueryBuilder($params['modelInstance']->getModelStorage())->compile($params['modelInstance'], $conditions);

        return $p->db->select($sql, $params['modelInstance']->getModelStorage());

    }

    protected static function initCollection(&$result){

        // optimized array
        // turn off SplFixedArray because php 5.4.0 dont support it
        if (false /*class_exists('SplFixedArray') && is_array($result) && count($result) > 0*/){

            $tree = new \SplFixedArray(max(array_keys($result))+1);

        } else {
            $tree = array();

        }
        $tree[0] = array();
        $tree[0]['children'] = array();

        return $tree;
    }

    protected static function postCollect(oPortal &$p, $tree){

        //return \SplFixedArray::fromArray($tree);
        return $tree;

    }

	
}
