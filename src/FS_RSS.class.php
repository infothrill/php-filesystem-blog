<?php
// $Id$
require_once ('FS_DirObject.class.php');

/**
 * This class is a hack to use DirObjects as a source for building RSS feeds.
 *
 *
 * @version $Revision$
 * @author Paul Kremer <pkremer@spurious.biz>
 * @copyright MIT License
 *           
 *           
 */
class FS_RSS {
	
	/**
	 * The constructor
	 *
	 * @return void
	 *
	 */
	function FS_RSS($cfg = '') {
		if (! is_array ( $cfg )) {
			trigger_error ( "Need config array", E_USER_ERROR );
		} else 		// GET CONFIG FROM given array:
		{
			$this->cfg = $cfg;
		}
		// get rid of most evil PHP premature optimization shit:
		clearstatcache ();
		
		if (! is_dir ( $this->cfg ['root'] )) {
			trigger_error ( $this->cfg ['root'] . " (root) is not a directory of does not exist", E_USER_ERROR );
		}
		$this->CHANNEL_PREFIX = 'channel_';
	}
	
	/**
	 * Sets or returns the channel
	 */
	function channel($channel = '') {
		if (strlen ( $channel ) > 0) {
			$this->channel = $channel;
		}
		return $this->channel;
	}
	
	/**
	 * makes sure that a channel is set
	 */
	function checkChannel() {
		if (! isset ( $this->channel )) {
			trigger_error ( "No channel set!", E_USER_ERROR );
		}
	}
	
	/**
	 * Will gather a list of items from disk an return a list of item ID's
	 */
	function getItems($offset = 0, $limit = 0, $sort = '', $reverse = false) {
		$this->checkChannel ();
		$dir = $this->cfg ['root'] . "/" . $this->CHANNEL_PREFIX . $this->channel . "/";
		
		$dir = new FS_DirObject ( $dir );
		return $dir->getObjects ( $offset, $limit, $sort, $reverse, 'item' );
	}
	
	/**
	 * Will gather a the specified item from disk an return an array of properties
	 */
	function getItemProperties($id) {
		$this->checkChannel ();
		$dir = $this->cfg ['root'] . "/" . $this->CHANNEL_PREFIX . $this->channel . "/" . $id . "/";
		$dir = new FS_DirObject ( $dir );
		return $dir->getProperties ();
	}
	function fetchItemXml($id) {
		$props = $this->getItemProperties ( $id );
		$xml = "<item>\n";
		if (isset ( $props ['title'] )) {
			$xml .= "\t<title>" . $props ['title'] . "</title>\n";
		}
		if (isset ( $props ['description'] )) {
			$xml .= "\t<description>" . $props ['description'] . "</description>\n";
		}
		if (isset ( $props ['link'] )) {
			$xml .= "\t<link>" . $props ['link'] . "</link>\n";
		}
		$xml .= "</item>\n";
		return $xml;
	}
	
	/**
	 * Will gather a the specified channel from disk an return an array of properties
	 */
	function getChannelProperties() {
		$this->checkChannel ();
		$basedir = $this->cfg ['root'] . "/" . $this->CHANNEL_PREFIX . $this->channel . "/";
		$dir = new FS_DirObject ( $basedir );
		$props = $dir->getProperties ();
		$dir = new FS_DirObject ( $basedir . 'image/' );
		$props ['image'] = $dir->getProperties ();
		return $props;
	}
	
	/**
	 * Returns a valid http header for the rss feed
	 */
	function httpHeader() {
		return "Content-Type: text/xml\n\n";
	}
	
	/**
	 * Returns a valid xml header for the rss feed (e.g.
	 * xml version and doctype)
	 */
	function xmlHeader() {
		$s = '<?xml version="1.0"?>' . "\n";
		$s .= '<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.91.dtd">';
		return $s;
	}
	
	/**
	 * Returns a valid Rss header for the rss feed (e.g.
	 * RSS version etc.)
	 */
	function rssHeader() {
		$p = $this->getChannelProperties ();
		$s = '<rss version="0.91"><channel>
	<title>' . $p ['title'] . '</title>
	<link>' . $p ['link'] . '</link>
	<description>' . $p ['description'] . '</description>
	<language>' . $p ['language'] . '</language>
	<image>
		<title>' . $p ['image'] ['title'] . '</title>
		<url>' . $p ['image'] ['url'] . '</url>
		<link>' . $p ['image'] ['link'] . '</link>
	</image>
	<pubDate>' . $p ['pubDate'] . '</pubDate>';
		return $s;
	}
	
	/**
	 * Returns a valid Rss footer for the rss feed (to close the XML opened with rssHeader())
	 */
	function rssFooter() {
		return '</channel></rss>';
	}
} // end class FS_RSS
?>