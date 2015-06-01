<?php

use webtFramework\Core\oPortal;
use webtFramework\Helpers\Text;

/**
* functions library for web-T::CMS
* @author goshi
* @package web-T[share]
*/


/**
* function return ip-addresss in special format
*/
function get_ip($is_light = false){
	
	if (!$is_light)
		$tmp = isset($_SERVER["REMOTE_HOST"]) ? "/".$_SERVER["REMOTE_HOST"] : "";
	else
		$tmp = '';
	return Text::cleanupTags((isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER["REMOTE_ADDR"]).$tmp);

}


/**
* function return htttp-forward in special format
*/
function get_http_forward(){
	
	$forward = getenv('HTTP_X_FORWARDED_FOR');
	if (empty($forward) || $forward == $_SERVER['REMOTE_ADDR']) $forward = '';

	return Text::cleanupTags($forward);

}



	
// function return position of the key in array
function get_key_pos($arr, $key_name){

	$i = 0;
	
	foreach ($arr as $k => $v){
	
		if ($k == $key_name) return $i;
		$i++;
	
	}
	
	// find nothing
	return '-1';
}

 
/* 
* getimage extension by its mime type (from getimagesize)
*/
function get_img_ext($img_type){
	
	switch ($img_type){
	
		case 1: $return = ".gif"; break;
		case 2: $return = ".jpg"; break;
		case 3: $return = ".png"; break;
		case 4: $return = ".swf"; break;
		case 5: $return = ".psd"; break;
		case 6: $return = ".bmp"; break;
		case 7: $return = ".tif"; break;
		case 8: $return = ".tif"; break;
		case 9: $return = ".jpc"; break;
		case 10: $return = ".jp2"; break;
		case 11: $return = ".jpx"; break;
		case 12: $return = ".jb2"; break;
		case 13: $return = ".swc"; break;
		case 14: $return = ".iff"; break;
		case 15: $return = ".wbmp"; break;
		case 16: $return = ".xbm"; break;
		
		default: $return = false;
	}
	
	return $return;
}







/////////////////////////////////////////////     search functions    /////////////////////////////////
/* function create sql query for search fields
 It makes query case insesitive
 Now works only with PostGRE and MySQL
 @version 2.3

Changelog:
	2.3	26.06.10/goshi	add innermode for inner fields
	2.2	26.10.09/goshi	fix bug with quoting parameters
	2.1	28.08.09/goshi	fix bug with enabled prefix in search field
	2.0	29.01.09/goshi	add fulltext index search
 	1.1	25.10.08	added LIKE for MYSQL instead of RLIKE
 	
 INPUT: sfields	-	array of search fields
	  keywords	-	array of keywords
	  wmode		-	mode for search (AND or OR)
	  tbl_prefix	-	prefix for tbl use
*/
/**
 * @param $item
 * @param $key
 * @deprecated
 */
function quote(&$item, $key){
	$item = "+".qstr($item)."";
}

/**
 * @param $item
 * @param $key
 * @param $prefix
 * @deprecated
 */
function prefix(&$item, $key, $prefix){
	if (strpos($item, '.') === false)
		$item = $prefix.$item;
}

/**
 * function compile fields for searching
 *
 * @param oPortal $p
 * @param $sfields
 * @param $keywords
 * @param $wmode
 * @param bool $tbl_prefix
 * @param bool $fulltext
 * @param string $innermode
 * @param array $fields
 * @return bool|string
 * @deprecated
 */
function make_search_fields(oPortal &$p, &$sfields, &$keywords, $wmode, $tbl_prefix = false, $fulltext = false, $innermode = 'OR', $fields = array()){

	$query = false;
	
	// making table prefix
	if ($tbl_prefix)
		$prefix = $tbl_prefix.".";
	else
		$prefix = "";
	
	
	if ($fulltext){
	
		$tmp_keys = $keywords;
			
		array_walk_recursive($sfields, 'prefix', $prefix);
		array_walk_recursive($tmp_keys, 'quote');
		$query = 'MATCH('.join(',', $sfields).') AGAINST(\''.join(' ', $tmp_keys).'\' IN BOOLEAN MODE)';
		
	} else {
	
		// getting verdsion of DB
		if ($p->vars['db_type'] == 'postgres'){
		
			$op = '~*';
		
		} elseif ($p->vars['db_type'] == 'mysql'){
		
			$op = 'LIKE';
		
		} else {
		
			$op = "LIKE";
		
		} 
		
		$i = 1;
		$keywords = qstr($keywords);
		foreach ($keywords as $k){
			$tmp_query = array();
			if (!empty($k)){
				if ($i == 1){
					$query .= "(";
				
				} else {
					
					$query .= " $wmode (";
				}
				
				foreach ($sfields as $v){
					// some optimization for integer fields
					if (!empty($fields) && ($fields[$v]['type'] == 'integer' || $fields[$v]['type'] == 'boolean')){
						if (is_numeric($k))
							$tmp_query[] = "(".(strpos($v, '.') === false ? $prefix : '').$v."=".(int)$k.")";
					} else
						$tmp_query[] = "(".(strpos($v, '.') === false ? $prefix : '').$v." $op '%".$k."%')";
				}
					
				$query .= join(" ".$innermode." ", $tmp_query).")";

			}
			
			$i++;
		
		}
		
	}
	
	if ($query)
		$query = "(".$query.")";
	
	return $query;

}

/**
* function compile WHERE array expression to the string
* @version 1.6
*
* Changelog:
*	1.6	25.04.11/goshi	add 'service' param for condition (use for foreign keys and other)
*	1.5	20.03.11/goshi	add subqueries
*	1.1	10.01.11/goshi	update for fields determining
* 
* @param array $conditions array with conditions
* @param array $order_cond[option] additional arrray with current order conditions
*
* @return array of compiled where items
 * @deprecated
*/
function compile_where_array($conditions, &$order_cond = null){

	$sql_add = array();
	if (is_array($conditions) && !empty($conditions)){
	
		foreach ($conditions as $k => $v){

			// checking for subquery
			reset($v);
			if (isset($v['subcond'])){
				if (!empty($v['subcond']['items'])){
				
					// accumulate subqueries
					$sql_add[] = '('.join(' '.$v['subcond']['op'].' ', compile_where_array($v['subcond']['items'])).')';
					
				}

			
			} else {

				if (!isset($v['value']))
					continue;

				$cond = isset($v['op']) ? $cond = qstr($v['op']) : '=';
		
				$v['value'] = qstr($v['value']);
	
				// prepare value
				if (is_array($v['value']) && (trim(mb_strtolower($cond)) == 'in' || trim(mb_strtolower($cond)) == 'not in')){
                    array_walk($v['value'], 'alter_values');
					$value = '('.join(',', $v['value']).')';
	
				} elseif (is_array($v['value']) && trim(mb_strtolower($cond)) == 'mva_in'){
					
					$key = qstr($v['key']);
					$value = array();
					foreach ($v['value'] as $z){
						$value[] = ' '.$key.' LIKE \'%;'.$z.';%\' ';
					}
					
					$cond = '';
					$v['key'] = '';
					unset($v['table']);
					$value = '('.join(' OR ', $value).')';
				
					
				} elseif (is_array($v['value']) && trim(mb_strtolower($cond)) == 'between'){
					
					$value = ' '.$v['value'][0].' AND '.$v['value'][1];
				
				} else
					$value = !is_numeric($v['value']) && !$v['service'] ? '\''.$v['value'].'\'' : $v['value'];
					
				$sql_add[] = (isset($v['table'])  ? '`'.qstr($v['table']).'`.' : '').($v['key'] != '' ? '`'.qstr($v['key']).'`' : '').$cond.''.$value;
				if (isset($v['order']) && $order_cond != null)
					$order_cond[] = '`'.qstr($v['key']).'` '.qstr($v['order']).'';
					
			}
		}
	}
	
	return $sql_add;
}



/**
 * compiling 'order'
 * @param null $value
 * @return string
 * @deprecated
 */
function compile_order_array($value = null){

    $order = '';
    if (isset($value) && is_array($value) && !empty($value)){

        // if we have set of arrays
        foreach ($value as $k => $v){
            $value[$k] = qstr($k).' '.(strtolower($v) == 'desc' ? ' DESC' : ($v != '' ? qstr($v) :' ASC'));
        }
        $order .= join(',', $value);

    }

    return $order;
}





/* alter functions */
function alter(&$item, $key = null){
	$item = '\''.$item.'\'';
}

function alter_values(&$item, $key = null){
	if ($item !== 'NULL')
		$item = '"'.$item.'"';
}

function alter_field(&$item, $key){
	$item = '`'.$item.'`';
}


// functions for convert time string to the Unix timestamp and revert
// Because strtotime works not correctly (convert only values from 00:00 to 23:59)
function strtotime_new($time_str){

	$time_arr = explode(':', $time_str);
	return  $time_arr[0]*3600 + $time_arr[1]*60;

}

// new getdate function - working with very little time ^)
function getdate_new($time_value){
	if (!is_array($time_value)){
		$hours = floor($time_value/3600);
		$mins = floor(($time_value - $hours*3600)/60);
	} else {
		$mins = $hours = 0;
	}
	return array('hours' => (strlen($hours) < 2 ?  "0".$hours : $hours), 'minutes' => (strlen($mins) < 2 ? "0".$mins : $mins));

} 


function time_new($time_value){

	$arr = getdate_new($time_value);

	return $arr['hours'].":".$arr['minutes'];

}

	
/*
*	Accepts $src and $dest arrays, replacing array $data
*/
function arr_replace($src, $dest, $data){
		if (!is_array($data)){
		
			return str_replace($src, $dest, $data);
		
		}
		
		foreach ($data as $k => $v){
		
			if (is_array($v)){
			
				$data[$k] = arr_replace($src, $dest, $v);
			
			} else {
			
				$data[$k] = str_replace($src, $dest, $v);
			
			}
		
		}
		
		return $data;

}

/**
* recursive striping slashed
*/
function unqstr($value)
{
    $value = is_array($value) ?
                array_map('unqstr', $value) :
                stripslashes($value);

    return $value;
}
	
/** 
 * cleaner for mysql data
 * @deprecated
*/
function mysql_cleaner($data, $magic_quotes_active){

    global $p;
	
	if (is_array($data)){
		foreach ($data as $k => $v){
			$data[$k] = mysql_cleaner($v, $magic_quotes_active);
		}
		
	} else {
		//undo any magic quote effects so mysql_real_escape_string can do the work
		if ($magic_quotes_active) {
			$data = stripslashes($data);
		}
		
		if (!is_numeric($data) && !is_object($data)) {

            $data = $p->db->cleanupEscape($data);
			//    $data = mysql_real_escape_string($data);
		}
		//echo htmlspecialchars($data)."<br><br>";
	}
	
	return $data;

}
	
/**
* Updated Function from ADODB version V4.65
*
* @version 1.22
*
* Changelog:
*	1.22	19.08.10/goshi	fix slashing of the double "
*	1.21	24.07.10/goshi	add DB checking for proxy
*	1.2	19.10.09/goshi	add DB checking for PHP 5.3
*	1.1	14.02.09/goshi	added mysqlrealescape
*
* Quotes array.
* Enhaced - if s is array - calling recursion, remove singlequoting
* Correctly quotes a string so that all strings are escaped
* An example is  qstr("Don't bother",magic_quotes_runtime());
* 
* @param string|array $s array or string to quote
* @param bool $magic_quotes[option]	if $s is GET/POST var, set to get_magic_quotes_gpc().
*				This undoes the stupidity of magic quotes for GPC.
*
* @return array quoted string to be sent back to database
 * @deprecated
*/
function qstr($s, $magic_quotes = null){

    global $p;
	// i.e PHP >= v4.3.0
	!isset($magic_quotes) || $magic_quotes === null ? $magic_quotes = get_magic_quotes_gpc() : null;
	if ($p->db && $p->db->instance != null) {
		return mysql_cleaner($s, $magic_quotes);
	} else { // before PHP v4.3.0
       		
		$replaceQuote = "\\'";
	
		if (!$magic_quotes) {
		
			if ($replaceQuote[0] == '\\'){
				// only since php 4.0.5
				$s = arr_replace(array('\\',"\0"), array('\\\\',"\\\0"), $s);
				//$s = str_replace("\0","\\\0", str_replace('\\','\\\\',$s));
			}
			$s = arr_replace('"','\\"',$s);

			//print_r( arr_replace("'", $replaceQuote, array('adsds' => "sadasd d''' '  \" ''' \"' ''''sad's'ad", array('grp' => "sdasd'sds '' sasd'' "))));
			//print_r(arr_replace("'", $replaceQuote, $s));
			return  arr_replace("'", $replaceQuote, $s);
		}
		
		// undo magic quotes for "
		$s = arr_replace('\\"','"',$s);
	
		if ($replaceQuote == "\\'")  // ' already quoted, no need to change anything
			return $s;
		else {// change \' to '' for sybase/mssql
			$s = arr_replace('\\\\','\\',$s);
			return arr_replace("\\'", $replaceQuote, $s);
		}
		
	}
}




/**
* Maximum upload size as limited by PHP
* Used with permission from Moodle (http://moodle.org) by Martin Dougiamas
*
* this section generates $max_upload_size in bytes
*/
function get_real_size($size=0) {
    /// Converts numbers like 10M into bytes
        if (!$size) {
            return 0;
        }
        $scan['GB'] = 1073741824;
        $scan['Gb'] = 1073741824;
        $scan['gB'] = 1073741824;
        $scan['G'] = 1073741824;
        $scan['MB'] = 1048576;
        $scan['mB'] = 1048576;
        $scan['Mb'] = 1048576;
        $scan['M'] = 1048576;
        $scan['m'] = 1048576;
        $scan['KB'] = 1024;
        $scan['Kb'] = 1024;
		$scan['kB'] = 1024;
        $scan['K'] = 1024;
        $scan['k'] = 1024;

        while (list($key) = each($scan)) {
            if ((strlen($size)>strlen($key))&&(substr($size, strlen($size) - strlen($key))==$key)) {
                $size = substr($size, 0, strlen($size) - strlen($key)) * $scan[$key];
                break;
            }
        }
        return $size;
}


/**
* Convert real size to friendly size
* @author goshi
*
* @param integer $size[optional] size for converting
* @param integer $precision[optional] precision for converting value
*
* @return string
*/
function get_friendly_size($size = 0, $precision = 2) {

        if (!$size) {
            return 0;
        }
        $scan['GB'] = 1073741824;
        $scan['MB'] = 1048576;
        $scan['KB'] = 1024;
        $scan['B'] = 1; 

        while (list($key, $value) = each($scan)) {
        	// using float precission - because some PHP cant handle much integer o_O 

            if (abs((float)$size) >= (float)$value){
            	$size = round($size/$value, $precision)." ".$key;
                break;
            }
        }
        return $size;
}



function weightCmp($a, $b){
	// specialy soring with weight =-1
	if ($a['weight'] == -1 ) return 1;
	if ($a['weight'] == $b['weight']) return 0;
	if ($a['weight'] > $b['weight']) return 1; 
	return -1;
}


function weight_titleCmp($a, $b){
	if ($a['weight'] == $b['weight']) {
        $r = mb_strcasecmp($a['title'], $b['title']);
		if ($r < 0)
			return -1;
		elseif ($r > 0)
			return 1;
		else
			return 0;
	}
	if ($a['weight'] > $b['weight']) return 1;
	return -1;
}

/**
 * function compare multibites strings
 * @param $str1
 * @param $str2
 * @param null $encoding
 * @return int
 */
function mb_strcasecmp($str1, $str2, $encoding = null) {
    if (null === $encoding) { $encoding = mb_internal_encoding(); }
    return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
}




/**
 * function return teaser text, which hav been got from description
 *
 * @param string	$text text for parsing
 * @param int $max_symbols max symbols count
 * @param int $max_words maximum words count
 * @param bool $is_strict[option]	if set is_strict -then max_symbols set like max symbols at all
 * @param bool $clear_tags[option]	if true then clear all tagse from text
 *
 * @return string
 */
function get_teaser($text, $max_symbols = 255, $max_words = 32, $is_strict = false, $clear_tags = true){

	if ($clear_tags)
		$text = Text::cleanupTags($text);
	
	if (!$is_strict){
	
		$sep = ' ';
		$words = explode($sep, trim($text));
		if (count($words) > $max_words)
			$text = join($sep, array_slice($words, 0, $max_words));
	
		if (mb_strlen($text) > $max_symbols){
			$i = 1;
			while (mb_strlen($text) > $max_symbols){
				$text = join($sep, array_slice($words, 0, $max_words-$i));
				$i++;
				if (mb_strlen($text) <= 0) break;
			}
			//$text = mb_substr($text, 0, $maxchar);
		}
		unset($words);
		unset($i);
		unset($sep);
	} else {
		$text = trim(strip_tags($text, '<a>'));
		
		preg_match('/(.{5,}?[.?!]){1,4}(.*)/is', $text, $found);
		
		if (isset($found[0])){
			$text = !$is_strict ? (mb_strlen($found[1]) < $max_symbols ? $found[0] : $found[1]) : (mb_strlen($found[1]) < $max_symbols ? mb_substr($found[0], 0, $max_symbols) : mb_substr($found[1], 0, $max_symbols));
		} else {
			$text = mb_substr($text, 0, $max_symbols);
		}
	}
	
	return $text;

}


/**
* function make avalable memory_get_usage on Windows and other platforms
*/
if (!function_exists('memory_get_usage'))
{
    function memory_get_usage()
    {
        //If its Windows
        //Tested on Win XP Pro SP2. Should work on Win 2003 Server too
        //Doesn't work for 2000
        //If you need it to work for 2000 look at http://us2.php.net/manual/en/function.memory-get-usage.php#54642
        if ( substr(PHP_OS,0,3) == 'WIN')
        {
			$output = array();
			exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );
       
			return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
        }else
        {
            //We now assume the OS is UNIX
            //Tested on Mac OS X 10.4.6 and Linux Red Hat Enterprise 4
            //This should work on most UNIX systems
            $pid = getmypid();
            exec("ps -eo%mem,rss,pid | grep $pid", $output);
            $output = explode("  ", $output[0]);
            //rss is given in 1024 byte units
            return $output[1] * 1024;
        }
    }
}



/**
* Dump for output variables
*
* @param mixed $var variable
* @param bool $flg exit flag
*/
function dump($var,$flg = true) {
	ob_start();
	var_dump($var);
	$data = ob_get_clean();

	if(empty($_SERVER['TERM'])) {
		print '<pre style="font-size: 12px;">' ;
	}

	print $data;

	if(empty($_SERVER['TERM'])) {
		print '</pre>';
	}
	
	if($flg) exit;
}

/**
*  dump to a file 
*/
function dump_file($var, $flg = false, $file = null) {

    if (!$file){
        global $INFO;
        $file = './'.$INFO['DOC_DIR'].'temp/dump_file.dat';
    }

    $bt = debug_backtrace();
    $caller = array_shift($bt);
	$old_ov = ini_get('xdebug.overload_var_dump');
	$old_he = ini_get('html_errors');
	ini_set('xdebug.overload_var_dump', 0);
	ini_set('html_errors', 0);
	ob_start();
	var_dump($var);
	$data = ob_get_clean();
	file_put_contents($file, date('H:i:s d-m-Y')." : ".$caller['file']." : line: ".$caller['line']." : ".get_friendly_size(memory_get_usage())." : ".$data."\r\n", FILE_APPEND);
	ini_set('xdebug.overload_var_dump', $old_ov);
	ini_set('html_errors', $old_he);
	if($flg) exit;
}

/**
 * generate crc16
 */
function crc16($data){
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++)
    {
        $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
    }
    return $crc;
}








/**
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param array $array2
 * @return array
 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
 */
function array_merge_recursive_distinct ( array $array1, array $array2, $method = 'rewrite' ){
	$merged = $array1;

	foreach ( $array2 as $key => &$value ){
		if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ){

			$merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value, $method );

		} else {

			if ($method == 'rewrite' || !is_numeric($key))
				$merged [$key] = $value;
			elseif ($method == 'combine' && is_numeric($key))
				$merged [] = $value;

		}
	}

	return $merged;
}


function get_langdivs_tpl(oPortal &$p, $source_tpl, $arr_content = array(), $active_lang = false, $is_quote = true, $with_spans = true){
	
	// setting up active language
	if (!$active_lang)
		$active_lang = ($p->query->get()->get('lang') ? /*(int)*/$p->query->get()->get('lang') : key($p->getLangTbl()));
	
	$full_lang_content = '';

	foreach ($p->getLangTbl() as $k => $v){
		
		// adding spans
		$lang_content = $source_tpl;
		
		if ($with_spans)
			$lang_content = "<span lang='".$v."' >".$lang_content."</span>";
			
		// prepare adding style!
		$add_style = ';background-image: url('.$p->getLangs()[$v]['picture'][0].');background-position: left center;background-repeat: no-repeat; padding-left: 20px';
		$full_lang_content .= str_replace(
			array('{LANG}', '{IS_ACTIVE}', '{FIELD_CONTENT}'),
			array($v, $active_lang == $k ? ''.$add_style : 'none'.$add_style, $is_quote ? htmlspecialchars($arr_content[$v], ENT_QUOTES) : $arr_content[$v]),
			$lang_content);
	
	}

	return $full_lang_content;

}

function get_arr_fields_lang(&$source_arr, $fields, $lang_tbl, $real_id = null){
	
	if (!is_array($fields))
		return false;

    $results = array();

	// for each fileds making languages values
	foreach($fields as $v){
	
		// for each languages
		foreach ($lang_tbl as $k1 => $k2){
	
			$content = ""; // empty field
			// try to find variable for this language
			foreach ($source_arr as $m){
			
				if ($real_id && $real_id == $m['real_id'] && $m['lang_id'] == $k2){
					$content = $m[$v];
					break;
				} elseif (!$real_id && $m['lang_id'] == $k2){
					$content = $m[$v];
					break;
				}
		
			}	
			
			$results[$v][$k2] = $content;
		}
	}

	return $results;

}




/**
* extend array with another - fixing bug with array_merge, which on int keys drop it to zero
*/
function array_extend($a, $b) {
    foreach($b as $k=>$v) {
        if( is_array($v) ) {
            if( !isset($a[$k]) ) {
                $a[$k] = $v;
            } else {
                $a[$k] = array_extend($a[$k], $v);
            }
        } else {
            $a[$k] = $v;
        }
    }
    return $a;
}


/** 
* function calculate item path
*/
function calc_item_path($id){

	return intval($id/100)."/".intval($id%100)."/";

}


/**
 * function generate from array picture value for saving
 * @param array $images
 * @return string
 */
function generate_picture_value($images = array()){

    return $images && !empty($images['images']) ? serialize(array_merge($images['images'], array('width' => $images['width'], 'height' => $images['height'], 'size' => $images['size']))) : '';
}






/**
* describing table and return array of the fields
 * @deprecated
*/
function describe_table(oPortal &$p, $table, $no_cache = false){

	$table_fields = array();
	if ($no_cache || !($table_fields = $p->cache->getSerial('describe.'.$table))){

        // check if table exists
        $sql = 'SHOW TABLES FROM `'.$p->getVar('storages')['base']['db_name'].'` LIKE \''.qstr($table).'\'';
        $table_exists = $p->db->selectRow($sql);

        if ($table_exists){

            // describe table
            $sql = 'DESCRIBE '.qstr($table);
            $res = $p->db->select($sql);

            if ($res && !empty($res)){
                foreach ($res as $data){
                    $table_fields[] = $data['Field'];
                }
            }
        }
		
		$p->cache->saveSerial('describe.'.$table, $table_fields);
		
	}
	return $table_fields;

}

/**
* getting primary key
*/
function get_primary($table_fields = array()){
	
	if (in_array('real_id', $table_fields))
		return 'real_id';
	elseif (in_array('id', $table_fields))
		return 'id';

}


/**
* function extract row title from standart fields list
*/
function get_row_title($row, $clear = false){

	if (isset($row['title']) && $row['title'] != ''){
		return $row['title'];
	/*elseif (isset($row['usernick']) && $row['usernick'] != ''){
		return $row['usernick'].(!$clear ? ' / '.($row['real_id'] ? $row['real_id'].' ' : $row['id']) : ''); */
	} elseif (isset($row['fname']) || isset($row['sname'])){
		return ($row['sname'] ? $row['sname'].' ' : '').$row['fname'].(!$clear ? ' / '.($row['real_id'] ? $row['real_id'].' ' : $row['id']) : '');
	} elseif (isset($row['email']) && $row['email'] != ''){
		return $row['email'];
	} elseif (isset($row['header']) && $row['header'] != ''){
		return get_teaser($row['header']);
	} elseif (isset($row['descr']) && $row['descr'] != '')
		return get_teaser($row['descr']);
	elseif (isset($row['real_id']))
		return $row['real_id'];
	else
		return $row['id'];

}


function extractFirstValue($data, $field, $oldData = array(), $fields = array(), $isMultilang = false){

    if ($isMultilang && $fields[$field]['multilang']){
        if (is_array($data[$field])){
            foreach ($data[$field] as $v)
                if (trim($v) != '') return trim($v);
        }
        // if we there - then seeking in old data
        if (is_array($oldData[$field])){
            foreach ($oldData[$field] as $v)
                if (trim($v) != '') return trim($v);
        }

    } else
        return $data && is_array($data) && $data[$field] != '' ? $data[$field] : $oldData[$field];

}

function extractClassname(oPortal &$p, $base_class){
    $class = is_string($base_class) ? $base_class : get_class($base_class);
    $class = explode('\\', $class);
    return $class[count($class) - 1];
}
