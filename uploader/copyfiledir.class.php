<?php
/**
 * CopyFileDir.
 * @note this is not public/promoted feature, since it's not done.
 **/
class CopyFileDir extends Filesystem {
	
	/**
	 * @param string path to moved item
	 * @param string path to target directory
	 * @return mixed If move succeeded, returns the new (relative) path, otherwise false.
	 *
	 * @todo copying file -> replace/cancel dialog
	 * @todo copy dirs
	 */
	public static function Action ($from, $target) {
				
		$from = self::$base_path . self::secureUserpath($from);
		$to_relative =  self::secureUserpath($target) .'/'. substr($from, strrpos($from, '/')+1);
		$to = self::$base_path . $to_relative;
		
		if ($from == $to) {
			trigger_error('Copying into same folder is not supported yet.');
			return false;
		}
		
		if (!is_dir($from)) {
			trigger_error('Copying folder is not supported yet.');
			return false;
		}
		
		if (!file_exists($from)) {
			trigger_error('File/Folder to copy not found ('.$from.').');
			return false;
		}
		
		// // problem: replacing without warning is rude
		// if (file_exists($to) AND !self::$can_delete) {
		// 	trigger_error('File/Folder in the target location already exists and you are not allowed to overwrite it ('.$to.').');
		// 	return false;
		// }
		
		if (is_file($to)) {
			trigger_error('File of the same name already exists in the target location. Please rename or remove it first.'); // @todo replace dialog?
			return false;
		
		} elseif (is_dir($to) AND glob($to.'/*')) {
			// @note would trigger warning
			trigger_error('Folder of the same name already exists in the target location and it\'s not empty.');
			return false;
		}

		return (copy ($from, $to)) ? $to_relative : false;

	}

} // END