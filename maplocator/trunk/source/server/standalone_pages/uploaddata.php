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
ini_set('max_execution_time', 100000);
$flag = checkIfAdministrator();
if(checkIfAuthorisedUser())
{
	if(checkIfAdministrator())
	{
?>
<html>
<head>
<script src="sites/all/themes/uap/scripts/jquery.js"></script>
<script type="text/javascript">

var divId = 1;
var categoryParentId = -1;
function DisplayContinents()
{
 	var resp = jQuery.ajax({
    	url:  "getdata.php?action=getContinent" ,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;
  continents = resp.split(",");
  var continentObj = document.upload.continentName;
  addOption("Select Continent" , "Select Continent" , continentObj , 0);
  var jsonObj = eval('(' + resp + ')');
  i = 1;
  for(key in jsonObj)
  {
	addOption(key, key , continentObj , i);
	i = i + 1;
  }
}

function addOption(opValue , opName , selectObj , index)
{
   var newOpt = new Option(opName, opValue);
   selectObj.options[index] = newOpt;
}

function DisplayCountry()
{
	var continentName = document.upload.continentName.value;
	var resp = jQuery.ajax({
    	url:  "getdata.php?action=getCountry&continentName=" + continentName ,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;
  var countryObj = document.upload.countryName;
  deleteOptions(countryObj);
  addOption("Select Country" , "Select Country" , countryObj , 0);
  var jsonObj = eval('(' + resp + ')');
  i = 1;
  for(key in jsonObj)
  {
	addOption(key, jsonObj[key] , countryObj , i);
	i = i + 1;
  }

}

function DisplayLayers(categoryId)
{
	var cityName = document.upload.cityName.value;
	var resp = jQuery.ajax({
    	url:  "getdata.php?action=getLayer&categoryId=" + categoryId + "&cityName=" + cityName,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;
	var layerObj = document.upload.layerName;
	deleteOptions(layerObj);
	addOption("Select Layer" , "Select Layer" , layerObj , 0);
	var jsonObj = eval('(' + resp + ')');
	i = 1;
	for(key in jsonObj)
	{
		addOption(jsonObj[key], jsonObj[key] , layerObj , i);
		i = i + 1;
	}

}



function DisplayCity()
{
	var countryId = document.upload.countryName.value;
 	var resp = jQuery.ajax({
    	url:  "getdata.php?action=getCity&countryId=" + countryId ,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;

  var cityObj = document.upload.cityName;
  deleteOptions(cityObj);
  addOption("Select City" , "Select City" , cityObj , 0);

  var jsonObj = eval('(' + resp + ')');
  i = 1;
  for(key in jsonObj)
  {
	addOption(key , key , cityObj , i);
	i = i + 1;
  }

}

function deleteOptions(obj)
{
  var length = obj.length;
  for(i=0;i<length;i++)
  {
	obj.remove(0);
  }
}


function LoadForm()
{
	document.getElementById("processing").style.display = "none";
	document.getElementById("deleting").style.display = "none";
	DisplayContinents();
	DisplayBasicCategories();
}


function CheckData()
{
	operationName = document.upload.operation.value;
	if(operationName == "Delete")
	{
		var agree=confirm("Are you sure you want to delete?");
		if (!agree)
		return false ;
	}
	continent = document.upload.continentName.value;
	country = document.upload.countryName.value;
	city = document.upload.cityName.value;
	fileName =  document.upload.uploadFile.value;
	categories = document.upload.categorylist.value;
	isChecked = false;

	if(continent == "Select Continent")
	{
		alert("Select Continent");
		document.upload.continentName.focus();
		return false;
	}

	if(country == "Select Country")
	{
		alert("Select Country");
		document.upload.countryName.focus();
		return false;
	}

	if(city == "Select City")
	{
		alert("Select City");
		document.upload.cityName.focus();
		return false;
	}

	if(categories == "")
	{

		alert("Select category");
		return false;
	}


	if(fileName == "" && operationName == "Upload")
	{
		alert("Please select the file to be uploaded");
		document.upload.uploadFile.focus();
		return false;
	}

	if(operationName == "Upload")
	{
		document.getElementById("processing").style.display = "block";
	}
	if(operationName == "Delete")
	{

		layerVal = document.upload.layerName.value;
		if(layerVal == "Select Layer")
		{
			alert("Select layer");
			document.upload.layerName.focus();
			return false;
		}
		else
		{
			document.getElementById("deleting").style.display = "block";
		}
	}
	document.getElementById("error").style.display = "none";
}

function uploadStatus()
{
	document.getElementById("error").style.display = "block";
	document.getElementById("processing").style.display = "none";
	document.getElementById("deleting").style.display = "none";
	var resp = document.getElementById('uploadframe').contentWindow.document.body.innerHTML;
	var ifrm = document.getElementById('error');
	ifrm.innerHTML = resp;
}

function CheckContinent()
{
	continent = document.upload.continentName.value;
	if(continent == "Select Continent")
	{
		alert("First Select Continent");
	}
}

function CheckCountry()
{
	country = document.upload.countryName.value;
	if(country == "Select Country")
	{
		alert("First Select Country");
	}
}

function showupload()
{
	document.getElementById("uploaddiv").style.display = "block";
	document.getElementById("inputforuploaddiv").style.display = "block";
	document.getElementById("submitupload").style.display = "block";
	document.getElementById("uplodchoice").style.display = "none";

	document.getElementById("deletediv").style.display = "none";
	document.getElementById("inputfordeletediv").style.display = "none";
	document.getElementById("submitdelete").style.display = "none";
	document.getElementById("deletechoice").style.display = "block";

	document.getElementById("error").style.display = "none";

}

function showlayerdeletion()
{
	document.getElementById("deletediv").style.display = "block";
	document.getElementById("inputfordeletediv").style.display = "block";
	document.getElementById("submitdelete").style.display = "block";
	document.getElementById("deletechoice").style.display = "none";

	document.getElementById("uploaddiv").style.display = "none";
	document.getElementById("inputforuploaddiv").style.display = "none";
	document.getElementById("submitupload").style.display = "none";
	document.getElementById("uplodchoice").style.display = "block";

	document.getElementById("error").style.display = "none";
}

function SetOperation(val)
{
	document.upload.operation.value  =  val;
}

function DisplayBasicCategories()
{
 	var resp = jQuery.ajax({
    	url:  "getdata.php?action=getBasicCategories" ,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;

  var categoryObj = document.upload.Category;

  var jsonObj = eval('(' + resp + ')');
  i = 0;
  for(key in jsonObj)
  {
	addOption(key , jsonObj[key] , categoryObj , i);
	i = i + 1;
  }

}

function SelectCategories(obj)
{
	var selectName = obj.name;
	var categoryId = obj.value;
	var opt = obj.options;
	options = getSelected(opt);
	DisplayLayers(options);
	document.upload.categorylist.value = options;
	parentObj = obj.parentNode;
	var children = parentObj.childNodes;
	for(i=0;i<children.length;i++)
	{
		var idval = children[i].id;
		if(idval)
		{
			if(idval.indexOf('categoryDiv') != -1)
			{
				parentObj.removeChild(children[i]);
			}

		}
	}

	var resp = jQuery.ajax({
    	url:  "getdata.php?action=getCategories&categoryId=" + categoryId,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;

  var ifrm = parentObj;
  if(resp.length !=3)
  {
	html = getLayerListPopup(resp);
	var newdiv = document.createElement('div');
	var divIdName = 'categoryDiv' + divId;
	divId = divId + 1;
	newdiv.setAttribute('id',divIdName);
	newdiv.style.display = 'inline';
	newdiv.innerHTML = html;
	ifrm.appendChild(newdiv);

  }
  else
  {
	if(selectName == "cityName")
		ifrm.innerHTML = "";
  }

}


 function getSelected(opt)
 {
      var selected = new Array();
	  var categoriesVal = '';
      var index = 0;
      for (var intLoop=0; intLoop < opt.length; intLoop++) {
         if (opt[intLoop].selected) {
            index = selected.length;
            selected[index] = new Object;
            selected[index].value = opt[intLoop].value;
			selected[index].index = intLoop;
			categoriesVal =  categoriesVal + opt[intLoop].value + ":";
         }
      }

	  var strLen = categoriesVal.length;
	  categoriesVal = categoriesVal.slice(0,strLen-1);
	  return categoriesVal;
   }

function getLayerListPopup(resp)
{
  var jsonObj = eval('(' + resp + ')');
  var  popuphtml = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<select multiple name = "Category" onchange = "SelectCategories(this)">';
  for(key in jsonObj){
    popuphtml += '<option value="' + key + '">' + jsonObj[key] + '</option>';
  }
  popuphtml += '</select>';
  return popuphtml;
}

</script>



</head>
	<body onload = "LoadForm();">

	<div id = "uplodchoice">
	<P><input type = "button" onclick = "showupload();" value ="CLICK HERE TO UPLOAD DATA" style="display:block"></p>
	</div>

    <div id = "deletechoice" style="display:block">
	<P><input type = "button" onclick = "showlayerdeletion();" value = "CLICK HERE TO DELETE LAYER DATA"></p>
    </div>

	 <form enctype="multipart/form-data" name = "upload" method="POST" target="uploadframe" action = "loaddata.php" onsubmit="return CheckData();">

	 <center>
	  <p><b>UPLOAD DATA</b></p>
	  <br/><br/>

	  <table>
	  <tr><td>
	  <b>Choose Continent : </b>
	  </td><td></td>
	  <td>
	  <select name = "continentName" onchange = "DisplayCountry();">
      </select>
	  </td></tr>
	  <tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>


	  <tr><td>
	  <b>Choose Country : </b>
	  </td><td></td>
	  <td>
	  <select name = "countryName" onclick = "CheckContinent();" onchange = "DisplayCity();" >
      <option>Select Country</option>
      </select>
	  </td></tr>
	  <tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>


	  <tr><td>
	  <b>Choose City : </b>
	  </td><td></td>
	  <td>
	  <select name="cityName" onclick = "CheckCountry();">
      <option>Select City</option>
	  </select>
	  </td></tr>
	  <tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>


	  <tr><td>
	  <b>Choose Category : </b>
	  </td><td></td>
	  <td>
	  <div id = "cat" style = "display:inline">
	  <select MULTIPLE name="Category" onchange = "SelectCategories(this)">
      </select>
	  </div>
	  </td></tr>
	  <tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>

	  <tr>
	  <td>
	  <div id = "uploaddiv" style="display:none">
	  <b>Please choose a file to upload [only zip with shape files]:</div></td> <td></td><td><div id = "inputforuploaddiv" style="display:none"><input name="uploadFile" type="file" /> </div> <br /></b>
	  </div>
	  </td>
	  </tr>

	  <tr>
	  <td>
	  <div id = "deletediv" style="display:none">
	  <b>Please select a layer to be deleted: </div></td> <td></td><td><div id = "inputfordeletediv" style="display:none">
	  <select name = "layerName" />
	  <option>Select Layer</option>
	  </select>
	  <br /></b>
	  <div id = "deletediv" style="display:none">
	  </td>
	  </tr>

	  <tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>
	  <tr><td></td>
	  <td>
	  <div id = "submitupload" style="display:none">
	  <input type="submit" name="uploadsubmit" class="button" id="submit_btn" value="Upload" onclick = "SetOperation(this.value);"/>
	  </div>
	  </td>
	  <td></td></tr>


	  <tr><td></td>
	  <td>
	  <div id = "submitdelete" style="display:none">
	  <input type="submit" name="deletesubmit" class="button" id="submit_btn" value="Delete" onclick = "SetOperation(this.value);"/>
	  </div>
	  </td>
	  <td></td></tr>

	  </table>
	  </center>
	  <div id="processing" style="display:none"><center><p><b>UPLOADING<img src="sites/all/themes/uap/images/loading.gif" WIDTH=200 HEIGHT=150 /></b></p></center></div>
	  <div id="deleting" style="display:none"><center><p><b>DELETING<img src="sites/all/themes/uap/images/loading.gif" WIDTH=200 HEIGHT=150 /></b></p></center></div>
	  <div id="error"></div>
	  <input type = "hidden" name = "operation"/>
	  <input type = "hidden" name = "categorylist"/>
	  <iframe name="uploadframe" id="uploadframe" onload="uploadStatus();" style="display:none"/>

</form>

</body>
</html>

<?php
	}
	else
		echo "<b><center>YOU DO NOT HAVE ADMINISTRATOR RIGHTS</center></b>";
	}
	else
		echo "<b><center>YOU NEED TO LOGIN TO UPLOAD DATA</center></b>";


?>
