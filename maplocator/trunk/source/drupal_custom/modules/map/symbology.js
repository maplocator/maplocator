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
*This  file contains functionality for symbology feature.
*
***/

var ARR_COLOR = [];
var categories ;
var wms_layer;
var jsonURL;
var title;
var filesize;
var arr_filtervalues;
var selectpolywms;
var color_by_arr, size_by_arr;
var SIZE_BY_COUNT= 5;
var SymbolgyOption = 'SIZEBY';
var objColorArr = new Object();
function init_color_array(){
 ARR_COLOR[0]= '#ff0000';
 ARR_COLOR[1]= '#00ff00';
 ARR_COLOR[2]= '#0000ff';
 ARR_COLOR[3]= '#ffff00';
 ARR_COLOR[4]= '#6600ff';
 ARR_COLOR[5]= '#ff66ff';
 ARR_COLOR[6]= '#00ccff';
 ARR_COLOR[7]= '#000099';
 ARR_COLOR[8]= '#ff9900';
 ARR_COLOR[9]= '#ff6600';
 ARR_COLOR[10]= '#aabbcc';
 ARR_COLOR[11]= '#123456';
 ARR_COLOR[12]= '#ff9933';
 ARR_COLOR[13]= '#000066';
 ARR_COLOR[14]= '#cc0033';
 ARR_COLOR[15]= '#ff3300';
 ARR_COLOR[16]= '#3399ff';
 ARR_COLOR[17]= '#bb5418';
 ARR_COLOR[18]= '#6600ff';
 ARR_COLOR[19]= '#993366';
}
/**
 *
 * @access public
 * @return void
 **/
function getLUTColor(value){
return objColorArr[value];
}
var symbologyLayer;
var select;
var MIN,MAX;
var MIN_RAD = 5;
var MAX_RAD = 50;
var FILTER_VAL=''
function AddgeoJSON(filter,title){
	FILTER_VAL = filter;

  	//map.layers[map.layers.length -1].redraw();
  /* check if it is json or mapserver based on file size*/
  if(filesize > 2) {
    var count =  arr_filtervalues.length;
    var expr='';
    var color;
    var hexcolor='';
    for(var i=0; i< count; i++){
      arr = arr_filtervalues[i].split(":");
      expr += escape(arr[0])+',';
      //color = document.getElementById(arr[0]+'_sample').style.backgroundColor + ',';
      color = document.getElementById(categories[i]).value;
      /*color = color.replace('rgb(','');
      color = color.replace(')','');
      rgb_arr = color.split(",");
      hexcolor += RGBtoHex(rgb_arr[0],rgb_arr[1],rgb_arr[2])+",";*/

	  color = color.replace("#","");
	  color = color.toUpperCase()
	  hexcolor += color +",";
	  //alert(hexcolor);
    }
    expr = expr.substring(0,expr.length-1);
    symbology_wms(wms_layer,filter,expr,hexcolor.substring(0,hexcolor.length-1),title);
    map.layers[map.layers.length -2].setOpacity(0.1);

  } else {
  	//if (map.getLayerIndex(symbologyLayer) == -1) {
	  	if (symbologyLayer == null) {
	  		if (SymbolgyOption != 'SIZEBY') {
			map.layers[map.layers.length -1].setOpacity(0.1);

		  	symbologyLayer = new OpenLayers.Layer.GML("Symbology", jsonURL, {
	          		format: OpenLayers.Format.GeoJSON,
		            styleMap: build_style(filter,SymbolgyOption),
		            isBaseLayer: false,
		            projection: new OpenLayers.Projection(base_map_projection)

		            });
		    }else{
		    	if (Validate_Sizeby() == false) {
	  				return;
	  			}
		    	symbologyLayer = new OpenLayers.Layer.GML("Symbology", jsonURL, {
	          		format: OpenLayers.Format.GeoJSON,
		            isBaseLayer: false,
		            projection: new OpenLayers.Projection(base_map_projection)

		            });
				symbologyLayer.events.on({
			      "featureadded": onFeatureAdd
			    });
			    symbologyLayer.redraw();
			    //jQuery("#symbology").css("height","250px");
			    var color = document.getElementById('color_sizeby').value;
			  	document.getElementById("btn_sizeby" ).style.backgroundColor = color;
			}

        } else{
        	if (SymbolgyOption == 'SIZEBY') {
        		if (Validate_Sizeby() == false) {
	  				return;
	  			}
        		var color = document.getElementById('color_sizeby').value;
        		changeStyle(color);
        		//jQuery("#symbology").css("height","250px");
        	}else{
        		symbologyLayer.styleMap = build_style(filter,SymbolgyOption);
        		map.layers[map.layers.length -2].setOpacity(0.1);

		    }
		    symbologyLayer.redraw();

        }


	    map.addLayers([symbologyLayer]);
		var popup;
	    var options = {
		    hover: false
			,onSelect: function(feature) {
		    var desc = feature.attributes[title] + ": "+ feature.attributes[filter];
			if (map.popups != null) {
				if(map.popups.length>0){
        			map.removePopup(map.popups[0]);
      			}
			}
		    popup = new OpenLayers.Popup.FramedCloud("chicken",
					           feature.geometry.getBounds().getCenterLonLat(),
			        		   null,
							   "<div class='msg' align='center'>" + desc + "</div>" ,
							   null, true,null );
            popup.autoSize = true;
			popup.minSize = new OpenLayers.Size(150, 70);
            popup.maxSize = new OpenLayers.Size(200, 200);
			popup.panMapIfOutOfView = true;
			map.addPopup(popup);
			//jQuery(".olPopupCloseBox").css("display","none");
            },
			onUnselect: function(feature){
					map.removePopup(popup);
            }
        };
	    if (select != null) {
	    	map.removeControl(select);
	    }
	    select = new OpenLayers.Control.SelectFeature(symbologyLayer, options);
	    map.addControl(select);
	    select.activate();
    /*}else{
       	symbologyLayer.styleMap = null;
		symbologyLayer.styleMap = build_style(filter,SymbolgyOption);
       	//console.log(symbologyLayer.redraw());
	  }*/

  }
  var o=document.getElementById('your_display_id').value;
  symbologyLayer.setOpacity(o);


}

function Validate_Sizeby(){
	var max = document.getElementById("txt_maxRad").value;
	var min = document.getElementById("txt_minRad").value;
	if (max=='') {
		alert('Please enter a valid max radius');
		return false;
	}else{
		if (IsNumeric(max)==false) {
			alert('Please enter a valid max radius');
			return false;
		}else{
			if (parseInt(max)< 1 || parseInt(max) > 50 ) {
				alert('Please enter a valid max radius between 1 to 50');
				return false;
			}
		}
	}
	if (min=='') {
		alert('Please enter a valid min radius');
		return false;
	}else{
		if (IsNumeric(min)==false) {
			alert('Please enter a valid min radius');
			return false;
		}else{
			if (parseInt(min)< 1 || parseInt(min) > 50 ) {
				alert('Please enter a valid min radius between 1 to 50');
				return false;
			}
		}
	}
	MIN_RAD = min;
	MAX_RAD = max;

	return true;
}

function IsNumeric(sText){
   var ValidChars = "0123456789.";
   var IsNumber=true;
   var Char;
   for (i = 0; i < sText.length && IsNumber == true; i++)
      {
      Char = sText.charAt(i);
      if (ValidChars.indexOf(Char) == -1)
         {
         IsNumber = false;
         }
      }
   if (sText=="") {
      	IsNumber = false;
   }
   return IsNumber;
}
function changeStyle(color){
	var cnt = symbologyLayer.features.length;

	var f= symbologyLayer.features;
	if (color.indexOf('#') == -1) {
	    color = '#' + color;
	}
	for(var i=0; i<cnt ; i++){
		//f[i].style.fillColor = color;
		//f[i].style.strokeColor =  color;
		f[i].style = getStyle(f[i].attributes[FILTER_VAL])
	}

}
function onFeatureAdd(event){

	event.feature.style = getStyle(event.feature.attributes[FILTER_VAL]);
	symbologyLayer.drawFeature(event.feature);


}
function getStyle( rad,color ){
	var style = OpenLayers.Util.extend({}, OpenLayers.Feature.Vector.style['default']);
	var r = getRadius(rad);
	//console.log(r);
	//console.log(parseInt(r));
	var col;
	if(color!=null){
		col = color;
		//alert('not null' + color);
	}else{
		col = document.getElementById('color_sizeby').value;
		//alert('null' + col);
	}
	if (col.indexOf('#') == -1) {
	    col = '#' + col;
	}
	style.pointRadius = r;

	style.fillOpacity = 0.7;
	style.fillColor= col;
	style.strokeColor = col ;
	style.strokeOpacity= 1;
	//console.log(style.pointRadius);
	return style;
}


function build_style(column,type) {
            var theme = new OpenLayers.Style();
            var rules = new Array();
			var rule_match;
           	var count = categories.length;
           	init_color_array();
            for(var i=0; i<count; i++){
	            	var filter = categories[i];
	            	var color = document.getElementById(filter).value;
	            	if (color =='') {
	            		color = ARR_COLOR[i];
	            		document.getElementById("btn" + i ).style.backgroundColor = color;
						//document.getElementById(filter + "_sample").style.backgroundColor = color;

	            	}else{
	            		if (color.indexOf('#') == -1) {
	            			var color = '#' + color;
	            		}

	            	}


						rule_match = new OpenLayers.Rule(
				        {
				              filter: new OpenLayers.Filter.Comparison({
				                type: OpenLayers.Filter.Comparison.EQUAL_TO,
				                property: column,
				                value: filter }),
				                symbolizer: {"Polygon": {'fillColor': color
								}}
				        });


			        rules.push(rule_match);

			}
			theme.addRules(rules);

            var stylemap = new OpenLayers.StyleMap({'default':theme, 'select': {'strokeColor': '#0000ff', 'fillColor': '#0000ff', 'strokeWidth': 2}});
            return stylemap;
}

function CloseSymbolgy(){
	if (symbologyLayer != null) {
		map.removeLayer(symbologyLayer);
    symbologyLayer=null;
	}
	/*
  if (selectpolywms != null) {
    map.removeLayer(selectpolywms);
    selectpolywms = null;
  }
  */
	jQuery("#symbology").css("display","none");
	map.layers[map.layers.length -1].setOpacity(0.7);
}

function HideSymbolgy(){
	jQuery("#sym_data").toggle(400);

	if (document.getElementById('anc_min_max').innerHTML == "_&nbsp;") {
		jQuery("#anc_min_max").html("+&nbsp;");
		//jQuery("#anc_min_max").title("Maximize");
	}else{
		jQuery("#anc_min_max").html("_&nbsp;");
		//jQuery("#anc_min_max").title("Minimize");
	}


}

function callSlider(evnt){
	slide(evnt,'horizontal', 100, 100, 0, 101, 0,'your_display_id');

}

function changeOpacity(){
  var o=document.getElementById('your_display_id').value;
  o = o/100;
  o = Math.round(o * 10)/10;
  symbologyLayer.setOpacity(o);

}
/**
 *
 * @access public
 * @return void
 **/
function LoadSymbology(layer){

 	var filter_by;
	var resp = jQuery.ajax({
    	url:  base_path + "ml_orchestrator.php?action=getFilterByColumn&layer_tablename=" + layer ,
    	type: 'GET',
    	timeout: 30000,
    	async: false
	}).responseText;
    var jsonObj = eval('(' + resp + ')');
    if (jsonObj['color_by']) {
    	color_by_arr = jsonObj['color_by'].replace(/'/g,'').split(",");
    }else{
    	color_by_arr = null;
    }

    if (jsonObj['size_by']) {
    	size_by_arr = jsonObj['size_by'].replace(/'/g,'').split(",");;
    }else{
    	size_by_arr=null;
    }
	title = jsonObj['title'];
	title = title.replace(/'/g,'');
	filesize = jsonObj['file_size'];
    var str ='';
    var opt_set = false;
	jQuery("#symbology").html(str);
	str += "<table border=1 width='100%'>";
		str += "<tr>";
			str += "<td>";
				str += "Select Method: ";
				if (color_by_arr!=null && color_by_arr.length > 0) {
					str += "<label for='rdoColor'>&nbsp;Color By&nbsp;</label>";
					str += "<input id ='rdoColor' type='radio' name='color' value='color' checked='true'/> ";
					opt_set = true;
				}
				if (size_by_arr!=null && size_by_arr.length > 0) {
					str += "<label for='rdoSize'>&nbsp;Size By&nbsp;</label>";
					if (opt_set == true) {
						str += "&nbsp;<input id ='rdoSize' type='radio' name='color' value='size' />";
					}else{
						str += "&nbsp;<input id ='rdoSize' type='radio' name='color' value='size' checked='true'/>";
					}

				}

			str +="</td>"
		str += "</tr>";
		str += "<tr>";
			str += "<td>";
				str += "<div id='symUI'></div>";
			str +="</td>"
		str += "</tr>";
	str += "</table>";
	jQuery("#symbology").html(str);
	jQuery('#rdoColor').click(function()  {
	  SymbolgyOption = 'COLORBY';
	  jsonURL = base_path+"json/"+layer+"_color.json";

	  generateSymUI(layer,color_by_arr);

  	});
  	jQuery('#rdoSize').click(function()  {
	  SymbolgyOption = 'SIZEBY';
	  jsonURL = base_path+"json/"+layer+"_size.json";

	  generateSymUI(layer,size_by_arr);

  	});
	try{
		if (document.getElementById('rdoColor').checked) {

			SymbolgyOption = 'COLORBY';
		  jsonURL = base_path+"json/"+layer+"_color.json";

		  generateSymUI(layer,color_by_arr);
		}
	}catch(e){}
	try{
		if (document.getElementById('rdoSize').checked) {
			SymbolgyOption = 'SIZEBY';
		  jsonURL = base_path+"json/"+layer+"_size.json";

		  generateSymUI(layer,size_by_arr);
		}
	}catch(e){}
  	jQuery("#symbology").css("display","block");
	jQuery("#symbology").css("top","20px");
	jQuery("#symbology").css("left","0px");
	jQuery("#symbology").dialog({
	    modal: false,
	    zIndex: 2001,
	    height: '500px',
	    width: '400px',
	    position: [850, 140],
	    title: 'Symbology',
	    close: function() {
	    			CloseSymbolgy();
    	       }
    });
}
function generateSymUI(layer,filter_by){
	//title= title_col;
	wms_layer = layer;
	//jsonURL = json;
	var str = '';
	jQuery("#symUI").html(str);

	str += "<table  width='100%'>";
		/*str += "<tr>";
			str += "<td align='right' height=25px>";
				str += "<div id='sym_header' class='msg'>";
				  str += "<input type='hidden' id='title_col' value='"+title+"'/>";
					str += "<a id='anc_min_max' href='#' title='Minimize' onclick='HideSymbolgy()' style='text-decoration:none; '>_&nbsp;</a>";
					str += "&nbsp;<a href='#' title='Close' onclick='CloseSymbolgy()' style='text-decoration:none; '>X&nbsp;</a>";
				str += "</div>";
			str += "</td>";
		str += "</tr>";*/

		str += "<tr>";
			str += "<td>";
				str += "<input type='hidden' id='title_col' value='"+title+"'/>";
				str += "<label for='ddlFilter'>&nbsp;Filter By&nbsp;</label>";
				str += "<SELECT id='ddlFilter' width='40px' onChange='genUIForCategories(options[selectedIndex].value);'></SELECT>";
			str +="</td>"
		str += "</tr>";
		str += "<tr>";
			str += "<td>";
				str +="<div id='sym_data' >";

				str +="</div>";
			str += "</td>";
		str += "</tr>";
		str += "<tr>";
			str += "<td align = 'center'>";
					str += "<input id='btnDisplay' type='button' value='Apply' disabled='true'>";
			str +="</td>"
		str += "</tr>";
		str += "<tr>";
			str += "<td style='display:inline'>";
				str += "<table style='margin-top:5px; margin-bottom:5px'><tr>";
					str += "<td>";
					str += "<label for='horizontal_trac_1'>&nbsp;Opacity&nbsp;</label>";
					str += "</td>";
					str += "<td>";
					str += "<SELECT id='ddlopacity' width='40px' style='display:none'></SELECT>";
				//str +="</td>"
				//str += "<td>";
					str += "<div class='horizontal_track' id='horizontal_trac_1'>";
	    				str += "<div id='horizontal_slit_1' class='horizontal_slit' >&nbsp;</div>";
	    				str += "<div class='horizontal_slider' id='your_slider_id' style='left: 0px;'";
	        			//str += "onmousedown='slide(event," + "'horizontal'" +", 100, 0, 100, 101, 0,"+ "'your_display_id'" +")';";
	        			str += "onmousedown='callSlider(event);' onmouseup='changeOpacity();'>&nbsp;";
						str += "</div>";
					str += "</div>";
					str += "</td>";
					str += "<td>";
					str += "<div class='display_holder' >";
	    				str += "<input id='your_display_id' class='value_display' type='text' value='100' onfocus='blur(this);' />";
					str += "</div>";
					str += "</td>";
				str += "</tr></table>";
			str += "</td>";
		str += "</tr>";

	str += "</table>";
	jQuery("#symUI").html(str);
	jQuery('#btnDisplay').click(function()  {

	  AddgeoJSON( ddlFilter.value,title);

  });

/*	var ddlopacity = document.getElementById('ddlopacity');
	for (var i=0.1; i< 1; i=i+0.1 ) {
	    var val = Math.round(i * 10)/10;
	    addOption(ddlopacity, val,val);
	}
	ddlopacity.value = 1;*/
	var count =  filter_by.length;
	var ddlFilter = document.getElementById('ddlFilter');
	for (var i=0; i< count; i++ ) {
		addCategories(ddlFilter,filter_by[i],filter_by[i]);
	}




	genUIForCategories(filter_by[0],title);


	//AddgeoJSON(filter_by[0]);
}

function createCategoriesForSizeBy(min,max){

	min = parseFloat(min) - 1;
	max = parseFloat(max) + 1;
	var step = (parseFloat(max)  - parseFloat(min) ) / SIZE_BY_COUNT ;

	var str="";
	var value = parseFloat(min);
	for( var i =0 ; i < SIZE_BY_COUNT; i++){

		if (i == SIZE_BY_COUNT - 1) {
			str += parseFloat(value) + " - " + parseFloat(parseFloat(value) + parseFloat(step));
		}else{
			str += parseFloat(value) + " - " + parseFloat(parseFloat(value) + parseFloat(step)) + ",";
		}
		//value = parseInt(value + step);
		value = parseFloat(min) + (parseFloat(step) * (i+1));

	}
	return str;
}

function getRadius(val){
	/*if (val == MIN) {
		return MIN_RAD;
	}
	if (val == MAX) {
		return MAX_RAD;
	}*/
	var rad = ((MAX_RAD * parseFloat(val))/parseFloat(MAX)) + parseFloat(MIN_RAD);
	/*if (parseFloat(rad) < MIN) {
		console.log('less');
		rad = parseFloat(rad) + 10;

	}*/
	return rad

}
function genUIForCategories(filter,title){
	var str='';
	var arr,filter_value,filter_cnt;
	str += "<table id='sym_wrapper' width='100%' height='99%'>"
	//var SymbolgyOption = 'SIZEBY';
	// Check for Size BY
	if (SymbolgyOption == 'SIZEBY') {
		var respdata  = jQuery.ajax({
	        type: "GET",
	        timeout: 30000,
	        url: "ml_orchestrator.php?action=getSizeByJSON&layer="+ wms_layer + "&filter="+ filter +"&title=" + title ,
	        async: false
	        }).responseText;
  		var jsonObj = eval('(' + respdata + ')');
  		MIN = jsonObj['Min'];
  		MAX = jsonObj['Max'];
  		var data = createCategoriesForSizeBy(jsonObj['Min'],jsonObj['Max']);
  		//var data = createCategoriesForSizeBy(2,22);
  		arr_filtervalues = data.split(",");

	}else{
		var respdata  = jQuery.ajax({
	        type: "GET",
	        timeout: 30000,
	        url: "ml_orchestrator.php?action=getCategories&layer="+ wms_layer + "&filter="+ filter ,
	        async: false
	        }).responseText;
  		var data = respdata;
  		respdata  = jQuery.ajax({
	        type: "GET",
	        timeout: 30000,
	        url: "ml_orchestrator.php?action=getlut_color&layer="+ wms_layer + "&filter="+ filter ,
	        async: false
	        }).responseText;
  		objColorArr = eval('(' + respdata + ')');
		/*  var data = respdata.split("|");*/
		arr_filtervalues = data.split(",");
		/*filesize = data[1];*/
	}

	if (SymbolgyOption != 'SIZEBY') {
		var count =  arr_filtervalues.length;
		var test='';
		categories = new Array();
	    init_color_array();
		for(var i=0; i< count; i++){
			arr = arr_filtervalues[i].split(":");
			filter_value = arr[0];
			if (arr.length > 1) {
				filter_cnt = arr[1];
			}else{
				filter_cnt = '';
			}

			str += "<tr>";
				str += "<td>";
					str +=  filter_value ;
					if (filter_cnt != '') {
						str += " (" +filter_cnt +")";
					}
				str += "</td>";
				str += "<td >";
					str += "<input id = 'btn" + i +"' value='         ' size='3' style='border-color: #D4D2CB; cursor:pointer;border-style:outset'>";
					/*str += "<input id = 'btn" + i +"' type='button' value='         ' size='5' style='border-color: #D4D2CB; cursor:pointer;'>";*/
					//str += "<input id = 'btn" + i +"' class=\"color {valueElement:'"+ filter_value + "', styleElement:'"+ filter_value + "_sample'}\" type='button' value='Color'>";
			  		//str += "Color: <input type='button' onclick='showColorGrid3(\""+filter_value+ "\",\"" +filter_value+"_sample\");' value='...'/>";
					//str += " <input type='text' ID='" + filter_value + "' size='9' value='" + ARR_COLOR[i] + "' style='display:none;'/>";
			  		str += " <input onchange='EnableApply();' type='text' ID='" + filter_value + "' size='9' value='" + getLUTColor(filter_value) + "' style='display:none;'/>";

				  str += "</td>";
			  	/*str += "<td>";
			  		str += "<input type='text' ID='" + filter_value + "_sample' size='1' value='' style='background-color:" + ARR_COLOR[i] + "'/>";
			  	str += "</td>";*/
			str += "</tr>";
			categories.push(filter_value);
	   }

  } else {
		str += "<tr valign=top>";
				str += "<td>";
					str += "Change Color "
				str += "</td>";
				str += "<td>";
					str += "<input id = 'btn_sizeby' value='         ' size='3' style='border-color: #D4D2CB; cursor:pointer;border-style:outset'>";
					/*str += "<input id = 'btn_sizeby' type='button' value='         ' size='5' style='border-color: #D4D2CB; cursor:pointer;'>";*/
					//str += "<input id = 'btn" + i +"' class=\"color {valueElement:'"+ filter_value + "', styleElement:'"+ filter_value + "_sample'}\" type='button' value='Color'>";
			  		//str += "Color: <input type='button' onclick='showColorGrid3(\""+filter_value+ "\",\"" +filter_value+"_sample\");' value='...'/>";
					str += " <input type='text' onchange='EnableApply();' ID='color_sizeby' size='9' value='#ff0000' style='display:none;'/>";
			  	str += "</td>";
		str += "</tr>";
		str += "<tr valign=top>";
				str += "<td>";
					str += "Min Radius "
				str += "</td>";
				str += "<td>";
					str += "<input id = 'txt_minRad' onchange='EnableApply();' type='text' value='5' size='5' style='border-color: #D4D2CB; '> &nbsp;(value in range 1 - 50)";
			  	str += "</td>";
		str += "</tr>";
		str += "<tr valign=top>";
				str += "<td>";
					str += "Max Radius "
				str += "</td>";
				str += "<td>";
					str += "<input id = 'txt_maxRad' onchange='EnableApply();' type='text' value='50' size='5' style='border-color: #D4D2CB; '>&nbsp;(value in range 1 - 50)";
			  	str += "</td>";
		str += "</tr>";

	}

	str += "</table>";



	jQuery("#sym_data").html(str);
    jQuery("#sym_data").css("height","300px");
	jQuery("#sym_data").css("overflow","auto");
	//alert(filter);
	AddgeoJSON(filter,title);
	var myPicker;
	var filter;
	if (SymbolgyOption != 'SIZEBY') {
		for(var i=0; i< count; i++){
		 	filter= categories[i];
			myPicker = new jscolor.color(document.getElementById('btn' + i ), {'valueElement': filter});
			myPicker.fromString(document.getElementById(filter).value);
		}
	}else{
			myPicker = new jscolor.color(document.getElementById('btn_sizeby' ), {'valueElement': 'color_sizeby'});
			myPicker.fromString(document.getElementById('color_sizeby').value);
	}




}

function EnableApply(){
	document.getElementById('btnDisplay').disabled = false;
}
function addCategories(selectbox, value, text) {
  var optn = document.createElement("OPTION");
  optn.text = text;
  optn.value = value;
  optn.onSelect="genUIForCategories("+ value + ")";
  selectbox.options.add(optn);
}
function addOption(selectbox, value, text) {
  var optn = document.createElement("OPTION");
  optn.text = text;
  optn.value = value;
  //optn.onSelect="SetBaseLayer("+ value + ")";
  selectbox.options.add(optn);
}

function symbology_wms(layer_tablename,column,expr,hexcolor,title){
    var mapfilename  = jQuery.ajax({
	        type: "POST",
	        timeout: 30000,
	        url: "mapscript.php",
	        data: "action=get&layer_tablename=" + layer_tablename + "&expr="+expr+"&color="+hexcolor+"&col=" +column+"&getinfo="+title,
	        async: false
	        }).responseText;
      wmsUrl = MapServerURL + "?";

      // reload if existing else add the wms layer to map
      //if (map.getLayerIndex(symbologyLayer) == -1) {
        if(symbologyLayer != null) {
           symbologyLayer.redraw(true);
		    } else {
           symbologyLayer = new OpenLayers.Layer.WMS('choropleth_'+layer_tablename,wmsUrl,
           {
             map: mapfilename,
             transparent: 'true',
             layers: 'choropleth_'+layer_tablename,
             format: 'image/png',
             reproject: false,
             units: "m"
           },
           {
            //singleTile: 'true',
            numZoomLevels: 19
           });
        }
           map.addLayer(symbologyLayer);

           symbologyLayer.events.register('click', symbologyLayer, function (e) {
            var pixel = e.xy;
            var toplayer = getTopLayer();
            var url =  toplayer.getFullRequestString({
              REQUEST: "GetFeatureInfo",
              BBOX: toplayer.map.getExtent().toBBOX(),
              X: e.xy.x,
              Y: e.xy.y,
              INFO_FORMAT: 'text/plain',
              QUERY_LAYERS: toplayer.params.LAYERS,
              WIDTH: toplayer.map.size.w,
              HEIGHT:toplayer.map.size.h
            });
            var index = url.indexOf('?');
            var var_data = url.substring(index+1,url.length);
            var fidstring;
            jQuery.ajax({
              url: MapServerURL ,
              type: 'GET',
              data: var_data,
              timeout: 30000,
              complete: function(XMLHttpRequest, textStatus){
                /* remove popups from map if any */
                popupClose();
                if(fidstring){
                  //var fid = fidstring.substring(fidstring.indexOf(' '),fidstring.indexOf(':'));
                  /*var fid = fidstring.match(/\d+/);
                  if(fid)
                    highlightPolygon(toplayer.name,fid);
                  */
                  // get lat lon
                  var lonlat = getTopLayer().getLonLatFromViewPortPx(pixel);
                  popup = new OpenLayers.Popup.FramedCloud("chicken",
                    lonlat,
								    null,
								    "<div class='msg' align='center'>" + titlestring + ":"+columnstring+"</div>" ,
								    null, true,popupClose );
                    popup.autoSize = true;
					          popup.minSize = new OpenLayers.Size(150, 70);
                    popup.maxSize = new OpenLayers.Size(200, 200);
					          popup.panMapIfOutOfView = true;
					          map.addPopup(popup);
                }
              },
              error: function(request,err) {
                //console.log(err);
                jQuery.unblockUI();
                alert('Error loading document');
              },
              success: function(resp) {
                //get the feature id
                var firstindex = resp.indexOf('Feature');
                var featureindex = resp.indexOf('Feature',firstindex+1);
                if (featureindex != -1) {
                  firstindex = resp.indexOf(':');
                  var colonindex = resp.indexOf(':',firstindex+1);
                  fidstring = resp.substring(featureindex,colonindex);
                }
                //get the title column value
                var titleindex = resp.indexOf(title+' =');
                var colindex = resp.indexOf(column+' =');
                if (titleindex != -1 && colindex != -1) {
                  var secindex = resp.indexOf('=',titleindex+1);
                  titlestring = resp.substring(secindex+1,colindex);
                  var index = resp.indexOf('=',colindex+1);
                  columnstring = resp.substring(index+1,resp.length);
                }
              }
            });
           });
         symbologyLayer.events.register(controls['double'],symbologyLayer , function (e){});
      //}
}

function popupClose(){
  if (map.popups.length>0) {
    map.removePopup(map.popups[0]);
  }
}
function RGBtoHex(R,G,B) {
  return toHex(R)+toHex(G)+toHex(B)
}

function toHex(N) {
  if (N==null) return "00";
    N=parseInt(N); if (N==0 || isNaN(N)) return "00";
    N=Math.max(0,N); N=Math.min(N,255); N=Math.round(N);
    return "0123456789ABCDEF".charAt((N-N%16)/16)
        + "0123456789ABCDEF".charAt(N%16);
}

function highlightPolygon(layer_tablename,polygonid){
  var layer_tablename = layer_tablename.replace('choropleth_','');
  var layername = layer_tablename +'_select';
  var mapfile = layer_tablename+'.map';
  selectpolywms = new OpenLayers.Layer.WMS(
    layername,
    MapServerURL +"?pid="+polygonid+"&",
    {
      map: mapfile,
      transparent: 'true', layers:layername,
      format: 'image/png',
      reproject:false,
      units: "m"
    },
    {singleTile: true}
  );
  map.addLayer(selectpolywms);
  var maplayers = map.layers.length-2;
  var wmslayer = map.layers[maplayers];
  //alert(wmslayer.name);
  map.raiseLayer(wmslayer,maplayers);
}
