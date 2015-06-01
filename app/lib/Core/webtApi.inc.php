<?php
/**
 * web-T::Core file for working with external API
 *
 * Date: 05.01.15
 * Time: 12:56
 * @version 1.0
 * @author goshi
 * @package web-T[Core]
 *
 * Changelog:
 *	1.0	05.01.2015/goshi
 */

namespace webtFramework\Core;


class webtApi {

    /**
     * @var oPortal
     */
    protected $_p;


    public function __construct(oPortal &$p, $params = array()){

        $this->_p = &$p;

    }

    /**
     * alias for @see call
     * mehtod call api method on all slaves
     * @param string $action
     * @param null $data
     * @param mixed $files array of files for sending, each file must start from '@' symbol
     * @param string $method
     * @param string $datatype
     * @param array $params
     */
    public function callSlaves($action, $data = null, $files = null, $method = 'get', $datatype = 'json', $params = array()){

        // recursively delete from other instances
        if ($this->_p->getVar('INSTANCES') && count($this->_p->getVar('INSTANCES')) > 1 && $this->_p->isMainInstance()){
            foreach ($this->_p->getVar('INSTANCES') as $i_name => $i_conf){
                // additional checking for current instance name
                if ($i_name != INSTANCE_NAME){
                    $this->_p->api->call($i_conf['url'], $this->_p->getVar('EXTERNAL_API_KEYS')['INSTANCES'], 'core', $action, $data, $files, $method, $datatype, $params);
                }
            }
        }
    }

    /**
     * alias for @see call
     * mehtod call api method on all other instances
     * @param string
     * @param null $data
     * @param mixed $files array of files for sending, each file must start from '@' symbol
     * @param string $method
     * @param string $datatype
     * @param array $params
     */
    public function callExceptMe($action, $data = null, $files = null, $method = 'get', $datatype = 'json', $params = array()){

        // recursively delete from other instances
        if ($this->_p->getVar('INSTANCES') && count($this->_p->getVar('INSTANCES')) > 1){
            foreach ($this->_p->getVar('INSTANCES') as $i_name => $i_conf){
                // additional checking for current instance name
                if ($i_name != INSTANCE_NAME){
                    $this->_p->api->call($i_conf['url'], $this->_p->getVar('EXTERNAL_API_KEYS')['INSTANCES'], 'core', $action, $data, $files, $method, $datatype, $params);
                }
            }
        }
    }

    /**
     * alias for @see call
     * call api method only on master
     * @param string $action
     * @param null $data
     * @param mixed $files array of files for sending, each file must start from '@' symbol
     * @param string $method
     * @param string $datatype
     * @param array $params
     */
    public function callMaster($action, $data = null, $files = null, $method = 'get', $datatype = 'json', $params = array()){

        // recursively delete from other instances
        if ($this->_p->getVar('INSTANCES') && count($this->_p->getVar('INSTANCES')) > 0 && !$this->_p->isMainInstance()){
            $this->_p->api->call($this->_p->getVar('INSTANCES')['main']['url'], $this->_p->getVar('EXTERNAL_API_KEYS')['INSTANCES'], 'core', $action, $data, $files, $method, $datatype, $params);
        }
    }


    /**
     * method call external api with parameters
     * @param string $server_name name of the server in 'EXTERNAL_API_KEYS' var, or separately server URL with protocol
     * @param string $api_key API key for server, if exists method try to find it in 'EXTERNAL_API_KEYS' var
     * @param string $resource
     * @param string $action
     * @param mixed $data
     * @param mixed $files array of files for sending, each file must start from '@' symbol
     * @param string $method can be 'get' or 'post'
     * @param string $datatype can be 'json' or 'xml'
     * @param array $params
     * @return mixed|string
     */
    public function call($server_name, $api_key = null, $resource, $action, $data = null, $files = null, $method = 'get', $datatype = 'json', $params = array()){

        // detect API key
        if ($this->_p->getVar('EXTERNAL_API_KEYS')[$server_name] && !$api_key){
            $api_key = $this->_p->getVar('EXTERNAL_API_KEYS')[$server_name];
        }

        if ($server_name && $api_key){

            $api_url = (strpos($server_name, 'http://') === false ? 'http://' : '').$server_name.'/api.php?apikey='.rawurlencode($api_key).'&resource='.rawurlencode($resource).'&action='.rawurlencode($action).'&method='.rawurlencode($method).'&datatype='.rawurlencode($datatype);

            $default_params = array(CURLOPT_TIMEOUT => 30);

            // for async mode set timeout for connection to 1ms
            if ($params && $params['async']){
                $default_params[CURLOPT_TIMEOUT_MS] = 1;
                $default_params[CURLOPT_NOSIGNAL] = 1;
            }

            $data = array('data' => $data);
            $response = $this->_p->Module('oWeb')->init()->post($api_url, $files && is_array($files) ? array_merge($data, $files) : $data, $default_params);

            if ($response && ($response = json_decode($response, true))){
                $response = $response['response'];
            }

            return $response;
        } else {
            return false;
        }
    }

}
