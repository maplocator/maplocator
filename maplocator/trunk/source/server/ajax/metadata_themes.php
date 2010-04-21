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
*This file contains functionality to manage themes.
*
***/


require_once('ml_header.php');

function getAllThemes($xmlDoc, &$rootNode) {
  $themes1 = getThemesByType(1);
  $themes2 = getThemesByType(2);
  if($themes1 === false || $themes2 === false) {
    setError($xmlDoc, $rootNode, "Error fetching information.");
  } else {
    setNoError($xmlDoc, $rootNode);
    $thmsNode = addXMLChildNode($xmlDoc, $rootNode, "themes", null, array('theme_type' => 1));
    foreach($themes1 as $theme) {
      addXMLChildNode($xmlDoc, $thmsNode, "theme", null, array('theme_id' => $theme['theme_id'], 'theme_name' => $theme['theme_name']));
    }
    $thmsNode = addXMLChildNode($xmlDoc, $rootNode, "themes", null, array('theme_type' => 2));
    foreach($themes2 as $theme) {
      addXMLChildNode($xmlDoc, $thmsNode, "theme", null, array('theme_id' => $theme['theme_id'], 'theme_name' => $theme['theme_name']));
    }
  }
}

function getThemesOfType($xmlDoc, &$rootNode, $paramsNode) {
  $theme_type = $paramsNode->getElementsByTagName('theme_type')->item(0)->nodeValue;

  $themes = getThemesByType($theme_type);
  if($themes === false) {
    setError($xmlDoc, $rootNode, "Error fetching information.");
  } else {
    setNoError($xmlDoc, $rootNode);
    $thmsNode = addXMLChildNode($xmlDoc, $rootNode, "themes", null, array('theme_type' => $theme_type));
    foreach($themes as $theme) {
      addXMLChildNode($xmlDoc, $thmsNode, "theme", null, array('theme_id' => $theme['theme_id'], 'theme_name' => $theme['theme_name'], 'icon' => getThemeIconUrl($theme['icon']), 'geolocation' => $theme['geolocation']));
    }
  }
}

function getThemesChildNodes($xmlDoc, &$rootNode, $paramsNode) {
  $theme_id = $paramsNode->getElementsByTagName('theme_id')->item(0)->nodeValue;
  $category_id = $paramsNode->getElementsByTagName('category_id')->item(0)->nodeValue;
  $level = $paramsNode->getElementsByTagName('level')->item(0)->nodeValue;

  if($category_id == null || $category_id == '') {
    $category_id = 0;
  }

  if($level == null || $level == '') {
    $level = 1;
  }

  if($theme_id == null || $theme_id == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $thms = _getThemeChildNodes($theme_id, $category_id, $level);
    if($thms === false) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $thmsNode = addXMLChildNode($xmlDoc, $rootNode, "theme", null, array('id' => $theme_id, 'category_id' => $category_id, 'level' => $level));

      if($category_id == 0) {
        $nid = 0;
        $images = '';
        $videos = '';
        $query = 'select nid, images, videos from "Theme" where theme_id = %d';
        $query_args = array($theme_id);
        $result = db_query($query, $query_args);
        if(!$result) {
          return false;
        } else {
          if($obj = db_fetch_object($result)) {
            $nid = $obj->nid;
            $images = array();
            $str = $obj->images;
            if($str != NULL || !empty($str)) {
              $images = explode(",", $str);
            }
            $videos = array();
            $str = $obj->videos;
            if($str != NULL || !empty($str)) {
              $videos = explode(",", $str);
            }
          }
        }
        if($nid > 0) {
          $teaser = getReadMoreDrupalNodeTeaser($nid, 150);
          addXMLChildNode($xmlDoc, $thmsNode, 'narrative', $teaser, array('nid' => $nid));
        }

        $mediaNode = addXMLChildNode($xmlDoc, $thmsNode, 'media');

        $base_path = base_path();

        $imagesNode = addXMLChildNode($xmlDoc, $mediaNode, 'images');
        foreach ($images as $im) {
          addXMLChildNode($xmlDoc, $imagesNode, "image", $im, array('src' => $base_path . 'sites/default/files/images/theme_' . $theme_id . '/' . $im));
        }

        $videosNode = addXMLChildNode($xmlDoc, $mediaNode, 'videos');
        foreach ($videos as $vd) {
          addXMLChildNode($xmlDoc, $videosNode, "video", $vd, array('src' => $base_path . 'sites/default/files/videos/theme_' . $theme_id . '/' . $vd));
        }
      }

      createXMLForThemeChildNodes($xmlDoc, $thmsNode, $thms);
    }
  }
}
?>
