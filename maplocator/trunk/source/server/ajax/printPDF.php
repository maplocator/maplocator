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

/*
This file includes functionlity to print map as pdf
*/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

//function to remove extra quotes in json
function _json_decode($string) {
     if (get_magic_quotes_gpc()) {
         $string = stripslashes($string);
     }

     return json_decode($string);
}
//function to convert imgae from png to jpg format
function png2jpg($originalFile, $outputFile, $quality) {
    $image = imagecreatefrompng($originalFile);
    imagejpeg($image, $outputFile, $quality);
    imagedestroy($image);
}
//This function creats a pdf on the fly and returns the url/file name
function printFPDF($pdfobj){

	require('fpdf/fpdf.php');

	$DIRPATH = $_SERVER['SCRIPT_FILENAME'];

	// remove the file name and append the new directory name
	if (PHP_OS == "WINNT" || PHP_OS == "WIN32") {
		$pos1 = strrpos($DIRPATH,'\\');
		$DIRPATH= substr($DIRPATH,0,$pos1)."\\pdf\\";
	}else{
		$pos1 = strrpos($DIRPATH,'/');
		$DIRPATH= substr($DIRPATH,0,$pos1)."/pdf/";
	}
	$pdf_FILE_NAME = session_id(). "MapLocator.pdf";
	$pdf_FILE_PATH = $DIRPATH. $pdf_FILE_NAME ;

	$pdf = new FPDF('P', 'pt', array(640,450));
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',10);

	$domain = getenv("HTTP_HOST");

	//base map
	$base_url = $pdfobj->BaseUrl;
	$base_url = str_replace(' and ','&' ,$base_url);

	if (strpos($base_url,'http://') === false) {
		//since client sends only relative path a full http url needs to constructed here
		$base_url = 'http://'. $domain. $base_url;
		//echo 'here';
	}


	$base_image = createImageForPDF($base_url,$DIRPATH.'base');
	$base_image_jpeg = $DIRPATH.'base'.session_id() .'.jpeg';
	png2jpg($base_image, $base_image_jpeg, 100);

	$layerURL = $pdfobj->LayerURL;

	$layerURL = str_replace(' and ','&' ,$layerURL );

	//since client sends only relative path a full http url needs to constructed here
	$layerURL = 'http://'. $domain. $layerURL;
	$layer = $pdfobj->Layer;
	$legendURL = $pdfobj->LegendURL;
	$legendURL = 'http://'. $domain. $legendURL;
	$legendURL = str_replace(' and ','&' ,$legendURL );

	$image = createImageForPDF($layerURL,$DIRPATH.$layer);

	$image_jpeg = $DIRPATH.$layer.session_id() .'.jpeg';
	png2jpg($image, $image_jpeg, 100);

	$url = 'http://'. $domain. base_path().'ImageMerge.php?baseimage='.$base_image_jpeg.'&overlayimage='.			$image_jpeg;

	$merged_image_buff = file_get_contents($url);

	$final_image =$DIRPATH.'merge.jpeg';

	$fh = fopen($final_image,"w");
  	fwrite($fh,$merged_image_buff);
	fclose($fh);


	$pdf->Image($final_image,0,30);

	$image = createImageForPDF($legendURL,$DIRPATH.$layer.'_legend');
	$image_jpeg = $DIRPATH.$layer.'_legend'.session_id() .'.jpeg';
	png2jpg($image, $image_jpeg, 100);
	$size = getimagesize($image);
	$width = $size[0];
	$height = $size[1];



	$pdf->sety(25);
	$pdf->Cell(0,0,"Layer Name: ". $pdfobj->LayerName);


	$top_scale = $pdfobj->TopScale;
	$bottom_scale = $pdfobj->BottomScale;


	$pdf->Text(0,430,"Scalebar: ");
	$pdf->Text(50,430,"| ".$top_scale." |");
	$pdf->Text(50,430,"  _____ ");
	$pdf->Text(50,440,"| ".$bottom_scale." |");
	$pdf->Text(475,445,"Created by Map Locator");


	if($width < 100){//show legend on the map/same page
		$pdf->Image($image_jpeg,640 - $width,30);
		$pdf->sety(20);
		$pdf->setX(-75);
		$pdf->Cell(20,10,"Legend ");
	}else{//show legend on the next page
		$pdf->AddPage();
		$pdf->Image($image_jpeg,0,30);
		$pdf->sety(20);
		$pdf->setX(5);
		$pdf->Cell(20,10,"Legend ");
	}



	$pdf->Output($pdf_FILE_PATH,'F');
	$pdf->Close();
	return $pdf_FILE_NAME;


}



function createImageForPDF($msURL,$name){

	$contents = file_get_contents($msURL);

	$tempImg = $name.session_id() .'.png';

    $fh = fopen($tempImg,"w");
  	fwrite($fh,$contents);
	fclose($fh);
	return $tempImg;
}

?>
