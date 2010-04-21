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
*This file contains  meta layer related functionality.
*
***/

require_once('ml_header.php');

function getMetaLayer($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;

  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = "select * from \"Meta_Layer\" where layer_tablename = '%s'";
    $result = db_query($query, $layer_tablename);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      if ($arr = db_fetch_array($result)) {
        setNoError($xmlDoc, $rootNode);
        $col_db_info = getDBColDesc("Meta_Layer");
        $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('tablename' => $layer_tablename, 'columns' => implode(",", array_keys(getDBColDesc($layer_tablename)))));
        foreach ($arr as $key => $val) {
          switch($key) {
            case 'layer_id':
            case 'layer_tablename':
              break;
            default:
              $colDesc = $key;
              if (is_array($col_db_info) && $col_db_info[$key] != '') {
                $colDesc = $col_db_info[$key];
              }
              addXMLChildNode($xmlDoc, $lyrNode, $key, $val, array('desc' => $colDesc));
            break;
          }
        }
      } else {
        setError($xmlDoc, $rootNode, "Error fetching information.");
      }
    }
  }
}

function saveMetaLayer($xmlDoc, &$rootNode, $paramsNode) {
  $layerNode = $paramsNode->getElementsByTagName('layer')->item(0);
  $layer_tablename = $layerNode->getAttribute('tablename');
  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    if(isUserAuthorizedToEditMetadata($layer_tablename, TABLE_TYPE_LAYER)) {
      $colNodes = $layerNode->childNodes;
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

      $query = 'update "Meta_Layer" set ' . $str_set . ' where layer_tablename = \'%s\'';
      $result = db_query($query, $layer_tablename);
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

function getLayerColDesc($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;

  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $col_db_info = getDBColDesc($layer_tablename);
    getTableColDesc($layer_tablename, 'layer', $col_db_info, $xmlDoc, $rootNode);
  }
}

function saveLayerColDesc($xmlDoc, &$rootNode, $paramsNode) {
  $layerNode = $paramsNode->getElementsByTagName('layer')->item(0);
  $layer_tablename = $layerNode->getAttribute('tablename');
  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    if(isUserAuthorizedToEditMetadata($layer_tablename, TABLE_TYPE_LAYER)) {
      $colNodes = $layerNode->childNodes;
      $err_flag = false;
      $err_cols = array();
      $qry = 'COMMENT ON COLUMN "'.$layer_tablename.'"."%s" IS ';
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

function getLayersOfType($xmlDoc, &$rootNode, $group_type) {
  if($group_type == 3) {
    $query = 'select layer_tablename, layer_name, access, p_nid, participation_type  from "Meta_Layer" where participation_type in (1,2, 3) and status = 1 order by layer_name;';
  } else {
    $query = 'select layer_tablename, layer_name, access, p_nid, participation_type  from "Meta_Layer" where status = 0';
  }
  $result = db_query($query);
  if(!$result) {
    setError($xmlDoc, $rootNode, "Error fetching data.");
  } else {
    setNoError($xmlDoc, $rootNode);
    $grpNode = addXMLChildNode($xmlDoc, $rootNode, "group_type", $group_type);
    $lyrsNode = addXMLChildNode($xmlDoc, $rootNode, "layers");
    getLayersList($xmlDoc, $lyrsNode, $result);

    $user = $GLOBALS['user'];
    if($user->uid) {
      $query = "";
      $user_roles = $user->roles;
      // Needs to be thought of.
      //if (in_array(SITE_ADMIN_ROLE, $user_roles)) {
      //  $query = 'select layer_tablename, layer_name, access, p_nid, participation_type  from "Meta_Layer" where status = 1 order by layer_name;';
      //} else {
        $lyrs = array();
        foreach($user_roles as $role) {
          if(substr($role, -6) == ' admin') {
            $lyrs[] = substr($role, 0, -6);
          } else if(substr($role, -10) == ' validator') {
            $lyrs[] = substr($role, 0, -10);
          } else if(substr($role, -7) == ' member') {
            $lyrs[] = substr($role, 0, -7);
          }
        }

        if (sizeof($lyrs) > 0) {
          array_walk($lyrs, "singleQuoteString");

          $str_lyrs = implode(",", $lyrs);

          $query = 'select layer_tablename, layer_name, access, p_nid, participation_type  from "Meta_Layer" where layer_tablename in ('.$str_lyrs.') and status = 1 order by layer_name;';
        }
      //}
      if ($query != '') {
        $result = db_query($query);
        if(!$result) {
          setError($xmlDoc, $rootNode, "Error fetching data.");
        } else {
          getLayersList($xmlDoc, $lyrsNode, $result);
        }
      }
    }
  }
}

function getColumnsForLayerOfType($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;
  $column_type = $paramsNode->getElementsByTagName('column_type')->item(0)->nodeValue;

  if($layer_tablename == null || $layer_tablename == '' || $column_type == null || $column_type == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $col_db_info = getDBColDesc($layer_tablename, null, $column_type, false);
    getTableColDesc($layer_tablename, 'layer', $col_db_info, $xmlDoc, $rootNode, false);

    $cols = array();
    foreach($col_db_info as $key => $val) {
      if(substr($key, 0, strlen(AUTO_DBCOL_PREFIX)) != AUTO_DBCOL_PREFIX) {
        $cols[] = $key;
      }
    }
    $colsinfo = getStartEndDatesForColumns($layer_tablename, $cols);

    $colnodes = $xmlDoc->getElementsByTagName('column');
    $i = 0;
    foreach ($colnodes as $colnode) {
      $colname = $colnode->getAttribute('name');
      $colnode->removeAttribute('type');
      $colnode->setAttribute('startdate', $colsinfo[$colname]['startdate']);
      $colnode->setAttribute('enddate', $colsinfo[$colname]['enddate']);
      $i++;
    }
  }
}

?>
