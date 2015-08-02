<?php
/**
 * Default profiler
 *
 * Date: 21.02.15
 * Time: 21:04
 * @version 1.0
 * @author goshi
 * @package web-T[Debug]
 * 
 * Changelog:
 *	1.0	21.02.2015/goshi 
 */

namespace webtFramework\Components\Debug\Profiler;

use webtFramework\Core\oPortal;
use webtFramework\Helpers\Debug;

class oProfilerDefault extends oProfilerAbstract{

    /**
     * default debug structure
     * @var array
     */
    private $_debug_parts = array(
        'common' => array(),
        'get' => array(),
        'post' => array(),
        'cookie' => array(),
        'session' => array(),
        'sql' => array(),
        'memory' => array(),
        'time' => array(),
    );

    /**
     * some counters for debugging
     * @var array
     */
    private $_debug_counters = array(
        'sql' => 0,
        'memory' => '',
        'time' => '');

    /**
     * excluded parts from debug describing
     * @var array
     */
    private $_debug_exclude = array('memory', 'time');


    public function __construct(oPortal &$p){

        parent::__construct($p);

        if ($this->_p->getVar('_debug') && is_array($this->_p->getVar('_debug'))){
            $this->_debug_parts = array_merge($this->_debug_parts, $this->_p->getVar('_debug'));
            $this->_p->setVar('_debug', null);
            // update memory data
            foreach ($this->_debug_parts['memory']['data'] as $k => $v){
                $this->_debug_parts['memory']['data'][$k] = $this->_p->filesystem->formatSizeFromBytes($v);
            }

        }

    }


    public function start($parameters = null){

        global $starttime;

        if ($starttime)
            return true;

        Debug::startTimer();

        return true;

    }


    public function stop(){

        Debug::endTimer();

        return $this->_debug_parts;

    }

    /**
     * Add text string to debug level + memory usage
     *
     * @param string $string what to add
     * @param array $params
     */
    public function add($string, $params = array()){

        $bt = debug_backtrace();
        $caller = $bt[2];

        $index = (int)count($this->_debug_parts['common']['data']);
        if (isset($params['error']) && is_array($params['error'])){
            $this->_debug_parts['common']['error'][$index] = $params['error']['message'];
        }
        $this->_debug_parts['common']['data'][$index] = str_replace(getcwd().'/', '', $caller['file'])." : line: ".$caller['line']." : ".$string;

        $this->_debug_parts['time']['data'][$index] = \webtFramework\Helpers\Debug::endTimer();
        // adding raw data from memory
        $this->_debug_parts['memory']['rawdata'][$index] = is_object($this->_p->server) ? $this->_p->server->memoryGetUsage() : memory_get_usage();

        $this->_debug_parts['memory']['data'][$index] = $this->_p->filesystem->formatSizeFromBytes($this->_debug_parts['memory']['rawdata'][$index]);

        $this->_debug_parts['common']['memory'][$index] = $index;
        $this->_debug_parts['common']['time'][$index] = $index;
    }


    /**
     * Add SQL string to debug level
     * @param \DbSimple_Mysql|\DBSimple_Generic $db
     * @param string $message
     */
    public function addSQL(&$db, $message)
    {

        // get context of the caller
        $bt = debug_backtrace();
        $caller = $bt[14];
        //  dump($caller);

        if (isset($caller['error']) && $caller['error'] != ''){
            $params['error']['code'] = $caller['error'];
            $params['error']['message'] = $caller['errmsg'];
        } else {
            $params['error'] = false;
        }

        if (strpos($message, 'ms;') !== false){
            $last = count($this->_debug_parts['sql']['data']) - 1;
            if (preg_match('/--\s([\d\.]+)\sms;\sreturned\s(?:(\d+)\srow\(s\))?/is', $message, $match)){
                $this->_debug_parts['sql']['sql_time'][$last] = $match[1];
                $this->_debug_parts['sql']['sql_results'][$last] = $match[2] != '' ? $match[2] : 1;
            } else {
                $this->_debug_parts['sql']['data'][$last] .= ' // '.$message;
                $this->_debug_parts['sql']['sql_time'][$last] = 0;
                $this->_debug_parts['sql']['sql_results'][$last] = 0;
            }
        } elseif (strpos($message, 'error #')){
            $last = count($this->_debug_parts['sql']['data']) - 1;
            $this->_debug_parts['sql']['error'][$last] = $message;

        } else {
            $this->_debug_parts['sql']['memory'][$this->_debug_counters['sql']] = count($this->_debug_parts['memory']['data']) - 1;
            $this->_debug_parts['sql']['time'][$this->_debug_counters['sql']] = count($this->_debug_parts['time']['data']) - 1;

            $this->_debug_parts['sql']['data'][$this->_debug_counters['sql']] = $message."\r\n".str_replace(getcwd().'/', '', $caller['file'])." : line: ".$caller['line'];
            $this->_debug_counters['sql']++;

        }

    }

    /**
     * detect if debug log has errors
     * @return bool
     */
    public function hasErrors(){

        $has_error = false;
        if (!empty($this->_debug_parts)){
            foreach ($this->_debug_parts as $v){
                if (isset($v['error']) && !empty($v['error'])){
                    $has_error = true;
                    break;
                }
            }
        }

        return $has_error;

    }

    /**
     * function convert array to string represent
     *
     * @param $array
     * @param string $step
     * @param bool $is_html
     * @return string
     */
    private function _describeAnidatedArray($array, $step = '', $is_html = false){

        $buf = '';
        if ($is_html)
            $step_val = '&nbsp;&nbsp;&nbsp;&nbsp;';
        else
            $step_val = '    ';
        foreach($array as $key => $value){
            if(is_array($value)){
                $buf .= $step.$key ." => \r\n". $this->_describeAnidatedArray($value, $step.$step_val) . "\r\n";
            } else
                $buf .= $step.$key." : ".$value."\r\n";
        }
        return $buf;
    }

    /**
     * function prepare base info from arrays
     *
     * @param $array
     * @return string
     */
    private function _describeDataArray($array){

        $std_array = array();
        $std_array[] = '&nbsp;';
        $error = '';

        if (isset($array['error'])){
            $std_array[] = $this->_p->trans('debug.error');
        }
        if (isset($array['sql_time'])){
            $std_array[] = $this->_p->trans('debug.sql_time');
        }
        if (isset($array['sql_results'])){
            $std_array[] = $this->_p->trans('debug.sql_results');
        }
        if (isset($array['memory'])){
            $std_array[] = $this->_p->trans('debug.memory');
        }
        if (isset($array['time'])){
            $std_array[] = $this->_p->trans('debug.time');
        }

        $buf = '<table><tr>';
        foreach ($std_array as $k => $v){
            $buf .= '<th class="t'.($k + 1).'">'.$v.'</th>';
        }
        $buf .= '</tr>';

        foreach ($array['data'] as $k => $v){

            //check for time and memory
            $sql_time = $sql_results = $time = $memory = '';
            $is_error = false;

            if (isset($array['error'])){
                $error = '<td>'.$array['error'][$k].'</td>';
                if ($array['error'][$k])
                    $is_error = true;
            }

            if (isset($array['sql_time'])){
                $sql_time = '<td>'.$this->_debug_parts['sql']['sql_time'][$array['sql_time'][$k]].'</td>';
            }

            if (isset($array['sql_results'])){
                $sql_results = '<td>'.$this->_debug_parts['sql']['sql_results'][$array['sql_results'][$k]].'</td>';
            }

            if (isset($array['memory'][$k])){
                $memory = '<td>'.$this->_debug_parts['memory']['data'][$array['memory'][$k]].'</td>';
            }
            if (isset($array['time'][$k])){
                $time = '<td>'.$this->_debug_parts['time']['data'][$array['time'][$k]].'</td>';
            }

            $buf .= '<tr '.($is_error ? 'class="debug-error"' : '').'><td >'.htmlspecialchars($v, ENT_QUOTES) ."</td>".$error.$sql_time.$sql_results.$memory.$time."</tr>";

        }

        $buf .= '</table>';

        return $buf;
    }

    /**
     * print debug information
     */
    public function getView($content, $data = null){

        $max_time = Debug::endTimer();
        $this->add("END: ".$max_time." sec");
        $this->_debug_counters['time'] = $max_time." sec";

        $this->_debug_parts['get']['data'] = $this->_describeAnidatedArray($_GET, '', true);
        $this->_debug_parts['post']['data'] = $this->_describeAnidatedArray($_POST, '', true);
        $this->_debug_parts['cookie']['data'] = $this->_describeAnidatedArray($_COOKIE, '', true);
        $this->_debug_parts['session']['data'] = $this->_describeAnidatedArray($this->_p->user->getSessionVal(), '', true);

        if (function_exists('memory_get_peak_usage')){
            $this->_debug_counters['memory'] = $this->_p->filesystem->formatSizeFromBytes(memory_get_peak_usage());
        }

        // prepare output buffering
        $cats_js = array();
        $cats_arr = array();
        $cats_containers_arr = array();

        $this->_debug_parts = array_reverse($this->_debug_parts);

        foreach ($this->_debug_parts as $k => $v){

            $img = $this->_p->getVar('debugger')['debug_assets_dir'].'/img/debug_'.$k.'.png';

            $cats_arr[$k] = array('error' => $v['error'], 'img' => file_exists($img) ? str_replace($this->_p->getDocDir(), '/', $img) : null);

            switch ($k){

                case 'memory':

                    $value = $this->_debug_parts['memory']['rawdata'][count($this->_debug_parts['memory']['rawdata']) - 1] - $this->_debug_parts['memory']['rawdata'][0];

                    if (isset($this->_debug_counters[$k])){
                        $cats_arr[$k]['value'] = $this->_debug_counters[$k];
                        $cats_arr[$k]['value_detailed'] = $this->_p->filesystem->formatSizeFromBytes($value);
                    }

                    break;

                default:
                    $cats_arr[$k]['value'] = $this->_debug_counters[$k];
                    break;

            }

            $cats_js[$k] = false;

            // check for exclude data
            if (in_array($k, $this->_debug_exclude)){

                $data = $this->_describeDataArray($this->_debug_parts['common']);

            } elseif (isset($v['data'])){

                if (is_array($v['data'])){

                    $data = $this->_describeDataArray($v);

                } else
                    $data = $v['data'];
            } else {
                $data = $this->_describeAnidatedArray($this->_debug_parts['common']['data']);
            }

            $cats_containers_arr[$k] = str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$data), "\0..\37'\\")));
        }

        $this->_p->tpl->addToken('CATS_JS', $cats_js);
        $this->_p->tpl->addToken('CATS', $cats_arr);
        $this->_p->tpl->addToken('CATS_CONTAINERS', $cats_containers_arr);

        return str_replace('</body>', $this->_p->tpl->get($this->_p->getVar('debugger')['debug_assets_dir'].WEBT_DS.'views'.WEBT_DS.'panel.'.$this->_p->tpl->getTplExt()), $content);

    }


} 