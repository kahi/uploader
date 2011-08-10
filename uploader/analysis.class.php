<?php

class Analysis {

	public $phpinfo = false;

	function Show () {
		global $lang;
		
		require_once 'analysis.t.phtml';
	}

}