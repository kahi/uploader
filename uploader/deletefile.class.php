<?php
/**
 * RenameFile. Is not supposed to handle moving files.
 **/
class DeleteFile extends Filesystem {
	
	/**
	 * @param string $from relative path to the original file
	 * @return bool
	 */
	public static function Action ($path) {
		
		$path = self::$base_path . self::secureUserpath($path);
		
		if (!is_file($path)) {
			trigger_error('File to delete not found ('.$path.').');
			return false;
		}
		
		return @unlink($path);

	}

} // END