<?php

/**
 * class Uploader. This is the core as well.
 */

class Uploader {

	const
		// version
		VERSION = '2.0';

	private static

		// string, unique uploader identifier
		// Used in session/cookie names etc. Helps to distinct various Uploader installations on the same server.
		$id,
		
		
		$debug_mode;

	public static

		// URLs and paths
		// @note all directories end with slash
		// @note using existing constants not recommended, use these variables instead
		$home_url,
		
		$root_path,
		$root_url,
		
		$template,
		$template_dir, // alias for previous
		$template_path,
		$template_url,

		$sys_path,
		
		$data_dir,
		$data_path,
		$data_url,

		// title of this installation (from settings)
		$title,

		// all users
		$users,

		// current user data
		$user;

	

	// Initialization
	public static function Init () {
		
		self::$debug_mode = UPL_DEBUG_MODE;
		
		// transcript settings into class-variables
		// @maybe todo global -> Init()'s arguments
		global $upl_users, $upl_directory, $upl_template, $upl_userfiles_directory, $upl_title;
		
		self::$home_url = UPL_HOME_URL;
		
		self::$root_path = UPL_ROOT_PATH;
		self::$root_url  = UPL_ROOT_URL;
				
		self::$sys_path = UPL_SYS_PATH;
		
		self::$template = ($upl_template) ? $upl_template : 'default';
		self::$template_dir = self::$template;
		self::$template_path = self::$sys_path . 'template_' . self::$template . '/';
		self::$template_url = self::$root_url . 'uploader/template_' . self::$template . '/';
		
		self::$data_dir = $upl_userfiles_directory;
		self::$data_path = UPL_ROOT_PATH . self::$data_dir. '/';
		self::$data_url = UPL_ROOT_URL . self::$data_dir . '/';
			
		self::$users = $upl_users;
		self::$title = $upl_title;
		
		$upl_users = NULL; // @note unset() can't work here
		$upl_directory = NULL;
		$upl_template = NULL;
		$upl_userfiles_directory = NULL;
		$upl_title = NULL;
		

		// uus (unique uploader strings)
		self::$id = substr(md5(self::$home_url), 0, 8);


		// check for possible problems
		self::Diagnostics();

		// 
		self::$user = new User(array('appid' => self::$id, 'users' => self::$users));


		// assign current task to the proper method
		self::Actions();

	}



	// read-only access to private variables
	/// probably not necessary
	public static function getId () {
		return self::$id;
	}



	// checks for possible problems
	private static function Diagnostics () {


		// data dir: exists?
		if (!is_dir(self::$data_path)) {

			// no? try to create it first
			if (!mkdir(self::$data_path, 0777))
				$fatal_error = new Error ('Directory ' . self::$data_dir . 'does not exist and can\'t be created automatically.');
		}


		// data dir: write permissions
		if (!is_writable(self::$data_path)) {

			// no? try to set first...
			// @todo this should not kill the app (browsing files should stay possible)
			if (!chmod(self::$data_path, 0777))
				$fatal_error = new Error ('Directory ' . self::$data_dir . ' isn\'t writeable and these permissions must be given manually.');
		}


		// users: check'n'normalize config data
		$free_access_exists = false;
		foreach (self::$users as $key=>$user) {

			// defects in users-array
			if (! array_key_exists('name', $user)
				OR ! array_key_exists('password', $user)
				OR ! array_key_exists('rights', $user)) {

				$fatal_error = new Error ('There is a problem in configuration data: all "users" have to contain 1. name 2. password 3. rigths. Values can be empty but they always have to be set.');
			}

			// exists free access?
			if (
				! $free_access_exists
				AND empty($user['name'])
				AND empty($user['password'])) {

				$free_access_exists = true;
			}

			// save hashed password, remove non-hashed
			// if config = md5 hash, don't hash again!
			$password = $user['password'];

			if (preg_match('/^.{48,48}$/', $user['password']))
				self::$users[$key]['hashed_password'] = $user['password'];
			else
				self::$users[$key]['hashed_password'] = sha1($user['password']);

			unset (self::$users[$key]['password']);

		} // end-foreach

	}



	/**
	 * Analyses the query-string and runs the proper functions
	 * 
	 * @note in adult applications is this called "router", I guess
	 * @note uploader is not designed to work without ajax support
	 * @note finally, this function should be rethought and refactored fucking much!
	 */  
	private static function Actions () {

		// transform URL like '?do=anything/well/quickly' into array('anything', 'well', 'quickly')
		$action = explode('/', strToLower(@$_GET['do']));

		switch ($action[0]) {
			
			
			case 'ajax':
				error_reporting(E_WARNING);
				
				self::CheckPermissions($action[1]);
				self::CheckHashes($action[1]);
				
				
				switch ($action[1]) {
					case 'deletedir':

						FileSystem::Init(array(
							'base_path' => self::$data_path,
							// 'can_delete' => self::$user->can('delete')
							));
						
						if (false !== ($result = DeleteDir::Action($_POST['path'])))
							self::jsonData($result);
						else
							self::jsonError('Deleting directory failed. Try to reload page and then try again.');
					
					break; case 'movefiledir':

						FileSystem::Init(array(
							'base_path' => self::$data_path,
							'can_delete' => self::$user->can('delete')));

						if (false !== ($result = MoveFileDir::Action($_POST['item'], $_POST['target'])))
							self::jsonData($result);
						else
							self::jsonError('Moving file/directory failed.');

					break; case 'copyfiledir':

						FileSystem::Init(array(
							'base_path' => self::$data_path,
							'can_delete' => self::$user->can('delete')));

						if (false !== ($result = CopyFileDir::Action($_POST['item'], $_POST['target'])))
							self::jsonData($result);
						else
							self::jsonError('Copying file/directory failed.');
							
					break; case 'renamedir':

						FileSystem::Init(array(
							'base_path' => self::$data_path,
							'can_delete' => self::$user->can('delete')));
						
						if (false !== ($result = RenameDir::Action($_POST['from'], $_POST['to_name'])))
							self::jsonData($result);
						else
							self::jsonError('Renaming directory failed. Try to reload page and then try again.');
					
					break; case 'renamefile':

						FileSystem::Init(array(
							'base_path' => self::$data_path,
							'can_delete' => self::$user->can('delete')));
						
						if (false !== ($result = RenameFile::Action($_POST['from'], $_POST['to_namecore'])))
							self::jsonData($result);
						else
							self::jsonError('Renaming file failed. Try to reload page and then try again.');
					
					
					break; case 'deletefile':

						FileSystem::Init(array(
							'base_path' => self::$data_path));

						if (false !== ($result = DeleteFile::Action($_POST['path'])))
							self::jsonData(true);
						else
							self::jsonError('Deleting failed (from unknown reason).');
					
					
					break; case 'getfilesizeextras':
					
						FileSystem::Init(array(
							'base_path' => self::$data_path,
							'can_delete' => self::$user->can('delete')));
						
						if (false !== ($result = FileSystem::getFileSizeExtras($_POST['path']))) 
							self::jsonData($result);
						else
							self::jsonError('Action failed.');
					
					
					break; case 'getdircontent':
					
						$_POST['root'] = self::$data_path; 
						
						FileSystem::Init(array(
							'base_path' => self::$data_path,
							'can_delete' => self::$user->can('delete')));

						if (false !== ($result = GetDirContent::Action($_POST)))
							self::jsonData($result);
						else
							self::jsonError('Can\'t load directory content. Try to reload the page.');
					
					
					break; case 'adddir': 

						FileSystem::Init(array(
							'base_path' => self::$data_path));

						if (false !== ($result = AddDir::Action($_POST['path'], $_POST['name'])))
							self::jsonData($result);
						else
							self::jsonError('Creating directory failed.');	
				
				
					// universal solution for ajax actions
					// @todo rethink, rewrite, or remove. this is fucking dirty.
					break; default:
						$_POST['root'] = self::$data_path; // @todo why the hell is this in POST?

						if (false !== ($result = call_user_func(array($action[1], 'Action'), $_POST))) // = e.g. AddDir::Action($_POST)
							self::jsonData($result);
						else
							self::jsonError('Action failed.');

				}

			break; case 'analysis':
				self::CheckPermissions('read');

				$analysis = new Analysis;
				$analysis->phpinfo = (@$action[1] == 'full');
				$analysis->Show();

			break; case 'login':
				$m = new Messages(self::$id);

				if (self::$user->Login($_POST, self::$users))
					$m->Add(__('Logged in, hooray!'));
				else
					$m->Add(__('Login failed. Please, try again.'));

				self::goHome();

			break; case 'logout':

				self::$user->Logout();
				self::goHome();

			break; default:
				// load login form -or- template
				if (!self::$user->can('read')) {
					require_once self::$sys_path . 'login.t.phtml';
					break;
				
				} elseif (!require_once self::$template_path . 'index.phtml') {
					$e = new Error('Chosen template "' . self::$template .'" does not exist.');
				}
				break;
		}

	}


	/**
	 * @todo Approve or refractor
	 */
	public static function jsonError ($message) {
		$extra = '';
		if (self::$debug_mode) {
			$last_error = error_get_last();
			$last_error = implode('; ', $last_error); // careful! might not be the cause! just probably relevant...
			$extra = " (Likely because: $last_error)";
		}
		
		die (json_encode(array('status'=>'error', 'data'=>$message.$extra)));
	}
	
	public static function jsonData ($message) {
		die (json_encode(array('status'=>'data', 'data'=>$message)));
	}


	/**
	 * 
	 */
	public static function goHome() {
		Header ('Location: ' . self::$home_url);
		exit;
	}


	/**
	 * 
	 */
	public static function CheckPermissions ($action) {
		if (true == false) die('CheckPermissions');
	}


	/**
	 * XSS prevention
	 */
	public static function CheckHashes () {
		if (true == false) die('CheckHashes');
	}

}
