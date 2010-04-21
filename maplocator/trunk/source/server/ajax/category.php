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

/**
 This files includes functionality to fecth themes under which layers are organized
 **/
global $user;
$errmsgstr = "Please try after sometime or contact admin.";


function getSource($theme_type) {
  $query= <<< EOF
      select a.*, b.layer_count from
  (SELECT * FROM "Theme" where theme_id = parent_id and theme_type = %d and status = 1 order by theme_name) as a
  left join
  (select theme_id, count(*) as layer_count from "Theme_Layer_Mapping", "Meta_Layer" where "Theme_Layer_Mapping".layer_id = "Meta_Layer".layer_id and "Meta_Layer".status = 1 group by theme_id) as b
    on a.theme_id = b.theme_id
EOF;
  return Theme::getThemes($query, $theme_type);
}

function getSubThemes($theme_id) {
  $query= <<< EOF
      select a.*, b.layer_count from
  (SELECT * FROM "Theme" where parent_id = %d AND theme_id <> parent_id and status = 1 order by theme_name) as a
  left join
  (select theme_id, count(*) as layer_count from "Theme_Layer_Mapping", "Meta_Layer" where "Theme_Layer_Mapping".layer_id = "Meta_Layer".layer_id and "Meta_Layer".status = 1 group by theme_id) as b
    on a.theme_id = b.theme_id
EOF;
  return Theme::getThemes($query, $theme_id);
}

function getLayersForTheme($theme_id) {
  $arr_theme = array();
  $layersChecked = $GLOBALS['layersChecked'];

  $j=0;

  $query = "SELECT layer_tablename, layer_name, access, p_nid, participation_type,layer_type FROM \"Meta_Layer\" where layer_id in( select layer_id from \"Theme_Layer_Mapping\" where theme_id = %d) and status = 1 order by layer_name";

  $arr_theme = Meta_Layer::getLayersInfoArray($query, $theme_id);
  return $arr_theme;
}

function getParticipatoryLayers() {
  $query = 'select layer_tablename, layer_name, access, p_nid, participation_type,layer_type  from "Meta_Layer" where participation_type in (1,2, 3) and status = 1 order by layer_name;';

  $arr_theme = Meta_Layer::getLayersInfoArray($query);

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
      }
      else if(substr($role, -10) == ' validator') {
        $lyrs[] = substr($role, 0, -10);
      } else if(substr($role, -7) == ' member') {
        $lyrs[] = substr($role, 0, -7);
      }
    }

    if (sizeof($lyrs) > 0) {
        array_walk($lyrs, "singleQuoteString");

        $str_lyrs = implode(",", $lyrs);

        $query = 'select layer_tablename, layer_name, access, p_nid, participation_type,layer_type  from "Meta_Layer" where layer_tablename in ('.$str_lyrs.') and status = 1 order by layer_name;';
    }
    //}
    if ($query != '') {
        $arr_theme1 = Meta_Layer::getLayersInfoArray($query);
        $arr_theme = array_merge($arr_theme, $arr_theme1);
    }
  }
  return $arr_theme;
}

function getInactiveLayers() {
    $arr_theme = array();
    $user = $GLOBALS['user'];

    if ($user->uid) {
        $user_roles = $user->roles;
        if (in_array(SITE_ADMIN_ROLE, $user_roles)) {
            $query = 'select layer_tablename, layer_name, access, p_nid, participation_type  from "Meta_Layer" where status = 0 order by layer_name;';
        } else {
            $lyrs = array();
            foreach($user_roles as $role) {
                if(substr($role, -6) == ' admin') {
                    $lyrs[] = substr($role, 0, -6);
                }
            }

            if (sizeof($lyrs) > 0) {
                array_walk($lyrs, "singleQuoteString");
                $str_lyrs = implode(",", $lyrs);
                $query = 'select layer_tablename, layer_name, access, p_nid, participation_type,layer_type  from "Meta_Layer" where layer_tablename in ('.$str_lyrs.') and status = 0 order by layer_name;';
            }
        }

        if ($query != '') {
            $arr_theme = Meta_Layer::getLayersInfoArray($query);
        }
    }
    return $arr_theme;
}

function quoteString(&$val, $key=NULL) {
    $val = "'" . $val . "'";
}

function getTheme($theme_type,$themeTree,$source) {
    $layersCheckedThemes = array();
    if(count($layersChecked) > 0) {
        $query = <<<EOF
            SELECT DISTINCT theme_id FROM "Theme_Layer_Mapping" WHERE layer_id IN
(SELECT layer_id
FROM "Meta_Layer"
WHERE layer_tablename IN (
EOF;
        $arr = $layersChecked;
        array_walk($arr, "quoteString");
        $query .= implode(",", $arr);
        $query .= "))";

        $result_theme = db_query($query);
        if(!$result_theme) {
        //Error occured
            $errmsgstr = $GLOBALS['errmsgstr'];
            die('Error fetching layers. ' . $errmsgstr);
        } else {
            while($theme_obj = db_fetch_object($result_theme)) {
                $layersCheckedThemes[] = $theme_obj->theme_id;
            }
        }
    }
    $arr_theme = array();
    switch ($theme_type) {
        case 1:
        case 2:
            if ($source == "source") {
                $arr_theme = getSource($theme_type);
            } else {
                $theme_id = $source;
                $arr_theme = array_merge(getSubThemes($theme_id), getLayersForTheme($theme_id));
            }
            break;
        case 3:
            $arr_theme = getParticipatoryLayers();
            break;
        case 6:
            $arr_theme = getInactiveLayers();
            break;
    }
    $str = json_encode($arr_theme);
    return $str;
}

?>
