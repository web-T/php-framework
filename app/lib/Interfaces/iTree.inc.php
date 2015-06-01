<?php
/**
 * Interface for Trees in web-T::Framework
 *
 * Date: 06.08.14
 * Time: 11:05
 * @version 1.0
 * @author goshi
 * @package web-T[framework]
 * 
 * Changelog:
 *	1.0	06.08.2014/goshi 
 */

namespace webtFramework\Interfaces;

use webtFramework\Core\oPortal;

class iTree extends \ArrayObject{

    /**
     * @var oPortal
     **/
    private $_p;

    public function __construct(oPortal $p, $input = array(), $flags = 0, $iterator_class = "ArrayIterator"){

        //$this->_p = $p;

        parent::__construct($input, $flags, $iterator_class);

    }

    /**
     * method for set property of the class
     * @param $param
     * @param $value
     * @return $this
     */
    public function setParam($param, $value){

        $param = '_'.$param;

        if (property_exists($this, $param)){
            $this->$param = $value;
        }

        return $this;
    }

    /**
     * get all descendants of selected entity
     * @param $entry_id
     * @return array of ids
     */
    public function getDescendants($entry_id){

        $children = array();

        if (!is_array($entry_id))
            $entry_id = array($entry_id);

        foreach ($entry_id as $eid){

            if (isset($this[$eid]) && isset($this[$eid]['children']) && $this[$eid]['children']){
                foreach ($this[$eid]['children'] as $v){
                    $children[] = (int)$v;
                    if (isset($this[$v]['children']) && $this[$v]['children']){
                        $children = array_merge($children, $this->getDescendants($v));
                    }
                }
            }
        }

        return $children;

    }

    /**
     * method apply function on all descendats of tree item
     * @param mixed $entry_id
     * @param mixed $function
     * @return $this
     * @throws \Exception
     */
    public function applyOnDescendants($entry_id, $function){

        if (!is_callable($function)){
            throw new \Exception('errors.itree.function_not_callable');
        }

        if (isset($this[$entry_id]) && isset($this[$entry_id]['children']) && $this[$entry_id]['children']){
            foreach ($this[$entry_id]['children'] as $v){
                $this[$v] = call_user_func_array($function, array($this[$v]));
                if (isset($this[$v]['children']) && $this[$v]['children']){
                    $this->applyOnDescendants($v, $function);
                }
            }
        }

        return $this;

    }

    /**
     * method apply function on selected item and all its descendats
     * @param mixed $entry_id
     * @param mixed $function
     * @return $this
     */
    public function applyOnMeAndDescendants($entry_id, $function){

        $this->applyOnDescendants($entry_id, $function);

        if (isset($this[$entry_id])){
            $this[$entry_id] = call_user_func_array($function, array($this[$entry_id]));
        }

        return $this;
    }

    /**
     * get parents to root item for each $id in set
     * @param int|array $id
     * @return array of ids
     */
    public function getAncestors($id){

        if (!is_array($id))
            $id = array($id);

        $tree_levels = $id;
        foreach ($tree_levels as $id){
            if (isset($this[$id]) && isset($this[$id]['owner_id']) && $this[$id]['owner_id']){
                while ($this[$id]['owner_id']){
                    // additional protection for owner
                    if (in_array($this[$id]['owner_id'], $tree_levels))
                        break;
                    $tree_levels[] = (int)$this[$id]['owner_id'];
                    $id = $this[$id]['owner_id'];
                }
            }

        }
        $tree_levels = array_reverse($tree_levels);

        return $tree_levels;
    }


}
