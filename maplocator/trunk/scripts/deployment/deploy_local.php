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

#machine specific deployment changes. 
$source_path = "C:/Documents and Settings/harshad/Desktop/maplocator/source"; #Make Changes Here. Set source path. Dont add trailing /
$deploy_path = "C:/Documents and Settings/harshad/Desktop/maplocator/deploy/maplocator"; #Make Changes Here. Set deploy path. Dont add trailing /
$backup_path = "C:/Documents and Settings/harshad/Desktop/maplocator/backup"; #Make Changes Here. Set backup path. Dont add trailing /

#modules that should be moved to deploy folder
$modules['external'] = array("captcha","fckeditor","image","nice_menus","google_analytics");
$modules['custom'] = array("map","node_mlocate_feature","node_mlocate_layerinfo","node_mlocate_participation","node_mlocate_themeinfo");

#path relative to the source root
$relative_paths = array ();
$relative_paths['drupal'] = "trunk/lib/common/drupal-6.14"; #copy contents to root

$relative_paths['drupal_modules'] = "trunk/lib/common/drupal_modules"; # copy this to sites/all/modules
$relative_paths['custom_modules'] = "trunk/source/drupal_custom/modules"; # copy this to sites/all/modules
$relative_paths['custom_theme'] = "trunk/source/drupal_custom/theme"; # copy this to sites/all/themes by the deployment name

$relative_paths['config_path'] = "trunk/config"; # copy this to root
$relative_paths['custom_ajax'] = "trunk/source/server/ajax"; # goes to root
$relative_paths['custom_flash'] = "trunk/source/server/flash"; # goes to root
$relative_paths['custom_standalone_pages'] = "trunk/source/server/standalone_pages"; # root
$relative_paths['thirdparty_javascript_code'] = "trunk/lib/common/javascript"; # root
$relative_paths['treeview'] = "trunk/lib/common/treeview"; #root

#copy directory
function copy_contents($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                copy_contents($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function copy_directory1( $source, $destination ) {
  if ( is_dir( $source ) ) {
    //@mkdir( $destination );
    $directory = dir( $source );
    while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
      if ( $readdirectory == '.' || $readdirectory == '..' ) {
        continue;
      }
      $PathDir = $source . '/' . $readdirectory;
      if ( is_dir( $PathDir ) ) {
        copy_contents( $PathDir, $destination . '/' . $readdirectory );
        continue;
      }
      copy( $PathDir, $destination . '/' . $readdirectory );
    }

    $directory->close();
  }else {
    copy( $source, $destination );
  }
}
#delete directory
function delete_directory($dirname) {
  if (is_dir($dirname))
    $dir_handle = opendir($dirname);
  if (!$dir_handle)
    return false;
  while($file = readdir($dir_handle)) {
    if ($file != "." && $file != "..") {
      if (!is_dir($dirname."/".$file))
        unlink($dirname."/".$file);
      else
        delete_directory($dirname.'/'.$file);
    }
  }
  closedir($dir_handle);
  rmdir($dirname);
  return true;
}

$error_flag = 0;

#verify if path exist
echo "------------------------------\n";
echo "VERYFYING PATHS\n";
echo "------------------------------\n";

if (!is_dir( $source_path )) {
  echo "source_path directory Does Not Exist\n";
  $error_flag =1;
}
if (!is_dir( $deploy_path )) {
  echo "deploy_path directory Does Not Exist\n";
  $error_flag =1;
}
if (!is_dir( $backup_path )) {
  echo "backup_path directory Does Not Exist\n";
  $error_flag =1;
}

foreach( $relative_paths as $key => $value) {
  #echo "Name: $key, path: $value \n";
  if (!is_dir( $source_path.'/'.$value )) {
    echo "$key Directory Does Not Exist\n";
    $error_flag=1;
  } else {
    $relative_paths[$key] = $source_path.'/'.$value;
  }
}

if($error_flag==1) exit(1);


# taking backup
$current_date = date("Y-m-d-H-i-s-T");
echo "------------------------------\n";
echo "TAKING BACKUP: $backup_path/maplocator-$current_date\n";
echo "------------------------------\n";
mkdir($backup_path.'/maplocator-'.$current_date);
copy_contents($deploy_path,$backup_path.'/maplocator-'.$current_date);
delete_directory($deploy_path);
mkdir($deploy_path);

echo "------------------------------\n";
echo "DEPLOYING SYSTEM\n";
echo "------------------------------\n";
# copying drupal
copy_contents($relative_paths['drupal'],$deploy_path); #copy contents to root

#copying drupal modules
copy_contents($relative_paths['drupal_modules'],$deploy_path.'/sites/all/modules'); #copy contents to root

#copying custom modules
copy_contents($relative_paths['custom_modules'],$deploy_path.'/sites/all/modules'); #copy contents to root

#copying custom themes
mkdir($deploy_path.'/sites/all/themes');
copy_contents($relative_paths['custom_theme'],$deploy_path.'/sites/all/themes'); #copy contents to root

#copying config to root
copy_contents($relative_paths['config_path'],$deploy_path);

#copying custom ajax to root
copy_contents($relative_paths['custom_ajax'],$deploy_path);

#copying customm flash to root
copy_contents($relative_paths['custom_flash'],$deploy_path);

#copying custom standalone pages to toor
copy_contents($relative_paths['custom_standalone_pages'],$deploy_path);

#copying thirdparty javascript code to root
copy_contents($relative_paths['thirdparty_javascript_code'],$deploy_path);

#copying treeview plugin to root
copy_contents($relative_paths['treeview'],$deploy_path.'/treeview');

#creating additional directories
mkdir($deploy_path.'/shapefiles');
mkdir($deploy_path.'/upload');
mkdir($deploy_path.'/sites/default/files');

?>
