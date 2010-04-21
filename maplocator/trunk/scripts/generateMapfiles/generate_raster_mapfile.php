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


if( $argc <9){
  die("Usage: php generate_raster_mapfile.php -t template file -f class_info file -l layer_tablename -i image file path \n");
}


$myfile=$argv[2];
$class_data_file=$argv[4];
$lyr_name=$argv[6];
$image_file_path=$argv[8];
$user=$argv[10];
$dbname=$argv[12];
$passwd=$argv[14];
$city=$argv[16];
$category=$argv[19];
$host="localhost";
$temp_array=split("/",$image_file_path);
$layer_tblname="lyr_".$lyr_name;
$layer_name="\t"."NAME ".'"'.$layer_tblname.'"'."\n";
$file=fopen($myfile, 'r');
$class_data=file($class_data_file);
//echo "-------------------------------------------------------------------------------";
$data=file($myfile);
array_splice($data,34,0,$layer_name);

$file_name=array_pop($temp_array);
$file="\t"."DATA ".'"'.$file_name.'"'."\n";
array_splice($data,45,0,$file);
//echo "-------------------------------------------------------------------------------";
$map_out_file = $layer_tblname.".map";

$fh=fopen($map_out_file,'w');

for($i=0;$i<count($data);$i++)
  {
    fwrite($fh,$data[$i]);
  }
fclose($fh);
for ($i=0;$i < count($class_data);$i++)
  {
    $arr=$class_data[$i];
    $temp_arr=split(" ",$arr);
    $fh=fopen($map_out_file,'a');
    fwrite($fh,"\t"."CLASS\n");
    fwrite($fh,"\t\t"."NAME".' "'.$temp_arr[0].'"'."\n");
    fwrite($fh,"\t\t"."EXPRESSION "."([pixel]=".$i.")"."\n");
    fwrite($fh,"\t\t"."COLOR ".$temp_arr[1]." ".$temp_arr[2]." ".$temp_arr[3]."\n");
    fwrite($fh,"\t"."END\n");
  }
fwrite($fh,"END\n");
fwrite($fh,"END\n");
$dbconn = pg_pconnect("host=".$host." port=5432 dbname=".$dbname." user=".$user." password=".$passwd);
if(!$dbconn) {
  die("Cant Connect");
}
else{
 
  $m="Meta_Layer";
  $a="access";
  $r="RASTER";
  $cat_str="Categories_Structure";
  $theme_map="Theme_Layer_Mapping";
  $query="insert into ".'"'.$m.'"'."(layer_name, layer_tablename, status, layer_type,".'"'.$a.'"'.", is_filterable,nid,participation_type, p_nid)VALUES("."'".$lyr_name."'".','."'".$layer_tblname."'".",1,"."'".$r."'".",0,0,0,0,0);";
  

  echo $query;
  $result = pg_query($dbconn,$query);
  if(!$result) {
    die("An error occured");
  }
  else 
    {
      echo $layer_tblname;
      $query0="select * from ".'"'.$m.'"'." where layer_tablename="."'".$layer_tblname."';";
      //echo $query0;
      $result0 = pg_query($dbconn,$query0);
      $res=pg_fetch_assoc($result0);
      $layer_id=$res['layer_id'];

      $cat_id=$out['category_id'];
      echo $layer_id;
      $query2="insert into ".'"'.$theme_map.'"'."(theme_id,layer_id,status,category_id) values (5,".$layer_id.",1,".$category.");";
      $result2=pg_query($dbconn,$query2);
      if(!$result1) {
	die("An error occured");
	}
      else {
	echo "Hello";
      }
  
    }
}
?>
