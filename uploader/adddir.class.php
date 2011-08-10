<?php

/**
 * Wrapper around mkdir().
 *
 * @package Uploader
 **/
class AddDir extends Filesystem {
	
	/**
	 * Create a directory. 
	 * @param string relative target path
	 * @param string new directory name
	 * @return mixed false if failes, string (new dir name) when succeeds 
	 */
	public function Action ($path, $name) {

		$name = self::webizeFilename($name);
		$path = self::$base_path . trim(self::secureUserpath($path), '/') . '/';
				
		// exists? dirname --> dirname-2
		while (is_dir($path.$name)) {

			if (!preg_match('/\-[0-9]{1,2}$/', $name)) {
				$name .= '-2';
			} else {
				$name = preg_replace_callback('/\-([0-9]{1,2})$/',
			        create_function('$matches', 'return "-".($matches[1]+1);'),
			 		$name);
			}

		}
		
		//die($path.$name);
		
		// $t = umask(0);
		$created = mkdir ($path.$name); // , 0777
		// umask($t);

		if (!$created) {
			// @todo Specify error message. (What are the reasons to fail?)
			trigger_error('Directory couldn\'t be created (from unknown reason).');
			return false;
		}

		return $name;

	}

} // END