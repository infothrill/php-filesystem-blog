<?php
// $Id$

/**
 * This class is a contains functionality for handling dirobjects.
 *
 * By definition, a DirObject is defined like the following:<br/>
 * A DirObject has a path, which matches a real existing directory in a filesystem<br/>
 * A DirObject has a type, given in the extension part of the directory name<br/>
 * A DirObject has none or many properties, given by files contained within the mentioned directory<br/>
 * The names of the properties are equal to their filenames, whereas the values equal the content of the files<br/>
 * A DirObject can contain other DirObjects, which are _not_ properties<br/>
 *
 *
 * @version $Revision$
 * @author Paul Kremer <pkremer@spurious.biz>
 * @copyright MIT License
 *           
 *           
 */
class FS_DirObject {
	
	/**
	 * The constructor
	 *
	 * @param string $dir
	 *        	Directory name
	 * @param array $cfg
	 *        	optional configuration
	 *        	
	 */
	function FS_DirObject($dir, $cfg = '') {
		$this->dir = $dir;
		if (preg_match ( "/\.\./", $this->dir )) {
			trigger_error ( "id contains invalid chars: . ", E_USER_ERROR );
		}
		if (! is_array ( $cfg )) {
			$this->cfg = array ();
		} else {
			$this->cfg = $cfg;
		}
		// get rid of most evil PHP premature optimization shit:
		clearstatcache ();
		
		if (! is_dir ( $this->dir )) {
			trigger_error ( "'" . $this->dir . "' (root) is not a directory or does not exist", E_USER_ERROR );
		}
	}
	
	/**
	 * makes sure that the given path does not contain any double dots '..', for better security related
	 * to directory traversal
	 *
	 * @param string $p
	 *        	path
	 * @return string $p path
	 */
	function safePath($p = '') {
		if (preg_match ( "/\.\./", $p )) {
			trigger_error ( "id contains invalid chars: . ", E_USER_ERROR );
		}
		return $p;
	}
	
	/**
	 * Will gather a list of DirObjects directly nested in the current DirObject
	 * and return a list of object ID's
	 *
	 * @param int $offset
	 *        	offset
	 * @param int $limit
	 *        	limit
	 * @param string $sort
	 *        	property name to sort by
	 * @param bool $reverse        	
	 * @param string $type
	 *        	get only Objects with this type
	 * @return array $objects list of ID's
	 */
	function getObjects($offset = 0, $limit = 0, $sort = '', $reverse = false, $type = '') {
		$items = array ();
		$subObjects = $this->getDirObjects ();
		foreach ( $subObjects as $d ) {
			if (strlen ( $type ) > 0 && ! preg_match ( "/\.$type$/", $d )) {
				// DO NOTHING: we are not interested, because the type does not match
			} else {
				$tmp = new FS_DirObject ( $this->dir . $d . '/' );
				$item = $tmp->getProperties ();
				if (strlen ( $sort ) > 0 && isset ( $item [$sort] )) {
					// get Item and remember the property to sort by:
					$items [$item [$sort]] = $d;
				} else {
					// no sorting, plain ID, also catch items with missing 'sort' property
					array_push ( $items, $d );
				}
			}
		}
		if (strlen ( $sort ) > 0) {
			if ($reverse) {
				arsort ( $items );
			} else {
				asort ( $items );
			}
			$items = array_values ( $items );
		}
		if ($offset > 0) {
			// trash elements in the beginning of the array:
			for($i = 0; $i < $offset; $i ++) {
				array_shift ( $items );
			}
		}
		if ($limit > 0) {
			// cut off everything behind:
			array_splice ( $items, $limit );
		}
		return $items;
	}
	
	/**
	 * Returns all DirObjects contained within the current DirObject
	 *
	 * @return array $dirs list of directory names
	 */
	function getDirObjects() {
		$dir = $this->dir;
		$dirs = array ();
		if (is_dir ( $dir )) {
			// open dir and look for sub dirs
			if ($dh = opendir ( $dir )) {
				while ( ($file = readdir ( $dh )) !== false ) {
					if ($file !== '.' && $file !== '..' && is_dir ( $dir . $file )) {
						array_push ( $dirs, $file );
					}
				}
				closedir ( $dh );
			} else {
				trigger_error ( "Could not open dir '$dir'", E_USER_ERROR );
			}
		} else {
			trigger_error ( "Not a directory: '$dir'", E_USER_ERROR );
		}
		return $dirs;
	}
	
	/**
	 * Returns the propertis of a DirObject (e.g.
	 * all the contents of all the files contained therein)
	 *
	 * @return array $props associative array with property value pairs
	 */
	function getProperties() {
		$dir = $this->dir;
		$props = array ();
		if (is_dir ( $dir )) {
			if ($dh = opendir ( $dir )) {
				while ( ($file = readdir ( $dh )) !== false ) {
					if ($file !== '.' && $file !== '..' && is_file ( $dir . $file ) && is_readable ( $dir . $file )) {
						$props [$file] = $this->fetchFile ( $dir . $file );
					}
				}
				closedir ( $dh );
				// get the modification time of the dir:
				$props ['_mtime'] = filemtime ( $dir . "." );
				$props ['_path'] = $dir . ".";
			} else {
				trigger_error ( "Could not open dir '$dir'", E_USER_ERROR );
			}
		} else {
			trigger_error ( "Not a directory: '$dir'", E_USER_ERROR );
		}
		return $props;
	}
	
	/**
	 * Fetches the content of the specified file and prepares it for the web, e.g.
	 * it trims trailing spaces and replaces all special characters with html entities.
	 *
	 * @param string $file
	 *        	filename
	 * @return string $data content of the file
	 */
	function fetchFile($file) {
		$data = rtrim ( file_get_contents ( $file ) );
		$data = htmlentities ( $data );
		return $data;
	}
} // end class FS_DirObject
?>