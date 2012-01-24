<?php

error_reporting(E_ALL); // Set E_ALL for debuging

if(!($GLOBALS['userAuth'] === 1 && $GLOBALS['userAccess'] === 1 && System::user()->AllowCookie('admin', true))){
	exit('Access Denied!');
}

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool
 **/
function access($attr, $path, $data, $volume) {
	return strpos(basename($path), '.') === 0 ? !($attr == 'read' || $attr == 'write') : ($attr == 'read' || $attr == 'write');
}

$opts = array(
	'debug' => true,
	'roots' => array(
		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => 'uploads',         // path to files (REQUIRED)
			'URL'           => GetSiteUrl().'uploads/', // URL to files (REQUIRED)
			'accessControl' => 'access',
			'uploadAllow' => array(
					// applications
					'application/x-executable',
					'application/vnd.ms-word',
					'application/vnd.ms-excel',
					'application/vnd.ms-powerpoint',
					'application/vnd.ms-powerpoint',
					'application/pdf',
					'application/xml',
					'application/vnd.oasis.opendocument.text',
					'application/x-shockwave-flash',
					'application/x-bittorrent',
					'application/x-jar',
					// archives
					'application/x-gzip',
					'application/x-gzip',
					'application/x-bzip2',
					'application/x-bzip2',
					'application/x-bzip2',
					'application/zip',
					'application/x-rar',
					'application/x-tar',
					'application/x-7z-compressed',
					// texts
					'text/plain',
					'text/html',
					'text/html',
					'text/javascript',
					'text/css',
					'text/rtf',
					'text/rtfd',
					'text/xml',
					'text/x-sql',
					'text/plain',
					'text/x-comma-separated-values',
					// images
					'image/x-ms-bmp',
					'image/jpeg',
					'image/jpeg',
					'image/gif',
					'image/png',
					'image/tiff',
					'image/tiff',
					'image/x-targa',
					'image/vnd.adobe.photoshop',
					'image/vnd.adobe.photoshop',
					'image/xbm',
					'image/pxm',
					//audio
					'audio/mpeg',
					'audio/midi',
					'audio/ogg',
					'audio/ogg',
					'audio/x-m4a',
					'audio/wav',
					'audio/x-ms-wma',
					// video
					'video/x-msvideo',
					'video/x-dv',
					'video/mp4',
					'video/mpeg',
					'video/mpeg',
					'video/quicktime',
					'video/x-ms-wmv',
					'video/x-flv',
					'video/x-matroska',
					'video/webm',
					'video/ogg',
					'video/ogg'
			)
		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

