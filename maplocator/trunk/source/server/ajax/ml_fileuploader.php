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
*This file contains functionality to upload a file to server.
*
***/

require_once('ml_header.php');
header("Content-Type: text/xml");

$fileElem = 'Filedata';

$fileobj = $_FILES[$fileElem];

$ffmpeg = 'ffmpeg';
if(isset($_SESSION['ffmpeg']) && $_SESSION['ffmpeg'] != '') {
  $ffmpeg = $_SESSION['ffmpeg'];
}


$status = '';
$message = '';

$file_upload_errors = array();
$file_upload_errors[0] = 'Upload success.';
$file_upload_errors[1] = 'Try smaller file size.';
$file_upload_errors[2] = 'Try smaller file size.';
$file_upload_errors[3] = 'The uploaded file was only partially uploaded.';
$file_upload_errors[4] = 'No file was uploaded.';
$file_upload_errors[6] = 'Missing a temporary folder.';
$file_upload_errors[7] = 'Failed to write file to disk.';
$file_upload_errors[8] = 'File upload stopped by extension.';

$user = $GLOBALS['user'];
if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user_roles)) {
  $status = "error";
  $message = "You are not authorized.";
}

if(!isset($_REQUEST['layer_tablename'], $_REQUEST['row_id']) || $_REQUEST['layer_tablename'] == '' || $_REQUEST['row_id'] == '') {
  $status = "error";
  $message = "Required parameters are not set";
}

if ($fileobj["error"] > 0) {
  $status = "error";
  $message = "Error uploading file: " . $file_upload_errors[$fileobj["error"]];
}

if($status == '') {
  $layer_tablename = '';
  $row_id = '';
  $filename = '';

  $layer_tablename = $_REQUEST['layer_tablename'];
  $row_id = $_REQUEST['row_id'];

  $filename = str_replace(" ", "_", $fileobj['name']);

  $type = $_REQUEST['type'];

  $path = str_replace(str_replace(base_path(), "", $_SERVER['PHP_SELF']), "", $_SERVER['SCRIPT_FILENAME']);
  if($type == "videos") {
    $path .= "sites/default/files/videos/";
  } else {
    $path .= "sites/default/files/images/";
  }

  $path .= $layer_tablename . "/";
  // Check if the layer directory exists.
  if(!is_dir($path)) {
    // If not, create it.
    mkdir($path);
  }

  $fext = '';
  $fileExists = false;
  $flvname = '';
  if($type == "videos") {
    $fext = substr(strrchr($filename, "."), 1);
    $flvname = substr($filename, 0, strlen($filename) - strlen($fext) - 1) . ".flv";
    $fileExists = file_exists($path . $flvname);
  } else {
    $fileExists = file_exists($path . $filename);
  }
  if($fileExists) {
    $status = 'error';
    $message = 'A file with same name exists already. Please choose a file with different name.';
  } else {
    move_uploaded_file($fileobj['tmp_name'], $path . $filename);

    $col_type = '';
    if($type == "images") {
      $col_type = 'media_columns';
    } else {
      $col_type = 'video_columns';
    }

    $query = 'select %s from "Meta_Layer" where layer_tablename = \'%s\'';
    $query_args = array($col_type, $layer_tablename);
    $result = db_query($query, $query_args);
    if(!$result) {
      $status = 'error';
      $message = 'Error with query.';
      @unlink($path.$filename);
    } else {
      if($obj = db_fetch_array($result)) {
        $cols = $obj[$col_type];
        if($cols == NULL || $cols == '') {
          $status = 'error';
          $message = 'No columns specified in DB.';
          @unlink($path.$filename);
        } else {
          $cols = str_replace("'", "", $cols);
          $colsarr = explode(",", $cols);
          $col = $colsarr[0];
          $fname = '';

          if($type == "videos") {
            $fname = $flvname;
            if(strtolower($fext) != 'flv') {
              // If the file is some other format, convert it to flv and delete original file.
              $cmd = $ffmpeg . ' -i '.$path.$filename.'  -ar 22050 -ab 32 -f flv -s 320×240 '.$path.$flvname;
              exec($cmd , $output , $return);

              if($return != 0) {
                $status = 'error';
                $message = 'Error converting file to flv.';
              }
              @unlink($path.$filename);
            }

            if($status == '') {
              // create thumbnail
              $tmb = str_replace(".flv" , "_tn.jpg", $flvname);
              $cmd = $ffmpeg . ' -itsoffset -4  -i '. $path.$flvname .' -vcodec mjpeg -vframes 1 -an -f rawvideo -s 100x100 '. $path.$tmb;
              exec($cmd , $output , $return);
              if($return != 0) {
                $status = 'error';
                $message = 'Error creating thumbnail.';
                @unlink($path.$filename);
              }
            }
          } else {
            $fname = $filename;
          }


          if($status == '') {
            // save the filename in column
            $query = 'update "%s" set "%s" = case when %s is NULL or %s = \'\' then \'%s\' else %s || \',%s\' end where %sid = %d';
            $query_args = array($layer_tablename, $col, $col, $col, $fname, $col, $filename, AUTO_DBCOL_PREFIX, $row_id);
            $result = db_query($query, $query_args);
            if(!$result) {
              unlink($path.$fname);
              if($type == "videos") {
                $tmb = str_replace(".flv" , "_tn.jpg", $fname);
                unlink($path.$fname);
              }
              $status = 'error';
              $message = 'Error with query.';
            } else {
              if($status == '') {
                $status = 'success';
                $message = 'File has been uploaded successfully.';
              }
            }
          }
        }
      }
    }
  }
}

$result =<<<EOT
  <result>
    <status>{$status}</status>
    <message>{$message}</message>
  </result>
EOT;
echo $result;

?>
