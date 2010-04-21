<?php
/*
All MapLocator code is Copyright 2010 by the original authors.

This work is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of the License, or any
later version.

This work is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See version 3 of
the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
Version 3 along with this program as the file LICENSE.txt; if not,
please see http://www.gnu.org/licenses/gpl-3.0.html.

*/

/***
*This file contains code related to layer download feature.
*
***/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

function return_error($msg) {
    $err = <<<END
<?xml version="1.0"?>
<mapdata>
<error value='true'>
<message>
END;
    $err .= $msg;
    $err .= <<< END
</message>
</error>
</mapdata>
END;
    return $err;
}

if((!isset($_REQUEST['file'])) || (!isset($_REQUEST['layer_name'])))
{
   die(return_error('Required parameters are not set'));
}
$file_path = $_SERVER['SCRIPT_FILENAME'];
$pos1 = strrpos($file_path,'/');
$file_path= substr($file_path,0,$pos1)."/shapefiles/";

$filename = $_REQUEST['file'];
$layer_name = $_REQUEST['layer_name'];
$layer_name = str_replace('_',' ',$layer_name);
$file_user_id = substr($filename,0,strpos($filename,'_'));
$file = $file_path.$filename;
$user = $GLOBALS['user'];
$user_ip = $_SERVER['REMOTE_ADDR'];

if($user->uid == $file_user_id){
	if(file_exists($file)) {
		$filesize = sprintf("%u", filesize($file));

		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);

    //update DB table
		$query = "insert into mlocate_download_history (uid,layer_name,file_size,download_date,file_name,user_ip) values (%d, '%s', %d, now() ,'%s','%s')";
    $query_args = array($user->uid, $layer_name, $filesize,$filename,$user_ip);
    $result = db_query($query,$query_args);
    if(!result){
      die(return_error("Error updating db"));
    }

	} else {
	  echo "Error:File doesnot exist";
    }

} else{
	  echo "Error:You are not authorized to download the file";
}
?>
