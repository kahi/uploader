<?php
/**
 * class Image
 *
 * contains functions for image-management, resizing, maybe more
 * 
 * @todo correct resizing smaller images (handling $resize_smaller)
 * @version 0.9
 * @copyright Peter Kahoun, http://kahi.cz
 * @license New-BSD 
 */
class Image {

	/**
	 * Type (extension) of source file
	 * 
	 * @var string 
	 * @see getType()
	 * @note could be solved with __get()
	 */
	private $type;
	
	
	/**
	 * Source file
	 * 
	 * @var resource
	 */	 
	private $file; 
	
	
	/**
	 * Log
	 * 
	 * @var array
	 */
	public $log;
	
	
	
	
	
	
	public function __construct ($settings = array()) {

		$defaults = array(
			'max_w' => 180,
			'max_h' => 180,
			'resize_smaller' => false,
			'crop_to_max_size' => false,
			'jpg_quality' => 85,
			'png_quality' => 5,
			);

		$settings = array_merge($defaults, $settings);

		foreach ($settings as $key => $value) {
			$this->$key = $value;
		}

	}


	/**
	 * Creates new file, a resized copy of file set in $this->path
	 *
	 *	@param string path to the output file
	 *
	 * @returns bool
	 *
	 * @todo test all cases
	 */
	public function resize ($target) {

		// prepare source file
		if (!isset($this->file))
			if (!$this->load())
				return false;

		$w = imageSX($this->file);
		$h = imageSY($this->file);


		// calculate new size
		if (!$this->resize_smaller AND ($w < $this->max_w AND $h < $this->max_h)) {

			// do nothing

		} elseif (!$this->crop_to_max_size OR ($this->crop-to-max-size AND ($w < $this->max_w OR $h < $this->max_h))) {
			
			$this->log[] = 'No crop way';
			
			// new size calculation
			$w_ratio = $w / $this->max_w;
			$h_ratio = $h / $this->max_h;

			$ratio = max($w_ratio, $h_ratio);

			$w_new = $w / $ratio;
			$h_new = $h / $ratio;

			// no crop
			$x = 0;
			$y = 0;

			$w_source = $w;
			$h_source = $h;

		} else {
			
			$this->log[] = 'Might need to crop';
			
			// new size calculation
			$w_ratio = $w / $this->max_w;
			$h_ratio = $h / $this->max_h;

			$ratio = min($w_ratio, $h_ratio);

			$this->log[] = "ratio = $ratio";
				
			// size = max values
			$w_new = $this->max_w;
			$h_new = $this->max_h;
			
			// source size
			$w_source = $this->max_w * $ratio;
			$h_source = $this->max_h * $ratio;

			// x/y start point calculation
			$w_new_temp = $w / $ratio;
			$h_new_temp = $h / $ratio;
			
			$x = 0;
			$y = 0;
			
			if ($w_new_temp > $this->max_w)
				$x = floor(($w - $w_source)/2);
			else
				$y = floor(($h - $h_source)/2);

		}

		$file_new = imageCreateTrueColor($w_new, $h_new);

		imageCopyResampled($file_new, $this->file, 0, 0, $x, $y, $w_new, $h_new, $w_source, $h_source);

		$this->log[] = "imageCopyResampled($file_new, $this->file, 0, 0, $x, $y, $w_new, $h_new, $w_source, $h_source)";

		
		// saving
		// @note is this right place?
		switch ($this->getType()) {
			case 'jpg':
				$result = imageJpeg($file_new, $target, $this->jpg_quality);
				break;
			case 'png':
				$result = imagePng($file_new, $target, $this->png_quality);
				break;
			case 'gif':
				$result = imageGif($file_new, $target);
				break;
		}

		imageDestroy($this->file);
		imageDestroy($file_new);

		return $result;

	}


	/**
	 * get the type of image-file
	 *
	 *	@returns string filetype (extension) of path defined in $this->path
	 */
	public function getType () {

		if (!isset($this->type))
			$this->type = str_replace('jpeg', 'jpg', substr(strrchr($this->path, '.'), 1));

		return $this->type;

	}

	/**
	 * saves the resource to $this->file (defined by $this->path)
	 *
	 *	@returns bool Succeed?
	 */
	public function load () {

		if (!$this->path OR !is_file($this->path))
			return false;

		switch ($this->getType()) {
			case 'jpg':
				$this->file = imageCreateFromJpeg($this->path);
				return true;
				break;
			case 'png':
				$this->file = imageCreateFromPng($this->path);
				return true;
				break;
			case 'gif':
				$this->file = imageCreateFromGif($this->path);
				return true;
				break;
			default:
				die ('Unknown type: ' . $this->getType());
				return false; // @todo ...not too great
		}

	}

}



