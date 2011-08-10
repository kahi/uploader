<?php
/**
 * DeleteDir. Deletes any directory's content too.
 **/
class DeleteDir extends Filesystem {
	
	/**
	 * @param string $path relative path to the directory
	 * @return bool
	 */
	public static function Action ($path) {
		
		$path = self::$base_path . self::secureUserpath($path);
		
		if (!is_dir($path)) {
			trigger_error('Directory to delete not found ('.$path.').');
			return false;
		}
		
		return self::unlinkTree($path);

	}
	
	public static function unlinkTree ($path) {
		
		$objects = glob($path.'/*');

		foreach ($objects as $o) {
			if (is_dir($o)) {
				if (!self::unlinkTree($o)) {
					trigger_error('Removing directory '.$o.' failed.');
					return false;
				}
			} else {
				if (!unlink($o)) {
					trigger_error('Removing file '.$o.' failed.');
					return false;
				}
			}
		}
		
		return rmdir($path);

	}

} // END