<?php

/**
* User core module
* 
* @version 3.0
* @author goshi
* @package web-T[CORE]
*
* Changelog:
 *  3.0     06.08.14/goshi  complete refactoring
 *  2.8.1   09.06.13/goshi  refactoring constructor - remove $DB
 *  2.8     09.05.13/goshi  fix bug with no value in 'is_admin' property
 *  2.7     02.02.13/goshi  add default auth user rules
 *  2.6.1   19.01.13/goshi  fix bug on get method with rules for one user
 *	2.6.0	19.05.12/goshi  add caching of user session
 *	2.5.0	05.05.12/goshi  add is_editor property for user data, getUserRules can work with arrays, method get returns rules also
*	2.4.5	16.02.12/goshi  add ip and forward properties
*	2.4.0	01.02.12/goshi  add isUsersOnline
*	2.31	20.01.12/goshi	field for sex and name in get_params
*	2.3		26.11.11/goshi	fix getting rules for objects
*	2.2		12.09.11/goshi	add isBot method
*	2.1		28.08.11/goshi	add applyBan method, add is_moderator flag for data, fix adding rules for checkAuth user
*	2.02	19.08.11/goshi	fix call of the storage
*	2.01	05.08.11/goshi	fix online users
*	2.0		02.08.11/goshi	fix saving sess_id for user
*	1.70	26.07.11/goshi	optimization for online users, dont kill session cookie id
*	1.62	25.07.11/goshi	some update for count users
*	1.61	23.07.11/goshi	fix deleting from admin part when storage object absent
*	1.6		19.07.11/goshi	adding storage object
*	1.52	23.05.11/goshi	use for get admin page_id adm_pages_nicks structure
*	1.51	27.03.11/goshi	fix get user params
*	1.5		23.03.11/goshi	fix checkFieldRules
*	1.4		21.03.11/goshi	add authByCode
*	1.3		16.03.11/goshi	add generateAuthCode and checkAuthCode
*	1.2		10.03.11/goshi	now user after authing always have full session
*	1.15	02.03.11/goshi	add generatePassword method
*	1.14	20.02.11/goshi	fix crossdomain sessions and data
*	1.13	31.12.10/goshi	fix crossdomain sessions and data
*	1.12	13.12.10/goshi	some cache update for anonymous user
*	1.11	10.11.10/goshi	fix users picture bug with empty data
*	1.1		13.09.10/goshi	add checkFieldRules method
*	1.01	13.09.10/goshi	fix hasRule and array
*	1.0		24.08.10/goshi	full refactoring - new rules version
*	0.94	20.08.10/goshi	add saveUserSession and getUserSession methods
*	0.93	10.08.10/goshi	fix some bugs for users ids, use new rules link tables
*	0.92	28.07.10/goshi	add encryptPassword method and checking password with it
*	0.91	30.03.10/goshi	add some protection for insert into sessions  
*	0.9		10.03.10/goshi	add getUsersOnline method
*	0.8		08.03.10/goshi	add server_aliases support
*	0.74	24.02.10/goshi	now setting cookies on current top level domain with subdomains
*	0.73	21.02.10/goshi	authorized users now always get new document  w/o browser cache :(
*	0.72	08.11.09/goshi	remove all deprecated functions
*	0.71	25.10.09/goshi	session tbl now don't save counter page
*	0.7	23.10.09/goshi	update working with session
*	0.6	24.05.09/goshi	move all rules to lang_id
*	0.52	02.05.09/goshi	fixes bug with admin init and checkAuth
*	0.51	06.04.09/goshi	remove is_admin flag, cheage _isAdmin function
*	0.5	03.04.09/goshi	add adm_rules, hasRule now support admin rules
*	0.41	03.03.09/goshi	add fucntion get_friends
*	0.4	27.02.09/goshi	adde hasRule method
*	0.31	24.02.09/goshi	remove some bugs and debug information
*	0.3	23.02.09/goshi	add fucntion get_params
*	0.2	23.02.09/goshi	add function getUsersByRules
*	0.1	14.02.09/goshi ...
*/

namespace webtFramework\Core;

use webtFramework\Interfaces\oModel;

/**
* @package web-T[CORE]
*/
class webtUser{

	/**
	 * @var oPortal
	 */
	private $_p;

    /**
     * user's data
     * @var null|array
     * @deprecated
     */
    public $data = null;

    /**
     * default anonymous userid
     * @var int
     */
    protected $_default_id = -1;

    /**
     * user id per bundle
     * @var array
     */
    protected $_app_id = array();

    /**
     * session id per bundle
     * @var array
     */
    protected $_app_sess_id = array();

    /**
     * is authed per bundle
     * @var array
     */
    protected $_app_is_auth = array();


    /**
     * user rules per bundle
     * @var array
     */
    protected $_app_rules = array();


    /**
     * user session per bundle
     * @var array
     */
    protected $_app_session = array();


    /**
	 * @var null|webtUserStorage
	 */
	public $storage = null;

    /**
     * current user's IP
     * @var array|mixed|null|string
     */
    protected $_ip = null;

    /**
     * current user's FORWARD
     * @var array|mixed|null|string
     */
    protected $_forward = null;

    /**
     * session lock flag
     * used on making serial manipulations with session data, on startSession() -> locksession() -> do something special -> stopSession()
     * @var bool
     */
    protected $_session_is_locked = false;
	
	/**
	 * current session cache
	 * @var array
	 */
	private $_session_cache = null;

    /**
     * version on access_token for user
     * @var int
     */
    private $_access_token_version = 1;

    /**
     * flag is session found
     * @var bool
     */
    private $_sess_found = false;

    public function __construct(oPortal &$p, $is_admin = null){

		$this->_p = &$p;

		$this->_ip = get_ip(true);
		$this->_forward = get_http_forward();

		// connect by autoloader storage
		$this->storage = new cProxy($p, array('instance' => 'webtUserStorage', 'include' => $p->getVar('FW_DIR').$p->getVar('lib_dir').$p->getVar('core_dir').'webtUserStorage.inc.php'));

		if ($p->getVar('is_debug')){

			$p->debug->add("USER: After construct storage");

		}

        // we need some hack for working autoinitialization
        $this->_p->user = &$this;

        $this->init($p->getApplication());

		if ($p->getVar('is_debug')){

			$p->debug->add("USER: After init user");

		}

	}

    /**
     * initialize method
     * @param string $application
     */
    public function init($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $this->_app_is_auth[$application] = $this->checkAuth($application);

        if ($this->_p->getVar('is_debug'))
			$this->_p->debug->add("USER: init : After check auth on init");

        if ($this->isAuth($application)){

            $this->_app_rules[$application] = $this->getUserRules($application);

        }

		if ($this->_p->getVar('is_debug'))
			$this->_p->debug->add("USER: init : After get user rules ");

	}

	/**
	* super function for generating strong password
	* @params string $password the password string
	* @params int $times how much times encrypt the password
	*/
	public function encryptPassword($password, $times = 10){

		for($i = 0; $i < $times; $i++){
			$password = hash('sha512', $this->_p->getVar('user')['salt'].$password);
			$password = md5($password.$this->_p->getVar('user')['salt']);
		}
		return $password;

	}

    /**
     * method returns user metrics
     *
     * @param bool $is_plain_text
     * @return array|string
     */
    public function getVisitorData($is_plain_text = false){

		$ret = array();

		// checking for IP, if not exists - then running from console
		$ip = get_ip();
		if ($ip == ''){
			$ret['script_filename'] = basename($_SERVER['SCRIPT_FILENAME']);
		} else {
			$ret['ip'] = $ip.":".$_SERVER['REMOTE_PORT'];
			$ret['http-accept'] = $_SERVER['HTTP_ACCEPT'];
			$ret['http-accept-charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
			$ret['http-accept-encoding'] = $_SERVER['HTTP_ACCEPT_ENCODING'];
			$ret['http-accept-language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$ret['http-connection'] = $_SERVER['HTTP_CONNECTION'];
			$ret['http-host'] = $_SERVER['HTTP_HOST'];
			$ret['request_uri'] = $_SERVER['REQUEST_URI'];
			$ret['http-referer'] = $_SERVER['HTTP_REFERER'];
			$ret['http-user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		// if we want it in plain text - set to 1
		if ($is_plain_text){

			$tmp = "";

			foreach ($ret as $k => $v){

				$tmp .= strtoupper($k).": ".$v."\r\n";

			}

			return $tmp;

		} else {

			return $ret;

		}

	}

    /**
     * method generate humanized password
     *
     * @return string
     */
    public function generatePassword(){

		// Must be a multiple of 2 !! So 14 will work, 15 won't, 16 will, 17 won't and so on
		$length =  6;

		$conso = array("b","c","d","f","g","h","j","k","l", "m","n","p","r","s","t","v","w","x","y","z");
		$vocal = array("a","e","i","o","u");
		$password = "";
		srand ((double)microtime()*1000000);
		$max = $length/2;
		for($i=1; $i<=$max; $i++){
			$password.=$conso[rand(0,19)];
			$password.=$vocal[rand(0,4)];
		}
		return $password;

	}

	/**
     * method generate auth code for user
     * @param null $data array of user data
     * @return string
     */
    public function generateAuthCode($data = null){

		if (!$data)
			$data = $this->data[$this->_p->getApplication()];

		// we use user + email + password protection. if user change any of them - then authcodes dont work
		return $this->encryptPassword($data['username'].$data['email'].$data['password']);

	}

    /**
     * generate access_token for user
     * @param null $data
     * @return string
     */
    public function generateAccessToken($data = null){

        if (!$data)
            $data = $this->data[$this->_p->getApplication()];

        // use first byte for
        return substr($this->_access_token_version.base64_encode($this->encryptPassword($data['username'].$data['email'].$data['password'])), 0, 64);

    }


    /**
	* method checking auth code for user
	*/
	public function checkAuthCode($code, $data = null){

		if (!$data)
			$data = $this->data[$this->_p->getApplication()];

		// we use user + email + password protection. if user change any of them - then authcodes dont work
		return $this->encryptPassword($data['username'].$data['email'].$data['password']) == $code;

	}

    /**
     * checking access token for user and return user id. access token valid all time, until you delete it in the table
     *
     * @param $access_token
     * @return array|bool
     */
    public function getByAccessToken($access_token){

        // we use user + email + password protection. if user change any of them - then authcodes dont work
        return $this->_p->db->selectRow($this->_p->db->getQueryBuilder()->compile($this->_p->Model('User'), array('no_array_key' => true, 'limit' => 1, 'where' => array('access_token' => $access_token, 'is_on' => true, 'lang_id' => $this->_p->getLangId()))));

    }

    /**
     * method cleanup query
     * @return mixed
     */
    private function _getClearQuery(){

		return $this->_p->query->cloning()->remove(array('objid', 'lang'));

	}


    /**
     * method checks if current user is bot
     * @return bool
     */
    public function isBot(){

		if (preg_match("/(bot|infoseek|scooter|slurp|spider|meta|yandex|google|rambler|aport|yahoo|lycos|altavista|crawler)/is", $_SERVER['HTTP_USER_AGENT'])){
			return true;
		} else {
			return false;
		}

	}

    /**
     * check for possibility manipulate session
     * @param null $query
     * @return bool
     */
    protected function _canManipulateSession($query = null){

        /**
         * TODO: refactor, remove old query sections
         */
        return strpos($query, $this->_p->getVar('statistic')['counter_url']) === false && $this->_p->getVar('statistic')['counter_filename'] != $query && !$this->isBot() && $this->_p->getVar('APP_TYPE') != WEBT_APP_CONSOLE && (!$this->_p->query->get()->get('page') || ($this->_p->query->get()->get('page') && $this->_p->query->get()->get('page') != 'uptime'));

    }

    /**
     * method try to find session
     * @param string $sess_id
     * @param string $application
     * @param int|null $user_id
     * @return array|mixed|null|void
     */
    protected function _findSession($sess_id, $application = null, $user_id = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // first of all try to find in current $_SESSION
        if (($sess = $this->getSessionVal('_webt_'.($application ? $application : '').'session')) &&
            $sess['title'] == $sess_id && (($user_id <> 0 && $sess['user_id'] == $user_id) || !$user_id)){
            return $sess;
        }

        // second chance - for database
        $conditions = array(
            //'no_array_key' => true,
            'where' => array(
                'title' => $sess_id,
                'application' => $application
            ), 'order' => array(
                'lastuse_time' => 'desc'
            ), 'limit' => 1
        );

        if ($user_id <> 0)
            $conditions['where']['user_id'] = $user_id;

        $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['session_model']);

        return $repo->findOneBy($conditions, $repo::ML_HYDRATION_ARRAY);

    }


    /**
     * method add new session history item
     *
     * @param $sess_id
     * @param $user_id
     * @param $login_time
     * @param string $application
     * @param $query
     * @return array|null|void
     */
    protected function _updateSessionHistory($sess_id, $user_id, $login_time, $application = null, $query){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        /*
         * if anonymous users need to make history of session - then comment if {} else statement
         */
        if ($user_id > 0){

            $model = $this->_p->Model($this->_p->getVar('user')['session_history_model']);
            $sql = $this->_p->db->getQueryBuilder()->compileInsert($model, array(
                'title' => $sess_id,
                'ip' => $this->_ip,
                'user_id' => $user_id,
                'login_time' => $login_time,
                'lastuse_time' => $this->_p->getTime(),
                'application' => $application,
                'page_nick' => $query
            ));

            return $this->_p->db->query($sql, $model->getModelStorage());
        } else {
            return false;
        }
    }

    /**
     * update current user session
     * @param $sess_id
     * @param $query
     * @param string $application
     *
     * @return array|null|void
     */
    protected function _updateSession($sess_id, $query, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $model = $this->_p->Model($this->_p->getVar('user')['session_model']);
        $storage = $model->getModelStorage();
        $sql = $this->_p->db->getQueryBuilder($storage)->compileUpdate($model, array(
            'ip' => $this->_ip,
            'lastuse_time' => $this->_p->getTime(),
            'page_nick' => $query
        ), array('where' => array(
            'title' => $sess_id,
            //'is_admin' => $is_admin,
            'application' => $application
        )));
        unset($model);

        return $this->_p->db->query($sql, $storage);

    }

    /**
     * creating session
     *
     * @param $sess_id
     * @param $user_id
     * @param string $application
     * @param null $query
     * @return array|null|void
     */
    protected function _createSession($sess_id, $user_id, $application = null, $query = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $model = $this->_p->Model($this->_p->getVar('user')['session_model']);
        $model->setModelData(array(
            'title' => $sess_id,
            'ip' => $this->_ip,
            'user_id' => $user_id,
            'login_time' => $this->_p->getTime(),
            'lastuse_time' => $this->_p->getTime(),
            //'is_admin' => $is_admin,
            'page_nick' => $query,
            'application' => $application
        ));

        $em = $this->_p->db->getManager();
        $id = $em->initPrimaryValue($model);

        $em->save($model);

        return $id;

    }


    /**
     * remove session
     *
     * @param string|null $sess_id
     * @param int|null $user_id
     * @param string $application
     * @return array|bool|null|void
     */
    protected function _removeSession($sess_id = null, $user_id = null, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $conditions = array('where' => array());

        if ($sess_id !== null){
            $conditions['where']['title'] = $sess_id;
        }

        if ($user_id !== null){
            $conditions['where']['user_id'] = $user_id;
        }

        /*if ($is_admin !== null){
            $conditions['where']['is_admin'] = $is_admin;
        }*/

        $conditions['where']['application'] = $application;

        if (!empty($conditions['where'])){
            $model = $this->_p->Model($this->_p->getVar('user')['session_model']);
            $storage = $model->getModelStorage();
            $sql = $this->_p->db->getQueryBuilder()->compileDelete($model, $conditions);
            unset($model);
            return $this->_p->db->query($sql, $storage);

        } else {

            return false;

        }

    }


    /**
     * method tries to find session id by user id and application
     * @param int|null $user_id
     * @param string $application
     * @return array|mixed|null|void
     */
    public function findUserSessionId($user_id = null, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $conditions = array(
            //'no_array_key' => true,
            'where' => array(
                'user_id' => $user_id,
                'application' => $application
            ), 'order' => array(
                'lastuse_time' => 'desc'
            ), 'limit' => 1
        );

        $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['session_model']);

        $sess = $repo->findOneBy($conditions, $repo::ML_HYDRATION_ARRAY);

        return $sess ? $sess['title'] : null;

    }


    /**
     * method check authorization (not authentication)
     *
     * @param string $application
     * @return bool
     */
    public function checkAuth($application = null){

        if (!$application)
            $application = $this->_p->getApplication();

        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }


        // getting session id
		$sess_id = "";

		// if you call check auth, then - you can't use your data object
		$this->data[$application] = null;

        $this->_sess_found = false;

		if (
            $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']) || $this->isAuth($application)/*)*/
        ){

			$this->_app_sess_id[$application] = $sess_id = $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']);

            $this->_sess_found = $this->_findSession($sess_id, $application);

			if ($this->_sess_found){
                $this->_app_id[$application] = $this->_sess_found['user_id'];
            }

            if ($this->_app_id[$application] > 0){
                $this->_app_is_auth[$application] = true;
            }

            if ($this->_p->getVar('is_debug')){

                $this->_p->debug->add("USER: checkAuth : After check cooked session");

            }

        }

		if ($sess_id == ""){

			if ($this->_p->getVar('is_debug')){

                $this->_p->debug->log('CHECK______NO_SESS_ID_________real_sess_id-'.session_id().'::sess_id-'.$sess_id.'::is_admin='.$application.'::'.$this->_app_id[$application].'::'.$this->isAuth()."\n");
			}

            // restore application
            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return false;

		}

		if ($this->_p->getVar('is_debug')){

			$this->_p->debug->add("USER: checkAuth : After check session id");

		}

        if ($this->_sess_found){

            if ($this->_p->getVar('user')['check_session_by_ip'] && $this->_sess_found['ip'] != get_ip()) return false;

            // call helper
            if (!$this->_postAuth(null, $application)){
                // restore application
                if ($old_application){
                    $this->_p->initApplication($old_application);
                }

                return false;
            }

			if ($this->_p->getVar('is_debug')){

				$this->_p->debug->add("USER: checkAuth : After session found");

			}

            // if all right writing to the database lastuse_time value and remove bad elements
			$query = $this->_p->query->buildStat($this->_getClearQuery(), false, true);

			// for admin users - insert new session (for coworking), but not for uptime
			if ($this->_canManipulateSession($query)){

                $this->_updateSessionHistory($sess_id, $this->_app_id[$application], $this->_sess_found['login_time'], $application, $query);

                $this->_updateSession($sess_id, $query, $application);

			}

			if ($this->_p->getVar('is_debug')){

				$this->_p->debug->add("USER: checkAuth : After insert into session table");

			}

			// updating all other old sessions
			// time out - from general variables INFO['user']['lastuse_timeout'] = 60*60*24*2; // two days
			if ($this->_p->getVar('user')['cleanup_mode'] == 'normal'){
				$cfile = 'cron.cleanup_sessions';

                // randomize cleanup
				if (mt_rand(0, $this->_p->getVar('user')['cleanup_mode_normal_probability_range']) == $this->_p->getVar('user')['cleanup_mode_normal_probability_range']/2 && $this->_p->lockFile($cfile, 24*60*60)){
					$this->cleanup();
					$this->_p->unlockFile($cfile);
				}
			}

			if ($this->_p->getVar('is_debug')){

				$this->_p->debug->add("USER: checkAuth : After cleanup sessions");

			}

            // restore application
            if ($old_application){
                $this->_p->initApplication($old_application);
            }

			return true;

		} else {

			if ($this->_p->getVar('is_debug')){
                $this->_p->debug->log('________NO_SESSION________real_sess_id-'.session_id().'::sess_id-'.$sess_id.'::is_admin='.$application.'::'.$this->_app_id[$application].'::'.$this->isAuth($application)."\n");
			}

            // restore application
            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return false;

		}

	}

	/**
	* method cleanup sessions data
	*/
	public function cleanup(){

        $qb = $this->_p->db->getQueryBuilder();

        $model = $this->_p->Model($this->_p->getVar('user')['session_model']);
        $sql = $qb->compileDelete($model, array('where' =>
            array('lastuse_time' => array('op' => '<', 'value' => $this->_p->getTime() - $this->_p->getVar('user')['lastuse_timeout']))));
		$this->_p->db->query($sql, $model->getModelStorage());

        $sql = $qb->compileOptimize($model);
        $this->_p->db->query($sql, $model->getModelStorage());

        $model = $this->_p->Model($this->_p->getVar('user')['session_history_model']);
        $sql = $qb->compileDelete($model, array('where' =>
            array('lastuse_time' => array('op' => '<', 'value' => $this->_p->getTime() - $this->_p->getVar('user')['lastuse_history_timeout']))));

        $this->_p->db->query($sql, $model->getModelStorage());

        $sql = $qb->compileOptimize($model);
        $this->_p->db->query($sql, $model->getModelStorage());

        unset($model);
        unset($qb);

	}


	/**
	* function return params (or selected parameter) by user id
	* Use this function - it is recomended for use
	*
	* @param  mixed $id  array or integer of the ids - be careful - you need to set only integer values
     * @param null|string $param
	* @param  string $application  current application name
	* @param  array $data   array with data object - no need to select from DB
	*
	* @return	mixed  ID of the user or false in otherwise
	*/
	public function get($id = false, $param = null, $application = null, $data = array()){

        if (!$application){
            $application = $this->_p->getApplication();
        }

		if ($id === false || $id === null)
			$id = $this->_app_id[$application];

		if ($id <= 0)
			return null;

        $model = null;
        $primary = null;

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        // find session id in saved sessions
        if (empty($data)){
            $model = $this->_p->Model($this->_p->getVar('user')['model']);

            $conditions = array('where' => array());
            if (is_array($id))
                $conditions['where'][$model->getPrimaryKey()] = array('op' => 'in', 'value' => $id);
            else
                $conditions['where'] = array($model->getPrimaryKey() => $id);

            $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, $conditions);

            $data = $this->_p->db->select($sql, $model->getModelStorage());

        } else

            $data = array($id => $data);

		$app = $this->_p->App($this->_p->getVar('user')['app']);

		if ($data){

			// getting rules
            $rules = $this->getUserRules($application, $id, true);

            if (!$model){
                $model = $this->_p->Model($this->_p->getVar('user')['model']);
            }

			foreach ($data as $k => $v){
				if (isset($v['picture']) && $v['picture'] && $model){
                    $model->setModelData($v);
					$data[$k]['picture'] = $model->getPictures('picture');
				} else {
				// if nothing found
					$data[$k]['picture'] = '';
				}
				// getting href
				if (method_exists($app, 'buildQuery') && $v['href'] == ''){
					$data[$k]['href'] = $app->buildQuery($v);
				}
				$data[$k]['name'] = $this->getAnyName($v);
				$data[$k]['sex'] = $v['gender'] ? ($v['gender'] == '1' ? 'man' : 'woman' ) : 'nosex';

				if (isset($rules[$k])){
					$data[$k]['rules'] = $rules[$k];
					$data[$k]['is_admin'] = $this->hasRule(ADMIN_RULE, 'edit', $application, $data[$k]['rules']);
					$data[$k]['is_moderator'] = $this->hasRule(MODERATOR_RULE, 'edit', $application, $data[$k]['rules']);
					$data[$k]['is_editor'] = $this->hasRule(EDITOR_RULE, 'edit', $application, $data[$k]['rules']);
				}

                // checking for social sessions
                if (!empty($data[$k]['session_facebook']) && is_string($data[$k]['session_facebook'])){
                    $data[$k]['facebook_raw'] = json_decode($data[$k]['session_facebook'], true);
                }

            }

			unset($rules);

			if (!is_array($id)){
				reset($data);
				$data = current($data);
                $data['ip'] = $this->_ip;
			}

			if (isset($data['session']) && $data['session'] != ''){
				$data['session'] = $this->storage->parse($data['session']);
			}

            unset($model);

            // restore application
            if ($old_application){
                $this->_p->initApplication($old_application);
            }

			// if all_right	return data
			if ($param != '')
				return $data[$param];
			else
				return $data;

		} else {

            unset($model);
            // restore application
            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return null;

		}

	}


    /**
     * setter for user (backend or frontend)
     * @param bool|int $id
     * @param array $data
     * @param string $application flag if user is admin
     * @param array $params additional parameters for setter
     * @return array|null
     */
    public function set($id = false, $data = null, $application = null, $params = array()){

        if (!$application)
            $application = $this->_p->getApplication();

        if ($id === false || $id === null)
            $id = $this->_app_id[$application];

        if ($id <= 0)
            return null;

        if (!$data)
            return null;

        $result = null;

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        $model = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['model'])->findOne($id);

        if ($model){

            $result = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['model'])->update($model, $data);

        }

        // update current user value
        if ($id == $this->_app_id[$application] && !$params['no_session_update'] && $result){
            foreach ($data as $k => $v){
                if (!preg_match('/^(\+\+|--)\d+$/', $v)){
                    $this->setData($k, $v);
                }
            }
        }

        // restore application
        if ($old_application){
            $this->_p->initApplication($old_application);
        }

        return $result;

    }

    /**
     * set current user data value
     * @param $key
     * @param $val
     */
    public function setData($key, $val){

        if (!is_array($this->data[$this->_p->getApplication()]))
            $this->data[$this->_p->getApplication()] = array();

        $this->data[$this->_p->getApplication()][$key] = $val;

    }

    /**
     * get user's rules list with `nick` keys
     * @param string $application
     * @param null $user_id
     * @return array
     */
    public function getUserRulesNick($application = null, $user_id = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $rules = $this->getUserRules($application, $user_id);

        $rule_nicks = array();
        if ($rules){
            foreach ($rules as $k => $v){
                if ($v['nick']){
                    $rule_nicks[$v['nick']] = $v['mask'];
                } else {
                    $rule_nicks[$k] = $v['mask'];
                }
            }
        }

        return $rule_nicks;

    }


    /**
     * get user name
     * @param null|array $data
     * @return string
     */
    public function getName($data = null){

        $name = array();
        if (!$data && $this->data[$this->_p->getApplication()])
            $data = &$this->data[$this->_p->getApplication()];

        if ($data && !empty($data)){
            if (isset($data['sname']))
                $name[] = $data['sname'];

            if (isset($data['fname']))
                $name[] = $data['fname'];

            if (isset($data['mname']))
                $name[] = $data['mname'];
        }

        return trim(join(' ', $name));

    }

    /**
     * method get user any name, which found
     * @param null|array $data
     * @return string
     */
    public function getAnyName($data = null){

        $name = '';
        if (!$data && $this->data[$this->_p->getApplication()])
            $data = &$this->data[$this->_p->getApplication()];

        if ($data && !empty($data)){
            $name = $this->getName($data);
            if (!$name){
                $name = $data['username'] ? $data['username'] : $data['usernick'];
            }
        }

        return $name;

    }

    /**
     * method return user rules
     * @param string $application
     * @param null $user_id
     * @param bool $force_array
     * @return array|mixed array of 'id' => 'nick'
     */
    public function getUserRules($application = null, $user_id = null, $force_array = false){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }


        if ($user_id == null)
			$user_id = $this->_app_id[$application];

        $rules_model = $this->_p->Model($this->_p->getVar('user')['rules_model']);

        $tbl_rules = $rules_model->getModelTable();
        $lnk_table = $this->_p->Model($this->_p->getVar('user')['model'])->getModelTable();
        $tbl_urules = $this->_p->Model($this->_p->getVar('user')['user_rules_model'])->getModelTable();

		if (!is_array($user_id))
			$user_id = array($user_id);

		// checking rules, that not in cache
		$not_in_cache = array();
		$urules = array();
		foreach ($user_id as $uid){

			if (!is_array($res = $this->_p->cache->getSerial('portal.user_rules.'.$tbl_urules.$tbl_rules.(int)$uid))){

				$not_in_cache[] = (int)$uid;

			} else {

				$urules[$uid] = $res;

			}

		}

		$urules_parsed = array();

		if (!empty($not_in_cache)){

            // extrace default rules
            $default_rules = $this->_p->cache->getSerial('portal.user_rules_default.'.$application.'.user');
            if ($default_rules === false && isset($rules_model->getModelFields()['is_default'])){
                // getting all default auth rules
                $repo = $this->_p->db->getManager()->getRepository($rules_model);

                $default_rules = (array)$repo->findBy(array(
                    'select' => array('a' => array(array('value' => 15, 'nick' => 'type'))),
                    'where' => array('is_default' => 1),
                    'group' => array('real_id')
                ), $repo::ML_HYDRATION_ARRAY);

                $this->_p->cache->saveSerial('portal.user_rules_default.'.$application.'.user', $default_rules);
            } else {
                $default_rules = array();
            }

            //if (!($application != 'backend' && count($not_in_cache) == 1 && $not_in_cache[0] == -1)){

                $model = $this->_p->Model($this->_p->getVar('user')['rules_model']);
                $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, array(
                    'select' => array('b' => array('type', 'elem_id')),
                    'join' => array(array(
                        'tbl_name' => $tbl_urules,
                        'alias' => 'b',
                        'conditions' => array(
                            'real_id' => array('table' => 'a', 'value' => 'b.this_id', 'type' => 'foreign_key')
                        )
                    )),
                    'where' => array(
                        'elem_id' => array('table' => 'b', 'op' => 'in', 'value' => $not_in_cache),
                        'tbl_name' => array('table' => 'b', 'value' => $lnk_table),
                        //'real_id' => array('table' => 'a', 'value' => 'b.this_id', 'type' => 'foreign_key')
                    )
                ));

                $res = array_merge($default_rules, (array)$this->_p->db->select($sql, $model->getModelStorage()));

            /*} else {
                $res = $default_rules;
            }*/

            if ($res){
				foreach ($res as $arr){
					$arr['mask'] = array();
					foreach ($this->_p->getVar('rules_mask') as $bit => $v){
						$arr['mask'][$v] = $arr['type'] & (1 << $bit);
					}

					if (!isset($urules_parsed[$arr['elem_id']]))
						$urules_parsed[$arr['elem_id']] = array();

					$urules_parsed[$arr['elem_id']][$arr['real_id']] = $arr;
				}
            }

			if (!empty($not_in_cache)){
				foreach ($not_in_cache as $k){
					$this->_p->cache->saveSerial('portal.user_rules.'.$tbl_urules.$tbl_rules.(int)$k, isset($urules_parsed[$k]) ? $urules_parsed[$k] : array());
                    if (isset($urules_parsed[$k]))
					    $urules[$k] = $urules_parsed[$k];
				}
			}

        }
		unset($urules_parsed);
		unset($not_in_cache);
        unset($rules_model);

		reset($urules);

        if ($old_application){
            $this->_p->initApplication($old_application);
        }

        return count($user_id) == 1 && !$force_array ? current($urules) : $urules;

		/*if (!is_array($res = $this->_p->cache->getSerial('portal.user_rules.'.$tbl_urules.$tbl_rules.(int)$user_id))){

			$res = array();
			$sql = "SELECT e.real_id AS ARRAY_KEY,e.nick,b.type FROM ".$tbl_urules." b,
				 ".$tbl_rules." e
				WHERE b.elem_id=".(int)$user_id." AND b.tbl_name='".$lnk_table."'
				AND e.real_id=b.this_id";

			$res = $this->_p->db->select($sql);
			if ($res)
				foreach ($res as $i => $arr){
					$arr['mask'] = array();
					foreach ($this->_p->vars['rules_mask'] as $bit => $v){
						$arr['mask'][$v] = $arr['type'] & (1 << $bit);
					}
					$res[$i] = $arr;
				}

			$this->_p->cache->saveSerial('portal.user_rules.'.$tbl_urules.$tbl_rules.(int)$user_id, $res);
		}

		return $res;*/
	}


    /**
     * method return anonymous rules list
     *
     * @param string $application
     * @return array|bool|mixed|null|void
     */
    public function getAnonymousRules($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        if (!($rules = $this->_p->cache->getSerial('portal.anonym_rules_'.$application.$this->_p->getLangId()))){

            if (isset($this->_p->Model($this->_p->getVar('user')['rules_model'])->getModelFields()['is_anonymous'])){

                // getting all anonymous rules
                $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['rules_model']);

                $rules = $repo->findBy(array(
                    'where' => array(
                        'is_anonymous' => 1,
                        'lang_id' => $this->_p->getLangId()
                    )
                ), $repo::ML_HYDRATION_ARRAY);

                unset($repo);

                $this->_p->cache->saveSerial('portal.anonym_rules_'.$application.$this->_p->getLangId(), $rules);

            }

		}

        if ($old_application){
            $this->_p->initApplication($old_application);
        }

        return $rules;

	}

    /**
     * set to object anonymous rules
     * @param oModel $model
     * @param null $application
     */
    public function setObjectAnomymousRules($model, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        $anonymous_rules = $this->getAnonymousRules($application);

        if ($anonymous_rules){

            $this->setObjectRules($model, $anonymous_rules, $application);

        }

        if ($old_application){
            $this->_p->initApplication($old_application);
        }

    }


    /**
     * set to object selected rules
     * @param oModel $model
     * @param array $rules array of rules
     * @param null $application
     */
    public function setObjectRules($model, $rules = array(), $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        if ($rules){

            $rmodel = $this->_p->Model($this->_p->getVar('user')['user_rules_model']);
            $qb = $this->_p->db->getQueryBuilder($rmodel->getModelStorage());

            $data = array();

            foreach ($rules as $v){

                $data[] = array(
                    'this_id' => get_primary($v),
                    'elem_id' => $model->getPrimaryValue(),
                    'tbl_name' => $model->getModelTable(),
                    'model' => $model->getModelName()
                );

            }

            $sql = $qb->compileInsert($rmodel, $data, true);

            $this->_p->db->query($sql, $rmodel->getModelStorage());

            unset($rmodel);
            unset($qb);

        }

        if ($old_application){
            $this->_p->initApplication($old_application);
        }

    }



    /**
     * check user rules by enabled array by nick
     *
     * @param string $rule rule nick for checking
     * @param string $mode mode's nick for checking - can be array or string
     * @param string $application current application name
     * @param array $rules array for rules for checking
     * @return array|bool
     */
    public function hasRule($rule, $mode = 'read', $application = null, $rules = array()){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        if (!$rules || empty($rules)){
			$rules = isset($this->_app_rules[$application]) ? $this->_app_rules[$application] : array();
		}

		$found = false;
		if (is_array($rules)){
			foreach ($rules as $v){
				if ($v['nick'] == $rule){
					if (is_array($mode))
						$found = array_intersect_key(array_flip($mode), $v['mask']);
					else
						$found = $v['mask'][$mode];

					break;
				}
			}
		}
		return $found;

	}

    /**
     * method return users ids by rules
     * @param string $application
     * @param array $rules
     * @param array $need_fields
     * @return array|bool array of 'id' => [need_fields]
     */
    public function getUsersByRules($application = null, $rules = array(), $need_fields = array()){

		if (!$rules || empty($rules))
			return false;

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        if (!is_array($rules))
            $rules = array($rules);


        $rules_model = $this->_p->Model($this->_p->getVar('user')['rules_model']);

        $tbl_rules = $rules_model->getModelTable();
        $tbl_urules = $this->_p->Model($this->_p->getVar('user')['user_rules_model'])->getModelTable();

        $model = $this->_p->Model($this->_p->getVar('user')['model']);

        $conditions = array(
            'no_array_key' => true,
            'join' => array(
                array('tbl_name' => $tbl_urules, 'alias' => 'b'),
                array('tbl_name' => $tbl_rules, 'alias' => 'e'),
            ),
            'where' => array(
                'id' => array('table' => 'a', 'type' => 'foreign_key', 'value' => 'b.elem_id'),
                'tbl_name' => array('table' => 'b', 'value' => $model->getModelTable()),
                'this_id' => array('table' => 'b', 'type' => 'foreign_key', 'value' => 'e.real_id'),
                'nick' => array('table' => 'e', 'op' => 'in', 'value' => $rules)
        ));

		/* if need fields not empty */
		if (!empty($need_fields)){
            $conditions['select'] = array('a' => array_merge(array($model->getPrimaryKey()), $need_fields));
		}

        $sql = $this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, $conditions);

		$res = $this->_p->db->select($sql, $model->getModelStorage());

		$return = array();
		if ($res && !empty($res)){

			foreach ($res as $arr){

				if (!$return[$arr[$model->getPrimaryKey()]])
					$return[$arr[$model->getPrimaryKey()]] = $this->get($arr[$model->getPrimaryKey()], null, $application, $arr);

			}

		}

        unset($model);

        if ($old_application){
            $this->_p->initApplication($old_application);
        }

        return $return;

	}

	/**
	* methods for starting/stoping PHP session
	*/
	public function startSession(){

        // need for regenerating id for restore old session data
        // checking for console
        if ($this->_p->getVar('APP_TYPE') == WEBT_APP_CONSOLE)
            return false;

        if ($this->_session_is_locked){
            throw new \Exception('Session is locked');
        }

        session_regenerate_id();

        // setting session on all subdomains
        session_name($this->_p->getVar('user')['session_name']);
        session_cache_limiter('nocache');
        if ($this->_p->getVar('is_debug'))
            $this->_p->debug->add("CORE: Before session_start");

        session_start();

        if ($this->_p->getVar('is_debug'))
            $this->_p->debug->add("CORE: After session_start");

        return true;
	}

	public function stopSession(){

        if ($this->_p->getVar('APP_TYPE') == WEBT_APP_CONSOLE)
            return false;

        $this->unlockSession();

		// session_write_close always destroy session!!!
		session_write_close();
        return true;
	}

    /**
     * locks session
     * @return bool
     */
    public function lockSession(){

        $this->_session_is_locked = true;

        return true;
    }

    /**
     * unlock session
     * @return bool
     */
    public function unlockSession(){

        $this->_session_is_locked = false;

        return true;
    }

    /**
     * set PHP session value
     * we need this methods, because sessions in PHP are very slow and blocked script
     *
     * @param $name
     * @param string $value
     * @return bool
     */
    public function setSessionVal($name, $value = ''){

		/**
		 * check - if value always in the cache
		 */
		if ($this->_session_cache && isset($this->_session_cache[$name]) && $this->_session_cache[$name] == $value)
			return true;

        if (!$this->_session_is_locked)
		    $this->startSession();

		/**
		 * caching session for future checking
		 */
		if (!$this->_session_cache){
			$this->_session_cache = array();
			$this->_session_cache = $_SESSION;
		}

		if ($value === '' || $value === null){
			unset($_SESSION[$name]);
			unset($this->_session_cache[$name]);
		} else {
			$_SESSION[$name] = $value;
			$this->_session_cache[$name] = $value;
		}
		//dump_file('Save to session: '.session_id().' :: '.$name, false);
		//dump_file($value, false);

        if (!$this->_session_is_locked)
		    $this->stopSession();

		if ($this->_p->getVar('is_debug'))
			$this->_p->debug->add("CORE: After setSessionVal: ".$name);

		//$this->getSessionVal();

        return true;
	}

    /**
     * get values from PHP session mechanism
     * @param string $name
     * @return mixed
     */
    public function getSessionVal($name = ''){

		/**
		 * if we can find data in cache - simply get it from it
		 */
		if ($this->_session_cache){
            if ($name === '')
                return $this->_session_cache;
            elseif (isset($this->_session_cache[$name]))
			    return $this->_session_cache[$name];
        }

        if (!$this->_session_is_locked)
		    $this->startSession();

		/**
		 * caching session for future checking
		 */
		if (!$this->_session_cache){
			$this->_session_cache = array();
			$this->_session_cache = $_SESSION;
		}


		$val = $name !== '' ? $_SESSION[$name] : $_SESSION;

		//dump_file('Get from session: '.session_id().' :: '.$name, false);
		//dump_file($_SESSION, false);

        if (!$this->_session_is_locked)
		    $this->stopSession();

		if ($this->_p->getVar('is_debug'))
			$this->_p->debug->add("CORE: After getSessionVal: ".$name);

		return $val;

	}

    /**
     * generate session id
     *
     * @return string
     */
    public function generateSessionID(){
        return md5(uniqid(""));
    }


    /**
     * postprocess after authorized
     * @param $user_id
     * @param string $application
     * @param null $user_data
     * @return bool
     */
    protected function _postAuth($user_id, $application = null, $user_data = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // updating data
        $this->data[$application] = $this->get($user_id ? $user_id : $this->_app_id[$application], false, $application);

        if (!$this->data[$application]) return false;

        // update rules
        $this->_app_rules[$application] = $this->getUserRules($application);
        $this->_app_session[$application] = $this->getUserSession($user_id ? $user_id : $this->_app_id[$application], $application, array('session' => $user_data ? $user_data['session'] : $this->data[$application]['session']));

        $this->data[$application]['is_admin'] = $this->hasRule(ADMIN_RULE, 'edit', $application);
        $this->data[$application]['is_moderator'] = $this->hasRule(MODERATOR_RULE, 'edit', $application);
        $this->data[$application]['is_editor'] = $this->hasRule(EDITOR_RULE, 'edit', $application);
        $this->data[$application]['counts'] = $this->data[$application]['counts'] != '' ? unserialize($this->data[$application]['counts']) : '';

        return true;

    }


    /**
     * authorize anonymous user
     * @param string $application
     */
    public function authAnonymous($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // register user
		// check for Cookie!
        $find_old_session = true;

		if ($this->_p->cookie->get($this->_p->getVar('user')['session_cookie'])){

			$sess_id = $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']);

        } else {

            $find_old_session = false;
			$sess_id = $this->generateSessionID();

			$this->_p->cookie->set($this->_p->getVar('user')['session_cookie'], $sess_id, $this->_p->getVar('user')['cookie_timeout'], "/", $this->_p->getVar('server_name'), false);

		}

        $this->_app_sess_id[$application] = $sess_id;
        $this->_app_id[$application] = $this->_default_id;

        $this->_app_rules[$application] = $this->getAnonymousRules($application);
		$this->data[$application] = null;

		$query = $this->_p->query->buildStat($this->_getClearQuery(), false, true);

		if ($this->_canManipulateSession($query)){

            if (!$this->_sess_found && $find_old_session && ($old_session = $this->_findSession($sess_id, $application, $this->_default_id))){

                $this->_updateSession($sess_id, $query, $application);

            } elseif ($this->_sess_found){

                $this->_updateSession($sess_id, $query, $application);

            } else {

                $this->_createSession($sess_id, $this->_app_id[$application], $application, $query);

            }

            $this->_updateSessionHistory($sess_id, $this->_app_id[$application], $this->_p->getTime(), $application, $query);

		}

		if ($this->_p->getVar('is_debug')){

            $this->_p->debug->log('AUTH_ANONYM_____real_sess_id-'.session_id().'::sess_id-'.$sess_id.'::is_admin=0::'.$this->_app_id[$application].'::'.$this->isAuth()."\n");

		}

	}


	/**
	* method auth user by his login and code
	* code have change, when user change main data of the profile
	*/
	public function authByCode($user, $code, $application = null, $save_user_in_cookie = false){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        if (!empty($user)){

            // check for application
            $old_application = null;
            if ($this->_p->getApplication() != $application){
                $old_application = $this->_p->getApplication();
                $this->_p->initApplication($application);
            }

            $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['model']);

            $res = $repo->findOneBy(array(
                'where' => array(
                    'username' => $user
                )
            ), $repo::ML_HYDRATION_ARRAY);

            unset($repo);

			if (!empty($res)){
				if ($this->checkAuthCode($code, $res)){

                    if ($old_application){
                        $this->_p->initApplication($old_application);
                    }

                    return $this->auth($user, null, $application, $save_user_in_cookie, true);
				}
			}

            if ($old_application){
                $this->_p->initApplication($old_application);
            }

        }

		return false;

	}

    /**
     * method auth user by special session code, which give
     * code have change, when user change main data of the profile
     */
    public function authByAccessToken($access_token, $save_user_in_cookie = false, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        if (!empty($access_token)){

            if ($user = $this->getByAccessToken($access_token)){
                return $this->auth($user['username'], null, $application, $save_user_in_cookie, true);
            }

        }

        return false;

    }



	/**
	* authorize user
	*
	* @param string	$user username
	* @param string $pass password
	* @param string $application[option] application for auth
	* @param bool $save_user_in_cookie[option] flag for save user name in cookie (! do not add it to the admins)
	* @param bool $always_checked[option] flag for not checking user and auth him forth
	*
	* @return bool	if Ok - return true
	*/
	public function auth($user, $pass, $application = null, $save_user_in_cookie = false, $always_checked = false){

		// protect from fields where user or password has not set
		if (($user == '' || $pass == '') && !$always_checked){
			return false;
		}

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        $conditions = array('where' => array('username' => $user));

		// using two tables for admins and for other users
        $model = $this->_p->Model($this->_p->getVar('user')['model']);

        if (isset($model->getModelFields()['is_on'])){
            $conditions['where']['is_on'] = 1;
        }

        $repo = $this->_p->db->getManager()->getRepository($model);

		if (!$always_checked)
            $conditions['where']['password'] = $this->encryptPassword($pass);

		$arr = $repo->findOneBy($conditions, $repo::ML_HYDRATION_ARRAY);

		if (!$arr && !$always_checked){

            unset($conditions['where']['username']);
            $conditions['where']['email'] = $user;

            $arr = $repo->findOneBy($conditions, $repo::ML_HYDRATION_ARRAY);

		}

        if ($arr){

			// check if is users with defined ip and it have ip address
			if ($this->_p->getVar('user')['is_check_auth_ip'] && $arr['ip'] != '' && $this->_ip != $arr['ip']){

                if ($old_application){
                    $this->_p->initApplication($old_application);
                }

                return false;
			}

			// register user
			// check old sess_id - it is very useful
			$sess_id = $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']) ? $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']) : $this->generateSessionID();

            // start standard php session
            $this->startSession();

            $primary = $model->getPrimaryKey();

			// for admins setting flag (only for statistic)

            $this->_p->cookie->remove($this->_p->getVar('user')['session_cookie']);

            $this->_app_sess_id[$application] = $sess_id;
            $this->_app_id[$application] = $arr[$primary];
            $this->_app_is_auth[$application] = true;


            $this->set(
                $this->_p->user->getId($application),
                array('last_login_date' => $this->_p->getTime()),
                $application,
                array('no_session_update' => true)
            );


            // setting cookie with sesion id for cache system
            $this->_p->cookie->set($this->_p->getVar('user')['session_cookie'], $sess_id, $this->_p->getVar('user')['is_cooked_session'] ? $this->_p->getVar('user')['cookie_timeout'] : 0, "/", $this->_p->getVar('server_name'), false);
            $this->_p->cookie->set($this->_p->getVar('user')['session_authed_cookie'], $arr[$primary], $this->_p->getVar('user')['cookie_timeout'], "/", $this->_p->getVar('server_name'), false);

            // magic restore data from user cookies to the session
            if ($this->_p->getVar('user')['is_merge_anonym_session'])
                $this->storage->merge('cookie');

            // call helper
            if (!$this->_postAuth(null, $application, $arr)){

                if ($old_application){
                    $this->_p->initApplication($old_application);
                }

                return false;
            }

			// if all right deleting old session of these user and writing new session
            $this->_removeSession(null, $arr[$primary], $application);
            $this->_removeSession($sess_id, $this->_default_id, $application);

			$query = $this->_p->query->buildStat($this->_getClearQuery(), false, true);

			if ($this->_canManipulateSession($query)){

                $this->_createSession($sess_id, $arr[$primary], $application, $query);

                $this->_updateSessionHistory($sess_id, $arr[$primary], $this->_p->getTime(), $application, $query);

			}


			if ($this->_p->getVar('is_debug')){

                $this->_p->debug->log('________AUTH_USER_________real_sess_id-'.session_id().'::sess_id-'.$this->_app_sess_id[$application].'::is_admin='.$application.'::'.$this->_app_id[$application].'::'.$this->isAuth($application)."\n");

			}

			$this->init($application);

            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return true;

		} else {

            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return false;

		}

	}

    /**
     * unauthorize user
     *
     * @param string $application application name
     * @return bool if Ok - return true
     */
    public function unauth($application = null){

		// getting session id
		$sess_id = "";

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

		if ($this->_p->cookie->get($this->_p->getVar('user')['session_cookie']))
			$sess_id = $this->_p->cookie->get($this->_p->getVar('user')['session_cookie']);

        if (isset($this->_app_sess_id[$application]) && $this->_app_sess_id[$application])
            $sess_id = $this->_app_sess_id[$application];

        $this->_sess_found = false;

		if ($sess_id == ""){

            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return false;

		}

		$result = $this->_removeSession($sess_id, null, $application);

		if ($result){

			// remove cookie session
			$this->_p->cookie->remove($this->_p->getVar('user')['session_cookie']);

            // remove main flag for authed
            $this->_p->cookie->remove($this->_p->getVar('user')['session_authed_cookie']);

            // but now making magic saving session data!
            if ($this->_p->getVar('user')['is_merge_anonym_session'])
                $this->storage->merge('session');

            $this->_app_sess_id[$application] = false;
            $this->_app_id[$application] = $this->_default_id;
            $this->_app_is_auth[$application] = false;
            $this->_app_rules[$application] = array();

			$this->data[$application] = null;
			$this->stopSession();

            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return true;
		} else {

            if ($old_application){
                $this->_p->initApplication($old_application);
            }

            return false;
		}

	}



	/**
     * checking fields rule
     * @param array $field_rules
     * @param array $check_rules
     * @param string $application
     * @return bool
     */
    public function checkFieldRules($field_rules = array(), $check_rules = array(), $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        if (is_array($field_rules)){

			$inter = array();
			foreach ($field_rules as $v){
				if (isset($this->_app_rules[$application]) && is_array($this->_app_rules[$application])){
					foreach ($this->_app_rules[$application] as $x){
						if ($v == $x['nick']){
							$inter[] = $v;
						}
					}
				}
			}

			if (empty($inter)) return false;
			else {
				if (empty($check_rules))
					$check_rules = array('read');
				foreach ($inter as $v){
					if (!$this->_p->user->hasRule($v, $check_rules, $application))
						return false;
					else
						return true;
				}
			}
		}

        return false;

	}


    /**
     * function check users rules for the selected object
     * Use this function only after checkAuth
     *
     * @param $tbl_name
     * @param $elem_id
     * @param string $application[option] current application name
     * @param string|null $rule_nick[option] it is nick (if needed) of the rule, that module want to see
     * @param string $method
     * @param array $user_rules
     * @return array|bool|mixed|null|void array of rules if user have rules
     */
    public function checkObjectRules($tbl_name, $elem_id, $application = null, $rule_nick = null, $method = 'read', $user_rules = array()){

		if (trim($tbl_name) == '' || !$elem_id) return false;

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $sess_id = $user = null;

		if (isset($this->_app_sess_id[$application]) && $this->_app_sess_id[$application]){

			$sess_id = $this->_app_sess_id[$application];
			$user = $this->_app_id[$application];

		}

		if (!$sess_id || !$user){

			if ($this->_p->getVar('is_debug')){

                $this->_p->debug->log('PERM____NO_SESS_ID_____real_sess_id-'.session_id().'::sess_id-'.$sess_id.'::is_admin='.$application.'::'.$user.'::'.$this->isAuth($application)."\n");

			}

			return false;

		}

        $model = $this->_p->Model($this->_p->getVar('user')['rules_model']);
        $tbl_rules = $model->getModelTable();

        $rmodel = $this->_p->Model($this->_p->getVar('user')['user_rules_model']);
        $tbl_urules = $rmodel->getModelTable();

        $umodel = $this->_p->Model($this->_p->getVar('user')['model']);

        $qb = $this->_p->db->getQueryBuilder($rmodel->getModelStorage());

		$rev_rules = array_flip($this->_p->getVar('rules_mask'));

        $conditions = array('where' => array());

		if (is_array($elem_id)){

            $conditions['where'][] = array('table' => 'a', 'field' => 'elem_id', 'value' => $elem_id, 'op' => 'in');

			$elem_id = array_map('intval', $elem_id);

		} else {

            $conditions['where'][] = array('table' => 'a', 'field' => 'elem_id', 'value' => $elem_id);

		}

		// check if our user is anonymous
		if (!$this->isAuth($application) && isset($model->getModelFields()['is_anonymous'])){

			// for anonymous and not admins we save datas in serial cache
            $cache = 'rules.anonymous.'.$tbl_name.'.'.md5(join(',', (array)$elem_id)).'.'.(int)$rev_rules[$method];
			if (!($result1 = $this->_p->cache->getSerial($cache))){

                $cconditions = $conditions;
                $cconditions['select'] = array('__groupkey__' => 'elem_id');
                $cconditions['join'] = array(
                    array('tbl_name' => $tbl_rules, 'alias' => 'b', 'conditions' => array(
                        array('table' => 'b', 'field' => 'real_id', 'value' => 'a.this_id', 'type' => 'foreign_key')
                    )),
                );
                $cconditions['where'][] = array('table' => 'b', 'field' => 'is_anonymous', 'value' => 1);
                //$cconditions['where'][] = array('table' => 'b', 'field' => 'real_id', 'value' => 'a.this_id', 'type' => 'foreign_key');
                $cconditions['where'][] = array('field' => 'tbl_name', 'value' => $tbl_name);
                $cconditions['where'][] = array('field' => 'type', 'value' => $rev_rules[$method], 'function' => 'bitsearch()');

                $sql1 = $qb->compile($rmodel, $cconditions);

				$result1 = $this->_p->db->select($sql1, $rmodel->getModelStorage());

				$this->_p->cache->saveSerial($cache, $result1);

			}

		} else {

			if ($rule_nick){

                $conditions['where'][] = array('table' => 'a', 'field' => 'nick', 'value' => $rule_nick);
                //$conditions['where'][] = array('table' => 'a', 'field' => 'this_id', 'value' => 'e.real_id', 'type' => 'foreign_key');
                $conditions['join'] = array(
                    array('tbl_name' => $tbl_rules, 'alias' => 'e', 'conditions' => array(
                        array('table' => 'a', 'field' => 'this_id', 'value' => 'e.real_id', 'type' => 'foreign_key')
                    ))
                );

			}

            $conditions['select'] = array('__groupkey__' => 'a.elem_id', 'a' => 'id');

            // if always set user rules, then check first them
            if (!empty($user_rules)){
                // collecting user rules with property
                $crules = array();

                foreach ($user_rules as $v){
                    if ($v['mask'][$method])
                        $crules[] = (int)$v['real_id'];
                }

                if (!$crules)
                    return false;

                $conditions['where'][] = array('table' => 'a', 'field' => 'this_id', 'value' => $crules, 'op' => 'in');
                $conditions['where'][] = array('table' => 'a', 'field' => 'tbl_name', 'value' => $tbl_name);
                $conditions['where'][] = array('table' => 'a', 'field' => 'type', 'value' => $rev_rules[$method], 'function' => 'bitsearch()');

                $sql1 = $qb->compile($rmodel, $conditions);

            } else {

                $conditions['join'][] = array('tbl_name' => $tbl_urules, 'alias' => 'b', 'conditions' => array(
                    array('table' => 'a', 'field' => 'this_id', 'value' => 'b.this_id', 'type' => 'foreign_key')
                ));
                $conditions['join'][] = array('model' => $umodel, 'alias' => 'c', 'conditions' => array(
                    array('table' => 'c', 'field' => $umodel->getPrimaryKey(), 'value' => 'b.elem_id', 'type' => 'foreign_key')
                ));

                $conditions['where'][] = array('table' => 'c', 'field' => $umodel->getPrimaryKey(), 'value' => $user);
                //$conditions['where'][] = array('table' => 'c', 'field' => $umodel->getPrimaryKey(), 'value' => 'b.elem_id', 'type' => 'foreign_key');
                $conditions['where'][] = array('table' => 'b', 'field' => 'tbl_name', 'value' => $umodel->getModelTable());
                //$conditions['where'][] = array('table' => 'a', 'field' => 'this_id', 'value' => 'b.this_id', 'type' => 'foreign_key');
                $conditions['where'][] = array('table' => 'a', 'field' => 'tbl_name', 'value' => $tbl_name);
                $conditions['where'][] = array('table' => 'a', 'field' => 'type', 'value' => $rev_rules[$method], 'function' => 'bitsearch()');
                $conditions['where'][] = array('table' => 'b', 'field' => 'type', 'value' => $rev_rules[$method], 'function' => 'bitsearch()');

                $sql1 = $qb->compile($rmodel, $conditions);
            }

			$result1 = $this->_p->db->select($sql1, $rmodel->getModelStorage());

		}

        unset($qb);
        unset($rmodel);
        unset($umodel);
        unset($sql);

		if ($result1 && !empty($result1)){

			return $result1;

		} else {
			// Oh no!!! We dont have permissions to do what we want

			if ($this->_p->getVar('is_debug')){
                $this->_p->debug->log('____NO_PERM_____real_sess_id-'.session_id().'::sess_id-'.$sess_id.'::is_admin='.$application.'::'.$user.'::'.$this->isAuth()."\n");
			}

			return false;

		}

	}


    /**
     * checking rules for uploaded files
     * method always use 'read' rule - because we downloading files
     *
     * @param $real_name
     * @param bool $rule_nick
     * @return array|bool|mixed|null|void
     */
    public function checkUploadRules($real_name, $rule_nick = false){

        $model = $this->_p->Model($this->_p->getVar('upload')['model']);

		$tbl_upload = $model->getModelTable();
		$method = 'read';

		// getting element id
        $elem_id = $this->_p->db->selectCell($this->_p->db->getQueryBuilder($model->getModelStorage())->compile($model, array(
            'select' => 'real_id',
            'limit' => 1,
            'where' => array(
                'is_on' => 1,
                'realname' => rawurldecode($real_name)
            )
        )), $model->getModelStorage());

        unset($model);

		return $this->checkObjectRules($tbl_upload, $elem_id, null, $rule_nick, $method);

	}



	/**
	* saving user session to the database
	* @param string $application[option] flag for checking admin
     * @return boolean
	*/
	public function saveUserSession($application = null){

        if (!$application)
            $application = $this->_p->getApplication();

        // check for application
        $old_application = null;
        if ($this->_p->getApplication() != $application){
            $old_application = $this->_p->getApplication();
            $this->_p->initApplication($application);
        }

        // using two tables for admins and for other users
        $model = $this->_p->getVar('user')['model'];
        $key = $this->_app_id[$application];

        $model = $this->_p->Model($model);
        $storage = $model->getModelStorage();

        $sql = $this->_p->db->getQueryBuilder($storage)->compileUpdate($model, array(
            'session' => serialize($this->_app_session[$application])
        ), array(
            'where' => array('[PRIMARY]' => $key)
        ));

        unset($model);
        if ($old_application){
            $this->_p->initApplication($old_application);
        }

		return $this->_p->db->query($sql, $storage);

	}


    /**
     * restore user session from the database
     *
     * @param int $user_id
     * @param string $application
     * @param array $params can consist of the 'session' param, which have serialized session for deserialization
     * @return array|mixed
     */
    public function getUserSession($user_id = null, $application = null, $params = array()){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // try get session from array
		if (is_array($params) && isset($params['session'])){

			$session = $params['session'];

		} else {

            $session = null;

            // check for application
            $old_application = null;
            if ($this->_p->getApplication() != $application){
                $old_application = $this->_p->getApplication();
                $this->_p->initApplication($application);
            }

            $model = $this->_p->getVar('user')['model'];
            $key = $user_id ? (int)$user_id : $this->_app_id[$application];

            $model = $this->_p->db->getManager()->getRepository($model)->findOne($key);

            if ($model){
                $session = $model->getSession();
            }
			unset($model);

            if ($old_application){
                $this->_p->initApplication($application);
            }

		}

		return is_array($session) ? $session : @unserialize($session);

	}



	/**
	* getting all active users
	*
	* @param string $application current application name
	* @return TRUE if user have rules
	*/
	public function getUsersOnline($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        // getting online list
        $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['session_history_model']);
        $users = $repo->findBy(array(
            'select' => array(
                '__groupkey__' => 'title',
                'a' => array('user_id', 'is_admin', 'application')
            ),
            'where' => array(
                'application' => $application,
                'lastuse_time' => array('op' => '>', 'value' => ($this->_p->getTime() - $this->_p->getVar('statistic')['active_timeout']))
            )
        ), $repo::ML_HYDRATION_ARRAY);

		$all_count = 0;

        unset($repo);

		if ($users){

			$all_count = count($users);

			// define allow fields
			$allow_fields = array('fname', 'sname', 'title', 'picture', 'id', 'real_id', 'lang_id');

			$keys = array();
			foreach ($users as $v){
				$keys[] = $v['user_id'];
			}

            $users = $this->get($keys, null, $application);

			if ($users){

				foreach ($users as $z => $arr){

					$arr_clear = array();
					foreach ($arr as $k => $v){
						if (in_array($k, $allow_fields))
							$arr_clear[$k] = $v;
					}

					$users[$z] = $arr_clear;
				}
			}

		}
		return array('all' => $all_count, 'auth' => $users);
	}


    /**
     * check if users online
     *
     * @param $ids
     * @param string $application current application name
     * @return array TRUE if user online
     */
    public function isUsersOnline($ids, $application = null){

		if (!is_array($ids)){
			$ids = array($ids);
		}

        if (!$application){
            $application = $this->_p->getApplication();
        }

        $ids = array_map('intval', $ids);

		// getting online list
        $repo = $this->_p->db->getManager()->getRepository($this->_p->getVar('user')['session_model']);
        $users = $repo->findBy(array(
            'select' => array('__groupkey__' => 'user_id', 'a' => array('user_id')),
            'where' => array(
                'application' => $application,
                'lastuse_time' => array('op' => '>', 'value' => ($this->_p->getTime() - $this->_p->getVar('statistic')['active_timeout'])),
                'user_id' => array('op' => 'in', 'value' => $ids)
            ),
            'group' => array('user_id')
        ), $repo::ML_HYDRATION_ARRAY);

		$online	= array();
		if ($users && !empty($users)){

			foreach ($ids as $v){
				$online[$v] = isset($users[$v]);
			}
		}
		unset($users);
		unset($ids);
        unset($repo);

		return $online;
	}

    /**
     * check for banned IP-addreses
     *
     * @param string $ip IP-address for checking
     * @param string $forward XHTTPforward parameter
     * @param string $useragent
     * @param bool $access_check see @common.php
     * @return bool if IP-in ban list - return true
     *
     */
    public function checkAccessIP($ip = null, $forward = null, $useragent = null, $access_check = false){

		if (!$ip)
			$ip = $this->_ip;

		$ip = preg_replace('/^([\d\.]+)(?:\:\d+)?$/', '$1', $ip);

		if (!($tmp_data = $this->_p->cache->getSerial('banned_ips_list'))){

            $res = $this->_p->db->getManager()->getRepository('BannedIP')->findBy();
			if ($res){
                $tmp_data = array();
				foreach ($res as $arr){

					if ($arr->getUseragent()){
						$tmp_data['useragents'] = array();
						if ($arr->getForward()){
							$tmp_data['useragents'][$arr->getUseragent()][$arr->getIp()][$arr->getForward()] = $arr->getIs_on();
						} elseif ($arr->getIp()){
							$tmp_data['useragents'][$arr->getUseragent()][$arr->getIp()] = $arr->getIs_on();
						} else {
							$tmp_data['useragents'][$arr->getUseragent()] = $arr->getIs_on();

						}
					} else {

						if ($arr->getForward())
							$tmp_data[$arr->getIp()][$arr->getForward()] = $arr->getIs_on();
						else
							$tmp_data[$arr->getIp()] = $arr->getIs_on();

					}

				}
			}

			$this->_p->cache->saveSerial('banned_ips_list', $tmp_data);
		}

		if ($access_check){

			if ($tmp_data[$ip] == $access_check)
				return true;
			else
				return false;

		} else {
			// access_check  - not setted - then try to get access for all site for this ip
			// if not access - return FALSE!!! - be careful, else - return true

			$is_all_allow = $tmp_data['*'];

			!$is_all_allow ? $is_all_allow = ACL_ACCESS_BANNED_ALL : null;

			// check for user agent type
			$useragent = $useragent ? $useragent : $this->_p->query->request->getHeaders('USER_AGENT');
            $access = $found = false;

			if (isset($tmp_data['useragents']) && !empty($tmp_data['useragents'])){
				foreach ($tmp_data['useragents'] as $k => $v){
					if (strpos($useragent, $k) !== false){
						// found! check for IP
						if (isset($tmp_data['useragents'][$k][$ip]))
							$access = ($forward ? $tmp_data['useragents'][$k][$ip][$forward] : $tmp_data['useragents'][$k][$ip]);
						else
							$access = $tmp_data['useragents'][$k];

						$found = true;
						break;
					}
				}
			}

			if (!$found)
				$access = ($forward ? $tmp_data[$ip][$forward] : $tmp_data[$ip]);

			if ($access == ACL_ACCESS_BANNED_ALL)
				return false;
			elseif ($is_all_allow == ACL_ACCESS_ALLOW_ALL && $access != ACL_ACCESS_BANNED_ALL)
				return true;
			elseif ($is_all_allow == ACL_ACCESS_BANNED_ALL && $access == ACL_ACCESS_ALLOW_ALL)
				return true;
			else
				return true;

		}

	}

    /**
     * method check access by restricitions
     * @param array $access_restrictions
     * @return array|bool
     */
    public function checkAccess($access_restrictions = array('ip')){

		$error = array();

		if (in_array('ip', $access_restrictions) && $this->checkAccessIP(null, false, false, ACL_ACCESS_BANNED_POST)){
			// check for banned IP-addresses
			$error[] = 'err_ip_banned';

		}

		if (in_array('register', $access_restrictions) && $this->_app_id[$this->_p->getApplication()] <= 0){
			// check for access for registered users only
			$error[] = 'err_banned_unreg';

		}

		if (empty($error))
			$error = false;

		return $error;
	}


    public function isAuth($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        return isset($this->_app_is_auth[$application]) && $this->_app_is_auth[$application];

    }


    public function getId($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        return $this->_app_id[$application];

    }


    public function getSessId($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        return $this->_app_sess_id[$application];

    }



    public function getRules($application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        return $this->_app_rules[$application];

    }


    public function getIp(){

        return $this->_ip;

    }

    public function getForward(){

        return $this->_forward;

    }

    public function getSession($item = null, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        if ($item){
            if (isset($this->_app_session[$application]) && isset($this->_app_session[$application][$item]))
                return $this->_app_session[$application][$item];
            else
                return null;
        } else {
            return $this->_app_session[$application];
        }

    }

    public function setSession($item, $value, $application = null){

        if (!$application){
            $application = $this->_p->getApplication();
        }

        if ($item){
            if (!isset($this->_app_session[$application]))
                $this->_app_session[$application] = array();

            $this->_app_session[$application][$item] = $value;
        }

        return $this;

    }


    /**
     * get user data
     * @param null $item
     * @param null $application
     * @return array|null
     */
    public function getData($item = null, $application = null){

        if (!$application)
            $application = $this->_p->getApplication();

        if ($item){
            if (isset($this->data[$application][$item]))
                return $this->data[$application][$item];
            else
                return null;
        } else {
            return $this->data[$application];
        }

    }


    /**
     * prepare compiled user data for inserting into templates, etc.
     */
    public function getCompiled($application = null){

        if (!$application)
            $application = $this->_p->getApplication();

        return array_merge((array)$this->data[$application], array(
            'rules' => $this->getUserRulesNick($application, $this->getId($application)),
            'sess_id' => $this->getSessId($application),
            'is_auth' => $this->isAuth($application),
            //'is_admin' => $this->isAdminAuth()
            )
        );

    }

}
