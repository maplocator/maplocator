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
* This file contains functionality to handle data in json format.
*
***/

  if(!isset($_REQUEST['json_request']) || empty($_REQUEST['json_request'])) {
    die(json_encode(set_error_arr("Invalid request")));
  }

  $jsonString = urldecode($_REQUEST['json_request']);
  $jsonString = str_replace("\\", "", $jsonString);
  $data = json_decode($jsonString, true);

  if(count($data) == 0) {
    die(json_encode(set_error_arr("Invalid request")));
  }

  require_once('ml_header.php');

  switch ($data['action']) {
    case 'getAllThemesList':
      get_all_themes_list();
      break;
    case 'getAllThemes':
      get_all_themes();
      break;
    case 'getThemeInfo':
      get_theme_info();
      break;
    case 'saveThemeInfo':
      save_theme_info();
      break;
    case 'deleteTheme':
      delete_theme();
      break;
    case 'getAllCategoriesList':
      get_all_categories_list();
      break;
    case 'getCategoryInfo':
      get_category_info();
      break;
    case 'saveCategoryInfo':
      save_category_info();
      break;
    case 'deleteCategory':
      delete_category();
      break;
    case 'getAllLayersList':
      get_all_layers_list();
      break;
    case 'getThemeLayerMapping':
      get_theme_layer_mapping();
      break;
    case 'saveThemeLayerMapping':
      save_theme_layer_mapping();
      break;
    default:
      die(json_encode(set_error_arr("Invalid request.")));
      break;
  }

  function get_all_themes_list() {
    $query = 'select theme_id, theme_name, theme_type from "Theme" order by theme_name';
    $result = db_query($query);
    $response = array();
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $themes = array();
      //require_once "Class_Theme.php";
      while ($obj = db_fetch_object($result)) {
        //$themes[] = new Theme($obj);
        $themes[] = array('id' => $obj->theme_id, 'name' => $obj->theme_name, 'type' => $obj->theme_type);
      }
      $response = set_no_error_arr("");
      $response['themes'] = $themes;
    }
    die(json_encode($response));
  }

  function get_all_themes() {
    $query = <<<EOT
    select theme_id, theme_name, theme_description, status, theme_type, parent_id,
      icon, nid, images, videos, astext(geolocation) as geolocation, country_id,
      created_by, created_date, modified_by, modified_date from "Theme"
EOT;
    $result = db_query($query);
    $response = array();
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $themes = array();
      require_once "Class_Theme.php";
      while ($obj = db_fetch_object($result)) {
        $themes[] = new Theme($obj);
      }
      $response = set_no_error_arr("");
      $response['themes'] = $themes;
    }
    die(json_encode($response));
  }

  function get_theme_info() {
    $data = $GLOBALS['data'];
    $params = $data['params'];
    $theme_id = $params['id'];

    if (empty($theme_id) || $theme_id == null) {
      die(json_encode(set_error_arr("Required parameters missing.")));
    }

    $query = <<<EOT
    select theme_id, theme_name, theme_description, status, theme_type, parent_id,
      icon, nid, images, videos, astext(geolocation) as geolocation, country_id,
      created_by, created_date, modified_by, modified_date from "Theme"
    where theme_id = %d
EOT;
    $query_args = array($theme_id);
    $result = db_query($query, $query_args);
    $response = array();
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $theme = array();
      require_once "Class_Theme.php";
      if ($obj = db_fetch_object($result)) {
        $theme = new Theme($obj);
      } else {
        die(json_encode(set_error_arr("No record found.")));
      }
      $response = set_no_error_arr("");
      $response['theme'] = $theme;
    }
    die(json_encode($response));
  }

  function save_theme_info() {
    $data = $GLOBALS['data'];
    $params = $data['params'];
    $theme_info = $params['theme_info'];

    $theme_id = $theme_info['id'];

    $user = $GLOBALS['user'];
    if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user->roles)) {
      die(json_encode(set_error_arr("You are not authorized.")));
    }

    // If theme_id == 0, it is an insert else update
    if ($theme_id == 0) {
      $query = <<<EOT
        insert into "Theme" (
          theme_name, theme_description, status, theme_type, icon, geolocation,
          country_id, images, videos, parent_id, created_by, created_date,
          modified_by, modified_date
        ) values (
EOT;
      $query .= prep_val_for_query($theme_info['name']) . ',';
      $query .= prep_val_for_query($theme_info['description']) . ',';
      $query .= $theme_info['status'] . ',';
      $query .= $theme_info['type'] . ',';
      $query .= prep_val_for_query($theme_info['icon']) . ',';
      $query .= prep_val_for_query($theme_info['geolocation']) . ',';
      $query .= prep_val_for_query($theme_info['country_id']) . ',';
      $query .= prep_val_for_query($theme_info['images']) . ',';
      $query .= prep_val_for_query($theme_info['videos']) . ',';
      $query .= ($theme_info['parent_id'] == 0) ? 'currval(\'"Theme_theme_id_seq"\'),' : $theme_info['parent_id'] . ',';
      $query .= $user->uid . ',';
      $query .= 'now(),';
      $query .= $user->uid . ',';
      $query .= 'now())';

// Becomes difficult to enter null values, so using old ways.
/*
          '%s', '%s', %d, %d, '%s', '%s',
          '%s', '%s', '%s', %d, now(), %d,
          now()
        )
EOT;
      $query_args = array(
        prep_val_for_query($theme_info['name']),
        prep_val_for_query($theme_info['description']),
        $theme_info['status'],
        $theme_info['type'],
        prep_val_for_query($theme_info['icon']),
        prep_val_for_query($theme_info['geolocation']),
        prep_val_for_query($theme_info['country_id']),
        prep_val_for_query($theme_info['images']),
        prep_val_for_query($theme_info['videos']),
        $user-uid,
        $user-uid
      );

      $result = db_query($query, $query_args);
*/
      $result = db_query($query);
      if (!$result) {
        die(json_encode(set_error_arr("Error talking to database.")));
      } else {
        die(json_encode(set_no_error_arr("New theme has been created.")));
      }
    } else {
      $query = 'Update "Theme" set ';
      $query .= 'theme_name = ' . prep_val_for_query($theme_info['name']) . ', ';
      $query .= 'theme_description = ' . prep_val_for_query($theme_info['description']) . ', ';
      $query .= 'status = ' . $theme_info['status'] . ', ';
      $query .= 'theme_type = ' . $theme_info['type'] . ', ';
      $query .= 'icon = ' . prep_val_for_query($theme_info['icon']) . ', ';
      $query .= 'geolocation = ' . prep_val_for_query($theme_info['geolocation']) . ', ';
      $query .= 'country_id = ' . prep_val_for_query($theme_info['country_id']) . ', ';
      $query .= 'images = ' . prep_val_for_query($theme_info['images']) . ', ';
      $query .= 'videos = ' . prep_val_for_query($theme_info['videos']) . ', ';
      $query .= 'parent_id = ' . $theme_info['parent_id'] . ', ';
      $query .= ' modified_by = ' . $user->uid . ', ';
      $query .= 'modified_date = now() ';
      $query .= 'where theme_id = ' . $theme_id;

      $result = db_query($query);
      if (!$result) {
        die(json_encode(set_error_arr("Error talking to database.")));
      } else {
        die(json_encode(set_no_error_arr("Theme has been updated.")));
      }
    }
  }

  function delete_theme() {
    $data = $GLOBALS['data'];
    $params = $data['params'];

    $theme_id = $params['id'];

    $user = $GLOBALS['user'];
    if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user->roles)) {
      die(json_encode(set_error_arr("You are not authorized.")));
    }

    if (empty($theme_id) || $theme_id == null) {
      die(json_encode(set_error_arr("Required parameters missing.")));
    }

    $query = 'delete from "Theme" where theme_id = %d';
    $query_args = array($theme_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      die(json_encode(set_no_error_arr("Theme has been deleted.")));
    }
  }

  function get_all_categories_list() {
    $query = 'select category_id, category_name, parent_id from "Categories_Structure" order by category_name';
    $result = db_query($query);
    $response = array();
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $categories = array();
      while ($obj = db_fetch_object($result)) {
        $categories[] = array('id' => $obj->category_id, 'name' => $obj->category_name, 'parent_id' => $obj->parent_id);
      }
      $response = set_no_error_arr("");
      $response['categories'] = $categories;
    }
    die(json_encode($response));
  }

  function get_category_info() {
    $data = $GLOBALS['data'];
    $params = $data['params'];
    $category_id = $params['id'];

    if (empty($category_id) || $category_id == null) {
      die(json_encode(set_error_arr("Required parameters missing.")));
    }

    $response = array();

    $query = 'select category_name, parent_id, nid from "Categories_Structure" where category_id = %d';
    $query_args = array($category_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $category = array();
      require_once "Class_Theme.php";
      if ($obj = db_fetch_object($result)) {
        //$category = new Theme($obj);
        $category['name'] = $obj->category_name;
        $category['parent_id'] = $obj->parent_id;
        $category['nid'] = $obj->nid;
      } else {
        die(json_encode(set_error_arr("No record found.")));
      }
      $response = set_no_error_arr("");
      $response['category'] = $category;
    }
    die(json_encode($response));
  }

  function save_category_info() {
    $data = $GLOBALS['data'];
    $params = $data['params'];
    $category_info = $params['category_info'];

    $category_id = $category_info['id'];

    $user = $GLOBALS['user'];
    if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user->roles)) {
      die(json_encode(set_error_arr("You are not authorized.")));
    }

    // If category_id == 0, it is an insert else update
    if ($category_id == 0) {
      $query = <<<EOT
        insert into "Categories_Structure" (
          category_name, parent_id
        ) values (
EOT;
      $query .= prep_val_for_query($category_info['name']) . ',';
      $query .= ($category_info['parent_id'] == 0) ? 'currval(\'"Categories_Structure_category_id_seq"\')' : $category_info['parent_id'];
      $query .= ')';

      $result = db_query($query);
      if (!$result) {
        die(json_encode(set_error_arr("Error talking to database.")));
      } else {
        die(json_encode(set_no_error_arr("New category has been created.")));
      }
    } else {
      $query = 'Update "Categories_Structure" set ';
      $query .= 'category_name = ' . prep_val_for_query($category_info['name']) . ', ';
      $query .= 'parent_id = ' . prep_val_for_query($category_info['parent_id']) . ' ';
      $query .= 'where category_id = ' . $category_id;

      $result = db_query($query);
      if (!$result) {
        die(json_encode(set_error_arr("Error talking to database.")));
      } else {
        die(json_encode(set_no_error_arr("Category has been updated.")));
      }
    }
  }

  function delete_category() {
    $data = $GLOBALS['data'];
    $params = $data['params'];

    $category_id = $params['id'];

    $user = $GLOBALS['user'];
    if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user->roles)) {
      die(json_encode(set_error_arr("You are not authorized.")));
    }

    if (empty($category_id) || $category_id == null) {
      die(json_encode(set_error_arr("Required parameters missing.")));
    }

    // Send error if the category has child categories.
    $query = 'select count(*) as count from "Categories_Structure" where category_id != parent_id and parent_id = %d';
    $query_args = array($category_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      if ($obj = db_fetch_object($result)) {
        if ($obj->count > 0) {
          die(json_encode(set_error_arr("The category has child categories. You need to delete them first.")));
        }
      }
    }

    $query = 'delete from "Categories_Structure" where category_id = %d';
    $query_args = array($category_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      die(json_encode(set_no_error_arr("Category has been deleted.")));
    }
  }

  function get_all_layers_list() {
    $response = array();
    $query = 'select layer_id, layer_tablename, layer_name from "Meta_Layer" order by layer_name';
    $result = db_query($query);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $layers = array();
      while ($obj = db_fetch_object($result)) {
        $layers[] = array('id' => $obj->layer_id, 'name' => $obj->layer_name);
      }
      $response = set_no_error_arr("");
      $response['layers'] = $layers;
    }
    die(json_encode($response));
  }

  function get_theme_layer_mapping() {
    $response = array();
    $data = $GLOBALS['data'];
    if (!isset($data['params'])) {
      die(json_encode(set_error_arr("Required parameters are not set.")));
    }
    $params = $data['params'];

    if (!isset($params['id']) || empty($params['id']) ) {
      die(json_encode(set_error_arr("Required parameters are not set.")));
    }
    $layer_id = $params['id'];

    // Check if the id passed is layer_tablename and not layer_id
    if ((string)(int)$layer_id !== (string)$layer_id) {
      $query = 'select layer_id from "Meta_Layer" where layer_tablename = \'%s\'';
      $query_args = array($layer_id);
      $result = db_query($query, $query_args);
      if (!$result) {
        die(json_encode(set_error_arr("Error talking to database.")));
      } else {
        if ($obj = db_fetch_object($result)) {
          $layer_id = $obj->layer_id;
        }
      }
    }

    $query = <<<EOT
    select tlm.id, tlm.theme_id, t.theme_type, tlm.category_id
    from "Theme" t, "Theme_Layer_Mapping" tlm
    where t.theme_id = tlm.theme_id and tlm.layer_id = %d
EOT;
    $query_args = array($layer_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    } else {
      $mapping = array();
      while ($obj = db_fetch_object($result)) {
        if ($obj->theme_type == 1) {
          $mapping['thematic'] = array('id' => $obj->id, 'theme_id' => $obj->theme_id, 'category_id' => $obj->category_id);
        } else {
          $mapping['geographical'] = array('id' => $obj->id, 'theme_id' => $obj->theme_id, 'category_id' => $obj->category_id);
        }
      }
      $response['mapping'] = $mapping;
    }
    die(json_encode($response));
  }

  function save_theme_layer_mapping() {
    $data = $GLOBALS['data'];
    $params = $data['params'];

    $user = $GLOBALS['user'];
    if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user->roles)) {
      die(json_encode(set_error_arr("You are not authorized.")));
    }

    $mapping = $params['mapping'];

    $layer_id = $mapping['layer_id'];

    $thematicMapping = $mapping['thematic'];
    $geographicalMapping = $mapping['geographical'];

    $thematicID = $thematicMapping['id'];
    $thematicThemeID = $thematicMapping['theme_id'];
    $thematicCategoryID = $thematicMapping['category_id'];

    $geographicalID = $geographicalMapping['id'];
    $geographicalThemeID = $geographicalMapping['theme_id'];
    $geographicalCategoryID = $geographicalMapping['category_id'];

    $result = _save_theme_layer_mapping($layer_id, $thematicID, $thematicThemeID, $thematicCategoryID);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    }

    $result = _save_theme_layer_mapping($layer_id, $geographicalID, $geographicalThemeID, $geographicalCategoryID);
    if (!$result) {
      die(json_encode(set_error_arr("Error talking to database.")));
    }

    die(json_encode(set_no_error_arr("Mapping saved.")));
  }

  function _save_theme_layer_mapping($layer_id, $mapping_id, $theme_id, $category_id) {
    $user = $GLOBALS['user'];
    if($user->uid == 0 || !in_array(SITE_ADMIN_ROLE, $user->roles)) {
      die(json_encode(set_error_arr("You are not authorized.")));
    }

    if ($mapping_id == 0) { // May be no action or insert
      if ($theme_id == 0 && $category_id == 0) { // No action
        return true;
      } else { // insert
        $query = 'insert into "Theme_Layer_Mapping" (layer_id, theme_id, category_id, created_by, created_date, modified_by, modified_date) values (%d, %s, %s, %d, now(), %d, now())';
        $query_args = array($layer_id, ($theme_id == 0 ? 'NULL' : $theme_id), ($category_id == 0 ? 'NULL' : $category_id), $user->uid, $user->uid);
        $result = db_query($query, $query_args);

        if (!$result) {
          return false;
        } else {
          return true;
        }
      }
    } else { // Update
      $query = 'update "Theme_Layer_Mapping" set layer_id = %d, theme_id = %s, category_id = %s, modified_by = %d, modified_date = now() where id = %d';
      $query_args = array($layer_id, ($theme_id == 0 ? 'NULL' : $theme_id), ($category_id == 0 ? 'NULL' : $category_id), $user->uid, $mapping_id);
      $result = db_query($query, $query_args);
      if (!$result) {
        return false;
      } else {
        return true;
      }
    }
    return true;
  }

  function set_error_arr($msg) {
    return array("error" => "1", "msg" => $msg);
  }

  function set_no_error_arr($msg) {
    return array("error" => "0", "msg" => $msg);
  }

  function prep_val_for_query($val) {
    if ($val == NULL) {
      return 'null';
    } else {
      return "'$val'";
    }
  }
?>
