<?php

/**
* web-T::CMS language working package
* @version 3.7
* @author goshi
* @package web-T[share]
*/

namespace webtFramework\Services;

use webtFramework\Core\oPortal;


/**
* Class clsLanguages definition
* @package web-T[share]
*/
class oLanguages{

	/**
	 * @var oPortal
	 */
	private $_p;

	// constructor
	public function __construct(oPortal &$p){
		$this->_p = $p;
	}

    /**
     * generate language table
     * @param array $params
     * @return array
     */
    public function getLangs($params = array()){

		if (!($all_langs = $this->_p->cache->getSerial('webt.langs'))){

            if (isset($this->_p->getVar('translator')['model']) && $this->_p->getVar('translator')['model']){

                $er = $this->_p->db->getManager()->getRepository($this->_p->getVar('translator')['model']);

                $res = $er->findBy(array('where' => array('is_on' => 1), 'order' => array('weight' => 'desc')));

                $all_langs = array();

                if ($res){

                    foreach ($res as $k => $v){

                        $all_langs[$k] = $v->getModelData();
                        if ($v->getPicture() && (!$params['native'] && $picture = $v->getPictures('picture')))
                            $all_langs[$k]['picture'] = $picture;

                        $all_langs[$k]['aliases'] = explode(',', str_replace(array(' ', ';'), array('', ','), $v->getAliases()));
                    }

                    if (!$params['native'])
                        $this->_p->cache->saveSerial('webt.langs', $all_langs);
                }
                unset($er);

            }

		}
		$lang_tbl = false;
		$codepage_tbl = false;
		$old_lang = $this->_p->getLangNick();

		if ($all_langs){

            $lang_vars = $langs_table = array();

			foreach ($all_langs as $arr){
						
				if ($this->_p->getVar('is_multilang')){
					$this->_p->query->setPart('lang', $arr['nick']);
                    $this->_p->setLangNick($arr['nick']);
                }
				$lang_tbl[$arr['nick']] = $arr['lang_pack'];
				
				if (is_string($arr['aliases']) && trim($arr['aliases']) != ''){
					$arr['aliases'] = explode(',', str_replace(array(';', ' '), array(',', ''), $arr['aliases']));
				}

                $lang_vars[$arr['id']] = array(
						'href' => ($arr['server_name'] != '' && !$params['native'] ? 'http://'.$arr['server_name'] : '').$this->_p->query->build($this->_p->query->get() ? $this->_p->query->get()->get() : null).($_SERVER['QUERY_STRING'] != ''? '?'.$_SERVER['QUERY_STRING'] : ''),
						'picture' => $arr['picture'],
						'nick' => $arr['nick'],
						'is_publish' => $arr['is_publish'],
						'aliases' => (array)$arr['aliases'],
						'title' => $arr['title'],
						'server_name' => $arr['server_name'],
						'main' => false,
						'altname' => $arr['altname'],
						);
                $langs_table[$arr['nick']] = $arr['id'];
				$codepage_tbl[$arr['nick']] = $arr['codepage'];
			
			}

            $this->_p->setLangs($lang_vars);
            $this->_p->setLangTbl($langs_table);
            unset($lang_vars);
            unset($langs_table);
		}

		$this->_p->setLangNick($old_lang);
		return array($lang_tbl, $codepage_tbl);
			
	}

    /**
     * loading text messages from database
     *
     * @param string $lang language's nick
     * @return array|bool|mixed|null|void
     */
    public function loadTextMessages($lang){

        if (!($db_langs = $this->_p->cache->getSerial('text_const_'.$this->_p->getLangTbl()[$lang])) &&
            isset($this->_p->getVar('translator')['translates_model']) &&
            $this->_p->getVar('translator')['translates_model']
        ){

            $er = $this->_p->db->getManager()->getRepository($this->_p->getVar('translator')['translates_model']);

            $db_langs = $er->findBy(array(
                'where' => array(
                    'is_on' => 1,
                    'lang_id' => $this->_p->getLangTbl()[$lang]
                )
            ), $er::ML_HYDRATION_ARRAY);

            $new_lang = array();

            if ($db_langs){
                foreach ($db_langs as $v){
                    if ($v['title2'] != ''){
                        if (!isset($new_lang[$v['title']]))
                            $new_lang[$v['title']] = array();
                        $new_lang[$v['title']][$v['title2']] = $v['descr'];
                    } else
                        $new_lang[$v['title']] = $v['descr'];
                }
            }
            $db_langs = $new_lang;
            unset($er);

            $this->_p->cache->saveSerial('text_const_'.$this->_p->getLangTbl()[$lang], (array)$db_langs);
        }

        return $db_langs;

    }
	
    /**
     * load selected language
     * @param $lang
     * @param string $bundle
     * @return array|string array of language constants
     */
    public function get(&$lang, $bundle = 'Frontend'){

		list($lang_table, $codepage_tbl) = $this->getLangs();
		
		if ($this->_p->getVar('is_debug')){
					
			$this->_p->debug->add("LANGUAGE: After parse _lang_table_pack");
					
		}
		
		$LANGUAGE = "";

        if (!$bundle)
            $bundle = 'Frontend';

        $bundle = preg_replace('/[^0-9a-zA-Z_]/is', '', $bundle);

		$dir = $this->_p->getVar('bundles_dir').$bundle.WEBT_DS.$this->_p->getVar('translator')['languages_dir'];

        $filename = '';
		if (isset($lang_table[$lang]))
            $filename = $lang_table[$lang];
		// $lang_dir must exists

		if (file_exists($dir.$filename) && is_file($dir.$filename)){
			include($dir.$filename);
        } elseif (file_exists($dir.$filename.'.php') && is_file($dir.$filename.'.php')){
            include($dir.$filename.'.php');
		} else {
			if (is_array($lang_table)){
				$lang = key($lang_table);
                if (file_exists($dir.$lang_table[$lang]))
				    include($dir.$lang_table[$lang]);
                elseif (file_exists($dir.$lang_table[$lang].'.php')){
                    include($dir.$lang_table[$lang].'.php');
                }
			}
		}
		
		if ($this->_p->getVar('is_debug')){
					
			$this->_p->debug->add("LANGUAGE: After require lang file");
					
		}
		
		// loading from database language
        $db_langs = $this->loadTextMessages($lang);
        if (is_array($db_langs))
            $LANGUAGE = array_merge_recursive_distinct((array)$LANGUAGE, $db_langs);

		// setting language of the portal and it is codepage
        $langs = $this->_p->getLangs();
        $langs[$this->_p->getLangTbl()[$lang]]['main'] = true;
        $this->_p->setLangs($langs);
        $this->_p->setLangNick($lang);
		$this->_p->setVar('codepage', $codepage_tbl[$lang]);
		$this->_p->setLangId($this->_p->getLangTbl()[$lang]);
		// hack for multibyte strings
		if (is_array($codepage_tbl))
			mb_internal_encoding($codepage_tbl[$lang]);
		
		return $LANGUAGE;
	
	}

    /**
     * translating phrase to current language
     * you can define something like this "param1.param2.param3" for arrays
     *
     * @param string $phrase transalte phrase
     * @param array $vars replacements for variables in `key` => 'value' format
     * @return null|string
     */
    public function trans($phrase, $vars = array()){

        $parray = (array)explode('.', $phrase);

        $translate = null;

        if (isset($this->_p->m[$parray[0]])){

            $translate = $this->_p->m[$parray[0]];
            foreach ($parray as $level => $m){
                if ($level > 0){
                    if (isset($translate[$m])){
                        $translate = $translate[$m];
                    } else {
                        break;
                    }
                }
            }
        } else {
            $translate = $phrase;
        }

        // replace variables
        if ($translate !== null && !is_array($translate) && !empty($vars) && is_array($vars)){
            foreach ($vars as $k => $v){
                $vars['%'.$k.'%'] = $v;
                unset($vars[$k]);
            }

            $translate = str_replace(array_keys($vars), $vars, $translate);
        }

        return $translate !== null && !is_array($translate) ? (string)$translate : $phrase;

    }

    /**
     * translate choice
     *
     * @param $phrase
     * @param $count
     * @return mixed|null
     */
    public function transChoice($phrase, $count){

        $mess = $this->getMessage($phrase);

        if ($mess){

            if (!is_array($mess))
                return $mess;

            // getting days count
            $d_cnt = abs($count) % 100;
            $n1 = $d_cnt % 10;
            if ($d_cnt > 10 && $d_cnt < 20)
                return isset($mess[5]) ? $mess[5] : $mess[1];

            if ($n1 > 1 && $n1 < 5) return $mess[2];
            if ($n1 == 1)
                return $mess[1];
            else
                return isset($mess[5]) ? $mess[5] : $mess[2];

        } else {

            return null;

        }

    }


    /**
     * in order to get string translation from method "trans", this method return real value, which can found in the
     * messages array
     * @param string $phrase
     * @return null|mixed
     */
    public function getMessage($phrase){

        $parray = (array)explode('.', $phrase);

        $translate = null;

        if (isset($this->_p->m[$parray[0]])){
            $translate = $this->_p->m[$parray[0]];

            foreach ($parray as $level => $m){
                if ($level > 0){
                    if (isset($translate[$m])){
                        $translate = $translate[$m];
                    } else {
                        break;
                    }
                }
            }
        }

        return $translate !== null ? $translate : $phrase;

    }

}
