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
* This is a stand alone page contains functionality to parse a xml file
*
***/
class XMLParser {
  private $record = Array();
  private $curr = "";
  private $xml_tags;
  private $xml_parser;
  private $sql = "";
  private $file;
  private $layer_tablename;
  private $col_type;
  private $text_datatype = Array("character varying","varchar","text","character","char");
  private $uid;
  private $layer_type;
  /* Takes an array with the tag names (column names) and initializes record with the values */
  private function InitializeTags($record_fields,$col_type) {
     $this->xml_tags = $record_fields;
     $len = count($this->xml_tags);
     for($i=0;$i<$len;$i++){
       $this->record[strtoupper(str_replace("'","",$this->xml_tags[$i]))] = "";
       $this->col_type[str_replace("'","",$this->xml_tags[$i])] = $col_type[str_replace("'","",$this->xml_tags[$i])];
     }
     $this->record[strtoupper("COORDINATES")] = "";
  }

  /* Initializes xml parser with the input file */
  private function InitializeParser($file) {
    $this->file = $file;
    $this->xml_parser = xml_parser_create();
    xml_set_object($this->xml_parser, $this);
    xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, true);
    xml_set_element_handler($this->xml_parser, array(&$this, 'StartElement'), array(&$this, 'EndElement'));
    xml_set_character_data_handler($this->xml_parser, array(&$this, 'ContentHandler'));
    if (!($fp = fopen($file, "r"))) {
       die(return_error("Error opening XML file"));
    }

    $this->ParseData($fp);
  }

private function log() {
print_r($this);
}
  private function StartElement($parser, $name, $attrs) {
    if(!array_key_exists($name, $this->record)) {
	  return;
	}
	$this->curr = $name;
  }

  private function EndElement($parser, $name) {
    if ("PLACEMARK" === $name) {
	  $this->EndPlacemark();
	  return;
	}
	if ("" == $this->curr) {
	  return;
	}
	assert($this->curr == $name);
	$this->curr = "";
  }

  private function EmitSql($record) {
    foreach($record as $tag => $value){
       if ("" !== $value) {
         if ("COORDINATES" === $tag) {
       	   $columns .= "__mlocate__topology,";
       	   $values .= "geomfromtext('".$this->layer_type."(".str_replace(","," ",$value) .")',-1)," ;
       	   $values .= ",";
         } else {
           $columns .=  strtolower($tag).",";
           /* check the data type of the column and insert quotes accordingly */
           if(in_array($this->col_type[strtolower($tag)],$this->text_datatype)){
             $values .= "'".$value."',";
           } else {
             $values .= $value.",";
           }
         }
      }
    }
    $this->sql .= "insert into ".$this->layer_tablename." (".substr($columns,0,strlen($columns)-1).",__mlocate__status,__mlocate__created_by,__mlocate__created_date,__mlocate__modified_by,__mlocate__modified_date) values(".substr($values,0,strlen($values)-1) ."0,".$this->uid.",now(),".$this->uid.",now());";
  }

  private function EndPlacemark() {
    $this->EmitSql($this->record);
    // Initialize $record again
	$this->InitializeTags($this->xml_tags,$this->col_type);
  }

  /* Initialize tags and parser */
  public function Initialize($record_fields,$file,$layer_tablename,$col_type,$uid,$layer_type) {
    $this->uid = $uid;
    $this->layer_tablename = $layer_tablename;
    $this->layer_type = $layer_type;
    $this->InitializeTags($record_fields,$col_type);
    $this->InitializeParser($file);
  }

  public function ContentHandler($parser, $data) {
    if ("" === $this->curr) {
	   return;
	}
	$this->record[$this->curr] .= $data;
  }

  public function ParseData($fp) {
  	while ($data = fread($fp, filesize($this->file))) {
      if (!xml_parse($this->xml_parser, $data)) {
        die(sprintf("XML error: %s at line %d",xml_error_string(xml_get_error_code($this->xml_parser)),xml_get_current_line_number($this->xml_parser)));
      }
    }
    xml_parser_free($this->xml_parser);
  }

  public function GetSqlFile(){
     /* dump the sql statements in a file and return the file name */
    $sqlfile = "upload/".$this->layer_tablename.".sql";
    if (!($sqlfp = fopen($sqlfile, "w+"))) {
       die(return_error("Error opening SQL file"));
    }
    fwrite($sqlfp,$this->sql);
    fclose($sqlfp);
    return $sqlfile;
  }
}
?>
