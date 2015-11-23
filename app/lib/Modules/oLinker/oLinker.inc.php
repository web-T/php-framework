<?php

/**
* Module working with element properties. It is croossbar for temporary properties values for all elements, that have 
* properties, such comments count, linked video, audio, files, views, rating and other
*
* @version 1.90
* @author goshi
* @package web-T[share]
*
* Changelog:
 *  1.90    15.05.13/goshi  some fixes for getting full data from getData -> getElems
 *  1.80    10.05.13/goshi  add getAll method
 *	1.70	01.09.12/goshi	add app finding for full data linking
*	1.63	23.01.12/goshi	fix cache bug
*	1.62	04.09.11/goshi	improve picture data
*	1.61	25.06.11/goshi	some popularity improvements
*	1.6		22.05.11/goshi	some cache adding
*	1.52	22.05.11/goshi	fix bug with linked this_tbl_name
*	1.51	07.03.11/goshi	fix linker for non special duplications
*	1.5		05.03.11/goshi	now you can use any link table - system autodetect all link tables
*	1.1		04.02.11/goshi  add reverse_links
*	1.02	25.10.10/goshi	minor fixes 
*	1.01	29.09.10/goshi	fix getData
*	1.0		24.09.10/goshi	getData now cat handle array
*	0.991	10.07.10/goshi	add checking for popularity exists
*	0.99	10.05.10/goshi	add checking for primary key in tables, add flag for getting full info for each items, add fullinfo fields detection
*	0.98	04.10.09/goshi	add universal linker table
*	0.97	24.09.09/goshi	fix bug with wrong items count for saving	
*	0.95	10.07.09/goshi	support duplicateLinks method - for duplicate info
*	0.91	27.05.09/goshi	remove some bugs with delete linked elements
*	0.9	17.05.09/goshi	remove image_ids to images_ids
*	0.8	04.05.09/goshi	added full support of inled data
*	0.1	21.07.08/goshi	
*
 * @TODO: refactor
*/

namespace webtFramework\Modules;

use webtFramework\Core\oPortal;
use webtFramework\Interfaces\oBase;



/**
* @package web-T[share]
*/
class oLinker extends oBase{

    /**
     * link table name for Linker table
     * @var string
     */
    protected $_this_tbl_name;

    /**
     * table name of linking element
     * @var string
     */
    protected $_tbl_name;

    /**
     * model name of linked item
     * @var null|string
     */
    protected $_model;

    /**
     * model name of connecting item
     * @var null|string
     */
    protected $_this_model;

    /**
     * standart link table name of linked element (extracted from linker model)
     * @var null|string
     */
    protected $_link_tbl;

    /**
     * linker model
     * @var string
     */
    protected $_linkerModel;

    /**
     * @var null|\webtFramework\Interfaces\oModel
     */
    protected $_linkerModelInstance;

    /**
     * element connected identifier
     * @var integer|array
     */
    protected $_elem_id;

    /**
     * array of links (use for optimization)
     * @var array
     */
    protected $_links		=	array();

    /**
     * array of reverse links
     * @var array
     */
    protected $_reverseLinks		=	array();

    /**
     * flag to get whole information for linked elements
     * maybe set to false on huge sites
     * @var bool
     */
    protected $_fulldata	= true;


    /**
     * base conditions for frontend controllers to get fulldata
     * @var array
     */
    protected $_conditions = array();

    /**
     * @param oPortal $p
     * @param bool|array $params
     */
    public function __construct(oPortal &$p, $params = false){

		parent::__construct($p, $params);

        $this->_linkerModel = 'Linker';

	}

    /**
     * method like singleton gets model of the linker
     * @return null|\webtFramework\Interfaces\oModel
     */
    public function getLinkerModel(){

        if (!$this->_linkerModelInstance){
            $this->_linkerModelInstance = $this->_p->Model($this->_linkerModel);
        }

        return $this->_linkerModelInstance;

    }

    /**
     * method cleanup properties
     * @return oLinker
     */
    public function cleanup(){

        $this->_this_tbl_name = $this->_tbl_name = $this->_elem_id = $this->_model = $this->_this_model = null;

        $this->_linkerModel = 'Linker';
        $this->_link_tbl = null;

        $this->_fulldata = true;
        $this->_links = array();

        return $this;

    }

	/**
     * duplicate links of element
     * All data must be intialized
     * @param mixed $data
     * @param bool|array $params
     * @return bool
     *
     * TODO: refactor
     */
    public function duplicateData($data, $params = array()){

		if ($params)
			$this->AddParams($params);


		if (!$this->_elem_id || !($this->_tbl_name || $this->_model) || empty($this->_links) || empty($data['to']))
			return false;

        if (!$this->_link_tbl){
            $this->_link_tbl = $this->getLinkerModel()->getModelTable();
            $linker_model = $this->getLinkerModel();
        } else {
            $linker_model = $this->_p->db->getQueryBuilder()->createModel($this->_link_tbl);
        }

        $aparams = array(
            'elem_id'		=>	$this->_elem_id
        );

        // initialize models
        if ($this->_model){
            $model = $this->_p->Model($this->_model);
            $aparams['model'] = $this->_model;
            $aparams['tbl_name'] = $model->getModelTable();
        } else {
            $model = $this->_p->db->getQueryBuilder()->createModel($this->_tbl_name);
            $aparams['tbl_name'] = $this->_tbl_name;
        }

		$old_id = $this->_elem_id;

        $params = array_merge((array)$params, array('full_data' => false));

		// turn off return from controller full data
		$this->_fulldata = false;

		foreach ($this->_links as $k => $v){

            // detect link nick
            $nick = $this->_getLinkNick($k, $v);

			// skip comments
			if ($nick == 'comments') continue;

			$this->_link_tbl = $linker_model->getModelTable();
			$this->_elem_id = $old_id;

			switch ($nick){

                case 'images':
                    $this->_p->Module('webtCMS:oImages')->AddParams($aparams)->duplicateData($data, $params);
                    break;

                case 'video':
                    $this->_p->Module('webtCMS:oVideo')->AddParams($aparams)->duplicateData($data, $params);
                    break;

                case 'upload':
                    $this->_p->Module($this->_p->getVar('upload')['service'])->AddParams($aparams)->duplicateData($data, $params);
                    break;

                case 'audio':
                    $this->_p->Module('webtCMS:oAudio')->AddParams($aparams)->duplicateData($data, $params);
                    break;

                case 'tags':
                    $this->_p->Module('webtCMS:oTags')->AddParams($aparams)->duplicateData($data, $params);
                    break;

                // for linker table and non standart - connect them
                default:

                    // so we sucks and need to determine link
                    $link_model = $this->_getLinkingModel($k, $v);

                    // build link query
                    $copy_cond = array('where' => array(
                        'elem_id' => $aparams['elem_id'],
                        'tbl_name' => $aparams['tbl_name']
                    ));

                    if (isset($link_model->getModelFields()['this_tbl_name'])){

                        try {

                            $this_model = $this->_p->Model($nick);
                            $copy_cond['where']['this_tbl_name'] = $this_model->getModelTable();

                        } catch (\Exception $e){

                            // check for existing application
                            if (($app = $this->_getLinkApp($nick, $v))){
                                $copy_cond['where']['this_tbl_name'] = $app->getModelInstance()->getModelTable();
                                unset($app);
                            } else
                                $copy_cond['where']['this_tbl_name'] = $this->_p->getVar('tbl_prefix').$nick;
                        }

                    }

                    $sql = $this->_p->db->getQueryBuilder($link_model->getModelStorage())->compile($link_model, $copy_cond);

                    $items = $this->_p->db->select($sql, $link_model->getModelStorage());

                    if ($items){

                        foreach ($items as $z => $x){
                            unset($items[$z]['id']);
                            $items[$z]['elem_id'] = $data['to'];
                        }

                        $this->_p->db->query($this->_p->db->getQueryBuilder($link_model->getModelStorage())->compileInsert($link_model, $items, true), $link_model->getModelStorage());


                    }
                    unset($items);
                    unset($sql);

                    break;
			}

		}

		// duplicate rating
		$oRating = $this->_p->Module('webtCMS:oRating');

		$oRating->AddParams(array(
				'elem_id' => (int)$this->_elem_id,
				'tbl_name' => $model->getModelTable()
				));
		$oRating->duplicateData(array('elem_id' => (int)$data['to'], 'tbl_name' => $model->getModelTable()));
		unset($oRating);

        // restoring base linked data
        $this->_tbl_name = $model->getModelTable();
		$this->_elem_id = $data['to'];

		return true;

	}

    /**
     * method return reverse links
     * @param array $params must consists 'this_tbl_name' - reverse table name, 'elem_id' - reverse elem_id or elem_ids
     * @return array|bool
     */
    public function getReverseLinks($params = array()){

        if ($params && !empty($params))
            $this->AddParams($params);

        if (($this->_this_tbl_name || $this->_this_model) && $this->_elem_id){

            if (!$this->_link_tbl){
                $this->_link_tbl = $this->getLinkerModel()->getModelTable();
                $linker_model = $this->getLinkerModel();
            } else {
                $linker_model = $this->_p->db->getQueryBuilder()->createModel($this->_link_tbl);
            }

            // initialize models
            $this_model = null;

            if ($this->_this_model){
                $this_model = $this->_p->Model($this->_this_model);
            } else {
                $this_model = $this->_p->db->getQueryBuilder()->createModel($this->_this_tbl_name);
            }

            $model = null;
            if ($this->_model){
                $model = $this->_p->db->getQueryBuilder()->createModel($this->_model);
                $this->_tbl_name = $model->getModelTable();
            }


            $conditions = array('where' => array('this_tbl_name' => $this_model->getModelTable()));

            if (is_array($this->_elem_id)){

                $conditions['where']['this_id'] = array('op' => 'in', 'value' => $this->_elem_id);

                // changing keys and values
                $this->_elem_id = array_flip($this->_elem_id);

            } else {
                $cache = array('reverse', $this->_this_tbl_name, $this->_elem_id);

                $this->_elem_id = array($this->_elem_id => array($this->_elem_id));

                $conditions['where']['this_id'] = $this->_elem_id;

            }

            // check for additional rule
            if (isset($this->_tbl_name) && $this->_tbl_name != '' && $linker_model->getModelTable() == $this->getLinkerModel()->getModelTable()){

                $conditions['where']['tbl_name'] = $this->_tbl_name;

                if (!empty($cache))
                    $cache[] = $this->_tbl_name;
            }

            // try to find in cache
            // TODO: Make cleanup reverse cache item
            /*if (!empty($cache) && !$this->_fulldata && ($data = $this->_p->cache->getSerial(join('_', $cache))) !== false)
                return $data;
            */

            if (isset($linker_model->getModelFields()['weight'])){
                $conditions['order'] = array('weight' => 'asc');
            } else {
                $conditions['order'] = array('this_id' => 'asc');
            }

            $sql = $this->_p->db->getQueryBuilder($linker_model->getModelStorage())->compile($linker_model, $conditions);

            $res = $this->_p->db->select($sql, $linker_model->getModelStorage());

            $tmp = array();

            if ($res){

                foreach ($res as $arr){

                    // if INTERNAL full_data, then return another type
                    /*if ($params['full_data']){
                        $tmp[(int)$arr['elem_id']] = $arr;

                    } else { */

                        // for EXTERNAL full data we must set another mode
                        if ($arr['tbl_name']){
                            $section = str_replace($this->_p->getVar('tbl_prefix'), '', $arr['tbl_name']);
                        } elseif ($linker_model->getModelTable() != $this->getLinkerModel()->getModelTable()) {
                            $section = str_replace(array('_lnk', $this->_p->getVar('tbl_prefix')), '', $linker_model->getModelTable());
                        } else {
                            $section = 0;
                        }

                        if ($this->_fulldata){
                            $arr['external_id'] = $arr['this_id'];
                            $tmp[$section][(int)$arr['elem_id']][] = $arr;
                        } else
                            $tmp[$section][$arr['this_id']][] = (int)$arr['elem_id'];
                    //}
                }

            }

            /*if ($this->_fulldata)
                return $this->_getElemsBySections($tmp, array('linked_rows' => $res));
            else { */
                if (!empty($cache) && !$this->_fulldata)
                    $this->_p->cache->saveSerial(join('_', $cache), $tmp);

                return $tmp;
            //}

        }

        return null;

    }



    /**
     * get linked data
     * @param bool|array $params
     * @return array|bool|mixed
     */
    public function getData($params = false){

		if ($params)
			$this->AddParams($params);

        if (($this->_tbl_name || $this->_model) && $this->_elem_id){

			// all right - we have rules
			// check if ids is array

            if (!$this->_link_tbl){
                $this->_link_tbl = $this->getLinkerModel()->getModelTable();
                $linker_model = $this->getLinkerModel();
            } else {
                $linker_model = $this->_p->db->getQueryBuilder()->createModel($this->_link_tbl);
            }

            // initialize models
            $model = null;

            if ($this->_model){
                $model = $this->_p->Model($this->_model);
            } else {
                $model = $this->_p->db->getQueryBuilder()->createModel($this->_tbl_name);
            }

            $this_model = null;
            if ($this->_this_model){
                $this_model = $this->_p->db->getQueryBuilder()->createModel($this->_this_model);
                $this->_this_tbl_name = $this_model->getModelTable();
            }

            $conditions = array('where' => array('tbl_name' => $model->getModelTable()));

            if (is_array($this->_elem_id)){

                $conditions['where']['elem_id'] = array('op' => 'in', 'value' => $this->_elem_id);

				// changing keys and values
				$this->_elem_id = array_flip($this->_elem_id);

			} else {
                $cache = array($this->_tbl_name, $this->_elem_id);

                $conditions['where']['elem_id'] = $this->_elem_id;

				$this->_elem_id = array($this->_elem_id => array($this->_elem_id));

			}

			// check for additional rule
			if (isset($this->_this_tbl_name) && $this->_this_tbl_name != '' && $linker_model->getModelTable() == $this->getLinkerModel()->getModelTable()){

                $conditions['where']['this_tbl_name'] = $this->_this_tbl_name;

				if (!empty($cache))
					$cache[] = $this->_this_tbl_name;
			}

			// try to find in cache
			if (!empty($cache) && !$this->_fulldata && ($data = $this->_p->cache->getSerial(join('_', $cache))) !== false)
				return $data;

            if (isset($linker_model->getModelFields()['weight'])){
                $conditions['order'] = array('weight' => 'asc');
            } else {
                $conditions['order'] = array('elem_id' => 'asc');
            }

            $sql = $this->_p->db->getQueryBuilder($linker_model->getModelStorage())->compile($linker_model, $conditions);

			$res = $this->_p->db->select($sql, $linker_model->getModelStorage());

			$tmp = array();

			if ($res && !empty($res)){

				foreach ($res as $arr){

					// if INTERNAL full_data, then return another type
					/* if ($params['full_data']){
						$tmp[(int)$arr['this_id']] = $arr;

                    } else { */

                        // for EXTERNAL full data we must set another mode
                        if ($arr['this_tbl_name']){
                            $section = str_replace($this->_p->getVar('tbl_prefix'), '', $arr['this_tbl_name']);
                        } elseif ($linker_model->getModelTable() != $this->getLinkerModel()->getModelTable()) {
                            $section = str_replace(array('_lnk', $this->_p->getVar('tbl_prefix')), '', $linker_model->getModelTable());
                        } else {
                            $section = 0;
                        }

                        if ($this->_fulldata){
                            $arr['external_id'] = $arr['elem_id'];
                            $tmp[$section][(int)$arr['this_id']][] = $arr;
                        } else
                            $tmp[$section][$arr['elem_id']][] = (int)$arr['this_id'];
                    //}
    			}

	    	}

			/*if ($this->_fulldata)
				return $this->_getElemsBySections($tmp, array('linked_rows' => $res));
			else { */
				if (!empty($cache) && !$this->_fulldata)
					$this->_p->cache->saveSerial(join('_', $cache), $tmp);

				return $tmp;
			//}

		}

        return null;

	}


    /**
     * method return all normal links. You can define links, that you want by define $params['links'] variable
     * @param bool|array $params
     * @return array array of links, like array('x' => array_of_items, 'y' => array_of_items)
     */
    public function getAll($params = false){

        if ($params)
            $this->AddParams($params);

        $links = array();

        if (($this->_tbl_name || $this->_model) && $this->_elem_id){

            if ($this->_model && !$this->_tbl_name){
                $model = $this->_p->Model($this->_model);
                $this->_tbl_name = $model->getModelTable();
            } else {
                $model = $this->_p->db->getQueryBuilder()->createModel($this->_tbl_name);
            }

            if (!empty($params['links'])){
                // normalize nicks

                $links_nicks = $params['links'];

            } else {

                $links_nicks = array('images', 'video', 'charts', 'figures', 'tags', 'audio', 'infographics', 'upload', 'default');
            }

            //get linked count
            $LParams = array(
                'elem_id' => $this->_elem_id,
                'tbl_name' => $model->getModelTable(),
                'model' => $model->getModelName(),
            );

            foreach ($links_nicks as $k => $v){

                $nick = $this->_getLinkNick($k, $v);

                switch ($nick){

                    case 'images':
                        $links['images'] = $this->_p->Module('webtCMS:oImages')->AddParams($LParams)->getData();
                        break;

                    case 'video':
                        $links['video'] = $this->_p->Module('webtCMS:oVideo')->AddParams($LParams)->getData();
                        break;

                    case 'charts':
                        $links['charts'] = $this->_p->Module('webtCMS:oCharts')->cleanup()->init()->AddParams($LParams)->getData();
                        break;

                    case 'figures':
                        $links['figures'] = $this->_p->Module('webtCMS:oFigures')->AddParams($LParams)->getData();
                        break;

                    case 'tags':
                        $links['tags'] = $this->_p->Module('webtCMS:oTags')->AddParams($LParams)->getData();
                        break;

                    case 'audio':
                        $links['audio'] = $this->_p->Module('webtCMS:oAudio')->AddParams($LParams)->getData();
                        break;

                    case 'infographics':
                        $links['infographics'] = $this->_p->Module('webtCMS:oInfographics')->AddParams($LParams)->getData();
                        break;

                    case 'upload':
                        $links['upload'] = $this->_p->Module($this->_p->getVar('upload')['service'])->AddParams($LParams)->getData();
                        break;

                    case 'default':
                        $links = array_merge($links, (array)$this->getData());
                        break;

                    default:
                        try {

                            $this_model = $this->_p->Model($nick);

                        } catch (\Exception $e){

                            // check for existing application
                            if (($app = $this->_getLinkApp($nick, $v))){
                                $this_model = $app->getModelInstance();
                                unset($app);
                            } else
                                $this_model = $this->_p->db->getQueryBuilder()->createModel($this->_p->getVar('tbl_prefix').$nick);
                        }

                        $links = array_merge($links, (array)$this->getData(array('this_model' => $this_model/*, 'this_tbl_name' => $this->_p->getVar('tbl_prefix').$nick*/)));
                        unset($app);
                        break;


                }

            }

        }

        return $links;

    }



	/**
	* function remove links of element
	* All data must be intialized
	*
	* @access	public
	*/
	public function removeData(){

        if (!$this->_elem_id || !($this->_tbl_name || $this->_model) || empty($this->_links))
			return false;

        if (!$this->_link_tbl){
            $this->_link_tbl = $this->getLinkerModel()->getModelTable();
            $linker_model = $this->getLinkerModel();
        } else {
            $linker_model = $this->_p->db->getQueryBuilder()->createModel($this->_link_tbl);
        }

        $aparams = array(
            'elem_id'		=>	$this->_elem_id
        );

        // initialize models
        $model = null;

        if ($this->_model){
            $model = $this->_p->Model($this->_model);
            $aparams['model'] = $this->_model;
            $aparams['tbl_name'] = $model->getModelTable();
        } else {
            $model = $this->_p->db->getQueryBuilder()->createModel($this->_tbl_name);
            $aparams['tbl_name'] = $this->_tbl_name;
        }

        $old_link_tbl = $this->_link_tbl;


		// turn off return from controller full data
		$this->_fulldata = false;

		foreach ($this->_links as $k => $v){

            // detect link nick
            $nick = $this->_getLinkNick($k, $v);

            $this->_link_tbl = $old_link_tbl;

			switch ($nick){

                // comments
                case 'comments':
                    $this->_p->Module('webtCMS:oComments')->AddParams($aparams)->removeData(array_merge((array)$aparams, array('remove_all' => true)));
                    break;

                // images
                case 'images':
                    $this->_p->Module('webtCMS:oImages')->AddParams($aparams)->removeData();
                    break;

                // charts
                case 'charts':
                    $this->_p->Module('webtCMS:oCharts')->cleanup()->init()->AddParams($aparams)->removeData();
                    break;

                // video
                case 'video':
                    $this->_p->Module('webtCMS:oVideo')->AddParams($aparams)->removeData();
                    break;

                // upload
                case 'upload':
                    $this->_p->Module($this->_p->getVar('upload')['service'])->AddParams($aparams)->removeData();
                    break;

                // audio
                case 'audio':
                    $this->_p->Module('webtCMS:oAudio')->AddParams($aparams)->removeData();
                    break;


                // for linker table and non standart - connect them
                default:

                    // so we sucks and need to determine link
                    $link_model = $this->_getLinkingModel($k, $v);

                    $qb = null;

                    try {

                        $this_model = $this->_p->Model($nick);

                    } catch (\Exception $e){

                        // check for existing application
                        if (($app = $this->_getLinkApp($nick, $v))){
                            $this_model = $app->getModelInstance();
                            unset($app);
                        } else
                            $this_model = $this->_p->db->getQueryBuilder()->createModel($this->_p->getVar('tbl_prefix').$nick);
                    }

                    $qb = $this->_p->db->getQueryBuilder($link_model->getModelStorage());

                    $conditions = array('where' => array(
                    ));

                    if (isset($link_model->getModelFields()['elem_id'])){
                        $conditions['where']['elem_id'] = $this->_elem_id;
                    }

                    if (isset($link_model->getModelFields()['model'])){
                        $conditions['where']['model'] = $model->getModelName();
                    } elseif (isset($link_model->getModelFields()['tbl_name'])){
                        $conditions['where']['tbl_name'] = $model->getModelTable();
                    }

                    if (isset($link_model->getModelFields()['this_tbl_name'])){
                        $conditions['where']['this_tbl_name'] = $this_model->getModelTable();
                    }

                    $sql = $qb->compileDelete($link_model, $conditions);

                    $this->_p->db->query($sql, $link_model->getModelStorage());

                    $this->_p->cache->removeSerial($model->getModelTable()."_".(int)$this->_elem_id."_".$this_model->getModelTable());
                    $this->_p->cache->removeSerial($link_model->getModelTable()."_".(int)$this->_elem_id);

                    unset($qb);
                    unset($this_model);
                    unset($link_model);

                    break;
			}

		}

		// ony now delete from universal linker table
        $sql = $this->_p->db->getQueryBuilder($linker_model->getModelStorage())->compileDelete($linker_model, array('where' => array(
            'tbl_name' => $model->getModelTable(),
            'elem_id' => $this->_elem_id
        )));

        $this->_p->db->query($sql, $linker_model->getModelStorage());


		// delete from popularity
        try {
            $pop_model = $this->_p->Model('webtCMS:Popularity');

            if ($pop_model){

                $qb = $this->_p->db->getQueryBuilder($pop_model->getModelStorage());

                $this->_p->db->query($qb->compileDelete($pop_model, array('where' => array(
                    'elem_id' => $this->_elem_id,
                    'tbl_id' => $this->_p->getTableHash($model->getModelTable())
                ))), $pop_model->getModelStorage());

                unset($qb);

            }
        } catch (\Exception $e){
            // do nothing
        }

		// delete from rating
		$this->_p->Module('webtCMS:oRating')->AddParams($aparams)->removeData();

		// delete tags links
		$this->_p->Module('webtCMS:oTags')->AddParams($aparams)->removeData();

		// delete deeplink
		$DParams = array(
			'tbl_name' => $model->getModelTable(),
			'elem_id' => $this->_elem_id,
		);
		$this->_p->Module('webtCMS:oDeepLinks')->AddParams($DParams)->removeData();

        return $this;

	}

    /**
     * method extract link's nick
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function _getLinkNick($key, $value){

        if (is_array($value)){
            $nick = $key;
        } else {
            $nick = $value;
        }

        return $nick;

    }

    /**
     * extract application from link
     * @param $nick
     * @param $value
     * @return \webtBackend\Interfaces\oAdminController|bool|\Frontend\Interfaces\oClip|null|\webtFramework\Interfaces\oApp
     * TODO: refactor to remove deprecated code
     */
    protected function _getLinkApp($nick, $value){

        $app = null;

        if (is_array($value) && isset($value['app'])){

            $app = $this->_p->App($value['app']);

        }

        return $app;
    }

    /**
     * method extract model from link
     * @param $key
     * @param $value
     * @return null|\webtFramework\Interfaces\oModel
     */
    protected function _getLinkingModel($key, $value){

        $link_model = null;

        // detect link nick
        $nick = $this->_getLinkNick($key, $value);

        try {

            $m = $this->_p->Model($nick);
            $link_model = $m->getModelLinkTable();
            $qb = $this->_p->db->getQueryBuilder($m->getModelStorage());

            $link_model = $qb->createModel($link_model);
            // duplicate model storage to the link table
            $link_model->setModelStorage($m->getModelStorage());

            unset($m);

        } catch (\Exception $e){
            // fallback
            $app = $this->_getLinkApp($nick, $value);

            if ($app && $app->getParam('link_tbl') && $this->getLinkerModel()->getModelTable() != $app->getParam('link_tbl')){

                $describe = $this->_p->db->getQueryBuilder($app->getModelInstance()->getModelStorage())->describeTable($app->getParam('link_tbl'));

                if (!empty($describe)){

                    $qb = $this->_p->db->getQueryBuilder($app->getModelInstance()->getModelStorage());

                    $link_model = $qb->createModel($app->getParam('link_tbl'));
                    // duplicate model storage to the link table
                    $link_model->setModelStorage($app->getModelInstance()->getModelStorage());

                    unset($qb);

                }

            }
            unset($app);
        }

        // if not found
        if (!$link_model) {

            $link_model = $this->getLinkerModel();

        }

        return $link_model;

    }

    /**
     * get model from settings
     * @param $key
     * @param $value
     * @return null|\webtFramework\Interfaces\oModel
     */
    protected function _getModel($key, $value){

        $model = null;

        // detect link nick
        $nick = $this->_getLinkNick($key, $value);

        try {

            $model = $this->_p->Model($nick);

        } catch (\Exception $e){

            // check for existing application
            if (($app = $this->_getLinkApp($nick, $value))){
                $model = $app->getModelInstance();
                unset($app);
            }
        }

        return $model;

    }


    /**
     * method save data in linked table
     * all data must be initialized
     * @param null|array $data array of identifiers of the linked elements
     *                  $data['links'] - main section for all links.
     *      Each link can be URL string, or array with structure:
     *      [iterator]=>
     *           array(2) {
     *               ["weight"]=> integer - weight of the element from min to max
     *               ["value"]=> integer - value of the link
     *          }
     * @return $this
     */
    public function saveData($data = null){

		// saving linked elems
		// data MUST be the simple POST[ch_elem]
		if ($data){

            // delete comments links
            unset($data['links']['comments']);

            $this->_elem_id = (int)$this->_elem_id;

            if (!$this->_link_tbl){
                $this->_link_tbl = $this->getLinkerModel()->getModelTable();
                $linker_model = $this->getLinkerModel();
            } else {
                /**
                 * TODO: maybe it would be better to get storage from current model
                 */
                $linker_model = $this->_p->db->getQueryBuilder()->createModel($this->_link_tbl);
            }

            $Params = array(
                'elem_id'		=>	$this->_elem_id,
            );

            // initialize models
            $this_model = $model = null;

            if ($this->_model){
                $model = $this->_p->Model($this->_model);
                $Params['model'] = $this->_model;
                $Params['tbl_name'] = $model->getModelTable();
            } else {
                $model = $this->_p->db->getQueryBuilder()->createModel($this->_tbl_name);
                $Params['tbl_name'] = $this->_tbl_name;
            }

            $is_this_model_found = false;

            foreach ($this->_links as $k => $v){

                // detect link nick
                $nick = $this->_getLinkNick($k, $v);

                /**
                 * there are predefined nicks
                 */
                switch ($nick){

                    case 'video':
                        $this->_p->Module('webtCMS:oVideo')->AddParams($Params)->saveData($data['links']['video']);
                        break;

                    case 'images':
                        $this->_p->Module('webtCMS:oImages')->AddParams($Params)->saveData($data['links']['images']);
                        break;

                    case 'audio':
                        $this->_p->Module('webtCMS:oAudio')->AddParams($Params)->saveData($data['links']['audio']);
                        break;

                    case 'upload':
                        $this->_p->Module($this->_p->getVar('upload')['service'])->AddParams($Params)->saveData($data['links']['upload']);
                        break;

                    case 'tags':

                        $this->_p->Module('webtCMS:oTags')->AddParams($Params)->saveData(isset($data['links']['tags']) ? array(
                            'tags' => $data['links']['tags'],
                            'elem_category' => $data['category'] ? $data['category'] : ($data['news_type'] ? $data['news_type'] : 0),
                            'date_post' => $data['date_post'] ? (int)strtotime($data['date_post']) : (int)strtotime($data['date_add']),
                            'is_on' => (int)$data['is_on'],
                            'is_top_section' => (int)$data['is_top_section'],
                            'is_top_subsection' => (int)$data['is_top_subsection'],
                            'is_article' => (int)$data['is_article'],
                        ) : $data['tags']);
                        break;

                    default:

                        // so we sucks and need to determine link
                        $link_model = $this->_getLinkingModel($k, $v);

                        $old_linked_data = array();

                        $qb = null;

                        if (!$this_model){

                            try {

                                $this_model = $this->_p->Model($nick);

                            } catch (\Exception $e){

                                // check for existing application
                                if (($app = $this->_getLinkApp($nick, $v))){
                                    $this_model = $app->getModelInstance();
                                    unset($app);
                                } else
                                    $this_model = $this->_p->db->getQueryBuilder()->createModel($this->_p->getVar('tbl_prefix').$nick);
                            }

                        }


                        $qb = $this->_p->db->getQueryBuilder($link_model->getModelStorage());

                        if ($link_model && $this->getLinkerModel()->getModelTable() != $link_model->getModelTable()){

                            $sql = $qb->compile($link_model, array(
                                'select' => array('__groupkey__' => 'this_id', 'a' => '*'),
                                'where' => array('elem_id' => $this->_elem_id, 'tbl_name' => $model->getModelTable())
                            ));

                            $old_linked_data = $this->_p->db->select($sql, $link_model->getModelStorage());

                        }

                        $delete_cond = array('where' => array(
                            'elem_id' => $this->_elem_id,
                        ));

                        if ($this->_model && isset($link_model->getModelFields()['model'])){
                            $delete_cond['where']['model'] = $model->getModelName();
                        } elseif (isset($link_model->getModelFields()['tbl_name'])){
                            $delete_cond['where']['tbl_name'] = $model->getModelTable();
                        }

                        if (isset($link_model->getModelFields()['this_tbl_name']))
                            $delete_cond['where']['this_tbl_name'] = $this_model->getModelTable();

                        $sql = $qb->compileDelete($link_model, $delete_cond);

                        $this->_p->db->query($sql, $link_model->getModelStorage());

                        $this->_p->cache->removeSerial($model->getModelTable()."_".(int)$this->_elem_id."_".$this_model->getModelTable());
                        $this->_p->cache->removeSerial($link_model->getModelTable()."_".(int)$this->_elem_id);


                        // unescape all chars
                        $arr_elems = array();

                        $x = $data['links'][$nick];

                        if (!is_array($x))
                            parse_str($x, $arr_elems);
                        else
                            $arr_elems = $x;

                        if ($arr_elems){

                            $sql = array();

                            // perform multiinsert
                            foreach ($arr_elems as $z => $x){

                                $row = array();

                                // add old data
                                if ($old_linked_data[$z]){
                                    $row = $old_linked_data[$z];
                                }

                                $row = array_replace($row, $x);

                                $row['this_id'] = $z;
                                $row['elem_id'] = $this->_elem_id;
                                $row['tbl_name'] = $model->getModelTable();
                                $row['this_tbl_name'] = $this_model->getModelTable();
                                $row['model'] = $model->getModelName();
                                $row['this_model'] = $this_model->getModelName();
                                //unset($row['id']);

                                $sql[] = $row;

                            }

                            $this->_p->db->query($qb->compileInsert($link_model, $sql, true), $link_model->getModelStorage());

                        }

                        // cleanup connecting model;
                        if (!$is_this_model_found)
                            unset($this_model);

                        break;

                }
                unset($data['links'][$nick]);


            }

			// saving popularity
            // it is old @depracted code
			if (isset($data['popularity'])){

                try {
                    $pop_model = $this->_p->Model('webtCMS:Popularity');

                    $qb = $this->_p->db->getQueryBuilder($pop_model->getModelStorage());

                    $this->_p->db->query($qb->compileDelete($pop_model, array('where' => array(
                        'elem_id' => $this->_elem_id,
                        'tbl_id' => $this->_p->getTableHash($model->getModelTable())
                    ))), $pop_model->getModelStorage());

                    $this->_p->db->query($qb->compileInsert($pop_model, array(
                        'tbl_name' => $model->getModelTable(),
                        'elem_id' => $this->_elem_id,
                        'tbl_id' => $this->_p->getTableHash($model->getModelTable()),
                        'popularity' => $data['popularity']
                    )), $pop_model->getModelStorage());

                    unset($pop_model);

                } catch (\Exception $e){
                    // do nothing
                }

            }


			// rating
			if (isset($data['rating'])){

				$this->_p->Module('webtCMS:oRating')->AddParams(array(
					'tbl_name'		=>	$this->_tbl_name,
					'elem_id'		=>	(int)$this->_elem_id
				))->saveData($data['rating'], true);
			}

			// saving deeplink
			/*$this->_p->Module('webtCMS:oDeepLinks')->AddParams(array(
				'title' => $data['title'],
				'tbl_name' => $model->getModelTable(),
				'elem_id' => (int)$this->_elem_id
				))->saveData($data['deeplink']);
            */

			/*if (is_array($data['links']) && !empty($data['links'])){
				// saving linked
				foreach ($data['links'] as $z => $x){

					unset($data['links'][$z]);
				}
			}*/

			// now saving reverse data
			if (!empty($data['reverse_links']) && $this->_reverseLinks){

                $conditions_delete = array('where' => array());

                $need_links_data = false;
				if ($this->_link_tbl == $this->getLinkerModel()->getModelTable()){

                    $conditions_delete['where']['this_tbl_name'] = $model->getModelTable();

                } else {
                    $need_links_data = true;
                }

                $qb = $this->_p->db->getQueryBuilder($linker_model->getModelStorage());

                foreach ($this->_reverseLinks as $l => $s){

                    // get nick
                    $nick = $this->_getLinkNick($l, $s);

                    if (isset($data['reverse_links'][$nick])){

                        $rows = array();

                        $tmp_model = $this->_getModel($l, $s);

                        $delete_cond = array('where' => array_replace($conditions_delete['where'], array(
                            'this_id' => $this->_elem_id,
                            'tbl_name' => $tmp_model->getModelTable(),
                        )));

                        if (isset($linker_model->getModelFields()['this_tbl_name'])){
                            $delete_cond['where']['this_tbl_name'] = $model->getModelTable();
                        }

                        $this->_p->db->query($qb->compileDelete($linker_model, $delete_cond), $linker_model->getModelStorage());


                        if (!empty($data['reverse_links'][$nick])){

                            // unescape all chars
                            $arr_elems = array();

                            if (!is_array($data['reverse_links'][$nick]))
                                parse_str($data['reverse_links'][$nick], $arr_elems);
                            else
                                $arr_elems = $data['reverse_links'][$nick];

                            if ($arr_elems){

                                $base_data = null;

                                if ($need_links_data && ($m = $this->_getModel($nick, $s))){

                                    $repo = $this->_p->db->getManager()->getRepository($m);
                                    $base_data = $repo->find(array_keys($arr_elems), array('group' => array('[PRIMARY]')), $repo::ML_HYDRATION_ARRAY);
                                }

                                foreach ($arr_elems as $z => $x){

                                    if ($base_data && isset($base_data[$z])){
                                        $row = $base_data[$z];
                                        $row['elem_category'] = $base_data[$z]['news_type'] ? $base_data[$z]['news_type'] : $base_data[$z]['category'];
                                        unset($row['id']);
                                    } else {
                                        $row = array();
                                    }

                                    $row['tbl_id'] = $this->_p->getTableHash($tmp_model->getModelTable());
                                    $row['lang_id'] = $this->_p->getLangId();
                                    $row['this_id'] = $this->_elem_id;
                                    $row['elem_id'] = $z;
                                    $row['tbl_name'] = $tmp_model->getModelTable();
                                    $row['model'] = $tmp_model->getModelName();
                                    $row['this_tbl_name'] = $model->getModelTable();
                                    $row['this_model'] = $model->getModelName();
                                    $row['weight'] = $x['weight'];

                                    $rows[] = $row;

                                }

                                $this->_p->db->query($qb->compileInsert($linker_model, $rows, true), $linker_model->getModelStorage());

                                unset($base_data);

                            }

                        }

                        unset($data['reverse_links'][$l]);
                        unset($tmp_model);

                    }

                }

			}

            unset($this_model);
            unset($model);

		}

        return $this;
	}



}

