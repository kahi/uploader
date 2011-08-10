<?php

class Debug {
	
	static private $counter = 0;
	
	function e ($s) {
		echo '<pre style="text-align:left; font-size:12px; margin:10px;">';
		echo var_export($s);
		echo '</pre>';
		echo '<hr />';
	}
	
	function dump($a) {self::e($a);}
	
	function point ($note = '') {
		self::$counter++;
		echo '['. $note . self::$counter . ']';
	}
	
}