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
*This file contains functionality to manage link table.
*
***/

require_once('ml_header.php');

function getLinkTables($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;

  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select link_tablename, link_name from "Meta_LinkTable" where layer_id = (select layer_id from "Meta_Layer" where layer_tablename= \'%s\')';
    $result = db_query($query, $layer_tablename);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('tablename' => $layer_tablename));

      while($obj = db_fetch_object($result)) {
        addXMLChildNode($xmlDoc, $lyrNode, "linktable", null, array('link_tablename' => $obj->link_tablename, 'link_name' => $obj->link_name));
      }
    }
  }
}

function getMetaLinkTable($xmlDoc, &$rootNode, $paramsNode) {
  $link_tablename = $paramsNode->getElementsByTagName('link_tablename')->item(0)->nodeValue;

  if($link_tablename == null || $link_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select ml.layer_tablename, mlnk.* from "Meta_LinkTable" as mlnk, "Meta_Layer" as ml where mlnk.layer_id = ml.layer_id and mlnk.link_tablename = \'%s\';';
    $result = db_query($query, $link_tablename);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      if($arr = db_fetch_array($result)) {
        $layer_tablename = $arr['layer_tablename'];
        $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('tablename' => $layer_tablename, 'columns' => implode(",", array_keys(getDBColDesc($layer_tablename)))));
        $lnk_col_info = getDBColDesc($link_tablename);
        $lnkNode = addXMLChildNode($xmlDoc, $rootNode, "link", null, array('tablename' => $link_tablename, 'columns' => implode(",", array_keys($lnk_col_info))));
        foreach ($arr as $key => $val) {
          switch($key) {
            case 'layer_tablename':
            case 'layer_id':
            case 'link_tablename':
              break;
            default:
              $colDesc = $key;
              if (is_array($lnk_col_info) && $lnk_col_info[$key] != '') {
                $colDesc = $lnk_col_info[$key];
              }
              addXMLChildNode($xmlDoc, $lnkNode, $key, $val, array('desc' => $colDesc));
              break;
          }
        }
      }
    }
  }
}

function saveMetaLinkTable($xmlDoc, &$rootNode, $paramsNode) {
  $linkNode = $paramsNode->getElementsByTagName('link')->item(0);
  $link_tablename = $linkNode->getAttribute('tablename');
  if($link_tablename == null || $link_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    if(isUserAuthorizedToEditMetadata($link_tablename, TABLE_TYPE_LINK)) {
      $colNodes = $linkNode->childNodes;
      $arr_set = array();
      foreach($colNodes as $colNode) {
        $col = $colNode->nodeName;
        $col_val = $colNode->nodeValue;
        if($col_val == null) {
          $arr_set[] = "$col = null";
        } else {
          $col_val = str_replace("'", "''", $col_val);
          $arr_set[] = "$col = '$col_val'";
        }
      }
      $str_set = implode(",", $arr_set);

      $query = 'update "Meta_LinkTable" set ' . $str_set . ' where link_tablename = \'%s\'';
      $result = db_query($query, $link_tablename);
      if (!$result) {
        setError($xmlDoc, $rootNode, "Error saving information.");
      } else {
        setNoError($xmlDoc, $rootNode, "Record saved.");
      }
    } else {
      setError($xmlDoc, $rootNode, "You are not authorized.");
    }
  }
}

function getLinkColDesc($xmlDoc, &$rootNode, $paramsNode) {
  $link_tablename = $paramsNode->getElementsByTagName('link_tablename')->item(0)->nodeValue;

  if($link_tablename == null || $link_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $col_db_info = getDBColDesc($link_tablename);
    getTableColDesc($link_tablename, 'link', $col_db_info, $xmlDoc, $rootNode);
  }
}

function saveLinkColDesc($xmlDoc, &$rootNode, $paramsNode) {
  $linkNode = $paramsNode->getElementsByTagName('link')->item(0);
  $link_tablename = $linkNode->getAttribute('tablename');
  if($link_tablename == null || $link_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    if(isUserAuthorizedToEditMetadata($link_tablename, TABLE_TYPE_LINK)) {
      $colNodes = $linkNode->childNodes;
      $err_flag = false;
      $err_cols = array();
      $qry = 'COMMENT ON COLUMN "'.$link_tablename.'"."%s" IS ';
      foreach($colNodes as $colNode) {
        $col = $colNode->nodeName;
        $col_val = $colNode->nodeValue;
        if($col_val == null) {
          $tmpqry = $qry . "null;";
        } else {
          $col_val = str_replace("'", "''", $col_val);
          $tmpqry = $qry . "'$col_val';";
        }
        $res = db_query($tmpqry, $col);
        if(!$res) {
          $err_flag = true;
          $err_cols[] = $col;
        }
      }
      if($err_flag) {
        setError($xmlDoc, $rootNode, "Error saving description for following columns: \r\n" . implode(",", $err_cols));
      } else {
        setNoError($xmlDoc, $rootNode, "Record saved.");
      }
    } else {
      setError($xmlDoc, $rootNode, "You are not authorized.");
    }
  }
}


?>
