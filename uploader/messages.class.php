<?php
/**
 * Session-based messages. Used on login-page, in most other cases an other class is used.
 * 
 */ 
class Messages {

	private 
		$session_name;

	/**
	 * @param string Used in session-name, necessary for correct functioning of multiple app installation on the same server
	 */
	public function __construct ($id = '') {
		$this->session_name = 'msg_' . $id;
	}
	
	
	/**
	 * @param string
	 */
	public function Add ($text) {		

		$_SESSION[$this->session_name][] = $text;
		
	}
	
	
	/**
	 * 
	 */
	public function deleteAll () {

		$_SESSION[$this->session_name] = NULL;
		
	}
	

	/**
	 * @return array
	 */
	public function getAll () {
		
		if (!isset($_SESSION[$this->session_name])) 
			return false;
		elseif (is_array($_SESSION[$this->session_name]))
			return $_SESSION[$this->session_name];
		else
			return array($_SESSION[$this->session_name]);

	}

} // end of class