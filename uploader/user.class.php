<?php
/**
 * class Users.
 *
 * remember this class has no access to passwords. The $users array should always contain password just under key 'hashes_password'
 */
class User {

	private
		/**
		 * Is user logged in?
		 *
		 * @var bool
		 **/
		$logged,

		/**
		 * Predefined rights user may have
		 *
		 * @var array
		 **/
		$possible_rights = array('read', 'write', 'delete'),


		/**
		 * Rights that user actually has (will be rewritten in constructor)
		 *
		 * @var array
		 **/
		$rights = array('read' => false, 'write' => false, 'delete' => false),

		/**
		 * User's name (if he's logged))
		 *
		 * @var string
		 **/
		$name,

		/**
		 * User's password in SHA1 (if he's logged)
		 *
		 * @var string
		 **/
		$hashed_password;


	public
		$session_username_name,
		$session_verif_name,
		$cookie_username_name,
		$cookie_verif_name;


	/**
	 * Checks sessions or cookies, fills class-variables (such as $rights)
	 * @param array $data Must contain: appid, users(array)
	 */
	public function __construct ($data) {

		$this->session_username_name = 'upl_logged_username_' . $data['appid'];
		$this->session_verif_name = 'upl_logged_verif_' . $data['appid'];
		$this->cookie_username_name = 'upl_autologin_username_' . $data['appid'];
		$this->cookie_verif_name = 'upl_autologin_verif_' . $data['appid'];

		$session_username = (isset($_SESSION[$this->session_username_name])) ? $_SESSION[$this->session_username_name] : false;
		$session_verif = (isset($_SESSION[$this->session_verif_name])) ? $_SESSION[$this->session_verif_name] : false;
		$cookie_username = (isset($_COOKIE[$this->cookie_username_name])) ? $_COOKIE[$this->cookie_username_name] : false;
		$cookie_verif = (isset($_COOKIE[$this->cookie_verif_name])) ? $_COOKIE[$this->cookie_verif_name] : false;

		// check sessions
		if ($session_username AND $session_verif) {

			// if user exists, compare passwords
			$found = false;
			foreach ($data['users'] as $u) {

				if ($session_username == sha1($u['name'])  AND  $session_verif == self::getSessionContent($u)) {
					$found = true;

					$this->Activate($u);

					// @?todo should i recreate session to revive it?
					break;
				}

			}

		// check cookies
		} elseif ($cookie_username AND $cookie_verif) { // @todo same with cookie

			// if user exists, compare passwords
			$found = false;
			foreach ($data['users'] as $u) {

				if ($cookie_username == sha1($u['name'])  AND  $cookie_verif == self::getCookieContent($u)) {
					$found = true;

					$this->Activate($u);
					$this->setSessions();
					$this->setCookies();

					break;
				}

			}

		// not logged
		} else {// @?todo logging as guest when user (username='' password='') exists

			$this->logged = false;

		}

	}


	/**
	 * 
	 * 
	 * @param array
	 */
	private function assignRights ($rights) {

		$rights = explode(',', $rights);
		foreach ($rights as $r) {
			$r = trim($r);

			if (empty($r)) continue;

			if (!in_array($r, $this->possible_rights))
				trigger_error("Trying to assign an unexisting right ($r)!");
			else {
				$this->rights[$r] = true;
			}
		}

	}


	/**
	 * Remove logged-sessions and autologin-cookies
	 */
	public function Logout () {
		$this->logged = false;

		$_SESSION[$this->session_username_name] = NULL;
		$_SESSION[$this->session_verif_name] = NULL;

		setCookie($this->cookie_username_name, '', time() - 3600);
		setCookie($this->cookie_verif_name, '', time() - 3600);

	}


	/**
	 * If user matching login-data exists, create logged-session and ev. autologin-cookie
	 * @param array Must use keys: name, password, possibly autologin
	 * @param array Each item must be array using keys: name, password, rights)
	 * @return bool Succeeded?
	 */
	public function Login ($input, $users) {

		// check input
		if (!array_key_exists('name', $input) OR !array_key_exists('password', $input)) {
			trigger_error('Not enough data for User::Login()');
			return false;
		}

		// create session
		$name = trim($input['name']);
		$password = trim($input['password']);
		$hashed_password = sha1($password);
		$autologin = isset($input['autologin']) ? true : false;

		$found = false;
		foreach ($users as $user) {

			// if a user matches login-data
			if ($user['name'] == $name  AND  $user['hashed_password'] == $hashed_password) {
				$found = true;

				$this->Activate($user);

				$this->setSessions();

				if ($autologin) {
					$this->setCookies();
				}

				break;
			}

		}

		return $found;

	}


	/**
	 * Fill class-variables according to chosen user (defined by parameter)
	 */
	private function Activate ($user) {

		$this->logged = true;
		$this->name = $user['name'];
		$this->hashed_password = $user['hashed_password'];
		$this->assignRights($user['rights']);

	}


	/**
	 * Wrapper for $user->rights[$right]
	 *
	 * @param string One of existing rights
	 * @return bool
	 */
	public function Can ($right) {

		if (!in_array($right, $this->possible_rights)) {
			trigger_error("Checking user for an unexisting right ($right)!");
			return false;
		} else {
			return $this->rights[$right];
		}

	}


	/**
	 * @return bool Private variable getter
	 */
	public function isLogged () {

 		return $this->logged;

	}


	/**
	 * @todo check that user was activated!
	 */
	private function setSessions () {
		$_SESSION[$this->session_username_name] = sha1($this->name);
		$_SESSION[$this->session_verif_name] = $this->getSessionContent();
	}


	/**
	 * @todo check that user was activated!
	 */
	private function setCookies () {
		setCookie($this->cookie_username_name, sha1($this->name), time() + 365*24*3600, '/');
		setCookie($this->cookie_verif_name, $this->getCookieContent(), time() + 365*24*3600, '/');
	}


	/**
	 * Returns correct autologin-cookie content (not the actual one)
	 */
	private function getCookieContent ($user = false) {

		if (false === $user)
			$user = $this->name; // @todo!!!

		return sha1('temporary salt :-)' . $this->hashed_password . $user);

	}


	/**
	 * Returns correct logged-session content (not the actual one)
	 */
	private function getSessionContent ($user = false) {

		if (false === $user) {
			$user['name'] = $this->name;
			$user['hashed_password'] = $this->hashed_password;
		}

		return sha1('temporary salt :-)' . $user['hashed_password'] . $user['name'] . $_SERVER['REMOTE_ADDR']);

	}



}


