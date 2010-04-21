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
*This file contains functionality to delete media associated with a lyer and/or features.
*
***/

require_once('ml_header.php');

function deleteMedia($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;
  $row_id = $paramsNode->getElementsByTagName('row_id')->item(0)->nodeValue;
  $media_type = $paramsNode->getElementsByTagName('type')->item(0)->nodeValue;
  $filename = $paramsNode->getElementsByTagName('filename')->item(0)->nodeValue;

  if(($layer_tablename == null || $layer_tablename == '') || ($row_id == null || $row_id == '') || ($media_type == null || $media_type == '') || ($filename == null || $filename == '')) {
    setError($xmlDoc, $rootNode, "Required parameters not set..");
    return;
  } else {
    if(!userHasEditLayerDataPerm($layer_tablename, $row_id)) {
      setError($xmlDoc, $rootNode, "You are not authorized.");
      return;
    } else {
      $col_type = 'media_columns';
      if($media_type == 'videos') {
        $col_type = 'video_columns';
      }

      $query = 'select %s from "Meta_Layer" where layer_tablename = \'%s\'';
      $query_args = array($col_type, $layer_tablename);
      $result = db_query($query, $query_args);
      if(!$result) {
        setError($xmlDoc, $rootNode, "Error talking to database. Please try again later.");
        return;
      } else {
        if($obj = db_fetch_array($result)) {
          $cols = $obj[$col_type];
          if($cols == NULL || $cols == '') {
            setError($xmlDoc, $rootNode, "No columns specified in DB.");
            return;
          } else {
            $cols = str_replace("'", "", $cols);
            $colsarr = explode(",", $cols);
            $col = $colsarr[0];

            $query = 'select %s from "%s" where __mlocate__id = %d';
            $query_args = array($col, $layer_tablename, $row_id);
            $result = db_query($query, $query_args);
            if(!$result) {
              setError($xmlDoc, $rootNode, "Error talking to database. Please try again later.");
              return;
            } else {
              if($obj = db_fetch_array($result)) {
                if($obj[$col] == null || $obj[$col] == '') {
                  setError($xmlDoc, $rootNode, "File not found.");
                  return;
                }
                $fls = explode(",", $obj[$col]);
                $indx = array_search($filename, $fls);
                if($indx === false) {
                  setError($xmlDoc, $rootNode, "File not found.");
                  return;
                } else {
                  array_splice($fls, $indx, 1);

                  $query = 'update "%s" set %s = \'%s\'';
                  $query_args = array($layer_tablename, $col, implode(",", $fls));
                  $result = db_query($query, $query_args);
                  if(!$result) {
                    setError($xmlDoc, $rootNode, "Error talking to database. Please try again later.");
                    return;
                  } else {
                    $path = str_replace(str_replace(base_path(), "", $_SERVER['PHP_SELF']), "", $_SERVER['SCRIPT_FILENAME']) . 'sites/default/files/';
                    if($media_type == 'videos') {
                      $path .= 'videos/' . $layer_tablename . '/' . $filename;
                      @unlink($path);
                      $path = substr($path, 0, strlen($path) - 4) . '_tn.jpg';
                      @unlink($path);
                    } else {
                      $path .= 'images/' . $layer_tablename . '/' . $filename;
                      @unlink($path);
                    }
                    setNoError($xmlDoc, $rootNode, "File deleted.");
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
?>
