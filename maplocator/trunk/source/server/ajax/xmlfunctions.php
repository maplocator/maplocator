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
*This file contains xml handling functionality .
*
***/


require_once 'functions.php';

function setError($doc, &$root, $msg) {
  return addXMLChildNode($doc, $root, "error", "1", array("msg"=>$msg));
}

function setNoError($doc, &$root, $msg = "") {
  return addXMLChildNode($doc, $root, "error", "0", array("msg"=>$msg));
}

function createXMLNode($doc, $key, $value = null, $attrbs = null) {
  $elem = $doc->createElement($key);

  if ($value != null) {
    $txt = $doc->createTextNode($value);
    $elem->appendChild($txt);
  }

  if ($attrbs != null) {
    foreach ($attrbs as $key => $val) {
      $elem->setAttribute($key, $val);
    }
  }

  return $elem;
}

function addXMLChildNode($doc, &$root, $key, $value = null, $attrbs = null) {
  $elem = createXMLNode($doc, $key, $value, $attrbs);

  if($root)
    $root->appendChild($elem);

  return $elem;
}

function sendErrorResponse($msg) {
  // create a new XML document
  $doc = new DomDocument('1.0');

  // create root node
  $respNode = $doc->createElement('response');
  $respNode = $doc->appendChild($respNode);

  setError($doc, $respNode, $msg);

  // get completed xml document
  $xml_string = $doc->saveXML();

  return $xml_string;
}

function getTableColDesc($tablename, $table_type = 'layer', $col_db_info, $doc, &$resultNode, $onlyDesc = true) {
  setNoError($doc, $resultNode);
  $tabNode = addXMLChildNode($doc, $resultNode, $table_type, null, array('tablename' => $tablename));

  if($onlyDesc) {
    foreach($col_db_info as $name => $val) {
      if(substr($name, 0, strlen(AUTO_DBCOL_PREFIX)) != AUTO_DBCOL_PREFIX) {
        addXMLChildNode($doc, $tabNode, "column", null, array("c_name" => $name, "c_desc" => $val));
      }
    }
  } else {
    foreach($col_db_info as $name => $info) {
      if(substr($name, 0, strlen(AUTO_DBCOL_PREFIX)) != AUTO_DBCOL_PREFIX) {
        $arr['name'] = $name;
        foreach($info as $key => $val) {
          $arr[$key] = $val;
        }
        addXMLChildNode($doc, $tabNode, "column", null, $arr);
      }
    }
  }
}

function getLayersList($xmlDoc, &$rootNode, $db_result) {
  while($obj = db_fetch_object($db_result)) {
    formLayersListXML($xmlDoc, $rootNode, $obj->layer_tablename, $obj->layer_name, $obj->access, $obj->p_nid, $obj->participation_type);
  }
}

function formLayersListXML($xmlDoc, &$rootNode, $layer_tablename, $layer_name, $access, $p_nid, $participation_type) {
  $imgPath = base_path() . path_to_theme().'/images/icons/';
  $layertype = '';

  /* get the layer type */
  $query = 'select layer_type from "Meta_Layer" where layer_tablename= \'%s\'';
  $result = db_query($query, $layer_tablename);
  while($row = db_fetch_object($result)) {
    $layertype = $row->layer_type;
  }

  $attribs = array();
  $attribs['layer_tablename'] = $layer_tablename;
  $attribs['layer_name'] = $layer_name;
  $attribs['icon'] = getLayerIconUrl($layer_tablename);
  $layersChecked = explode(":", $_COOKIE['layersChecked']);
  if (in_array($layer_tablename, $layersChecked)) {
    $attribs['checked'] = 1;
  } else {
    $attribs['checked'] = 0;
  }
  $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, $attribs);
  $toolsNode = addXMLChildNode($xmlDoc, $lyrNode, "tools");

  addTool($xmlDoc, $toolsNode, 'information', $imgPath .'information.png', $layer_name . ' Information', 'getLayerMetadata', array($layer_tablename));

  if ($participation_type > 0) {
    addTool($xmlDoc, $toolsNode, 'participate', $imgPath .'participate.png', 'Participation Info for ' . $layer_name, 'showParticipationInfo', array($layer_tablename, $p_nid));
  }

  if($access) {
    addTool($xmlDoc, $toolsNode, 'download', $imgPath .'download-layertree.png', 'Download ' . $layer_name, 'getDownloadFormats', array($layer_tablename));
  }

  addTool($xmlDoc, $toolsNode, 'ZTE', $imgPath .'zoom-to-extent.png', 'Zoom to layer extent', 'zoomToExtent', array(getLayerExtent($layer_tablename,$layertype)));
}

function addTool($xmlDoc, &$rootNode, $name, $icon, $tooltip, $callfunc = null, $callfuncparams = null) {
  $attribs = array();
  $attribs['name'] = $name;
  $attribs['icon'] = $icon;
  $attribs['tooltip'] = $tooltip;
  $toolNode = addXMLChildNode($xmlDoc, $rootNode, "tool", null, $attribs);
  if($callfunc != null) {
    $attribs = array();
    $attribs['name'] = $callfunc;
    $callNode = addXMLChildNode($xmlDoc, $toolNode, "callfunc", null, $attribs);

    if($callfuncparams != null) {
      $paramsNode = addXMLChildNode($xmlDoc, $callNode, "params");
      foreach($callfuncparams as $param) {
        addXMLChildNode($xmlDoc, $paramsNode, "param", $param);
      }
    }
  }
}

function createXMLForThemeChildNodes($xmlDoc, &$rootNode, $thms) {
  foreach($thms as $thm) {
    if($thm['type'] == 'layer') {
      $attr = array('id' => $thm['id'], 'name' => $thm['name'], 'type' => $thm['layer_type'], 'nid' => $thm['nid']);
      $tNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, $attr);
    } else {
      $attr = array('type' => $thm['type'], 'id' => $thm['id'], 'name' => $thm['name'], 'icon' => getThemeIconUrl($theme['icon']));
      $tNode = addXMLChildNode($xmlDoc, $rootNode, "theme", null, $attr);
      if(isset($thm['children'])) {
        createXMLForThemeChildNodes($xmlDoc, $tNode, $thm['children']);
      }
    }
  }
}
?>
