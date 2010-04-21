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
*This file contains defination of class meta layer ani its related functionalities.
*
***/
class Meta_Layer {

  static function getLayersInfoArray($query, $query_args=null) {
    $arr_theme=array();
    $j=0;

    $layersChecked=$GLOBALS['layersChecked'];

    if ($query_args == null) {
      $result_layer=db_query($query);
    }
    else {
      $result_layer=db_query($query, $query_args);
    }
    if (!$result_layer) {
      $errmsgstr=$GLOBALS['errmsgstr'];
      die('Error fetching layer info. ' . $errmsgstr);
    }
    else {
      while ($layer_obj=db_fetch_object($result_layer)) {
        $layer_tablename=$layer_obj->layer_tablename;
        $layer_name=$layer_obj->layer_name;
        $participation_type=$layer_obj->participation_type;
        $p_nid=$layer_obj->p_nid;
        $access=$layer_obj->access;
        $layer_type=$layer_obj->layer_type;
        $arr_theme[$j]["id"]="li" . $layer_tablename;
        $arr_theme[$j]["text"]=treeViewEntryHTML($layer_tablename, $layer_name, $participation_type, $layer_type, $p_nid, $access);
        $arr_theme[$j]["title"]=$layer_name;
        $j++;
      }
    }
    return $arr_theme;
  }

  static function saveMetaLayer($Request,$layer_tablename){
    foreach($Request as $key => $value) {
          if(substr($key, 0, 5) == 'edit-') {
            if($value == "") {
              $val = "NULL";
            } else {
              $val = "'".str_replace("'", "''", $value)."'";
            }
            $val_encoded = htmlentities($val);
            $fields[substr($key, 5)] = $val_encoded;
          }
      }
      $set_arr = array();
      foreach($fields as $key => $value) {
          $set_arr[] = "{$key} = {$value}";
      }
      $set_str = implode(", ", $set_arr);
      $query = 'update "Meta_Layer" set '.$set_str.' where layer_tablename = \'%s\'';
      $query_args = array($layer_tablename);
      $result = db_query($query, $query_args);
      if(!$result) {
          return "Error saving info. Please try after sometime or contact the admin.";
      } else {
          return "Metadata has been saved.";
      }
  }

function saveLayerPermissions($Request,$for_role){
	 $arr_perms = "";
      foreach($Request as $key => $value) {
        if(substr($key, 0, 5) == 'edit-') {
          $value = str_replace("'", "''", substr($key, 5));
          $value = str_replace("_", " ", $value);
          $arr_perms[] = $value;
        }
      }
      $perms = implode(",", $arr_perms);

      $str_default_perms = getDefaultLayerPerms($for_role);

      $is_default = 0;
      if(strcmp($perms, $str_default_perms) == 0) {
        $is_default = 1;
      }

      if($is_default) {
        $query = "delete from mlocate_permission where role_name = '%s'";
        $query_args = array($for_role);
      } else {
        $query = "insert into mlocate_permission(role_name, perm) values('%s','%s')";
        $query_args = array($for_role, $perms);
      }
      $result = db_query($query, $query_args);
      if(!$result) {
        return "Error fetching data. Please try after some time or contact the admin.";
      } else {
        return "Permissions set.";
      }

  }

}
