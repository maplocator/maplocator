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
*This file contains functionality related to global resource tables.
*
***/
require_once('ml_header.php');

function getAllGlobalResources($xmlDoc, &$rootNode) {
  $query = 'select resource_tablename from "Meta_Global_Resource"';
  $result = db_query($query);
  if(!$result) {
    setError($xmlDoc, $rootNode);
  } else {
    setNoError($xmlDoc, $rootNode);
    $rsrcNode = addXMLChildNode($xmlDoc, $rootNode, "resource_tables");
    while($obj = db_fetch_object($result)) {
      $resource_tablename = $obj->resource_tablename;
      addXMLChildNode($xmlDoc, $rsrcNode, "resource", null, array('tablename' => $resource_tablename, 'columns' => implode(",", array_keys(getDBColDesc($resource_tablename)))));
    }
  }
}

function getGlobalResourceMapping($xmlDoc, &$rootNode, $paramsNode) {
  $tableNode = $paramsNode->getElementsByTagName('table')->item(0);
  $tablename = $tableNode->nodeValue;
  $table_type = $tableNode->getAttribute('type');
  if($tablename == null || $tablename == '' || $table_type == null || $table_type == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select id, resource_tablename, resource_column, table_column from "Global_Resource_Mapping" where tablename = \'%s\' and table_type = \'%s\'';
    $result = db_query($query, array($tablename, $table_type));
    if(!$result) {
      setError($xmlDoc, $rootNode, "Error fetching data.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $tblNode = addXMLChildNode($xmlDoc, $rootNode, "table", null, array("tablename" => $tablename, "table_type" => $table_type));
      while($obj = db_fetch_object($result)) {
        $resource_tablename = $obj->resource_tablename;
        $resource_column = $obj->resource_column;
        $table_column = $obj->table_column;
        addXMLChildNode($xmlDoc, $tblNode, "mapping", null, array("id" => $obj->id, "resource_tablename" => $resource_tablename, "resource_column" => $resource_column, "table_column" => $table_column));
      }
    }
  }
}

function getGRMappedTableColumns($xmlDoc, &$rootNode, $paramsNode) {
  $tableNode = $paramsNode->getElementsByTagName('table')->item(0);
  $tablename = $tableNode->nodeValue;
  $table_type = $tableNode->getAttribute('type');
  if($tablename == null || $tablename == '' || $table_type == null || $table_type == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = "select table_column from \"Global_Resource_Mapping\" where tablename = '%s' and table_type = '%s'";
    $result = db_query($query, array($tablename, $table_type));
    if(!$result) {
      setError($xmlDoc, $rootNode, "Error fetching data.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $arr_cols = array();
      while($obj = db_fetch_object($result)) {
        $arr_cols[] = $table_column = $obj->table_column;
      }
      addXMLChildNode($xmlDoc, $rootNode, "table", null, array("tablename" => $tablename, "table_type" => $table_type, 'columns' => implode(",", array_keys(getDBColDesc($tablename))), "mapped_columns" => implode(",", $arr_cols)));
    }
  }
}

function saveGlobalResourceMapping($xmlDoc, &$rootNode, $paramsNode) {
  $tableNode = $paramsNode->getElementsByTagName('table')->item(0);
  $tablename = $tableNode->getAttribute('tablename');
  $table_type = $tableNode->getAttribute('type');

  if(isUserAuthorizedToEditMetadata($tablename, $table_type)) {
    $mappingNode = $tableNode->getElementsByTagName('mapping')->item(0);
    $id = $mappingNode->getAttribute('id');
    $table_column = str_replace("'", "''", $mappingNode->getAttribute('table_column'));
    $resource_tablename = $mappingNode->getAttribute('resource_tablename');
    $resource_column = str_replace("'", "''", $mappingNode->getAttribute('resource_column'));

    if($id == null || $id == '0') { // new entry
      $query = "insert into \"Global_Resource_Mapping\" (resource_tablename, resource_column, tablename, table_type, table_column) values ('%s', '{$resource_column}', '%s', '%s', '{$table_column}')";
      $query_args = array($resource_tablename, $tablename, $table_type);
    } else { // update
      $query = "update \"Global_Resource_Mapping\" set resource_tablename = '%s', resource_column = '{$resource_column}', table_column = '{$table_column}' where id = %d and tablename = '%s' and table_type = '%s'";
      $query_args = array($resource_tablename, $id, $tablename, $table_type);
    }

    $result = db_query($query, $query_args);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error saving information.");
    } else {
      if(db_affected_rows($result) > 0) {
        setNoError($xmlDoc, $rootNode, "Record saved.");
      } else {
        setError($xmlDoc, $rootNode, "Error saving information.");
      }
    }
  } else {
    setError($xmlDoc, $rootNode, "You are not authorized.");
  }
}

function deleteGlobalResourceMapping($xmlDoc, &$rootNode, $paramsNode) {
  $tableNode = $paramsNode->getElementsByTagName('table')->item(0);
  $tablename = $tableNode->getAttribute('tablename');
  $table_type = $tableNode->getAttribute('type');

  if(isUserAuthorizedToEditMetadata($tablename, $table_type)) {
    $mappingNode = $tableNode->getElementsByTagName('mapping')->item(0);
    $id = $mappingNode->getAttribute('id');
    $table_column = str_replace("'", "''", $mappingNode->getAttribute('table_column'));
    $resource_tablename = $mappingNode->getAttribute('resource_tablename');
    $resource_column = str_replace("'", "''", $mappingNode->getAttribute('resource_column'));

    if($id == null || $id == '0') { // new entry
      setError($xmlDoc, $rootNode, "Incorrect information.");
      return;
    } else { // update
      $query = "delete from \"Global_Resource_Mapping\" where resource_tablename = '%s' and resource_column = '{$resource_column}' and table_column = '{$table_column}' and id = %d and tablename = '%s' and table_type = '%s'";
      $query_args = array($resource_tablename, $id, $tablename, $table_type);
    }

    $result = db_query($query, $query_args);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error deleting information.");
    } else {
      if(db_affected_rows($result) > 0) {
        setNoError($xmlDoc, $rootNode, "Record deleted.");
      } else {
        setError($xmlDoc, $rootNode, "Record not found.");
      }
    }
  } else {
    setError($xmlDoc, $rootNode, "You are not authorized.");
  }
}

?>
