<?php
require_once("FS_RSS.class.php");

$cfg = array(
		'root' => '/home/username/public_html/fs_blog/',
		'version' => 0.91 
);

$fs_rss = new FS_RSS( $cfg );
$fs_rss->channel("example"); // technical name of the channel (e.g. located in directory root/channel_xyz)

header($fs_rss->httpHeader());
print $fs_rss->xmlHeader();
print $fs_rss->rssHeader();

// get all items, sort them by modification time and reverse their order
foreach ($fs_rss->getItems( 0, 0, '_mtime', true ) as $itemID) {
	print $fs_rss->fetchItemXml( $itemID );
}
print $fs_rss->rssFooter();
?>