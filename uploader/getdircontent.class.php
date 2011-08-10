<?php
/**
 * 
 **/
class GetDirContent extends Filesystem {
		
	/**
	 * @param string path to wanted directory content
	 * @param array possible content: dirs_only, files_only (strings)
	 * @param int
	 * @return array 
	 * @todo implement depth
	 */
	public static function Action ($data) {
		
		$root = (isset($data['root'])) ? $data['root'] : '';
		$dir = (isset($data['dir'])) ? rtrim($data['dir'], '/') . '/' : false;
		$dir = ltrim($dir, '/');
		$conditions = (isset($data['conditions'])) ? (array) $data['conditions'] : array();
		$depth = (isset($data['depth'])) ? $data['depth'] : false;
		if ($depth < 1)
			$depth = false; // $depth 0 = unlimited

		$file_list = array();

		// open directory
		if (!is_dir($root.$dir)) {
			trigger_error('Directory <em>'.$dir.'</em> doesn\'t exist.');
			return false;
		}

		if ($h = opendir($root.$dir)) {
			$i = 0;

			// each file...
			while (false !== ($filename = readdir($h))) {
				
				$filepath = $root.$dir.$filename;

				if (!self::isHiddenFile($filename)) {

					// file?
					if (is_file($filepath) AND !in_array('dirs_only', $conditions)) {

						$file_list[$i]['fileordir'] = 'file';
						$file_list[$i]['dir'] = $dir;
						$file_list[$i]['name'] = $filename; //iconv('iso-8859-1', 'utf-8', $filename); //  @todo what is the filesystem charset?
						
						$file_list[$i]['ext'] = self::getFilenameExt($filename);
						$file_list[$i]['type'] = Filesystem::getFileType($file_list[$i]['ext']);
						
						list($file_list[$i]['size_value'], $file_list[$i]['size_unit']) = self::niceFileSize(fileSize($filepath));
						
						// date
						$file_list[$i]['date_c'] = fileCTime($filepath);
						$today = (date('dmY', time()) == date('dmY', $file_list[$i]['date_c']));
						$file_list[$i]['date_c_nice'] = (!$today) ? date('j.n.Y', $file_list[$i]['date_c']) : __('today');
						$file_list[$i]['date_c_class'] = (!$today) ? '' : 'today';
						
						$file_list[$i]['date_m'] = filemTime($filepath);
						$today = (date('dmY', time()) == date('dmY', $file_list[$i]['date_m']));
						$file_list[$i]['date_m_nice'] = (!$today) ? date('j.n.Y', $file_list[$i]['date_m']) : __('today');
						$file_list[$i]['date_m_class'] = (!$today) ? '' : 'today';
						
						// time
						// time is irrelevant, if the date is lower by 2+ days (let's say)

						$old = (time() - $file_list[$i]['date_c'] > 3600*24*2);
						$file_list[$i]['time_c_nice'] = date('G:i', $file_list[$i]['date_c']);
						$file_list[$i]['time_c_class'] = (!$old) ? '' : 'old';
						

						$old = (time() - $file_list[$i]['date_m'] > 3600*24*2);
						$file_list[$i]['time_m_nice'] = date('G:i', $file_list[$i]['date_m']);
						$file_list[$i]['time_m_class'] = (!$old) ? '' : 'old';
	
						$i++;

					// directory?
					} elseif (is_dir($filepath) AND !in_array('files_only', $conditions)) {

						$file_list[$i]['fileordir'] = 'dir';
						$file_list[$i]['dir'] = $dir;
						$file_list[$i]['name'] = $filename;
						$file_list[$i]['path'] = ltrim($dir.$filename, './');

						// recursion
						if ($depth === false  OR  (int) $depth - 1 > 0)
							$file_list[$i]['content'] = self::Action(array(
								'dir' => $dir.$filename,
								'conditions' => $conditions, 
								'depth' => $depth - 1,
								'root' => $root
							));

						$i++;

					}

				}
			}

			closedir($h);
		}

		return $file_list;

	}
	

} // END