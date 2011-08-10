<?php

class Error {

	public $title;
	public $message;


	function __construct ($message = false) {
		
		// quick showing
		if ($message) {
			$this->message = $message;
			$this->Show();	
		}
		
	}
	

	public function Show () {
		global $lang;
		
		if (empty($this->title))
			$this->title = 'Uploader: Error';
		require_once 'error.t.phtml';
		
		exit;
	}
	
}	
