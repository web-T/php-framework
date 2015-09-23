<?php

/**
* web-T::CMS Tree generate controller
* @version 1.0
* @author goshi
* @package web-T[share]
*		
*
* Changelog:
 *  1.0 14.08.14/goshi  full refactor
 *  0.4 06.08.14/goshi  use iTree class for trees
 *  0.3 12.07.13/goshi  add removeCache
*	0.2	15.09.11/goshi	added links and fix some bugs
*	0.1	21.06.11/goshi	...
*/

namespace webtFramework\Services;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oModelType;
use webtFramework\Interfaces\iTree;

/**
* Class oTree definition
* @package web-T[share]
*/
class oTree extends oModelType{

	protected static function updateHref(oPortal &$p, &$tree, &$nicks, &$deep, $child){
					
		/*foreach ($tree[$child]['children'] as $v){
		
			if ($tree[$v]['href'] == ''){
			
				// getting href to top news
				$tree[$v]['page_id'] = null;
				$cat_nick = $tree[$v]['nick'];
				if (count($nicks[$cat_nick]) == 1){
				
					$tree[$v]['page_id'] = $p->page_nicks[$cat_nick][0];
					//dump($p->page_nicks[$cat_nick][0], false);
				
				} elseif (count($nicks[$cat_nick]) > 1 && isset($p->page_nicks) && is_array($p->page_nicks)){
					
					foreach ($nicks[$cat_nick] as $z){
						if (!$p->page_nicks && !empty($p->page_nicks[$cat_nick])){
							foreach ($p->page_nicks[$cat_nick] as $x){
								if ($v == $z && isset($tree[$z]['owner_id']) && $tree[$tree[$z]['owner_id']]['nick'] == $p->ap[$p->ap[$x]['owner_id']]['nick']){
									$tree[$v]['page_id'] = $x;
									//dump($x ." ---- " . $cat_nick, false);
									//dump($p->page_nicks[$cat_nick], false);
									break 2;
								}
							}
						}
					}
				}
			
				if ($tree[$v]['owner_id'] > 0){
					//$query = array($tree[$tree[$v]['owner_id']]['nick'] => "", 'sub' => $tree[$v]['nick']);
					$query = array('sub' => $tree[$v]['nick']);
				} else
					$query = array(self::$_params['page_params']['nick'] => '', 'sub' => $tree[$v]['nick']);
			
				$tree[$v]['href'] = $p->query->build($query, false, null);
				//dump($tree[$v]['href'], false);
				$tree[$v]['query'] = $p->query->parse($tree[$v]['href'], true);
				$tree[$v]['rss'] = $p->query->build(array($tree[$v]['nick'] => "", 'rss' => ''), false, null);
			}
			
			if (!empty($tree[$v]['children'])){
				// set type of page - if it contains simple pages - then - it is list, if it contains only lists - then it is catslist
				$tree[$child]['type'] = 'catslist';
				++$deep;
				self::updateHref($p, $tree, $nicks, $deep, $v);
				$deep--;
				
			}
							
			$tree[$v]['level'] = $deep;
			
		} */
	
	}

    protected static function collectData(oPortal &$p, $primary, $fields_list, $params = array()){

        $lang_id = isset($params['lang_nick']) && $params['lang_nick'] && isset($p->getLangTbl()[$params['lang_nick']]) ? $p->getLangTbl()[$params['lang_nick']] : $p->getLangId();

        $conditions = array(
            'where' => array(),
            'order' => array()
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

        if (isset($params['modelInstance']->getModelFields()['weight'])){
            $conditions['order']['weight'] = 'desc';
        } else {
            $conditions['order'][$params['modelInstance']->getPrimaryKey()] = 'desc';
        }

        $sql = $p->db->getQueryBuilder($params['modelInstance']->getModelStorage())->compile($params['modelInstance'], $conditions);

        return $p->db->select($sql, $params['modelInstance']->getModelStorage());

    }

    protected static function initCollection(&$data){

        $tree = array();
        $tree[0]['children'] = array();

        return $tree;
    }

    protected static function postCollect(oPortal &$p, $tree){

        return new iTree($p, $tree);

    }

    /**
     * @param oPortal $p
     * @param mixed $tree
     * @return mixed|iTree
     */
    protected static function postBuild(oPortal &$p, $tree){

        if (!is_object($tree))
            $tree = new iTree($p, $tree);

        // restore oPortal var
        $tree->setParam('p', $p);

        return $tree;

    }



}

