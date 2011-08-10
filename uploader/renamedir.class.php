<?php
/**
 * RenameDir.
 **/
class RenameDir extends Filesystem {
	
	/**
	 * @param string path to a directory
	 * @param string new name of the directory
	 * @return mixed If rename succeeded, returns new string (new name), otherwise false.
	 */
	public static function Action ($from, $to_name) {
		
		$from = self::$base_path . self::secureUserpath($from);
		
		if (!is_dir($from)) {
			trigger_error('File to rename not found ('.$from.').');
			return false;
		}
		
		$to_name = self::webizeFilename($to_name);
		$to = dirname($from) .'/'. $to_name;
		
		if ($from == $to)
			return $to_name;
		
		if (is_dir($to)) {
			trigger_error('Directory of that name (<em>'. $to_name .'</em>) already exists.');
			return false;
		}
		
		return (rename ($from, $to)) ? $to_name : false;

	}

} // END