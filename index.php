<?php
// Welcome in Uploader configuration.
// Need help? -> http://uploader.kahi.cz


// Basic settings

$upl_title = 'Uploader';
$upl_language = 'en';
$upl_template = 'default';
$upl_userfiles_directory = 'files'; // name of the folder with uploaded files


// Users settings

$upl_users[] = array ('name' => 'me', 'password' => 'asdf', 'rights' => 'read, write, delete');

// $upl_users[] = array ('name' => 'Example2', 'password' => 'secret42', 'permissions' => 'read, write');

// HELP: 
// Existing rights:  1. read  2. write (means add files and folders)  3. delete (means delete and overwrite files and folders)




// ===========================================================
// OK, that's all! Open it in your browser NOW. ==============
// ===========================================================

define('UPL_DEBUG_MODE', true); // affects error reporting level


// Define paths
// ************

define('UPL_HOME_URL', 'http://' . $_SERVER['HTTP_HOST'] . array_shift(explode('?', $_SERVER['REQUEST_URI'])));
define('UPL_ROOT_URL', substr(UPL_HOME_URL, 0, strrpos(UPL_HOME_URL, "/")) . "/");
define('UPL_ROOT_PATH', dirname(__FILE__) . '/');
define('UPL_SYS_PATH', UPL_ROOT_PATH . 'uploader/');

// sleep(1.5); // ajax delay simulation

// Load Uploader main init-file
// ****************************

if ( ! include_once UPL_SYS_PATH . '_init.php')
	die ('ERROR: Can\'t load "' . UPL_SYS_PATH . '_init.php" file.');