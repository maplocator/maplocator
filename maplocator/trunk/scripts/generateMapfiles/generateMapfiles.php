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

//to generate colors randomly in mapfile
global $statehash;
$statehash = array();//color array
$colorstr = "";
$searchcolor ="";
$dbname = "";
$passwd = "";
$conntype = "postgis";
$host = "localhost";
$layer_passed = 0;
$layername = "";

if($argc < 5 || $argc > 9){
  die("Usage: php generateMap.php -u DBUser -d DBName -p password -l layer_tablename");
}

while(count($argv) > 0){
  $args = array_shift($argv);
  switch($args){
    case '-u':
      $user = array_shift($argv);
      break;
    case '-d':
      $dbname = array_shift($argv);
      break;
    case '-p':
      $passwd = array_shift($argv);
      break;
    case '-l':
      $layername = array_shift($argv);
      $layer_passed = 1;
      break;
  }
}

$dbconn = pg_pconnect("host=".$host." port=5432 dbname=".$dbname." user=".$user." password=".$passwd); //connectionstring
//$dbconn = pg_pconnect("host=".$host." port=5432 dbname=".$dbname." user=".$user); //connectionstring
if (!$dbconn) {
  die("Can't connect");
}

if("" == $layername) {
  $query = "select layer_tablename,color_by,layer_type from \"Meta_Layer\" where status = 1 AND (layer_type='POLYGON' OR layer_type='MULTIPOLYGON' OR layer_type='LINESTRING' OR layer_type='MULTILINESTRING')";
} else {
  $query = "select color_by,layer_type from \"Meta_Layer\" where layer_tablename='".$layername."'";
}
$result = pg_query($dbconn,$query);
if(!$result) {
  die("An error occured");
}

while($res = pg_fetch_assoc($result)){
  if(!$layer_passed) {
    $layername = $res['layer_tablename'];
  }
  $col = $res['color_by'];
  $layer_type = $res['layer_type'];
  if($col === "'desc'") {
     $col = str_replace("'","\"",$col);
  } else{
     $col = str_replace("'","",$col);
  }
    $mapfilename = $layername.".map";

  echo "\nGenerated map file:".$mapfilename;
  $newmapfile = fopen($mapfilename,"w");
	//read map file template from a file and write to new file.
	if(file_exists("header")){
	  $file_handle = fopen("header","r");
	  while (!feof($file_handle)) {
		$line = fgets($file_handle);
		 //write to new file
		//echo $line;
		fwrite($newmapfile,$line);
	  }
	  fclose($file_handle);
	}
	fwrite($newmapfile,"  layer");
	fwrite($newmapfile,"\n  name ".$layername);
	fwrite($newmapfile,"\n  connectiontype ".$conntype);
	fwrite($newmapfile,"\n  connection \"user= ".$user." dbname=".$dbname." password=".$passwd." host=".$host."\"\n");

        //check the layer type and decide the type of the layer based on that
        if($layer_type === "POLYGON" || $layer_type === "MULTIPOLYGON")
             fwrite($newmapfile,"  type polygon \n");
        else if($layer_type === "LINESTRING" || $layer_type === "MULTILINESTRING")
             fwrite($newmapfile,"  type line \n");
        else if($layer_type === "POINT" || $layer_type === "MULTIPOINT"){
             fwrite($newmapfile,"  type point \n");
             fwrite($newmapfile,"  tolerance 20 \n");
        }
	if(file_exists("footer")){
	  $file_handle = fopen("footer","r");
	  while (!feof($file_handle)) {
		$line1 = fgets($file_handle);
			 //write to new file
			fwrite($newmapfile,$line1);
	  }
	  fclose($file_handle);
	}
	$query1 = "select srid from geometry_columns where f_table_name= '".$layername."'";
	$srid_res = pg_query($dbconn,$query1);
  if(!$srid_res) {
    die("An error occured");
  }
  while($srid_obj = pg_fetch_object($srid_res)) {
    $srid = $srid_obj->srid;
  }
	$data = "  data \"__mlocate__topology from ".$layername." using unique __mlocate__id using SRID = ".$srid."\"\n";
	fwrite($newmapfile,$data);
	if(!($col === '' || $col == null)){
	  fwrite($newmapfile,"  classitem \"".$col."\"");
	}
	//call fn to get color array
	fetchColor($layername,$col,$dbconn);
	foreach($statehash as $key=>$value){
		fwrite($newmapfile,"\n\tclass\n");
		/* ----- if value is not default ------*/
		fwrite($newmapfile,"\t\tname ");
		fwrite($newmapfile,"\"".$key."\"");
		if($key !==''){
		  fwrite($newmapfile,"\n\t\texpression ");
		  fwrite($newmapfile,"\"".$key."\"");
    }
		fwrite($newmapfile,"\n\t\tstyle\n");
    if($layer_type === "POINT" || $layer_type === "MULTIPOINT"){
      fwrite($newmapfile,"\t\t\tsymbol 'custom_icon' \n");
      fwrite($newmapfile,"\t\t\tsize 30 \n");
    }
		fwrite($newmapfile,"\t\t\tcolor ");
		fwrite($newmapfile,$value);
		fwrite($newmapfile,"\t\t\toutlinecolor 000 000 000\n");
		//fwrite($newmapfile,"\t\t\tantialias true\n");
		fwrite($newmapfile,"\t\tend\n");
		fwrite($newmapfile,"\tend\n");

	}
	fwrite($newmapfile," end\n");//layer end


    /* -------------select layer ----------*/
	fwrite($newmapfile,"\n  layer");
	fwrite($newmapfile,"\n  name ".$layername."_select");
	fwrite($newmapfile,"\n  connectiontype ".$conntype);
	fwrite($newmapfile,"\n  connection \"user= ".$user." dbname=".$dbname." password=".$passwd." host=".$host."\"\n");

  //check the layer type and decide the type of the layer based on that
  if($layer_type === "POLYGON" || $layer_type === "MULTIPOLYGON")
       fwrite($newmapfile,"  type polygon \n");
  else if($layer_type === "LINESTRING" || $layer_type === "MULTILINESTRING")
       fwrite($newmapfile,"  type line \n");
  else if($layer_type === "POINT" || $layer_type === "MULTIPOINT")
       fwrite($newmapfile,"  type point \n");

 if(file_exists("selectlayer")){
   $file_handle = fopen("selectlayer","r");
	 while (!feof($file_handle)) {
	  	$line2 = fgets($file_handle);
			 //write to new file
			//$newmapfile = fopen($mapfilename,"w");
			fwrite($newmapfile,$line2);
   }
	 fclose($file_handle);
 }
 fwrite($newmapfile,"    outlinecolor 000 000 255\n");
 fwrite($newmapfile,"    end\n");
 fwrite($newmapfile,"  end\n");
 fwrite($newmapfile,"  filter '__mlocate__id = %pid%'\n");
 $data1 = "  data \"__mlocate__topology from ".$layername." using unique __mlocate__id \"\n";
 fwrite($newmapfile,$data1);
 fwrite($newmapfile,"  end\n");



    /* ------------- search layer ---------*/
	fwrite($newmapfile,"\n  layer");
	fwrite($newmapfile,"\n  name ".$layername."_search");
	fwrite($newmapfile,"\n  connectiontype ".$conntype);
	fwrite($newmapfile,"\n  connection \"user= ".$user." dbname=".$dbname." password=".$passwd." host=".$host."\"\n");

        //check the layer type and decide the type of the layer based on that
        if($layer_type === "POLYGON" || $layer_type === "MULTIPOLYGON")
             fwrite($newmapfile,"  type polygon \n");
        else if($layer_type === "LINESTRING" || $layer_type === "MULTILINESTRING")
             fwrite($newmapfile,"  type line \n");
        else if($layer_type === "POINT" || $layer_type === "MULTIPOINT")
             fwrite($newmapfile,"  type point \n");
		if(file_exists("wfs")){
		  $file_handle = fopen("wfs","r");
		  while (!feof($file_handle)) {
			$line1 = fgets($file_handle);
				 //write to new file
				fwrite($newmapfile,$line1);
		  }
		  fclose($file_handle);
		}
        if(file_exists("selectlayer")){
	   $file_handle = fopen("selectlayer","r");
	   while (!feof($file_handle)) {
		   $line2 = fgets($file_handle);
			 //write to new file
			//$newmapfile = fopen($mapfilename,"w");
			fwrite($newmapfile,$line2);
	   }
	   fclose($file_handle);
	}
	/* generate color for search polygons for a layer */
	for($i=0;$i<3;$i++){
	  $color = rand(150,255);
	  if($color > 255)
	 	 echo "color greater than 255";
	  else{
	  	$searchcolor .= $color." ";
	  }
	}
	$searchcolor = substr($searchcolor,0,-1);
	fwrite($newmapfile,"    color ");
	fwrite($newmapfile,$searchcolor."\n");
	fwrite($newmapfile,"    outlinecolor 000 000 000\n");
	fwrite($newmapfile,"   end\n");
	fwrite($newmapfile,"  end\n");
	fwrite($newmapfile,"  filter '__mlocate__id IN (%pid%)'\n");
    $data1 = "  data \"__mlocate__topology from ".$layername." using unique __mlocate__id\"\n";
    fwrite($newmapfile,$data1);
    fwrite($newmapfile,"  transparency 65\n");
    fwrite($newmapfile,"  end\n");


  /* -------------search_BB ----------*/
	fwrite($newmapfile,"\n  layer");
	fwrite($newmapfile,"\n  name ".$layername."_searchBB");
	fwrite($newmapfile,"\n  connectiontype ".$conntype);
	fwrite($newmapfile,"\n  connection \"user= ".$user." dbname=".$dbname." password=".$passwd." host=".$host."\"\n");

  //check the layer type and decide the type of the layer based on that
  if($layer_type === "POLYGON" || $layer_type === "MULTIPOLYGON")
       fwrite($newmapfile,"  type polygon \n");
  else if($layer_type === "LINESTRING" || $layer_type === "MULTILINESTRING")
       fwrite($newmapfile,"  type line \n");
  else if($layer_type === "POINT" || $layer_type === "MULTIPOINT")
       fwrite($newmapfile,"  type point \n");

 if(file_exists("selectlayer")){
   $file_handle = fopen("selectlayer","r");
	 while (!feof($file_handle)) {
	  	$line2 = fgets($file_handle);
			 //write to new file
			//$newmapfile = fopen($mapfilename,"w");
			fwrite($newmapfile,$line2);
   }
	 fclose($file_handle);
 }
 fwrite($newmapfile,"    outlinecolor 000 000 255\n");
 fwrite($newmapfile,"    width 3\n");
 fwrite($newmapfile,"    end\n");
 fwrite($newmapfile,"  end\n");
 fwrite($newmapfile,"  filter '__mlocate__id in (%pid%)'\n");
 $data1 = "  data \"__mlocate__topology from ".$layername." using unique __mlocate__id \"\n";
 fwrite($newmapfile,$data1);
 fwrite($newmapfile,"  end\n");

 fwrite($newmapfile,"END\n");//map end
	fclose($newmapfile);
	$statehash = array();
	$searchcolor = "";
}

function fetchColor($layername,$colorby,$dbconn){
     global $statehash;
     $colorstr = "";
     if(!($colorby === '' || $colorby == null)){
		 $query2 = "select {$colorby} from \"{$layername}\" order by {$colorby}";
		 $result = pg_query($dbconn,$query2);
		if(!$result){
		  echo "An error occured.\n";
		  exit;
		}
		while($row = pg_fetch_row($result)){
		  //for every unique value create a color.
		  //add to array if it doesn't exist and set value to colors
		  if(!array_key_exists($row[0],$statehash)){
			//calculate the color.
			for($i=0;$i<3;$i++){
			  $color = rand(128,255);
			  if($color > 255)
				echo "color greater than 255";
			  else{
				$colorstr .= $color." ";
			  }
			}
			$colorstr = substr($colorstr,0,-1);
			$colorstr.= "\n";
			$statehash[$row[0]] = $colorstr;
		  }
		  $colorstr = "";
		}
	}else{
		 //some default color for the entire polygon
		 for($i=0;$i<3;$i++){
			  $color = rand(128,255);
			  if($color > 255)
				echo "color greater than 255";
			  else{
				$colorstr .= $color." ";
			  }
			}
			$colorstr = substr($colorstr,0,-1);
			$colorstr.= "\n";
			$statehash[''] = $colorstr;
    }
}
?>
