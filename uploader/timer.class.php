<?php
/**
 * class Timer. Time is ticking...
 */

class Timer {

	private static $start = 0;
	private static $duration = 0;


	public static function Start() {
		self::$start = microtime();
	}


	public static function Stop() {
		self::$duration = microtime() - self::$start;
		return $time;
	}


	public static function Get($round = 4) {
		if (self::$duration)
			return round(self::$duration, $round);
		else
			return round(microtime() - self::$start, $round);
	}

}
