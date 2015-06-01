<?php
/**
 * Xhprof profiler fascade
 *
 * Date: 21.02.15
 * Time: 16:19
 * @version 1.0
 * @author goshi
 * @package web-T[Debug]
 * 
 * Changelog:
 *	1.0	21.02.2015/goshi 
 */

namespace webtFramework\Components\Debug\Profiler;


class oProfilerXhprof extends oProfilerAbstract {

    public function start($parameters = null){

        if (function_exists('xhprof_enable')){

            xhprof_enable($parameters);

        } else {

            throw new \Exception('errors.debug.profiler_not_installed_xhprof');

        }

    }


    public function stop(){

        // stop profiler
        $this->_data = xhprof_disable();

        $XHPROF_ROOT = $this->_p->getVar('BASE_APP_DIR') .'/vendor/facebook/xhprof/';
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

        // save raw data for this profiler run using default
        // implementation of iXHProfRuns.
        $xhprof_runs = new \XHProfRuns_Default();

        // save the run under a namespace "xhprof_SERVER_NAME"
        $run_id = $xhprof_runs->save_run($this->_data, "xhprof_".$this->_p->getVar('server_name'));

        if ($this->_p->getVar('is_dev_env')){

            $nl = $this->_p->getVar('STREAM_TYPE') == ST_BROWSER ? '<br>' : null;

            $this->_p->response->send('-------------------'.$nl);
            $this->_p->response->send('Assuming you have set up the http based UI for XHProf at some address, you can view run at '.$nl);
            $this->_p->response->send('http://<xhprof-ui-address>/index.php?run='.$run_id.'&source=xhprof_'.$this->_p->getVar('server_name').$nl);
            $this->_p->response->send('-------------------'.$nl);

        }

        return $this->_data;

    }

    public function add($string, $params = array()){
        return true;
    }

    public function getView($content, $data = null){
        // unfortunately - you need to use external viewers to view debug information

        //$XHPROF_ROOT = $this->_p->getVar('BASE_APP_DIR') .'/vendor/facebook/xhprof/';
        //include_once $XHPROF_ROOT . "/xhprof_html/index.php";

        return $content;
    }


} 