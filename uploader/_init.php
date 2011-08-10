<?php
/**
 * Uploader. This is it. Story continues in uploader.class.php on Init()
 */ 


// php version test
if ( ! version_compare(phpversion(), "5.0", ">="))
	die ('Uploader requires PHP 5 or higher.');


// error reporting
error_reporting((UPL_DEBUG_MODE) ? E_ALL : E_ALL ^E_NOTICE );


// session start
session_start();


// autoloading
function __autoload($class) {
	$class = strtolower($class);
	
	if ( ! @include_once UPL_SYS_PATH . $class . '.class.php')
		die ('Error: Can\'t require class "' . $class . '".');
}

function __($text) {
	return Translator::Translate($text);
}

// start Timer
Timer::Start();


// start Uploader class
// Uploader->Actions() may handle output, then the template (see below) is not loaded.
$uploader = Uploader::Init();