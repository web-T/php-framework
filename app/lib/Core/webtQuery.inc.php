<?php

/**
 * Base class for queries web-T::CMS system
 * @version 1.8.2
 * @author goshi
 * @package web-T[CORE]
 *
 * TODO: need full refactor
 */

namespace webtFramework\Core;

use webtFramework\Components\Request\oRoute;
use webtFramework\Components\Request\oQuery;

/**
* @package web-T[CORE]
*/
class webtQuery {

    /**
     * custom routes from applications
     * they has priority over the standart CMS-generated routes
     * @var array
     */
    protected $_routes = array();

    /**
     * additional query parts
     * @var array
     */
    protected $_parts = array();

    /**
     * array of queries instances, which generated by applications
     * @var array of oQuery
     */
    protected $_queries = array();

    /**
     * @var webtRequest
     */
    public $request;

    /**
     * @var oPortal
     */
    private $_p;


	public function __construct(oPortal &$p){

        $this->_p = &$p;

        $this->request = new cProxy($p, array('instance' => 'webtRequest'));

    }
	

	/**
	* build special query string for statistic
	*/
	protected function _buildStatSection($arr, &$get_page, &$is_arr, $is_set_empty = false){
		
		$result = "";
		$is_arr > 0 ? $add = "[]" : $add = "";

		foreach ($arr as $k => $v){
	
			if ($k != 'lang' && $k != 'page' && $k != 'smphr'){
				// remove semaphore from query
				// if we have got array - make recursion
				if (is_array($v)){
					$is_arr++;
					$result .= $this->_buildStatSection($v, $get_page, $is_arr, $is_set_empty);
					$is_arr--;
				} else {
					$result .= $k.$add."/".rawurlencode($v).($v !== '' ? "/" : "");
				}
					
			} elseif ($k == 'page' && !$get_page){
	
				$result .= $v."/";
				$get_page = true;
					
			} elseif (!$get_page && $k != 'lang'){
				
				$result .= $k.$add."/";
				$get_page = true;
				
			} elseif ($v == '' && $is_set_empty){

				$result .= $k.$add."/.*/";
			
			}
	
		}

		return $result;
				
	}

	/**
	* building query for statistic use
    * @param array|oQuery $arr
	* @param bool $is_set_empty	set flag if in query must have empty fields
	* @param bool $no_add_parts	set flag if in you dont want to add parts (used for clearing from cache)
	*
     * @return string
	*/
    public function buildStat($arr, $is_set_empty = false, $no_add_parts = false){
			
		// setting up flag for get name of page
		$get_page = false;
		// setting up is we in array flag 
		$is_arr = 0;
		
		$result = $this->_buildStatSection($arr instanceof oQuery ? $arr->get() : $arr, $get_page, $is_arr, $is_set_empty);
		// adding link add
		if (!$no_add_parts) {

			$tmp_link_add = $this->getParts();
			foreach ($tmp_link_add as $k => $v){
			
				$result .= "$k/$v/";
				
			}		
		}
		
		$result = substr($result, 0, strrpos($result, '/'));
	
		return $result;
		
	}

    /**
     * parse query string from cache table
     * Query format:
     * [/][page][/[key1]/[value1]][/[key2]]...[_add/(_page|_clip)][lang/lang_ig/]
     *
     * @param string $str string for parsing
     * @return array hash with keys 'user' and 'type'
     */
    public function parseStat($str){
		
		$type = '';	// type of the entry - posible values: '' (empty), '_clip', '_page' (see common.php)
				
		$sep = '/';
		/* prepare separator for regexp */
		$tmp_sep = preg_quote($sep, "/");
		
		// remove all arguments from query
		$str = preg_replace('/(.*)(\?.*)$/is', '$1', $str);

		// remove block types
		foreach ($this->_p->getVar('cache')['block_types'] as $v){
			reset($v);
			$tmp_k = key($v);
			$tmp_v = preg_quote($tmp_k.$sep.$v[$tmp_k], "/");
 			$match = array();
			if (preg_match('/'.$tmp_v.'/is', $str, $match)){
				$type = $v[$tmp_k];
				$str = preg_replace('/'.$tmp_v.'(?:'.$tmp_sep.'?)/is', '', $str);
				break;		
			}
		}
		
		// check for language and addon
		$new_lang = false;
		if (preg_match('/lang'.$tmp_sep.'([^'.$tmp_sep.']+)/is', $str, $match)){
			$new_lang = $match[1];
			$str = preg_replace('/lang'.$tmp_sep.'[^'.$tmp_sep.']+(?:'.$tmp_sep.'?)/is', '', $str);
		}
				
		$result = explode($sep, rawurldecode(preg_replace("/^".$tmp_sep."(.*)(?:".$tmp_sep.preg_quote($this->_p->getVar('doc_types')[$this->get()->getContentType()], "/").")?(?:".$tmp_sep."?)$/", "$1", $str)));
		
		$i = 0;
		$tmp_res = array();
		
		foreach ($result as $k){
		
			if ($i == 0){
			
				if (!$result[$i]){
					// setting current page
					$result[$i] = $this->_p->getVar('router')['default_query_page'] ? $this->_p->getVar('router')['default_query_page'] : 'main';
				}
				
				$tmp_res['page'] = $result[$i];
				
			} else {
			
				// check is value odd
				if ($i % 2){
					
					if ($k != '')
						$tmp_res[$k] = '';
				
				} else {
				
					$tmp_res[$result[$i-1]] = $k;
				
				}
				
			}
			
			$i++;
										
		}
	
		if (empty($tmp_res['page'])){

			// getting start page
			$tmp_res['page'] = $this->_p->getVar('router')['default_query_page'] ? $this->_p->getVar('router')['default_query_page'] : 'main';
			
		}
		
		// storing parsed query
        if (isset($type) && !empty($type)) $tmp_res['_add'] = $type;
        if (isset($new_lang) && !empty($new_lang)) $tmp_res['lang'] = $new_lang;

        return $tmp_res;

	}

    /**
     * build friendly query string from array
     *
     * @param $arr
     * @param $get_page
     * @param $is_arr
     * @param bool $real_tree
     * @return string|null
     */
    protected function _buildFriendly($arr, &$get_page, &$is_arr, $real_tree = false){
		
		$result = "";
		if (!is_array($arr)) return null;

		foreach ($arr as $k => $v){
	
			//echo $k.">".$v."<br>";
			//echo (int)($v != '' && strcmp($k,'page') != 0);			
			if (strcmp($k,'page') != 0 && ($real_tree || (!$real_tree && $v != ''))){
			
				// remove semaphore from query
				// if we have got array - make recursion
				if (is_array($v)){
					$result .= $this->_buildFriendly($v, $get_page, $k);
				} else {
					// check for empty $v - it is possible, if we build real_trees
					$v = $v != '' ? rawurlencode($v)."/" : '';
					$is_arr != false ? $result .= $is_arr."[".$k."]/".$v: $result .= ($k == 'lang' ? $v : $k."/".$v);
				}
					
			} elseif ($k == 'page' && !$get_page){
			// fix bug for $p->query->build($p->q) for static URLs
			// the key 'page' now not have included
				$result .= $v."/";
				$get_page = true;
					
			} elseif (!$get_page){
				
				$is_arr != false ? $result .= $k."[]/" : $result .= $k."/";
				$get_page = true;
			}
	
		}
	
		return $result;
				
	}

    /**
     * build non friendly URL
     *
     * @param $arr
     * @param $get_page
     * @param $is_arr
     * @return string
     */
    protected function _buildNonFriendly($arr, &$get_page, $is_arr){
		
		$result = "";
		foreach ($arr as $k => $v){
	
			if ($v != ''){

				if (is_array($v)){
					$result .= $this->_buildNonFriendly($v, $get_page, $k);
				} else {
					$is_arr != false ? $result .= $is_arr."[".$k."]=".rawurlencode($v)."&" : $result .= $k."=".rawurlencode($v)."&";
					if ($k == 'page' && !$get_page) $get_page = true;
				}
					
			} elseif (!$get_page) {
				// if we have not got page yet
				$is_arr != false ? $result .= "page[]=".$k."&" : $result .= "page=".$k."&";
				$get_page = true;
					
				// if aready we have got page, empty keys would be ignored!!!
				// key 'page' must not be set in array
				// Be careful!!
					
			} 
												
		}
		
		return $result;
				
	}

    /**
     * method returns query vector w/o 'lang' and 'page' parameters
     *
     * @param array|oQuery $query[option] optional query for parsing
     * @return array
     */
    public function getVector($query = null){
		
		$line = array();

		if ($query == null)
			$query = $this->get()->get();
        elseif ($query instanceof oQuery)
            $query = $query->get();

		if (!empty($query) && is_array($query)){
			$line[] = $query['page'];
			//dump($query, false);
			foreach ($query as $k => $v){
				if (strcmp($k, 'page') != 0 && strcmp($k, 'lang') != 0){
					$line[] = $k;
					$v != '' ? $line[] = $v : null;
				}
			}
		}
		return $line;
	}
	
	/**
	* function parse query vector
	*/
	public function parseVector($vector, $only_parse = false){
		
		return $this->parse('/'.($this->get()->has('lang') ? $this->get()->get('lang').'/' : '').(is_array($vector) ? join('/', $vector) : $vector), $only_parse, true);
			
	}

    /**
     * Build URL
     *
     * @param array|oQuery $arr hash of query for build
     * @param bool $is_full_query[option]	flag for generating full query with protocol and domain
     * @param int	$content_type[option]	content type of the builded query
     * @param array|string $add_tree[option]	tree add for friendly query
     * @return string
     */
    public function build($arr = null, $is_full_query = false, $content_type = null, $add_tree = null){

        // check for query
        if (!$arr && $this->get()){
            $arr = $this->get();
        }

        $subdomain = '';

        if ($arr instanceof oQuery){

            if ($arr->getSubdomain()){

                $subdomain = $arr->getSubdomain();

            } elseif ($this->_p->getVar('subdomains') && in_array($arr->get('page'), $this->_p->getVar('subdomains'))){

                $subdomain = $arr->get('page');
                $arr->remove('page');

            }

            // update content type
            if (!$content_type && $arr->getContentType()){
                $content_type = $arr->getContentType();
            }

        } elseif ($this->_p->getVar('subdomains')){

            // emulate 'page' item from query
            if (!isset($arr['page']) && $arr){
                reset($arr);
                $arr['page'] = key($arr);
                unset($arr[$arr['page']]);
            }

            if (in_array($arr['page'], $this->_p->getVar('subdomains'))){
                $subdomain = $arr['page'];
                unset($arr['page']);
            }

        }

        // create temporary array
        if ($arr instanceof oQuery){
            $arr_instance = $arr->get();
        } else {
            $arr_instance = $arr;
        }

        if (!is_array($arr_instance))
            $arr_instance = array();

        if ($this->_p->getVar('router')['is_friendly_URL']){
		
			$result = $this->_p->getVar('ROOT_URL');
			
			// adding tree
			// counter for up levels - need for unclean, when $v == '' (see below)
			//$add_tree = '';

            /*$page_nick = '';

			if (isset($this->ap)){
				$owner_id = 0;
				$tree = array();

				if (is_array($arr)){
					reset($arr);
					if (key($arr) != 'lang' && key($arr) != 'page'){
						$page_nick = key($arr); 
						if (!$page_id)
							$page_id = $arr[key($arr)];
					
					} else { 
						$page_nick = $arr['page'];
					}
				}
				
				// find right owner
				if ($page_id){
					$owner_id = $this->ap[$page_id]['owner_id'];
				} else {
					foreach ($this->ap as $v){
						
						if (isset($v['nick']) && strcmp($v['nick'], $page_nick) == 0){
							$page_id = $v['real_id'];
							if (isset($this->ap[$v['owner_id']]['real_id'])){
								$owner_id = $this->ap[$v['owner_id']]['real_id'];
								break;
							}
						}
					}
				}
				
				// find tree to the page
				if ($owner_id > 0){
					while ($owner_id > 0){
						
						if ($this->ap[$owner_id]['in_menu'] > 0){
							if ($this->ap[$owner_id]['subdomain'])
								$subdomain = $this->ap[$owner_id]['nick'];
							else {
								$tree[] = $this->ap[$owner_id]['nick'];
								//$add_cnt++;
							}
						}
						$owner_id = $this->ap[$owner_id]['owner_id'];
					}
				} elseif ($this->ap[$page_id]['subdomain']){
					// if isset page
					if (isset($arr['page']))
						unset($arr['page']);
					else
						unset($arr[$this->ap[$page_id]['nick']]);
					
					$subdomain = $this->ap[$page_id]['nick'];
					
				}
								
				if (!empty($tree)){
					$tree = array_reverse($tree);
					$add_tree = join('/', $tree)."/";
				}
				//echo "Page id: ".$page_id." - ".$this->ap[$page_id]['title']."; subdomain: ".$this->ap[$page_id]['subdomain']." - ".$subdomain." -- ".$add_tree."<br>";

				// if no pages tree yet, but we have subdomains list - try to find subdomain
			} else */

            $main_lang = null;

			// check for main page and main language and correct links to them
			if ($this->_p->getLangs()){
                $langs = $this->_p->getLangs();
				reset($langs);
				$main_lang = key($langs);
                unset($langs);
			}
			$is_main_page = false;

			if ((is_array($arr_instance) && (($this->_p->getVar('router')['default_query_page'] == key($arr_instance) || $arr_instance['page'] == $this->_p->getVar('router')['default_query_page'])) && count($arr_instance) == 1) || !is_array($arr_instance)){
				if ($this->_p->getLangId() == $main_lang)
					$is_main_page = true;

                $arr_instance = array();
			}

			// adding link add
			if (!$is_main_page){

				$tmp_link_add = $this->getParts();
				$tmp_arr = is_array($arr_instance) ? array_keys($arr_instance) : array();
				foreach ($tmp_link_add as $k => $v){
					// try to sliced from query link addings
					if (in_array($k, $tmp_arr))
						unset($arr_instance[$k]);
						//array_splice($arr_instance, get_key_pos($arr_instance, $k), 1);
					// remove blank values
					if ($k == 'lang'){
						$result .= "$v/";
					} else if ($v != '')
						$result .= "$k/$v/";
				
				}

			}
						
			// set up flag for get name of page
			$get_page = false;

			// set up array flag
			$is_arr = false;

            if ($add_tree && is_array($add_tree))
                $add_tree = join('/', $add_tree);
            elseif ($add_tree && strpos($add_tree, '/') == 0)
                $add_tree = mb_substr($add_tree, 1, mb_strlen($add_tree)-1);
            elseif (!$add_tree)
                $add_tree = '';

			$result .= $add_tree.$this->_buildFriendly($arr_instance, $get_page, $is_arr, true);

			if ($content_type !== null){

                $result .= $this->_p->getVar('doc_types')[$content_type];
				
			} else {
                // if not set page name - remove last slash!
                //$result = substr($result, 0, strlen($result) - 1);
            }

		} else {

            // if using dynamic links
			$result = $this->_p->getVar('ROOT_CGI').'?';
			
			// adding link add
			$tmp_link_add = $this->getParts();
			//$tmp_arr = array_keys($arr);

            $tmp_result = array();

			foreach ($tmp_link_add as $k => $v){
				// try to sliced from query link addings
				//if (in_array($k, $tmp_arr))
				//	array_splice($arr, get_key_pos($arr, $k), 1);
				// added in ver 2.0 - remove blank values
				if ($v != '')
                    $tmp_result[$k] = $v;
					//$result .= "$k=$v&";
			
			}

            // setting up flag for get name of page
			$get_page = false;
			// setting up is we in array flag 
			$is_arr = false;

            $arr_instance = array_merge($tmp_result, $arr_instance);

			$result .= $this->_buildNonFriendly($arr_instance, $get_page, $is_arr);

            $result = mb_substr($result, 0, mb_strlen($result) - 1);

            // add first slash to the builded query
            if (mb_strpos($result, '/') !== 0){
                $result = '/'.$result;
            }
		
		}


        // adding full query if force or if subdomain enabled
        if ($subdomain != ''){

            $result = ($this->_p->getVar('router')['default_scheme'] ? $this->_p->getVar('router')['default_scheme'] : 'http')."://".$subdomain.'.'.($arr instanceof oQuery && $arr->getDomain() ? $arr->getDomain() : ($this->get()->getDomain() ? $this->get()->getDomain() : $this->_p->getVar('server_name'))).$result;

        } elseif ($is_full_query || ($this->_p->getVar('subdomains') && $this->get()->getSubdomain())){

            // get servername for current language
            reset($arr_instance);
            $first = current($arr_instance);

            $server_name = $this->_p->getLangs()[$this->_p->getLangId()]['server_name'] != '' ? $this->_p->getLangs()[$this->_p->getLangId()]['server_name'] : ($this->get()->getSubdomain()  && $first == $this->get()->getSubdomain() ? $this->get()->getSubdomain()."." : '').($this->get()->getDomain() ? $this->get()->getDomain() : $this->_p->getVar('server_name'));
            $result = ($this->_p->getVar('router')['default_scheme'] ? $this->_p->getVar('router')['default_scheme'] : 'http')."://".$server_name.$result;

        }

        unset($arr_instance);

        return $result;

	}

    /**
     * if you are using cms on another domain - you MUST set server_name variable on settings page
     * method determines server and domain
     * @param string|null server name for parsing
     * @return array
     */
    public function parseServerName($base_server_name = null){

		if ($this->_p->getVar('is_debug')){ 
			$this->_p->debug->add("QUERY: Before get server name");
		}		
		
		$match = array();
		
		$subdomain = '';


        if (!$base_server_name)
            $base_server_name = ($this->_p->getVar('server_name') ? $this->_p->getVar('server_name') : $_SERVER['HTTP_HOST']);

		// try to find server name
		$server_name = $base_server_name;

		if ($server_name){

			if ($this->_p->getVar('server_aliases') && is_array($this->_p->getVar('server_aliases')) && $this->_p->getVar('server_aliases')){
				foreach ($this->_p->getVar('server_aliases') as $v){
					if (preg_match('/(.*)\.'.preg_quote($v).'/', $server_name, $match)){
						$subdomain = $match[1];
						$server_name = $v;
					}
				}
			}

            if ($subdomain == '' && $this->_p->getVar('server_name') && strpos($base_server_name, $this->_p->getVar('server_name')) !== false && preg_match('/(.*)\.'.preg_quote($this->_p->getVar('server_name')).'/', $server_name, $match)){
				$subdomain = $match[1];
                $server_name = $this->_p->getVar('server_name');
            }

            // check subdomain in allowed subdomains list
            if ($subdomain){

                $subdomain_found = false;

                if ($this->_p->getVar('subdomains') && count($this->_p->getVar('subdomains'))){

                    for ($i = 0, $cnt = count($this->_p->getVar('subdomains')); $i < $cnt; $i++){

                        if ($subdomain == $this->_p->getVar('subdomains')[$i]){

                            $subdomain_found = true;
                            break;

                        }

                        /*$qt = preg_quote($this->_p->getVar('subdomains')[$i], "/");
                        if (preg_match("/".$qt."\.(.*)$/i", $server_name, $match)){
                            $subdomain = $this->_p->getVar('subdomains')[$i];
                        }*/

                    }

                }

                if (!$subdomain_found){
                    $subdomain = '';
                    $server_name = $base_server_name;
                }

            }

        }
		
		$return = array(
			'subdomain' => $subdomain,
			'domain' => $server_name
		);
		
		if ($this->_p->getVar('is_debug')){ 
			$this->_p->debug->add("QUERY: After get server name");
		}
		
		return $return;
	}

    /**
     * return selected query from collection
     * @param null $id
     * @return oQuery|null
     */
    public function get($id = null){

        if ($id === null)
            $id = count($this->_queries) - 1;

        return isset($this->_queries[$id]) ? $this->_queries[$id] : null;

    }

    /**
     * setter for query
     * @param oQuery $query
     * @return $this
     */
    public function set(oQuery $query){

        $this->_queries[] = $query;

        return $this;

    }

    /**
     * parse query string to array
     *
     * Query format:
     *	dynamic:	[?[lang=lang_id&][p=page][&key1=value1][&key2=value2]...]
     *	static:		[/][lang_ig/][page][/[key1]/[value1]][/[key2]]...[/[[page_name]]]]
     *
     * @param $str
     * @param boolean $only_parse[option] if set to true - only parse query and retrun parsed value
     * @param boolean $is_strict[option] if set to true - now subdomain was add
     * @return array
     */
    public function parse($str, $only_parse = false, $is_strict = false){

		$conn_lang = false;
		
		// prepare source for server name 
		$match = array();


        if (preg_match('/^http(?:s)?:\/\/([^\/]+)(.*)/i', $str, $match)){
            $_SERVER_NAME = $match[1];
            $str = $match[2];
        } else
            $_SERVER_NAME = $_SERVER['HTTP_HOST'];

        // prepare subdomains list
        if (!$this->_p->getVar('subdomains') && $this->_p->getVar('router')['model']){

            if (!is_array($subdomains = $this->_p->cache->getSerial('webt.subdomains')) && isset($this->_p->getVar('router')['model']) && $this->_p->getVar('router')['model']){

                $subdomains = array();

                $m = $this->_p->Model($this->_p->getVar('router')['model']);
                if ($m){
                    $sql = $this->_p->db->getQueryBuilder($m->getModelStorage())->compile($m, array('select' => 'nick', 'where' => array('is_subdomain' => 1)));

                    $res = $this->_p->db->select($sql, $m->getModelStorage());
                    if ($res){

                        foreach ($res as $arr)
                            $subdomains[] = $arr['nick'];

                    }
                    unset($res);
                }

                $this->_p->cache->saveSerial('webt.subdomains', $subdomains);
            }

            $this->_p->setVar('subdomains', $subdomains);
        }

        if (!$this->_p->getVar('subdomains'))
            $this->_p->setVar('subdomains', array());


        $server = $this->parseServerName($_SERVER_NAME);

        $types = array_flip($this->_p->getVar('doc_types'));
        reset($types);

        if ($this->_p->getVar('router')['is_friendly_URL']){
		
			$sep = '/';

			// remove all arguments from query
			$str = preg_replace(array('/\/+/is', '/(.*)(\?.*)$/is'), array('/', '$1'), $str);
			
			// crop last page_name
			// setting page name to the variable
			$matches = array();
			$pages = array();
		
			foreach ($this->_p->getVar('doc_types') as $v){
				$pages[] = preg_quote($v, "/")."$";
			}
			$pages = join('|', $pages);
	
			preg_match('/'.$pages.'/i' , $str, $matches);
			

			if (isset($matches[0])){
				$content_type = $types[$matches[0]];
				$str = preg_replace('/\/?'.$pages.'/i', '', $str);
			} else {
                $content_type = current($types);
			}
			
			$tmp_sep = preg_quote($sep, "/");
			
			$result = explode($sep, rawurldecode(preg_replace("/^".$tmp_sep."(.*)(?:".$tmp_sep.preg_quote($this->_p->getVar('doc_types')[$content_type], "/").")?$/", "$1", $str)));
			
			// parsing subkeys 
			$tmp_res = array();
					
			// cutting language
			if (isset($result[0]) && isset($this->_p->getLangTbl()[$result[0]])){

				$tmp_res['lang'] = $result[0];
				$result = array_slice($result, 1);
				// trying to change language
				// connecting languages
				$conn_lang = $tmp_res['lang'];
			
			}

			//echo "Subdomain :".$this->_p->vars['subdomain']."<br>";
			//echo (int)in_array($result[0], $this->_p->vars['reserved_pages']);
			//print_r($this->_p->vars['reserved_pages']);
			
			
			// if subdomain exists - adding them into query list, and checking in reserved vars
			if (!$is_strict && $server['subdomain'] && !in_array($result[0], $this->_p->getVar('reserved_pages'))){
				$result = array_merge(array($server['subdomain']), $result);
			}

			$i = 0;

            // remove last empty item
			if ($result[count($result)-1] == '')
                unset($result[count($result)-1]);

			$result = array_reverse($result);
			// cutting first empty

			//echo "BASE URI: ", print_r($result),"<br>";
			//echo "Count: ".count($result),"<br>";
			$v = '';
			foreach ($result as $k){
		
				if ($i == count($result) - 1){
					if (!$result[$i]){
						// setting current page
						$result[$i] = $this->_p->getVar('router')['default_query_page'] ? $this->_p->getVar('router')['default_query_page'] : 'main';

					}
				
					$tmp_res['page'] = $result[$i];
				
				} else {
					//echo "Prepare: $i - ".$k."<br>";
					// check is value odd
					if ($i % 2 || $i == count($result) - 2){
															
						// check if we have an array!!!
						if (in_array($k, array_keys($tmp_res))){
						
							if (is_array($tmp_res[$k])){
								$tmp_res[$k][] = $v;
							} else {
								$new_tmp = $tmp_res[$k];
								$tmp_res[$k] = array();
								$tmp_res[$k][0] = $new_tmp;
								$tmp_res[$k][1] = '';
							}
								
						
						} elseif ($k != '') {
						
							$tmp_res[$k] = $v;
						}
						$v = '';
				
					} elseif ($k != '') {
					
						$v = $k;

					}
				
				}
			
				$i++;
			
			}
			$tmp_res = array_reverse($tmp_res, true);

		} else {
		
			// if we using dynamic mode
			$arr = parse_url($str);

            $content_type = current($types);
			
			$tmp_res = array();
			if (isset($arr['query']))
				parse_str($arr['query'], $tmp_res);
			
			// cutting language
			reset($tmp_res);
			if (key($tmp_res) && key($tmp_res) == 'lang' && !empty($tmp_res['lang'])){
		
				// trying to change language
				// connecting languages
				$conn_lang = $tmp_res['lang'];
				$tmp_res = array_slice($tmp_res, 1);
			
			}
		}
			
		
		if (empty($tmp_res['page'])){
	
			// getting start page
			$tmp_res['page'] = $this->_p->getVar('router')['default_query_page'];
	
		}
		
		if (!$only_parse){
					
			// storing parsed query
			$this->_p->q = $tmp_res;


            if (!$is_strict){

                //$subdomain = '';

                //$found = false;

                /*for ($i = 0, $cnt = count($this->_p->getVar('subdomains')); $i < $cnt; $i++){

                    $qt = preg_quote($this->_p->getVar('subdomains')[$i], "/");
                    if (preg_match("/".$qt."\.(.*)$/i", $_SERVER_NAME, $match)){
                        $subdomain = $this->_p->getVar('subdomains')[$i];
                        // determine server name
                        if (!$this->_p->getVar('server_name')){
                            $this->_p->setVar('server_name', $match[1]);
                            $this->setDomain($match[1]);
                        }
                    }

                }*/

                //$this->setSubdomain($server['subdomain']);

                if (/*!$found && */!$this->_p->getVar('server_name')){
                    $this->_p->setVar('server_name', $_SERVER_NAME);
                    //$this->setDomain($server['domain']);
                }
            }

            $this->_queries[] = new oQuery($tmp_res, $content_type, $server['domain'], $server['subdomain']);

			// connect language if needed
			if ($conn_lang){
			
				$this->_p->initLangs($conn_lang);
			
			}
			
		}

        return $tmp_res;

	}

    /**
     * clone certain query
     * @param null $id
     * @return null|oQuery
     */
    public function cloning($id = null){

        if ($id === null){
            $id = count($this->_queries) - 1;
        }

        if (isset($this->_queries[$id]))
            return clone $this->_queries[$id];
        else
            return null;

    }

    /**
     * return stored queries count
     * @return int
     */
    public function count(){

        return count($this->_queries);

    }

    /**
     * getter of request
     * @return cProxy|webtRequest
     */
    public function getRequest(){

        return $this->request;

    }

    /**
     * setter of request
     * @param webtRequest $request
     * @return $this
     */
    public function setRequest(webtRequest $request){

        $this->request = $request;

        return $this;

    }


    /**
     * set part of the additional query
     * @param $key
     * @param $value
     * @return $this
     */
    public function setPart($key, $value){

        if ($key){
            $this->_parts[$key] = $value;
        }

        return $this;

    }

    /**
     * remove value from additional query parts
     * @param $key
     * @return $this
     */
    public function removePart($key){

        if (isset($this->_parts[$key])){
            unset($this->_parts[$key]);
        }

        return $this;
    }

    /**
     * get part of the query
     * @param $key
     * @return null
     */
    public function getPart($key){

        if (isset($this->_parts[$key])){

            return $this->_parts[$key];

        } else {

            return null;

        }

    }

    public function getParts(){

        return $this->_parts;

    }



    /**
     * method parses headers from the array (like a $_SERVER)
     * @param array $data
     * @return array
     */
    public function parseHeaders($data = null){

        if (!$data)
            $data = $_SERVER;

        $headers = array();
        $contentHeaders = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);
        foreach ($data as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (isset($contentHeaders[$key])) {
                $headers[$key] = $value;
            }
        }

        if (isset($data['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $data['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($data['PHP_AUTH_PW']) ? $data['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($data['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $data['HTTP_AUTHORIZATION'];
            } elseif (isset($data['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $data['REDIRECT_HTTP_AUTHORIZATION'];
            }

            if (null !== $authorizationHeader) {
                if (0 === stripos($authorizationHeader, 'basic')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)));
                    if (count($exploded) == 2) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                } elseif (empty($data['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest'))) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
                    $data['PHP_AUTH_DIGEST'] = $authorizationHeader;
                }
            }
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic '.base64_encode($headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW']);
        } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }

        return $headers;
    }




    /**
     * method add routing to the collection
     * @param $name
     * @param oRoute $Route
     * @throws \Exception
     */
    public function addRoute($name, oRoute $Route){

        if (!isset($this->_routes[$name])){
            $this->_routes[$name] = $Route;
        }/* else {
            throw new \Exception('errors.router.route_exists');
        }*/

    }

    /**
     * extract from routers collection routing
     * @param $name
     * @return null|oRoute
     */
    public function getRoute($name){

        if (isset($this->_routes[$name])){
            return $this->_routes[$name];
        } else
            return null;

    }

    /**
     * method starts route parsing
     * @param webtRequest $request
     * @return mixed
     * @throws \Exception
     */
    public function route(webtRequest $request = null){

        if (!$request){
            $request = $this->request;
        }

        if ($this->_routes){

            // detect locales
            $locales = join('|', array_keys($this->_p->getLangTbl()));

            $uri = $request->getUri();
            if ($locales && preg_match('#^/(?:'.$locales.')(/.*)$#', $uri, $matches)){
                $uri = $matches[1];
            }

            /**
             * @var oRoute $route
             */
            foreach ($this->_routes as $route){

                // prepare all requirements
                $replaces = array();
                if ($route->getRequirements()){
                    $replaces = array_merge($replaces, $route->getRequirements());
                }

                $path = $route->getPath();
                foreach ($replaces as $k => $v){
                    $path = str_replace('{'.$k.'}', '(?<'.$k.'>'.$v.')', $path);
                }

                // replace other items
                //dump($path);
                $path = preg_replace('#\{(.*?)\}#is', '(?<$1>.*?)', $path);

                if (preg_match('#^'.$path.'#is', $uri, $matches)){

                    // detect controller
                    $defaults = $route->getDefaults();
                    if (isset($defaults['_controller'])){
                        $argv = array(&$this->_p);
                        if ($matches && count($matches) > 1){
                            foreach ($matches as $k => $v){
                                if (!is_numeric($k)){
                                    $argv[] = $v;
                                }
                            }
                        }

                        if (is_callable($defaults['_controller'])){
                            return call_user_func_array($defaults['_controller'], $argv);
                        } else {
                            // detect right application/controller
                            $controller = explode(':', $defaults['_controller']);

                            if (file_exists($this->_p->getVar('APP_DIR').$this->_p->getVar('bundles_dir').$controller[0].WEBT_DS.ucfirst($this->_p->getVar('apps_dir'))))
                                $app_path = $this->_p->getVar('APP_DIR').$this->_p->getVar('bundles_dir').$controller[0].WEBT_DS.ucfirst($this->_p->getVar('apps_dir'));
                            else
                                $app_path = $this->_p->getVar('APP_DIR').$this->_p->getVar('bundles_dir').$controller[0].WEBT_DS.$this->_p->getVar('apps_dir');

                            if (file_exists($app_path.$controller[1].'.app.php'))
                                $c = $controller[1];
                            else
                                $c = mb_strtolower($controller[1]);

                            //dump($app_path.mb_strtolower($controller[1]).'.app.php');
                            if (file_exists($app_path) &&
                                is_dir($app_path) &&
                                file_exists($app_path.$c.'.app.php')){

                                // connect app
                                require_once($app_path.$c.'.app.php');

                                $app_name = '\\'.$controller[0].'\Apps\\'.$c;

                                $app = new $app_name($this->_p);
                                if (method_exists($app, $controller[2])){
                                    return call_user_func_array(array($app, $controller[2]), $argv);
                                } else {
                                    throw new \Exception('errors.router.no_method_exists');
                                }

                            } else {

                                throw new \Exception('errors.router.no_controller_exists');

                            }
                        }


                    }

                }
            }
        } else {

            throw new \Exception('errors.router.no_routes_defined');

        }

        throw new \Exception('errors.router.no_route_found');

    }


}
