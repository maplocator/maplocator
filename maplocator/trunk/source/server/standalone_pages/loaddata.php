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
* This is a stand alone page contains functionality for upload / delete layer in the system through web UI
*
***/

  require_once 'functions.php';
  require_once './includes/bootstrap.inc';
  require_once 'functions.php';
  require_once 'XMLParser.php';
  ini_set('max_execution_time', 100000);
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

	class UploadData
	{
	  protected $themeName , $sourceFile , $layerName , $zipFile , $baseFolderPath , $htDocsPath , $errorMsg , $errorMsgArrayCount , $category , $logger;

	  public function __construct($themeName , $fileName , $category)
	  {
  		$filePath = "/home/atree/deploy/uploaddata.log";
		$this->logger = fopen($filePath, 'a');
		$str = "START UPLOAD DATA FOR ".$themeName." ".$category."\n";
		fwrite($this->logger , $str);
		$this->errorMsgArrayCount = 0;
		$this->themeName = $themeName;
		$this->sourceFile = $fileName;
		$this->category = $category;
		fwrite($this->logger , $this->sourceFile['uploadFile']['name']." is the file \n");

	  }

	  public function UploadFile()
	  {
		fwrite($this->logger , "START UPLOAD FILE\n");
		$filepath = "upload/zipfiles/";
		$fileName = $this->sourceFile['uploadFile']['name'];
		$destFile = $filepath.$fileName;
		if (move_uploaded_file($this->sourceFile['uploadFile']['tmp_name'] , $destFile))
		{
			fwrite($this->logger , "File is uploaded successfully\n");
		}
		else
		{
			fwrite($this->logger , "Problem occured during uploading file\n");
			$this->errorMsg[$this->errorMsgArrayCount] = "Some problem has occured while uploading a zip file";
			$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
		}
		fwrite($this->logger , "END UPLOAD FILE\n");
	  }

	  public function CheckZipFileContents($currentFilePath)
	  {

		fwrite($this->logger , "START CheckZipFileContents\n");
		$isUnziped = true;
		$currentFilePath = str_replace("\\" , "/" , $currentFilePath);
		$position = strrpos($currentFilePath,'/');
		$this->htDocsPath = substr($currentFilePath,0,$position);
		$this->htDocsPath = str_replace("htdocs/uap", "cgi-bin", $this->htDocsPath);
		$this->baseFolderPath = substr($currentFilePath,0,$position)."/upload/zipfiles/";
		$zipFilePath = $this->baseFolderPath.$this->sourceFile['uploadFile']['name'];
		$this->zipFile = $zipFilePath;
		$extns = array('shp','shx','dbf','prj','sbn','sbx','txt','shp.xml');
		$zip = zip_open($zipFilePath);
		$mandetoryFileCount = 0;
		if($zip)
		 {
			while($entry = zip_read($zip))
			{
			  $filename = zip_entry_name($entry);
			  $position = strrpos($filename ,'/');
			  if($position<=0)
			  {
				fwrite($this->logger , "Format of the zip file is not correct\n");
				$this->errorMsg[$this->errorMsgArrayCount] = "Format of the zip file is not correct";
				$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
				$isUnziped = false;
				break;
			  }
			  $extn = strchr($filename,'.');
			  $posimg = strpos($filename , "images");
			  $posvideo = strpos($filename , "video");
			  $file_extn =  substr($extn,1);
			  if (!in_array(substr($extn,1),$extns) and $file_extn != "" and !$posimg and !$posvideo)
			  {
				fwrite($this->logger , "Allowed File Extensions are :shp, shx, dbf, prj, sbn, sbx, txt, shp.xml.<br/>Files other than these extensions are invallid\n");
				$this->errorMsg[$this->errorMsgArrayCount] = "Allowed File Extensions are :shp, shx, dbf, prj, sbn, sbx, txt, shp.xml.<br/>Files other than these extensions are invallid";
				$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
				$isUnziped = false;
				break;
	          }
			 if('.shx' === $extn || '.dbf' === $extn)
			 {
				$mandetoryFileCount = $mandetoryFileCount + 1;
			 }
             if('.shp' === $extn)
			 {
				 $start = strpos($filename,'/');
				 $start = $start + 1;
				 $layer_name = substr($filename, $start, -4);
				  $this->layerName = $layer_name;
				  $mandetoryFileCount = $mandetoryFileCount + 1;
			  }
            }
			if($mandetoryFileCount<3 && $isUnziped)
			{
				fwrite($this->logger , ".shp , .shx and .dbf are the mandetory files.Please check if all of these files are there\n");
				$this->errorMsg[$this->errorMsgArrayCount] = ".shp , .shx and .dbf are the mandetory files.Please check if all of these files are there";
				$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
				$isUnziped = false;
			}
			if($isUnziped)
				$isUnziped = $this->Unzip();
		 }
		 else
		 {
			fwrite($this->logger , "Problem occured while opening a zip file\n");
			$this->errorMsg[$this->errorMsgArrayCount] = "Some problem has occured while opening uploaded a zip file";
			$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
			$isUnziped = false;
		 }
		 fwrite($this->logger , "END CheckZipFileContents\n");
		 return $isUnziped;
	  }


	  public function Unzip()
	  {
		$data_path = "upload/zipfiles/".$this->layerName;
		$isUnziped = true;
		fwrite($this->logger , "Start CheckZipFileContents\n");
		if(mkdir($data_path))
		{
			$zipextract_path = $data_path;
            $zip = new ZipArchive;
            $res = $zip->open($this->zipFile);
			$sourcePath = $data_path."/".$this->layerName;
			$dstPath = $data_path."/final";
			fwrite($this->logger , "Checkout path : ".$dstPath."\n");
			if ($res === TRUE)
			{
              $zip->extractTo($zipextract_path);
              $zip->close();
			  rename($sourcePath , $dstPath);
            }
			else
			{
				fwrite($this->logger , "Some problem has occured while unziping uploaded zip file\n");
				$this->errorMsg[$this->errorMsgArrayCount] = "Some problem has occured while unziping uploaded zip file";
				$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
				$isUnziped = false;
            }
		}
		return $isUnziped;
	}

		public function GetErrorMessage()
		{
			return $this->errorMsg;
		}

	  public function InitDataBaseRelatedParameters($db_url)
	  {
		fwrite($this->logger , "Start InitDataBaseRelatedParameters\n");
		$dbuser = preg_replace('/pgsql:\/\/([^:@][^:@]*).*/','$1',$db_url);
		$dbname = substr(strrchr($db_url,'/'),1);
		$scriptPath = $this->baseFolderPath."data_import.py";
		$import_cmd = "cd upload/zipfiles/ ; python data_import.py ".$dbname." ". $dbuser." ".$this->layerName." \"".$this->themeName."\""." \"".$this->category."\"";
		fwrite($this->logger , "Command : ".$import_cmd."\n");
		$this->ExceuteCommand($import_cmd , $db_url);
		fwrite($this->logger , "End InitDataBaseRelatedParameters\n");
	  }

	  public function ExceuteCommand($import_cmd , $db_url)
	  {
		 fwrite($this->logger , "Command is : ".$import_cmd."\n");
		 exec($import_cmd,$output,$status);
		 fwrite($this->logger , "Command is : ".$output."\n");
		  if (0 != $status)
		  {
		    fwrite($this->logger , "Error while executing a command\n");
			$this->HandleError();
		 }
		else
		{
			$count = count($output);
			$this->GenerateMapFiles($db_url , $output[$count-1]);
			$this->MoveImagesAndVideo($output[$count-1]);
		}
	}

	public function MoveImagesAndVideo($layerName)
	{
		fwrite($this->logger , "Move images and video\n");
	    $dstImgPath = "sites/default/files/images/".$layerName;
		fwrite($this->logger , $dstImgPath."\n");
		$imgDir =  'upload/zipfiles/'.$this->layerName.'/final/images';
		fwrite($this->logger , $imgDir."\n");
		$videoDir = 'upload/zipfiles/'.$this->layerName.'/final/video';
		fwrite($this->logger , $videoDir."\n");
		$videoPath = "sites/default/files/video/".$layerName;
		fwrite($this->logger , $videoPath ."\n");
		if(is_dir ($imgDir))
		{
			rename($imgDir,  $dstImgPath);
		}
		if(is_dir ($videoDir))
		{
			rename($videoDir ,  $videoPath);
		}

		if ($handle = opendir($videoPath))
		{
			fwrite($this->logger , "OPEN VIDEO DIR\n");
			while (false !== ($file = readdir($handle)))
			{
			  $jpgFile = "";
			  fwrite($this->logger , "INSIDE FILE HANDILG LOOP ****\n");
			  if(strpos($file , ".avi") || strpos($file , ".AVI") || strpos($file , ".mpg") || strpos($file , ".MPG"))
			  {
					$extn = strchr($file,'.');
					$file_extn =  substr($extn,1);
					$flvFile = str_replace($file_extn , "flv", $file);
					$flvFilePath =  '"' . $videoPath . "/" . $flvFile . '"';
					$aviFilePath =  '"' . $videoPath . "/" . $file . '"';
					$aviFileToBeDeleted = $videoPath . "/" . $file;
					$jpgPath = str_replace(".flv" , "_tn.jpg", $flvFilePath);
					$cmd = 'ffmpeg -i '.$aviFilePath.' '.$flvFilePath;
					exec($cmd , $output , $status);
					$cmd = 'ffmpeg  -itsoffset -4  -i '. $flvFilePath .' -vcodec mjpeg -vframes 1 -an -f rawvideo -s 100x100 '. $jpgPath;
					exec($cmd , $output , $status);

					unlink($aviFileToBeDeleted);
			  }

			}
			closedir($handle);
		}
		fwrite($this->logger , "END Move images and video\n");
	}

	public function GenerateMapFiles($db_url , $layerTableName)
	{

		fwrite($this->logger , "Generate map file\n");

		$dbuser = preg_replace('/pgsql:\/\/([^:@][^:@]*).*/','$1',$db_url);
		$dbname = substr(strrchr($db_url,'/'),1);

		$str = 'pgsql://'.$dbuser.':';
		$nStr = str_replace($str,"",$db_url);
		$dbStr = '@localhost/'.$dbname;
		$dbpwd  = str_replace($dbStr,"",$nStr);

		$generateMapFileCmd = "cd upload/zipfiles/generateMapfiles/ ; php generateMapfiles.php -u ".$dbuser." -d ". $dbname." -p ".$dbpwd." -l ".$layerTableName;
		fwrite($this->logger , "Command : ".$generateMapFileCmd."\n");
		exec($generateMapFileCmd,$output,$status);
		if($status != 0)
		{
			fwrite($this->logger , "Problem while generating a map file\n");
			$this->errorMsg[$this->errorMsgArrayCount] = "Some Problem has ocurred in the generation of the map files";
			$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
		}

		$this->MoveMapFiles();
	}

	public function MoveMapFiles()
	{
		fwrite($this->logger , "Move map file\n");
		$moveCmd = 'cd upload/zipfiles/generateMapfiles/ ;  mv *.map /usr/lib/cgi-bin/';
		exec($moveCmd,$output,$status);
	}

	public function HandleError($layerFileName)
	{
		fwrite($this->logger , "Handle errors\n");
		$logFileDir = $this->baseFolderPath."logs/";
		$sqlScriptPath = $this->baseFolderPath."layersqls/";
		$directory = opendir($logFileDir);
		while($entryName = readdir($directory))
		{
			$count = substr_count($entryName,"log");
			if($count>0)
			{
				$logFileDir = $logFileDir.$entryName;
				$sqlScriptPath = $sqlScriptPath.$entryName;
				break;
			}
		}
		$file_handle = fopen($logFileDir , "r");
		while (!feof($file_handle))
		{
			$line = fgets($file_handle);
			$count = substr_count($line,"ERROR");
			if($count > 0)
				{
					$this->errorMsg[$this->errorMsgArrayCount] = $line;
					$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
					$this->errorMsg[$this->errorMsgArrayCount] = "Go through $logFileDir for more details";
					$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
					$this->errorMsg[$this->errorMsgArrayCount] = "$sqlScriptPath contains the sql script for the same";
					$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
					break;
				}
 		}
		fclose($file_handle);
	}

	public function DoCleanUp()
	{

		fwrite($this->logger , "DoCleanUp\n");
		$isCleanedUp = true;
		$cmd = "cd upload/zipfiles/ ; python cleanup.py";
		exec($cmd , $output , $status);
		if($status != 0)
		{
			fwrite($this->logger , "Some Problem has ocurred clean up of the folders\n");
			$this->errorMsg[$this->errorMsgArrayCount] = "Some Problem has ocurred clean up of the folders.";
			$this->errorMsgArrayCount = $this->errorMsgArrayCount + 1;
			$isCleanedUp = false;
		}
		return $isCleanedUp;
	}

	public function CleanUpAfterUpload()
	{
			fwrite($this->logger , "Cleaning up after upload\n");
			$dataFolderPath = $this->baseFolderPath.$this->layerName;
			unlink($this->zipFile);
			rmdir_r($dataFolderPath);
			fclose($this->logger);
	}


 }

function rmdir_r ( $dir, $DeleteMe = TRUE )
{
	if ( ! $dh = @opendir ( $dir ) ) return;
	while ( false !== ( $obj = readdir ( $dh ) ) )
	{
		if ( $obj == '.' || $obj == '..') continue;
		if ( ! @unlink ( $dir . '/' . $obj ) ) rmdir_r ( $dir . '/' . $obj, true );
	}

	closedir ( $dh );
	if ( $DeleteMe )
	{
		@rmdir ( $dir );
	}
}
	  $operation = $_REQUEST['operation'];
	  $city = $_REQUEST['cityName'];

	  if($operation == "Upload")
	  {

		  $fileSize = $_FILES['uploadFile']['size'];

		  $errors = array();
		  $succMsg = '';
		  $categoryName = $_REQUEST['categorylist'];

		  $uploadDataObj = new UploadData($city , $_FILES , $categoryName);

		  if ("" != $city)
		  {
				if($fileSize != '0')
				{
					if($uploadDataObj->DoCleanUp())
					{
						$uploadDataObj->UploadFile();
						$currentFilePath = $_SERVER['SCRIPT_FILENAME'];
						if($uploadDataObj->CheckZipFileContents($currentFilePath))
						{
							$uploadDataObj->InitDataBaseRelatedParameters($db_url);
							$uploadDataObj->CleanUpAfterUpload();
							$errors = $uploadDataObj->GetErrorMessage();
							if(count($errors) == 0)
							{
								$succMsg =  "DATA IS UPLODED SUCCESSFULLY";
								notify_admin_of_layer_updation("" , "insert" , $city);
							}
						}
						else
						{
							$errors = $uploadDataObj->GetErrorMessage();
									}
					}
					else
					{
						$errors = $uploadDataObj->GetErrorMessage();
					}
				}
				else
				{
					$errors[0] = "File size should be less than 10MB";
				}
		  }

			if(count($errors) > 0)
			{
				echo "<center>";
				echo "<p><b>ERRORS WHILE DATA UPLOAD</b></p>";
				echo '<table align ="center" >';
				foreach ($errors as $error)
				{
					echo "<tr><td>$error</td></tr>";
				}
				echo "</table>";
				echo "</center>";
			}

		 echo '<center><p><b>'.$succMsg.'</b></p></center>';
	}

	else if($operation == "Delete")
	{
	  $layerName = $_REQUEST['layerName'];
	  $dbuser = preg_replace('/pgsql:\/\/([^:@][^:@]*).*/','$1',$db_url);
	  $dbname = substr(strrchr($db_url,'/'),1);



	    $str = 'pgsql://'.$dbuser.':';
		$nStr = str_replace($str,"",$db_url);
		$dbStr = '@localhost/'.$dbname;
		$dbpwd  = str_replace($dbStr,"",$nStr);

	  $delete_cmd = "python upload/zipfiles/deletelayers/delete_layer.py ".$dbname." ". $dbuser." ".$dbpwd." \"".$layerName."\"";
	  exec($delete_cmd,$output,$status);
	  if (0 != $status)
	  {
		foreach ($output as $error)
		{
			echo "<p><center><b>$error</b></center></p>";
		}

	  }
		else
		{
			echo "<p><b><center>Layer is deleted successfully</center></b></p>";
			notify_admin_of_layer_updation($layerName , "delete" , $city);
		}

	}

?>

