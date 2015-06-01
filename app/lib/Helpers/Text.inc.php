<?php
/**
 * Texts helper for web-T::CMS
 * Date: 19.02.12
 * Time: 16:38
 * @author goshi
 * @version 1.3
 * @package web-T[share]
 *
 * Changelog:
 * 2.0  25.02.15/goshi  refactor
 * 1.4  30.05.13/goshi  add protection for typograph work
 * 1.3	11.06.12/goshi	add restoreLinks
 * 1.2	10.06.12/goshi	fixing remove bad words
 * 1.1	15.04.12/goshi	add cleanupEmptyParagraphs
 * 1.0  19.02.12/goshi
 */

namespace webtFramework\Helpers;

use webtFramework\Core\oPortal;


class Text {


    /**
     * check text for bad words
     * @static
     * @param oPortal $p
     * @param string $text text for processing
     * @param string $mode mode of processing - can be 'exception' or 'replace'
     * @return bool
     */
    static function checkBadWords(oPortal &$p, &$text, $mode = 'exception'){

		// getting last issue number
		if (!($from = $p->cache->getSerial('bad_vocabulary'))){

            $model = $p->Model('webtCMS:BadVocabulary');
            $sql = $p->db->getQueryBuilder($model->getModelStorage())->compile($model, array('no_array_key'));
			$bad_words = $p->db->select($sql, $model->getModelStorage());

			// prepare replace and normalize
			$from = array();
			if (!empty($bad_words)){
				$before = '(^|[^\p{L}])';
				$after = '([^\p{L}]|$)';
				foreach ($bad_words as $v){
					$from[] = '/'.$before.preg_quote($v['title']).$after.'/isu';
				}
			}

            $p->cache->saveSerial('bad_vocabulary', $from);
		}
		//dump_file($from, false);
		// prepare preg_match
		foreach ($from as $v){
			if (preg_match($v, $text)){
				switch ($mode){

					case 'replace':
						$text = preg_replace($v, ' $1***$2', $text);
						break;

					case 'exception':
					default:
						return false;
						break;
				}
			}
		}

		return true;
	}

    /**
     * Method for check unique text with shingles
     * @static
     * @param $text1
     * @param $text2
     * @param array $options consists of ('max_shigles_length' => 'maximum shingles for check')
     * @return float|int
     */
    static function checkUniqueText($text1, $text2, $options = array()){

		if (!isset($options['max_shigles_length'])){
			$options['max_shigles_length'] = 5;
		}

		if (trim($text1) == '' || trim($text2) == '')
			return 0;

		$min_diff = 0;
		for ($i = 1; $i < $options['max_shigles_length']; $i++) {
			$first_shingles = array_unique(self::getShingles($text1, $i));
			$second_shingles = array_unique(self::getShingles($text2, $i));
			//dump($first_shingles, false);
			//dump($second_shingles);

			if (count($first_shingles) < $i-1 || count($second_shingles) < $i-1) {
				//echo "Количество слов в тексте меньше чем длинна шинглы<br />";
				continue;
			}

			$intersect = array_intersect($first_shingles, $second_shingles);

			$merge = array_unique(array_merge($first_shingles, $second_shingles));

			$diff = (count($intersect)/count($merge))/0.01;

			if ($diff > $min_diff)
				$min_diff = $diff;

			if (round($diff, 2) >= 100){
				//dump($text1, false);
				//dump($text2, false);
				//echo "Количество слов в шингле - $i. Процент схожести - ".round($diff, 2)."%\r\n";
			}
		}

		return round($min_diff, 2);
	}

    /**
     * method get shingles from text
     * @param $text
     * @param int $n
     * @return array
     */
    static function getShingles($text, $n=3){

		$shingles = array();
		$text = self::cleanupTags($text);
		$text = preg_replace("/[,|\.|'|\"|\\|\/]/i", "",$text);
		$text = preg_replace("/[\n|\t]/i", " ",$text);
		$text = preg_replace('/(\s\s+)/', ' ', trim($text));
		$elements = explode(" ",$text);
		for ($i=0;$i<(count($elements)-$n+1);$i++) {
			$shingle = '';
			for ($j=0;$j<$n;$j++){
				$shingle .= mb_strtolower(trim($elements[$i+$j]), 'UTF-8')." ";
			}
			if(strlen(trim($shingle)))
				$shingles[$i] = trim($shingle, ' -');
		}
		return $shingles;

	}


	/**
	 * convert string to lower
	 */
	static function lower($text){

		return str_replace(array('Й','Ц','У','К','Е','Н','Г','Ш','Щ','З','Х','Ъ','Ф','Ы','В','А','П','Р','О','Л','Д','Ж','Э','Я','Ч','С','М','И','Т','Ь','Б','Ю','Ї','І','Є','Q','W','E','R','T','Y','U','I','O','P','A','S','D','F','G','H','J','K','L','Z','X','C','V','B','N','M'), array('й','ц','у','к','е','н','г','ш','щ','з','х','ъ','ф','ы','в','а','п','р','о','л','д','ж','э','я','ч','с','м','и','т','ь','б','ю','ї','і','є','q','w','e','r','t','y','u','i','o','p','a','s','d','f','g','h','j','k','l','z','x','c','v','b','n','m'), $text);

	}


	/**
	 * convert string to upper
	 */
	static function upper($text){
		return str_replace( array('й','ц','у','к','е','н','г','ш','щ','з','х','ъ','ф','ы','в','а','п','р','о','л','д','ж','э','я','ч','с','м','и','т','ь','б','ю','ї','і','є','q','w','e','r','t','y','u','i','o','p','a','s','d','f','g','h','j','k','l','z','x','c','v','b','n','m'),
			array('Й','Ц','У','К','Е','Н','Г','Ш','Щ','З','Х','Ъ','Ф','Ы','В','А','П','Р','О','Л','Д','Ж','Э','Я','Ч','С','М','И','Т','Ь','Б','Ю','Ї','І','Є','Q','W','E','R','T','Y','U','I','O','P','A','S','D','F','G','H','J','K','L','Z','X','C','V','B','N','M'),
			$text);
	}


	/**
	 * transliterate string
	 */
	static function transliterate($cyrstr, $force_change = true, $params = array()){
		$ru = array('А', 'а',
			'Б', 'б',
			'В', 'в',
			'Г', 'г',
			'Д', 'д',
			'Е', 'е',
			'Ё', 'ё',
			'Ж', 'ж',
			'З', 'з',
			'И', 'и',
			'Й', 'й',
			'К', 'к',
			'Л', 'л',
			'М', 'м',
			'Н', 'н',
			'О', 'о',
			'П', 'п',
			'Р', 'р',
			'С', 'с',
			'Т', 'т',
			'У', 'у',
			'Ф', 'ф',
			'Х', 'х',
			'Ц', 'ц',
			'Ч', 'ч',
			'Ш', 'ш',
			'Щ', 'щ',
			'Ъ', 'ъ',
			'Ы', 'ы',
			'Ь', 'ь',
			'Э', 'э',
			'Ю', 'ю',
			'Я', 'я',
			'Ї', 'ї',
			'І', 'і',
			'Є', 'є',
			' ', '\'');


		$en = array('A', 'a',
			'B', 'b',
			'V', 'v',
			'G', 'g',
			'D', 'd',
			'E', 'e',
			'E', 'e',
			'Zh', 'zh',
			'Z', 'z',
			'I', 'i',
			'J', 'j',
			'K', 'k',
			'L', 'l',
			'M', 'm',
			'N', 'n',
			'O', 'o',
			'P', 'p',
			'R', 'r',
			'S', 's',
			'T', 't',
			'U', 'u',
			'F', 'f',
			'H', 'h',
			'C', 'c',
			'Ch', 'ch',
			'Sh', 'sh',
			'Sch', 'sch',
			'', '',
			'Y', 'y',
			'', '',
			'E', 'e',
			'Ju', 'ju',
			'Ja', 'ja',
			'Ji', 'ji',
			'I', 'i',
			'Je', 'je',
			'-', '');
		// check for force change
        $is_string = false;
        if (!is_array($cyrstr)){
            $cyrstr = array($cyrstr);
            $is_string = true;
        }

        foreach ($cyrstr as $k => $v){
            if ($force_change){
                $tmp = preg_replace((!empty($params) && $params['fieldReg'] ? $params['fieldReg'] : '/[^a-zA-Z0-9-.]/is'), '', strtolower(str_replace($ru, $en, $v)));
                $tmp = preg_replace('/-{1,}/is', '-', $tmp);
                // delete last separator
                $cyrstr[$k] = preg_replace('/(.*)-$/is', '$1', $tmp);
            } else {
                $cyrstr[$k] = str_replace($ru, $en, $v);
            }
        }

        if ($is_string){
            return $cyrstr[0];
        } else {
            return $cyrstr;
        }
	}

    /**
     * special method for transliterate fields names
     * @static
     * @param $cyrstr
     * @param bool $force_change
     * @param array $params
     * @return mixed
     */
    static function transliterate_field($cyrstr, $force_change = true, $params = array()){
        return str_replace('-', '_', Text::transliterate($cyrstr, $force_change, $params));
    }

	/**
	 * Entity escaping for xml sitemaps
	 *
	 * @access public
	 */
	static function escapeEntities($url){

		$url = str_replace(
			array("&", "'", '"', ">", "<"),
			array("&amp;", "&apos;", "&quot;", "&gt;", "&lt;"),
			$url);

		return $url;

	}

    /**
     * strip non-allowed HTML tags from string
     * @param $str
     * @param array $allowed_tags
     * @return string
     */
    static function stripSelectedHTMLTags($str, $allowed_tags = array()){

        $allowed_tags = array_map(function($tag) {return '<'.$tag.'>';}, $allowed_tags);
        return strip_tags($str, join(',', $allowed_tags));

    }

    /**
     * cleanup string from HTML tags, special symbols and framework constructions
     * @param $str
     * @return array|mixed|string
     */
    static function cleanupTags($str){

        if (is_array($str)){

            foreach ($str as $k => $v){

                $str[$k] = self::cleanupTags($v);

            }

            return $str;

        } else {

            if (trim($str) == '') return $str;

            $str = preg_replace("/(<([^>])*>|>|<)/Uis", "", $str);
            $str = preg_replace(array("/\[video_.+?\]/U", "/\[audio_.+?\]/U", "/\[news_.+?\]/U", "/\[banner_.+?\]/U", "/\[file_.+?\]/U", "/\[p=.+?\]/U", '/\[\$[A-Z_]+\]/is'),"",$str);

            return $str = html_entity_decode(str_replace(
                array("&#096;",
                    "&#039;",
                    "&nbsp;",
                    "&ndash;",
                    '&quot;'),
                array("`",
                    "'",
                    " ",
                    "-",
                    '"'),
                $str), ENT_QUOTES, 'utf-8');

        }


    }


	/**
	 * cleanup all entities in text
	 *
	 * @static
	 * @param $str
	 * @param string $to
	 * @return mixed
	 */
	static function cleanupEntities($str, $to = ''){

		return 	preg_replace('/&(#)?[a-zA-Z0-9]{0,};/', $to, $str);

	}

	/**
	 * cleanup trim all spaces
	 */
	static function cleanupRepeat($descr){

		//$descr = mb_ereg_replace("(\s){2,}", '\\1', $descr, 'mi');
		$descr = preg_replace('/(\s){2,}/uis', '$1', $descr);

		/** function potential dangerous for digital numbers **/
		//$descr = preg_replace('/( ?.)\1{4,}/is', '$1$1$1', $descr);
		// experimental !!! cleanup all letters, which more, then 3
		//$descr = preg_replace('/(.?)\1{3,}/Uis', '$1', $descr);
		return $descr;
	}

	/**
	 * cleanup quotes
	 */
	static function cleanupQuotes($descr, $replace = '"'){

		//return str_replace(array("'", "“", "”", "‘", "’", "&lsquo;", "&rsquo;", "&ldquo;", "&rdquo;", "«", "&laquo;", "&#171;", "»", "&raquo;", "&#187;"), $replace, $descr);
		return str_replace(array("“", "”", "‘", /*"’",*/ "&lsquo;", "&rsquo;", "&ldquo;", "&rdquo;", "«", "&laquo;", "&#171;", "»", "&raquo;", "&#187;"), $replace, $descr);
	}


	/**
	 * cleanup dashes in text
	 */
	static function cleanupDash($descr, $r = "&mdash;"){

		return str_replace(
			array(" - ",       ",- ",     "&nbsp;- ", " &ndash; ", " – "),
			array(" ".$r." ", ",".$r." ", " ".$r." ", " ".$r." ", " ".$r." "),
			$descr);
	}

	/**
	 * cleanup empty paragraphs
	 * @static
	 * @param $descr
	 * @return mixed
	 */
	static function cleanupEmptyParagraphs($descr){

		preg_match('/<p[^>]*>\p{Z}<\/p>/uis', $descr, $match);
		//dump_file($match);
		return preg_replace('/<p[^>]*>\p{Z}<\/p>/uis', '', $descr);

	}


	/**
	 * cleanup messy M$ code
	 */
	static function cleanupMicrosoft($descr){

		$descr = preg_replace(array('/<link rel="File-List" href="file:\/\/\/[^"]*">/isU', '/\slang="[^"]+"/isU'), '', $descr);
		$descr = str_replace(array('<!--[if [^\]]+]-->', 'class="MsoNormal"', '<!--StartFragment-->', '<!--EndFragment-->', 'class="MsoNormalTable"', 'mso-bidi-font-style: normal;'), '', $descr);

		//$descr = preg_replace('/^\s*(.*)\s*$/isU', '$1', $descr);
		$descr = preg_replace('/<!--\[if gte mso \d+\]>.*?<!\[endif\]-->/isU', '', $descr);
		$descr = preg_replace('/<!--\?xml:namespace[^>]+-->/isU', '', $descr);

		return $descr;
	}


	/**
	 * convert all br to p
	 */
	static function br2p($string){
		//str_replace(array('<br>', '<br/>', '<br />'), '',
		return str_replace(array('<p><p', '</p></p>', '<p><P', '<P><p', '<P><P', '</P></p>', '</p></P>', '</P></P>'), array('<p', '</p>', '<p', '<p', '<p', '</p>', '</p>', '</p>'), preg_replace('#<p>[\n\r\s]*?</p>#Um', '', '<p>'.preg_replace('#(<br\s*?/?>){2,}#Um', '</p><p>', trim($string, "\t\r\n\x7f..\xff\x0..\x1f")).'</p>'));
	}

	/**
	 * calculating symbols in string
	 */
	static function calcLangChars($value, $lang, $in_percent = false){

		switch ($lang){

			case 'ru':
				$reg = '[^ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮЇІЄйцукенгшщзхъфывапролджэячсмитьбюїіє0-9\s]';
				$new_value = mb_ereg_replace($reg, '', $value, 'i');
				break;

			case 'en':
			default:
				$reg = '/[^A-Za-z0-9\s]/Uis';
				$new_value = preg_replace($reg, '', $value);
				break;
		}

		return array('non_lang_len' =>  $in_percent ? (mb_strlen($value) - mb_strlen($new_value))/mb_strlen($value)*100 : mb_strlen($new_value), 'total_len' => mb_strlen($value));
	}


	/**
	 * calculating uppercase symbols in string
	 */
	static function calcLangUpperChars($value, $lang, $in_percent = false){

		switch ($lang){

			case 'ru':
				$reg = '[^ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮЇІЄ]';
				$new_value = mb_ereg_replace($reg, '', $value, 'i');
				break;

			case 'en':
			default:
				$reg = '/[^A-Z]/Us';
				$new_value = preg_replace($reg, '', $value);
				break;
		}

		return array('non_caps_len' =>  $in_percent ? (mb_strlen($value) - mb_strlen($new_value))/mb_strlen($value)*100 : mb_strlen($new_value), 'total_len' => mb_strlen($value));
	}


	/**
	 * method restore broken links in text
	 */
	static function restoreLinks($data){

		if ($data != ''){

			$bits = preg_split('/(<a(?:\s+[^>]*)?>.*?<\/a>|<[a-z][^>]*>)/isU', $data, null, PREG_SPLIT_DELIM_CAPTURE);

			/*$GLOBALS['temp_links'] = array();

			if (!function_exists('_add_markers')){
				function _add_markers($m){
					//global $_temp_links;
					static $i=-1;
					$k = " ~~~".++$i."~~~ ";
					$GLOBALS['temp_links'][$k] = $m[0];
					return $k;
				}
			}

			$data = preg_replace_callback('~<(a)[^>]*>.+<\/\1>~isU', '_add_markers', $data);

			preg_match('/((?:(?!(?:(?:https?|ftp):\/\/|www.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|])[^<])*?)/is', $data, $match);

			dump($match);

			$bits = preg_split('/(<a(?:\s+[^>]*)?>.*?<\/a>|<[a-z][^>]*>)/is', $data, null, PREG_SPLIT_DELIM_CAPTURE);
			*/

			$reconstructed = '';

			if (is_array($bits)){

				foreach ($bits as $bit) {
					if (strpos($bit, '<') !== 0) {//not inside an <a> or within < and > so check for urls
						$bit = preg_replace('/((?:https?|ftp):\/\/[^\s<]+)/is', '<a href="$1">$1</a>', $bit);
						//dump(htmlspecialchars($bit), false);
					}
					$reconstructed .= $bit;
				}
			}
			//dump(htmlspecialchars($reconstructed));
			$data = $reconstructed;

		}
		return $data;
	}


    /**
     * method remove links from plain text (need for title)
     * @static
     * @param $data
     * @return mixed
     */
    static function removeLinks($data){

        if ($data != ''){

            $data = preg_replace('/((?:https?|ftp):\/\/[^\s<]+)/is', '', $data);

        }
        return $data;
    }


    /**
     * cleanup empty paragraphs
     * @static
     * @param string $descr
     * @param oPortal $p
     * @return string
     */
    static function typograph($descr = '', oPortal &$p){

		// check for maxlength of the text
		if (trim($descr) != '' && mb_strlen(strip_tags($descr), $p->getVar('codepage')) < 50000){

            // checking for resulting length
            $base_length = mb_strlen($descr, $p->getVar('codepage'));
            if ($base_length){
                $base_descr = $descr;
                $descr = $p->Module('oWeb')->init()->post('http://www.typograf.ru/webservice/', array('chr' => 'UTF-8', 'text' => $descr));
                //dump(htmlspecialchars($descr), false);
                // protection from bad typograph work
                if (mb_strlen($descr)/$base_length*100 < 94){
                    $descr = $base_descr;
                }
            }

		}

		return $descr;
	}

    /**
     * filter for remove <pre> tag from text
     * @static
     * @param string $descr
     * @param oPortal $p
     * @return mixed|string
     */
    static function removePreTag($descr = '', oPortal &$p){
        // check length
        if (trim($descr) != '' ){

            $descr = str_replace(array('<pre>', '</pre>'), array('<p>', '</p>'), $descr);
            //dump(htmlspecialchars($descr), false);

        }

        return $descr;
    }

}

