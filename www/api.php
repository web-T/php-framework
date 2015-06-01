<?php

/**
 * web-T::CMS api
 *
 * Query must be like apikey=...&resource=...&action=...&method=...&datatype=...
 * Always, if you send data from your app with POST method - you must use 'data' container for it
 * Default datatype is JSON
 *
 * @version 2.0
 * @author goshi
 * @package web-T[Web]
 *
 * Changelog:
 *	2.0	16.02.15/goshi	...
 *	1.0	24.10.12/goshi	...
 */

use webtFramework\Core\oPortal;
use webtFramework\Components\Response\oResponse;

// remove session cookies
unset($_COOKIE['sess_id']);
unset($_COOKIE['PPHPID']);

// turn off abort processing script on user abort
// we need to guaranted all operations
ignore_user_abort(true);

/**
 * Include share libraries
 */
include('../app/common.php');

/**
 * setting debug simple mode
 */
ini_set('xdebug.overload_var_dump', 0);
ini_set('html_errors', 0);
define('CODEPAGE', $p->getVar('codepage'));

$p->initApplication($p->getVar('api')['default_application']);

$p->query->request->createFromGlobals();

/**
 * detecting request
 **/
if (preg_match('/application\/json/is', $p->query->request->getServer('CONTENT_TYPE')) || preg_match('/application\/json/is', $p->query->request->getServer('HTTP_CONTENT_TYPE'))){
    $inputJSON = file_get_contents('php://input');
    $parsed_request = json_decode($inputJSON, true);
    if (is_array($parsed_request)){
        $p->query->request->setPost($parsed_request);
    }
}


/**
 * check API KEY
 */
if (!($p->query->request->get('apikey') && in_array(trim($p->query->request->get('apikey')), $p->getVar('API_KEYS')))){
    sleep(2);
    $p->response->send(new oResponse(
        array('response' => 'Wrong API key!', 'status' => 500),
        404,
        null,
        CT_JSON
    ));
    exit;
}

// set attribute for api app
$p->setVar('APP_TYPE', WEBT_APP_API);


$result = array();
$status = 404;


class webtAPIService{

    /**
     * @var null|oPortal
     */
    protected $_p = null;

    /**
     * base request (autodetect type from application/x-www-form-urlencoded or applications/json)
     * @var array|null
     */
    protected $_request = null;

    protected static $instance;  // object instance

    private function __construct(&$p = null) {
        if ($p){
            $this->_p = $p;
        }
    }
    private function __clone() { /* … */ }
    private function __wakeup() { /* … */ }

    /**
     * get singleton
     * @param null $p
     * @return webtAPIService
     */
    public static function getInstance(&$p = null) {
        if ( is_null(self::$instance) ) {
            self::$instance = new webtAPIService($p);
        }
        return self::$instance;
    }

    /**
     * method set right parsed request
     * @param string|array $request
     * @return webtAPIService
     */
    public function setRequest($request = null){

        if (is_string($request)){
            $parsed_request = json_decode($request, true);
            if (is_array($parsed_request)){
                $this->_request = $parsed_request;
            } else
                $this->_request = $request;
        } else
            $this->_request = $request;

        return $this;

    }

    /**
     * method return parse request
     * @return array|null
     */
    public function getRequest(){
        return $this->_request;
    }

    /**
     * checking is method allowed
     *
     * @param array $allowed_methods
     * @param null $method
     *
     * @return bool
     */
    protected function _checkAllowMethod($allowed_methods = array(), $method = null){

        if (!$method){
            return false;
        }

        if (!in_array($method, $allowed_methods)){
            return false;
        } else
            return true;

    }

    /**
     * checking is method allowed
     * @param array $allowed_rules
     * @param array $user_rules
     * @return bool
     */
    protected function _checkAllowAction($allowed_rules = array(), $user_rules = array()){

        $found = false;
        if (is_array($user_rules)){
            foreach ($user_rules as $rule_id => $rule_data){
                if (in_array($rule_data['nick'], $allowed_rules)){
                    $found = true;
                    break;
                }
            }
        }
        return $found;

    }


    public function run($resource, $action, $method){

        // try to get controller
        try {

            $app = $this->_p->ApiApp($resource);

            // check allowed method
            if (!$this->_checkAllowMethod($app->getAllowedMethods(), trim($method))){
                return new oResponse('', 500);
            }

            $data = $this->getRequest() ? $this->getRequest()['data'] : null;
            if ($app->getAuthMethods()){

                foreach ($app->getAuthMethods() as $m){
                    if (method_exists($app, $m)){
                        if (!$app->$m($data)){
                            return new oResponse('Not enough permissions for operations', 403);
                        }
                    }
                }

            }

            // check method exist
            $m = $action.ucfirst(trim($method));
            if (!method_exists($app, $m)){
                return new oResponse($this->_p->trans('errors.api.method_not_found'), 404);
            } else {
                return $app->$m($this->getRequest() ? $this->getRequest()['data'] : null);
            }


        } catch (\Exception $e){

            if ($e->getCode() == ERROR_NO_API_FOUND){
                return new oResponse($this->_p->trans($e->getMessage()), 404);
            } else {
                return new oResponse($e->getMessage(), 500);
            }

        }

    }


}

/**
 * checking for auth user
 * for authorizing you need to use POST method with array auth => array('access_token' => '...')
 **/

// restore vars if we send files
if ($p->query->request->getPost('auth') && !is_array($p->query->request->getPost('auth'))){

    $post = $p->query->request->getPost();
    parse_str($p->query->request->getPost('auth'), $post['auth']);
    $p->query->request->setPost($post);

}

if ($p->query->request->getPost('data') && !is_array($p->query->request->getPost('data'))){

    $post = $p->query->request->getPost();
    parse_str($p->query->request->getPost('data'), $post['data']);
    $p->query->request->setPost($post);

}

if ($p->query->request->getPost('auth')['access_token']){

    // checkint for external API settings
    if ($p->getVar('EXTERNAL_APIS') && DEFAULT_EXTERNAL_API && isset($p->getVar('EXTERNAL_APIS')[DEFAULT_EXTERNAL_API])){

        // add crossdomain  call to external api for checking access_token
        $api_url = 'http://'.DEFAULT_EXTERNAL_API.'/api.php?apikey='.rawurlencode($p->getVar('EXTERNAL_APIS')[DEFAULT_EXTERNAL_API]).'&resource=auth_by_access_token&method=post&datatype=json';

        $response = $p->Module('oWeb')->init()->post($api_url, array('auth' => array('access_token' => $p->query->request->getPost('auth')['access_token'])), array(CURLOPT_TIMEOUT => 10));
        if (!($response && ($json_response = json_decode($response, true)))){

            // some problems
            sleep(2);
            $status = 403;

        }

    } elseif (!$p->user->authByAccessToken($p->query->request->getPost('auth')['access_token'])){

        sleep(2);
        $status = 403;

    }

}

/*
 * checking for auth user
 * for authorizing you need to use POST method with array auth => array('access_token' => '...')
 */
$response = null;
if ($status != 403){

    $response = webtAPIService::getInstance($p)
        ->setRequest($p->query->request->getPost())
        ->run($p->query->request->get('resource'), $p->query->request->get('action'), $p->query->request->get('method'));

}

$array_response = array(
    'response' => $response ? $response->getContent() : null,
    'status' => $response ? $response->getStatus() : $status
);

// now detect type
switch (strtolower($p->query->request->get('datatype'))){

    case 'xml':
        $p->response->send($array_response, null, CT_XML);
        break;

    case 'json':
    default:
        $p->response->send($array_response, null, CT_JSON);
        break;

}
