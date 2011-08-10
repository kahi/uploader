<?php
/**
 * class Compression (static)
 *
 * Reading/Creating Zip archives
 */
class Compression {


/**
 * If the class was already initialized.
 *
 * @var bool
 * @see init()
 */
public static
	$initiated = false;


/**
 * List of supported types.
 *
 * @var array
 * @see init()
 */
public static
	$supported_types;

public static
	$settings = array(
		'overwrite_when_unpacking' => true,
		'create_extra_dir_when_unpacking' => true
	);


/**
 * Initialization, prepares the class for static using (= static constructor)
 */
function init () {

	self::$initiated = true;

	self::$supported_types = array();

	if (class_exists('ZipArchive'))
		self::$supported_types[] = 'zip';

}


/**
 * Do we support that type of archive?
 *
 * @param string extension of archive-file
 * @returns bool
 */
function support($type) {
	if (!self::$initiated) self::init();

	return in_array($type, self::$supported_types);
}


/**
 * Get the extension (type) of a file
 *
 * @param string path to a file
 * @returns string
 */
function pathToType($path) {
	if (!self::$initiated) self::init();

	return substr(strrchr($path, '.'), 1);
}


/**
 * 
 * 
 * @param  
 * @returns bool Succeeded?
 * @todo Think over!
 */
public function unpack($source, $destination = false) {
	if (!self::$initiated) self::init();

	// @todo switch according to type to another functions
	$type = self::pathToType($source);
	
	if (function_exists('self::unpack'.$type)) {
		return call_user_func_array('self::unpack'.$type, array($source, $destination));
	} else {
		error_log('Function self::unpack'.$type.' missing.', 0); // this way??

		return false;
	}

}


/**
 * 
 * 
 * @param type name descr
 * @returns type
 * @todo
 */
private function unpackZip($source, $destination = false) {

	$zip = new ZipArchive();

	if (is_resource($zip = $zip->open($source))) {
		$splitter = ($create_zip_name_dir === true) ? "." : "/";
		if ($dest_dir === false)
			$dest_dir = substr($source, 0, strrpos($source, $splitter))."/";

		// Create the directories to the destination dir if they don't already exist
		create_dirs($dest_dir);

		// For every file in the zip-packet
		while ($zip_entry = zip_read($zip)) {

		// Now we're going to create the directories in the destination directories

		// If the file is not in the root dir
		$pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");

		if ($pos_last_slash !== false) {
			// Create the directory where the zip-entry should be saved (with a "/" at the end)
			create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
		}

		// Open the entry
		if (zip_entry_open($zip,$zip_entry,"r")) {

			// The name of the file to save on the disk
			$file_name = $dest_dir.zip_entry_name($zip_entry);

			// Check if the files should be overwritten or not
			if ($overwrite === true || $overwrite === false && !is_file($file_name)) {

				// Get the content of the zip entry
				$fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

				if(!is_dir($file_name))
				file_put_contents($file_name, $fstream );

				// Set the rights
				if(file_exists($file_name)) {
					chmod($file_name, 0777);
					$results[] = array($file_name, true);
				}
				else {
					$results[] = array($file_name, false);
				}
			}

			// Close the entry
			zip_entry_close($zip_entry);
		}
		}
		// Close the zip-file
		zip_close($zip);

		// Present Messages
		echo '<ol>';
		foreach ($results as $r) {
			$msg_type = ($r[1]) ? 'ok' : 'error'; // @todo rewrite error reporting
			$msg = ($r[1]) ? 'Done' : 'Failed';
			echo "<li class='message $msg_type'><em>$msg</em> - <a href='$r[0]'>$r[0]</a>";
		}
		echo '</ol>';
	
	} else return false;

}

/**
 * 
 * 
 * @param string $source path to the file/directory that should be packed
 * @param string $destination optional. path to the output archive-file (w/ OR w/o filename). when set false or skipped, ... result is predictible
 * @returns mixed false when failed, string (filename) when suceeded
 * @todo
 */
public function pack ($source, $destination_directory = false, $destination_filename = false, $can_overwrite = true, $type = 'zip') {
	
	if (!self::$initiated) self::init();

	$destination = '';
	
	// set destination directory if user didn't
	if (!$destination_directory)
		$destination_directory = dirname($source);
	else
		$destination_directory = rtrim($destination_directory, '/');
		
	// set destination filename if user didn't
	if (!$destination_filename) {
		$source_name = array_pop(explode('/', $source));
		$destination_filename = $source_name;
		
		if (is_dir($source)) 
			$destination_filename .= '.dir';

	}
	
	$destination = $destination_directory . "/$destination_filename.$type";


	// maybe generate new unique filename
	if (!$can_overwrite AND is_file($destination)) {
		$destination_filename .= '_' . base_convert(time(), '10', '36');
		$destination = $destination_directory . "/$destination_filename.$type";
	}
		
	// create empty zip file
	$zip = new ZipArchive();
	
	if ($zip->open($destination, ZIPARCHIVE::CREATE) !== true) {
	    die("cannot open <$filename>\n");
	}

	$zip->addFile( . "/too.php","/testfromfile.php");
	echo "numfiles: " . $zip->numFiles . "\n";
	echo "status:" . $zip->status . "\n";
	$zip->close();
	
	
}




/**
 * Gets list of files in archive
 * 
 * @param string path to the file
 * @returns array
 */
function preview($path) {
	
	// @todo

}


/**
 * 
 * 
 * @param type name descr
 * @returns type
 * @todo
 */
function create_dirs($path) {
	if (!self::$initiated) self::init();

	if (!is_dir($path)) {
		$directory_path = "";
		$directories = explode("/",$path);
		array_pop($directories);

		foreach($directories as $directory) {
			$directory_path .= $directory."/";
			if (!is_dir($directory_path)) {
				mkdir($directory_path);
				chmod($directory_path, 0777);
			}
		}
	}
}

} // Happy ending of another class... I hope the parser was able to work up to this place!