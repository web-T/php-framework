<?php

/**
* Session/cookies storage library for user data
* 
* @version 0.5
* @author goshi
* @package web-T[CORE]
*
* Changelog:
 *  0.5 09.06.13/goshi  refactoring constructor
*	0.4	17.02.12/goshi use session methods from user
*	0.3	26.08.11/goshi fix session var create
*	0.2	19.08.11/goshi add parse method
*	0.1	19.07.11/goshi	
* 
*/

namespace webtFramework\Core;

/**
* @package web-T[CORE]
*/
class webtUserStorage{

	/**
	 * @var oPortal
	 */
	protected $_p = null;

    /**
     * name of the session variable for storage
     * @var string
     */
    private $_session_var_name = 'webt_session';

    /**
     * name of the cookie for storage
     * @var string
     */
    private $_cookie_var_name = 'webt_s';

	/**
	 * cached values from session - so we don't need always to start/stop it
	 * @var null|Array
	 */
	//private $_cache = null;

	/**
	* can be 'cookie', 'session', 'db', 'auto' 
	* auto mode can detect possible optimized algo
	*/
	protected $_driver = 'auto';

	public function __construct(oPortal &$p, $params = array()){

		$this->_p = $p;

		if (isset($params['driver']))
			$this->_driver = $params['driver'];
		
		// init session and cache it
		if ($this->_p->user->isAuth() && $this->_p->user->getId() > 0){
			
			$this->_p->user->setSessionVal($this->_session_var_name, serialize($this->_p->user->getData('session') != '' ? $this->_p->user->getData('session') : array()));
		}
		
	}
	
	/**
	* cleanup all temporary data
	*/
	public function cleanup(){
	
		$this->_p->user->setSessionVal($this->_session_var_name);
	
	}
	
	
	/**
	* getting session info from cookie
	*/
	protected function _getSessionFromCookie(){
	
		if (isset($_COOKIE) && isset($_COOKIE[$this->_cookie_var_name])){
			return json_decode($_COOKIE[$this->_cookie_var_name], true);
		} else
			return array();
	
	}

	/**
	* getting session info from cookie
	*/
	protected function _putSessionToCookie($session = ''){
	
		$data = $_COOKIE[$this->_cookie_var_name] = json_encode($session);
		return $data;
	}
	
	/**
	* parsing data for storage session into array
	*/
	public function parse($session_data){
		
		if ($session_data != '' && !is_array($session_data)){
			return @unserialize($session_data);
		} else 
			return $session_data;
	
	}

	
	/**
	* getting session value
	*/
	public function get($var = null){
	
		$session = array();
	
		switch ($this->_driver){
		
		case 'auto':
		
			if ($this->_p->user->isAuth() && $this->_p->user->getId() > 0){

				$session = $this->_p->user->getSessionVal($this->_session_var_name);
				if (!$session)
					$session = $this->_p->db->selectCell($this->_p->db->getQueryBuilder()->compile($this->_p->Model('User'), array('select' => 'session', 'where' => array('[PRIMARY]' => $this->_p->user->getId()))), $this->_p->Model('User')->getModelStorage());
				if (is_array($session))	
					$session = serialize($session);
					
				$this->_p->user->setSessionVal($this->_session_var_name, $session);
				$session = $this->parse($session);
				
			} else {
			// getting all session data from cookies
				$session = $this->_getSessionFromCookie();
				
			}
		
			break;
		
		}

		if (!is_array($session))
			$session = array();

		return $var !== null ? $session[$var] : $session;
	
	}

    /**
     * setting session
     * @param string|array $var
     * @param string $value
     * @return bool
     */
	public function set($var, $value = ''){
	
		if (!$var) return false;	
		
		$session = (array)$this->get();
		if ($value === '' && !is_array($var))
			unset($session[$var]);
		else if (is_array($var)){
			foreach ($var as $k => $v){
				$session[$k] = $v;
			}
		} else
			$session[(string)$var] = $value;

		switch ($this->_driver){
		
		case 'auto':
			if (/*$this->_p->user->isAuth() && */ $this->_p->user->getId() > 0){
				
				$ser = serialize($session);
				
				$this->_p->db->query($this->_p->db->getQueryBuilder()->compileUpdate($this->_p->Model('User'), array('session' => $ser), array('where' => array('[PRIMARY]' => $this->_p->user->getId()))), $this->_p->Model('User')->getModelStorage());
				
				$this->_p->user->setSessionVal($this->_session_var_name, $ser);
			} else {
			// getting all session data from cookies
				$encoded = $this->_putSessionToCookie($session);
				$this->_p->cookie->set($this->_cookie_var_name, $encoded, 140);
			}
		
			break;
		
		}

        return true;
	} 
	
	/**
	* merging session from cookies to the database
	* fix all cookie changes
	*/
	public function merge($source = 'cookie'){
	
		switch ($source){
				
		case 'cookie':
			
			$session = $this->_getSessionFromCookie();
			if (/*$this->_p->user->isAuth() &&  */$this->_p->user->getId() > 0 && is_array($session) && !empty($session)) {
				$ses = (array)$this->get();
				foreach ($session as $k => $v){
					$ses[$k] = $v;
				}	
				$this->set($ses);
			}
			
			break;

		case 'session':
		default:
			
			$session = $this->_p->user->getSessionVal($this->_session_var_name);
			if (!$session)
				$session = $this->_p->db->selectCell($this->_p->db->getQueryBuilder()->compile($this->_p->Model('User'), array('select' => 'session', 'where' => array('[PRIMARY]' => $this->_p->user->getId()))), $this->_p->Model('User')->getModelStorage());

			if ($session != ''){
				$session = $this->parse($session);
			}
			//dump_file($session, false);
			
			$encoded = $this->_putSessionToCookie($session);
			$this->_p->cookie->set($this->_cookie_var_name, $encoded, 140);
			
			break;

				
		}
	
	}
	
}
