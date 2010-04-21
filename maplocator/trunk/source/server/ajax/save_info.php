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
This file includes functionality to save data in DB
**/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require_once("functions.php");

function runtimeSave($tablename, $table_type, $topology = NULL) {
  $tbl_cols = array();

  $query = GetQueryColumnDetails($tablename, NULL, FALSE);
  $result_cols = db_query($query[0], $query[1]);
  if(!$result_cols) {
  } else {
    while($obj = db_fetch_object($result_cols)) {
      $tbl_cols[$obj->column_name]['col_type'] = $obj->column_type;
      $tbl_cols[$obj->column_name]['col_null'] = $obj->column_null;
      $tbl_cols[$obj->column_name]['col_desc'] = $obj->column_description;
    }
  }
  $tbl_cols_names = array_keys($tbl_cols);

  global $user;
  $fields = array();
  foreach($_REQUEST as $key => $value) {
    if(substr($key, 0, 5) == 'edit-') {
      $value_encoded = htmlentities(str_replace("'", "''", $value));
      $col = substr($key, 5);
      if(in_array($col, $tbl_cols_names)) {
        $col_type = $tbl_cols[$col]['col_type'];
        switch($col_type) {
          case 'smallint':
          case 'int':
          case 'integer':
          case 'bigint':
          case 'serial':
          case 'bigserial':
            $val = $value_encoded;
            if($value_encoded == '') {
              if($tbl_cols[$col]['col_null'] == 'NOT NULL') {
                die($tbl_cols[$obj->column_name]['col_desc'] . " cannot be empty.");
              }
              $val = 'NULL';
            }
            $fields[$col] = $val;
            break;
          default:
            $val = $value_encoded;
            if($value_encoded == '') {
              if($tbl_cols[$col]['col_null'] == 'NOT NULL') {
                die($tbl_cols[$obj->column_name]['col_desc'] . " cannot be empty.");
              }
              $val = 'NULL';
               $fields[$col] = $val;
            }else{
               $fields[$col] = "'".$val."'";
            }
            break;

        }
      } else {
        $fields[$col] = "'".$value_encoded."'";
      }
    }
  }

  if($fields['__id'] == "''") {
    unset($fields['__id']);
  }

  /* hardcoding for India Birds */
  if($table_type == TABLE_TYPE_LINK && ereg("lnk_[0-9]+_india_birdsightings", $tablename)) {
    $query = "select mlocate_id from birdspecies_list where c_name = '%s'";
    $result = db_query($query, str_replace("'", "", $fields['name']));
    if(!$result) {
    } else {
      $obj = db_fetch_object($result);
      if($obj->mlocate_id) {
        $fields['mlocate_id'] = $obj->mlocate_id;
      }
    }
  }

  $passed_fields = $fields;
  $fields[AUTO_DBCOL_PREFIX.'modified_by'] = $user->uid;
  $fields[AUTO_DBCOL_PREFIX.'modified_date'] = 'now()';

  $op = "";
  if(!isset($fields['__id'])) {
    $fields[AUTO_DBCOL_PREFIX.'created_by'] = $user->uid;
    $fields[AUTO_DBCOL_PREFIX.'created_date'] = 'now()';
    if($topology != NULL) {
      //check if the topology type of the table is multipoint or multipolygon
      $geomtypequery = "select type,srid from geometry_columns where f_table_name = '%s'";
      $geomtyperesult =  db_query($geomtypequery, $tablename);
      while ($row= db_fetch_object($geomtyperesult)) {
         $geomtype = $row->type;
         $srid = $row->srid;
      }
      if(strpos($geomtype,'MULTI') === false){
          //return false if the word is not found
          //here if not multipoint or multipolygon
          $fields[AUTO_DBCOL_PREFIX.'topology'] = "geomfromText('{$topology}',".$srid.")";
      }
      else{
         //type is multipoint or multipolygon
         $topology_edited = editGeomType($topology);
         $fields[AUTO_DBCOL_PREFIX.'topology'] = "geomfromText('{$topology_edited}',".$srid.")";
      }
    }
    //if India Bird Layer then get assign the site_id while adding the point
    if(ereg("lyr_[0-9]+_india_birdlocations", $tablename)){
        $query1 = 'select max(site_id) as site_id from "%s"' ;
        $query_linktb = 'select link_tablename from "Meta_LinkTable" where layer_id = (select layer_id from "Meta_Layer" where layer_tablename = \'%s\')';

        $result = db_query($query1, $tablename);
		if(!$result) {
			//Error occured
		   die('Error fetching max value');
		} else {
			while($obj = db_fetch_object($result))
			{
			   $lyr_max = $obj->site_id;
			}
		}
		$result = db_query($query_linktb, $tablename);
		if(!$result) {
			//Error occured
		   die('Error fetching link tablename');
		} else {
			while($obj = db_fetch_object($result))
			{
			   $lnk_tbname = $obj->link_tablename;
			}
		}

    $query2 = 'select max(site_id) as site_id from "%s"';
    $result = db_query($query2, $lnk_tbname);
		if(!$result) {
			//Error occured
		   die('Error fetching max value');
		} else {
			while($obj = db_fetch_object($result))
			{
			   $lnk_max = $obj->site_id;
			}
		}
		if($lyr_max > $lnk_max)
		  $site_id = $lyr_max + 1;
		else
      $site_id = $lnk_max + 1;
      $query = 'insert into "%s" (%s, site_id) values ('.implode(",",array_values($fields)).' ,%d)';
      $query_args = array($tablename, implode(",",array_keys($fields)), $site_id);
    } else {
      $query = 'insert into "%s" (%s) values ('.implode(",",array_values($fields)).')';
      $query_args = array($tablename, implode(",",array_keys($fields)));
    }
    $op = "insert";
  } else {
    $id = $fields['__id'];
    unset($fields['__id']);
    $set_val = "";
    foreach($fields as $key => $value) {
      if($key == "layer_id" ) {
        $key = AUTO_DBCOL_PREFIX.$key;
      }
      $set_val .= " $key = $value,";
    }
    $set_val = substr($set_val, 0, -1);

    $query = 'update "%s" set '.$set_val.' where '.AUTO_DBCOL_PREFIX.'id = %d';

    $query_args = array($tablename, $id);
    $op = "update";
  }

  $result = db_query($query, $query_args);

  if(!$result) {
    echo "Error saving info.";
  } else {
    notify_layer_admin($tablename, $table_type, $passed_fields);
    echo "Record saved.";
    if($table_type == TABLE_TYPE_LAYER && $op == 'insert') {
      setcookie('featureAddedTo', $tablename);
    }
  }
}

function editGeomType($topology_to_edit){
	$topology_edited = "MULTI".$topology_to_edit;
	$pos = strpos($topology_edited,"(");
	$sub1 = substr($topology_edited,0,$pos+1);
	$sub2 = substr($topology_edited,$pos+1,strlen($topology_edited));
	$topology_edited = $sub1.'('.$sub2.')';
	return $topology_edited;
}

function notify_layer_admin($tablename, $table_type, $fields) {
  // If the table type is neither LAYER nor LINK, we do not support it. So return.
  if($table_type != TABLE_TYPE_LAYER && $table_type != TABLE_TYPE_LINK) {
    return FALSE;
  }
  global $user;
  $to = "";

  $op = "Add";
  if(isset($fields[AUTO_DBCOL_PREFIX.'id']) && $fields[AUTO_DBCOL_PREFIX.'id'] != "''") {
    $op = "Update";
  }

  $name = "";
  // If the table type is link, the layer tablename set above will be updated to the correct one.
  if($table_type == TABLE_TYPE_LINK) {
    $query = "select link_name from \"Meta_LinkTable\" where link_tablename = '%s'";
    $result = db_query($query, $tablename);
    if(!$result) {
      return FALSE;
    } else {
      $obj = db_fetch_object($result);
      $name = '"' . $obj->link_name . '" of ';
    }

    $query = "select layer_name, layer_tablename from \"Meta_Layer\" where layer_id = (select layer_id from \"Meta_LinkTable\" where link_tablename = '%s')";
  } else {
    $query = "select layer_name, layer_tablename from \"Meta_Layer\" where layer_tablename = '%s'";
  }

  $result = db_query($query, $tablename);
  if(!$result) {
    return FALSE;
  } else {
    $obj = db_fetch_object($result);
    if($obj->layer_tablename == "") {
      return FALSE;
    }

    $layer_tablename = $obj->layer_tablename;
    $layer_name = $obj->layer_name;
  }

  $name .= '"' . $layer_name . '"';
  $subject = "{$table_type} {$op}: {$name}";

  // Get the layer admins email IDs.
  $query = "select mail from users where uid in (select uid from users_roles where rid in (select rid from role where name = '%s admin' OR name = '%s validator' OR name = '".SITE_ADMIN_ROLE."'));";
  $result = db_query($query, $layer_tablename);
  if(!$result) {
    return FALSE;
  } else {
    $admin_ids = array();
    while($obj = db_fetch_object($result)) {
      $admin_ids[] = $obj->mail;
    }

    if(sizeof($admin_ids) == 0) {
      return FALSE;
    } else {
      $to = implode(",", $admin_ids);
      if(!in_array($user->mail,$admin_ids)){
         //user mail id not present in the list already
         $to.= ",".$user->mail;
      }
    }
  }

  if($to == "") {
    return FALSE;
  } else {

    $body = $user->name . "(" . $user->mail . ") has added following details: \r\n\r\n";

    foreach($fields as $key => $value) {
      $body .= '"' . $key . '": ' . $value . "\r\n";
    }

    $default_from = variable_get('site_mail', ini_get('sendmail_from'));

    // Additional headers
    $headers['To'] = $to;
    $headers['From'] = 'update@indiabiodiversity.org';

    // Bundle up the variables into a structured array for altering.
    $message = array(
      'to'       => $to,
      'from'     => isset($from) ? $from : $default_from,
      'subject'  => $subject,
      'body'     => $body,
      'headers'  => $headers
    );

    return drupal_mail_send($message);
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
?>
