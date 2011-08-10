<?php
/**
 * class Filesystem. Extends default PHP's abilities of working with files, directories etc. 
 */

class Filesystem {
	
	protected static
		// path to the data folder ( + relative path = whole path to a file); ends with slash
		// supposed to be filled in Init()
		$base_path = '',
		
		// desctructive operations controller
		// supposed to be ev. set in Init()
		$can_delete = false;
		
	public static
		
		// dangerous (forbidden) files' extensions
		$forb_ext = 'php php3 php4 php5 php6 phtml py pl pyo pyc jsp cgi asp aspx htaccess',

		// hidden files
		$hidden_files = array('.htaccess', '..', '.', 'Thumbs.db'),
		$hidden_files_patterns = array('/^\..+/'), // perl-compatible regular expressions
		
		// extension <=> file-type table
		// @todo: research
		$filetypes = array (
			'archive' => array('zip', 'rar', 'exe', '7z', 'gzip', 'gz', 'tar', 'dmg', 'bz2'),
			'image' => array('jpg', 'jpeg', 'jp2', 'gif', 'png', 'bmp', 'tif', 'tga', 'raw', 'ico', 'psd', 'ai', 'cdr'), 
			'audio' => array('mp3', 'ogg', 'wmv', 'wav', 'xm'),
			'video' => array('mpg', 'mpeg', 'avi', 'swf'),
			'doc' => array('doc', 'docx', 'xls', 'xlsx', 'dot', 'dotx', 'xlt', 'xltx', 'ppt', 'pptx', 
				'rtf', 'txt', 'odt', 'ods', 'sxc', 'pdf', 'odp',
				'html', 'htm', 'mht'),
		);
	
	
	public static function Init ($data) {
		if (isset($data['base_path']))
			self::$base_path = $data['base_path'];
		
		if (isset($data['can_delete']))
			self::$can_delete = $data['can_delete'];
	}
		
	/**
	 * @todo!
	 */
	public static function addFiles ($data) {
		
	}


	/**
	 * Supposed to work for directory-names too.
	 */
	public static  function webizeFilename ($name) {
		
		$name = StrToLower($name);

		// standard characters
		$in	= Array('À','Á','Â','Ã','Ä','Å','Ā','Ă','Ç','Ć','Ĉ','Ċ','Č','Ď','Đ','È','É','Ê','Ë','Ē','Ĕ','Ė','Ę','Ě','Ĝ','Ğ','Ġ','Ģ','Ĥ','Ì','Í','Î','Ï','Ĩ','Ī','Ĭ','Į','İ','Ĵ','Ķ','Ĺ','Ļ','Ľ','Ŀ','Ł','Ñ','Ń','Ņ','Ň','Ŋ','Ò','Ó','Ô','Õ','Ö','Ō','Ŏ','Ő','Ŕ','Ŗ','Ř','Ś','Ŝ','Ş','Š','Ţ','Ť','Ù','Ú','Û','Ü','Ũ','Ū','Ŭ','Ů','Ű','Ų','Ŵ','Ý','Ŷ','Ÿ','Ź','Ż','Ž','à','á','â','ã','ä','å','ā','ă','ą','ç','ć','ĉ','ċ','č','ď','đ','è','é','ê','ë','ē','ĕ','ė','ę','ě','ĝ','ğ','ġ','ģ','ĥ','ħ','ì','í','î','ï','ĩ','ī','ĭ','į','ı','ĵ','ķ','ĺ','ļ','ľ','ŀ','ł','ñ','ń','ņ','ň','ŉ','ŋ','ò','ó','ô','õ','ö','ō','ŏ','ő','ŕ','ŗ','ř','ś','ŝ','ş','š','ţ','ť','ù','ú','û','ü','ũ','ū','ŭ','ů','ű','ų','ŵ','ý','ÿ','ŷ','ź','ż','ž');
		$out  = Array('A','A','A','A','A','A','A','A','C','C','C','C','C','D','D','E','E','E','E','E','E','E','E','E','G','G','G','G','H','I','I','I','I','I','I','I','I','I','J','K','L','L','L','L','L','N','N','N','N','N','O','O','O','O','O','O','O','O','R','R','R','S','S','S','S','T','T','U','U','U','U','U','U','U','U','U','U','W','Y','Y','Y','Z','Z','Z','a','a','a','a','a','a','a','a','a','c','c','c','c','c','d','d','e','e','e','e','e','e','e','e','e','g','g','g','g','h','h','i','i','i','i','i','i','i','i','i','j','k','l','l','l','l','l','n','n','n','n','n','n','o','o','o','o','o','o','o','o','r','r','r','s','s','s','s','t','t','u','u','u','u','u','u','u','u','u','u','w','y','y','y','z','z','z');
		$name = str_replace($in,$out,$name);

		// everything lowercase
		$name = StrToLower($name);

		// special characters
		$in  = Array('+','=','´','ˇ','€','[',']','{','}','(',')','§',',',':');
		$out = Array('-','-','' , '','e','_','_','_','_','_','_','_','-','-');
		$name = str_replace($in,$out,$name);

		// delete other chars
		$name = ereg_replace('[^\.a-z 0-9_-]', '', $name); // delete unwanted

		// multiple spaces to "-"
		$name = ereg_replace(' +', '-', $name);

		// merge multiple "-"
		$name = ereg_replace('-+', '-', $name);

		// delete _ and - from sides
		$name = trim($name, '-_');

		return $name;

	}


	/**
	 * 
	 */
	public static function secureUserpath ($s) {
		$s = preg_replace('/\.{2}/', '', $s);
		$s = preg_replace('/\/{2}/', '/', $s);
		return $s;
	}
	
	public static function isHiddenFile ($filename) {
		
		if (in_array($filename, self::$hidden_files))
			return true;
		
		foreach (self::$hidden_files_patterns as $pattern) 
			if (preg_match($pattern, $filename)) 	
				return true;
		
		return false;

	}

	public static function getFilenameExt ($fn) {

		$ext = substr(strrchr($fn, '.'), 1);
		return $ext;

	}
	
	public static function getFileType ($ext) {
		
		$ext = strToLower($ext);
		
		foreach (self::$filetypes as $key => $exts)
			if (in_array($ext, $exts))
				return $key;

		return 'unknown';
		
	}

	public static function niceFileSize($bytes) {

		if($bytes >= 1048576) {
			 return array(round($bytes / 1024 / 1024, 1), 'MiB');
		} elseif ($bytes >= 1024) {
			 return array(round($bytes / 1024, 0), 'KiB');
		} else {
			 return array('<1', 'KiB'); // 1 KiB is everything < 1 KiB (ok?)
		}

	}
	
	
	public static function getFileSizeExtras ($relative_path_to_file) {
		$path = self::$base_path . $relative_path_to_file;
		
		if (!is_file($path)) {
			return false;
		}
		
		$out = array();
		$out['size'] = filesize($path);
		$out['nicesize'] = self::niceFileSize($out['size']);
		$out['hash_md5'] = hash_file('md5', $path);
		$out['hash_sha1'] = hash_file('sha1', $path);
		
		return $out;
	}
	
	
	
	
} // end of class