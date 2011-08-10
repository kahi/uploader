<?php
/**
 * RenameFile. Is not supposed to handle moving files.
 **/
class RenameFile extends Filesystem {
	
	/**
	 * @param string $from absolute path to the original file
	 * @param string $to_namecore new filename without extension
	 * @note user isn't allowed to change the filename extension
	 * @return mixed If rename succeeded, returns new filename (string), otherwise false.
	 */
	public static function Action ($from, $to_namecore) {
		
		$from = self::$base_path . self::secureUserpath($from);
		
		if (!is_file($from)) {
			trigger_error('File to rename not found ('.$from.').');
			return false;
		}
		
		$to_name = self::webizeFilename($to_namecore) .'.'. self::getFilenameExt($from);
		$to = dirname($from) .'/'. $to_name;
		
		if (!self::$can_delete AND is_file($to)) {
			trigger_error('File of that name already exist and you are not allowed to overwrite it ('.$to.').');
			return false;
		}
		
		return (rename ($from, $to)) ? $to_name : false;

	}

} // END