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
 Contains Comman utility functions
***/

var DEFAULT_LAYERTREE_OPT = 1;
var SEARCH_OPT = 4;
var VALIDATION_TAB_OPT = 5;

var NO_PARTICIPATION = 0;
var RESTRICTED_PATICIPATION = 1;
var PUBLIC_PARTICIPATION = 2;
var SANDBOX_PARTICIPATION = 3;

var user_id;

function setMainDivSize() {
  var minMainWidth = 600;

  var mainWidth = jQuery(window).width() - 270;
  if(mainWidth < minMainWidth) {
    mainWidth = minMainWidth;
  }

  jQuery("#main").css("width", mainWidth);
}

var calculate = function () {
	var winWidth = jQuery(window).width();
	var winHeight = jQuery(window).height();

	jQuery("#wrapper").width(winWidth);
	jQuery("#wrapper").height(winHeight);

	jQuery("#mapArea").width(winWidth);
	jQuery("#mapArea").height(winHeight - 100);

  jQuery("#dataOnDemand").css("height",(jQuery("#dataOnDemand").parent().height()-60));
}

function setSizes() {
  jQuery("#fLayerSelector").css("height",(jQuery("#layerSelectPane").height()-60));
  jQuery("#fLayerOrdering").css("height",(jQuery("#layerOrderPane").height()-60));
}

function linkIcons() {
  jQuery(".panelToggler").click(function(){
    control = jQuery(this).attr("id") + "";
    jQuery(this).blur();
    control = control.substring(0,(control.length - 2));
    jQuery("#" + control + "Pane").toggle(400);
    return false;
  });

  jQuery(".flashPanelToggler").click(function(){
    control = jQuery(this).attr("id");
    jQuery(this).blur();
    control = control.substring(0,(control.length - 2));
    toggleFlashPopup(control + "Pane");
    return false;
  });

  jQuery("#advanced .tool").hover(
    function(){
      jQuery(".optional").show();
    },
    function(){
      jQuery(".optional").hide();
    }
  );

  jQuery("#resetTreeViewControl").click(function(){
    resetTreeView();
    RemoveCheckedLayers();
  });

  //
  jQuery("#uploadData").click(function(){
    uploadData();
  });

   //contribute button
   jQuery("#btn_contribute").click(function(){
       contribute();
  });

  // Multi Layer Search
  jQuery("#btn_search").click(function(){
       multiLayerSearch();
  });

  //download layer
  jQuery("#downloadLayer").click(function(){
       getDownloadFormats();
  });

  jQuery("#symbologylink").click(function(){
      showSymbology();
  });
  jQuery("#lnkmeasurement").click(function(){
      showMeasurementTool();
  });
  jQuery("#login").click(function(){
    jQuery("#userLogin").toggle(400);
  });

  jQuery("#loginCloseButton").click(function(){
    jQuery("#userLogin").hide(400);
  });

  jQuery("#closeMessage").click(function(){
    jQuery("#messages").hide(400);
  });

}

function makePanelsResizable() {
  jQuery(".resizeablePane").resizable({
    alsoResize: jQuery(this).attr("id") + " .displayPane",
    minWidth: 200,
    minHeight: 200
  });
  jQuery("#layerSelectPane").resizable({
    alsoResize: "#layerTree",
    minWidth: 530,
    minHeight: 260,
    resize: function(event, ui) {
    }
  });

  jQuery("#layerOrderPane").resizable({
    alsoResize: "#fLayerOrdering",
    minWidth: 300,
    minHeight: 200
  });
}

function makePanelsDraggable() {
  jQuery(".mapPane").draggable({handle: ".dragHandle"});
}

function showAdvancedToolSet(show) {
  if(show == true) {
    jQuery("#advancedToolSet").css("display", "block");
  } else {
    jQuery("#advancedToolSet").css("display", "none");
  }
}

//function setActiveLayerInfo(layer_tablename, layer_name, layericon, bbox) {
function setActiveLayerInfo(obj_layerInfo) {
  var layer_tablename = obj_layerInfo.layer_tablename;
  var layer_name = obj_layerInfo.layer_name;
  var layericon = obj_layerInfo.icon;
  var bbox = obj_layerInfo.extent;
  var is_timebased = parseInt(obj_layerInfo.is_timebased);

  // Layer name link
  jQuery('#activeLayer div a')[1].innerHTML = layer_name;
  jQuery(jQuery('#activeLayer div a')[1]).attr("href", "javascript:getLayerMetadata('" + layer_tablename + "');");

  // Layer info icon
  jQuery("#anc_LayerInfo").attr("href", "javascript:getLayerMetadata('" + layer_tablename + "');");

  // Charts icon
  jQuery("#anc_LayerCharts").attr("href", "javascript:showCharts('" + layer_tablename + "', '" + layer_name + "', 'layer');");

  // layer icon
  if(layericon == "") {
    jQuery(jQuery('#activeLayer img')[0]).attr("src", "");
    jQuery(jQuery('#activeLayer img')[0]).hide();
  } else {
    jQuery(jQuery('#activeLayer img')[0]).attr("src", layericon);
  }

  // ZTE icon
  //jQuery("#anc_LayerExtent").attr("href", "zoomToExtent('" + bbox + "')");
  jQuery(jQuery('#activeLayer div a')[0]).attr("href", "javascript:zoomToExtent('" + bbox + "');");

  // Time Line
  if (is_timebased) {
    jQuery("#timeline").find("a").attr("href", "javascript:showTimeLineDataUI('" + layer_tablename + "', '" + layer_name + "', 'layer');");
    jQuery("#timeline").show();
  } else {
    jQuery("#timeline").hide();
  }
}

var prevopt ='';
function changeLayerOptions(opt,reload) {
  if ( opt == null) {
    opt = DEFAULT_LAYERTREE_OPT;
  }

  jQuery("#layerGroupType").find("li").removeClass("selected");

  jQuery('#layerOption'+opt).addClass("selected");

  if (opt == SEARCH_OPT || opt == VALIDATION_TAB_OPT) {
    jQuery.cookie('layerOptions', DEFAULT_LAYERTREE_OPT);
  } else {
    jQuery.cookie('layerOptions', opt);
  }

  if (prevopt == '') {
    prevopt = opt;
  }

  if (opt == SEARCH_OPT) {
    jQuery("#search").css("display",'block');
    search_data(null, prevopt, opt);
    jQuery('#btn_contribute').css("display","none");
    jQuery('#downloadLayer').css("display","none");
  } else if (opt == VALIDATION_TAB_OPT) {
      jQuery("#search").css("display",'none');
      ShowLayersForValidation(prevopt,opt);
      if(user_id) {
        jQuery('#btn_contribute').css("display","block");
      }
      jQuery('#downloadLayer').css("display","none");
  } else if (opt == 1 || opt == 2 || opt == 3 || opt == 6) {
    jQuery("#search").css("display",'none');
    getCategory(prevopt,opt);
    if(user_id) {
      jQuery('#btn_contribute').css("display","block");
    }
    jQuery('#downloadLayer').css("display","block");
  }
  if (prevopt != opt) {
    prevopt = opt;
  }
}

var resetTreeView = function() {
  jQuery.cookie('layersChecked', null, {expires: -1});

  jQuery.cookie('themeTree1', null, {expires: -1});

  jQuery.cookie('themeTree2', null, {expires: -1});

  changeLayerOptions(DEFAULT_LAYERTREE_OPT);
}

function showModalPopup(inrHTML, dialogTitle, onCloseCallback, callbackArgs) {
  var lft = tp = ht = wd = 0;
  var mp = jQuery('#map');
  if(mp.length == 0) {
    ht = 400;
    wd = 600;
    lft = 100;
    tp = 100;
  } else {
    ht = mp.height() * 0.8;
    wd = mp.width() * 0.85;
    lft = mp.offset().left + ((mp.width()-wd)/2);
    tp = mp.offset().top + ((mp.height()-ht)/2);
  }

  modalPopup(inrHTML, dialogTitle, ht, wd, lft, tp, onCloseCallback, callbackArgs);
}

function modalPopup(inrHTML, dialogTitle, ht, wd, lft, tp, onCloseCallback, callbackArgs) {
  var divModalPopup = jQuery("#divModalPopup");
  divModalPopup.html(inrHTML);
  divModalPopup.dialog({
    modal: true,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    zIndex: 2001,
    minheight: '275px',
    minwidth: '600px',
    height: ht+'px',
    width: wd+'px',
    maxHeight: ht+'px',
    maxWidth: wd+'px',
    position: [lft, tp],
    title: dialogTitle,
    close: function() {
      divModalPopup.empty();
      if(onCloseCallback) {
        onCloseCallback(callbackArgs);
      }
    },
    resize: onModalPopupResize
  });
  onModalPopupResize();
  divModalPopup.linkize();
}

function onModalPopupResize(ht) {
  var divModalPopup = jQuery('#divModalPopup');
  ht = divModalPopup.height();
  jQuery("#detailsPane").height(ht - 23);
}

function showAjaxLinkPopup(url, title, callbackOnClose, callbackArgs) {

  blockUI();
  lnk = url;
  jQuery.ajax({
    url: lnk,
    type: 'GET',
    error: function(request,err) {
      jQuery.unblockUI();
      alert('Error loading document');
    },
    success: function(resp){
      if(jQuery("#divModalInfo").length > 0) {
        jQuery("#divModalInfo").remove();
      }
      jQuery("body").append("<div id='divModalInfo'></div>");
      jQuery("#divModalInfo").html(resp);
      var lft = tp = ht = wd = 0;
      var mp = jQuery('#map');
      ht = mp.height() * 0.6;
      wd = mp.width() * 0.7;
      lft = mp.offset().left + ((mp.width()-wd)/2);
      tp = mp.offset().top + ((mp.height()-ht)/2);
      jQuery("#divModalInfo").dialog({
        modal: true,
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        height: ht+'px',
        width: wd+'px',
        maxHeight: ht+'px',
        maxWidth: wd+'px',
        position: [lft, tp],
        title: title,
        close: function() {
          jQuery("#divModalInfo").remove();
          if(callbackOnClose) {
            callbackOnClose(callbackArgs);
          }
        }
      });
      jQuery("#divModalInfo").linkize();
      jQuery.unblockUI();
    }
  });
}

function reloadParentTab(args) {
  ul_ID = args[0];
  eval((jQuery("#"+ul_ID).find(".active")[0].firstChild.href).substring(11));
}

function toggleFlashPopup(divID) {
  var curZ = parseInt(jQuery("#"+divID).css("z-index"));
	if(curZ < 1000) {
		showFlashPopup(divID);
	} else {
		hideFlashPopup(divID);
	}
}

function showFlashPopup(divID, z_delta) {
	if(z_delta == null) {
		z_delta = 2000;
	}
	var curZ = parseInt(jQuery("#"+divID).css("z-index"));
	jQuery("#"+divID).css("z-index", curZ + z_delta);
}

function hideFlashPopup(divID, z_delta) {
	var curZ = parseInt(jQuery("#"+divID).css("z-index"));
  if(curZ < 0) {
    return;
  }
	jQuery("#"+divID).css("zIndex", -1);
}

function minimizeFlashPopup(divID, alsoResize) {
  jQuery("#" + divID).height(25);
  jQuery("#" + divID).resizable('disable');
  jQuery("#" + alsoResize).height(0);
}

function maximizeFlashPopup(divID, height, alsoResize) {
  jQuery("#" + divID).height(height);
  jQuery("#" + divID).resizable('enable');
  jQuery("#" + alsoResize).parent().height("100%");
  jQuery("#" + alsoResize).height(jQuery("#" + alsoResize).parent().height()-60);
}

function addLayerOrderingElemAtTop(id, label) {
	var obj = new Object();
	obj.id = id;
	obj.label = label;
	if(FABridge.FAB_LayerOrdering) {
		var fLayerOrdering = FABridge.FAB_LayerOrdering.root();
		fLayerOrdering.addItemAt(0, obj);
	} else {
		alert("Error connecting to the Layer Ordering interface.");
	}
}

function removeLayerOrderingElem(id, label) {
	var obj = new Object();
	obj.id = id;
	obj.label = label;
	if(FABridge.FAB_LayerOrdering) {
		var fLayerOrdering = FABridge.FAB_LayerOrdering.root();
		fLayerOrdering.removeItem(obj);
	} else {
		alert("Error connecting to the Layer Ordering interface.");
	}
}

function clearLayerOrdering() {
	if(FABridge.FAB_LayerOrdering) {
		var fLayerOrdering = FABridge.FAB_LayerOrdering.root();
		fLayerOrdering.clear();
	}
}

function showCharts(layer_tablename, title, infotype, row_id, divid) {
  var flashVars = "";
  flashVars += 'basePath='+base_path;
  flashVars += '&dataFile=ml_data.php';
  flashVars += '&layer_name=' + title;
  flashVars += '&layer_tablename=' + layer_tablename;
  if(infotype == "row") {
    flashVars += '&infotype=row';
    flashVars += '&row_id=' + row_id;
  } else {
    flashVars += '&infotype=layer';
  }

  var src = base_path + "ml_charts/ml_charts.swf";

  var embd = getFlashAppAddScript(src, "fLayerCharts", flashVars);

  if(divid == null) {
    showModalPopup(embd);
  } else {
    jQuery("#"+divid).html(embd);
  }
}


function changeDivWidth(val) {
  if(val == 0) {// change width to original values
    jQuery('#divMultiLayerSearchPopup').parent().parent().width(500);
    jQuery('#divMultiLayerSearchPopup').parent().width(500);
    jQuery('#divMultiLayerSearchPopup').width(468);
  } else { //change width to higher values
  jQuery('#divMultiLayerSearchPopup').parent().parent().width(800);
  jQuery('#divMultiLayerSearchPopup').parent().width(800);
  jQuery('#divMultiLayerSearchPopup').width(768);
  }
}

function getLayersChecked() {
  return layersChecked;
}

function mls_addFeatureid(val, ltname) {
  if(FABridge.FAB_MultiLayerSearch) {
    var f_MultiLayerSearch = FABridge.FAB_MultiLayerSearch.root();
    f_MultiLayerSearch.addFeatureid(val, ltname);
  } else {
    alert("Error connecting to the Multi Layer Search interface");
  }
}

function mls_getSearchIds(layer_tablename) {
  var searchids = "";
  if(FABridge.FAB_MultiLayerSearch) {
    var f_MultiLayerSearch = FABridge.FAB_MultiLayerSearch.root();
    searchids = f_MultiLayerSearch.getSearchIds(layer_tablename);
  } else {
    alert("Error connecting to the Multi Layer Search interface");
  }
  return searchids;
}

function chkFlashVersion() {
  // Major version of Flash required
  var requiredMajorVersion = 10;
  // Minor version of Flash required
  var requiredMinorVersion = 0;
  // Minor version of Flash required
  var requiredRevision = 0;

  // Version check based upon the values defined in globals
  var hasRequestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);

  if(!hasRequestedVersion) {
    var msg = 'The current flash player plugin version is ' + GetSwfVer() + '. For the site to work correctly, please <a href="http://get.adobe.com/flashplayer/" target="blank">click here</a> upgrade to the latest version.';
    modalPopup(msg, "Flash update", 120, 260, 500, 300);
  }
}

function getFlashAppAddScript(src, id, flashvars) {
  var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
  var isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
  var isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;
  var isChromeOrSafari = (navigator.userAgent.indexOf("AppleWebKit") != -1) ? true : false;

  var embd = '';
  if (isIE && isWin && !isOpera) {
    // For IE, use object
    embd = '<object id="' + id + '" name="' + id + '"';
    embd += ' width="100%" height="100%"';
    embd += ' codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab"';
    embd += ' classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">';
    embd += ' <param name="movie" value="' + src + '" />';
    embd += ' <param name="quality" value="high" />';
    embd += ' <param name="bgcolor" value="#869ca7" />';
    embd += ' <param name="allowScriptAccess" value="sameDomain" />';
    if(flashvars != null) {
      embd += ' <param name="FlashVars" value="'+flashvars+'" />';
    }
    embd += ' <param name="wmode" value="transparent" />';
    embd += '</object>';
  } else {
    // For other browsers, user embed
    embd = '<embed id="' + id + '" name="' + id + '"';
    embd += ' width="100%" height="100%"';
    embd += ' src="' + src + '"';
    embd += ' quality="high" bgcolor="#869ca7"';
    embd += ' align="middle"';
    embd += ' play="true"';
    if(flashvars != null) {
      embd += ' FlashVars="'+flashvars+'"';
    }
    embd += ' loop="false"';
    embd += ' quality="high"';
    embd += ' allowScriptAccess="sameDomain"';
    embd += ' type="application/x-shockwave-flash"';
    embd += ' pluginspage="http://www.adobe.com/go/getflashplayer"';
    embd += ' wmode="transparent"';
    if(isChromeOrSafari) { // For some reason this needs to be done for Chrome.
      embd += '/>';
    } else {
      embd += ' >';
      embd += '</embed>';
    }
  }

  //document.write(embd);
  return embd;
}

//called from flash, depending upon the tab - the variable is set for feature popup to show or not.
function setFeatureClick(val,search_type) {
  if (search_type!=null) {
  	  switch(search_type){
  	  		case 0:
  	  			 isMultiLayerSearchON = false;
  	  			break;
  	  		case 1:
  	  			isMultiLayerSearchON = true;
  	  			break;
  	  		case 2:
  	  			// for bbox search we don't require to set this flag
				isMultiLayerSearchON = false;
  	  			mls_DrawBBOX();

  	  	} // switch

  }
}

function AdjustWidthForSearch(val) {
  if(val == 0) {// change width to original values
    jQuery('#divMultiLayerSearchPopup').parent().parent().width(800);
  	jQuery('#divMultiLayerSearchPopup').parent().width(800);
  	jQuery('#divMultiLayerSearchPopup').width(768);
  } else { //Minimize for search
  	jQuery('#divMultiLayerSearchPopup').parent().parent().width(190);
    jQuery('#divMultiLayerSearchPopup').parent().width(190);
    jQuery('#divMultiLayerSearchPopup').width(175);
  }
}

function multiLayerSearch() {
  var old_opt = CurrentTabOption;
  CurrentTabOption = SEARCH_OPT;
  var flashVars = "";
  flashVars += 'bridgeName=FAB_MultiLayerSearch';
  flashVars += '&basePath='+base_path;
  flashVars += '&dataFile=ml_orchestrator.php';
  flashVars += '&layersChecked=' + layersChecked;
  flashVars += '&bbox=' + getBBOX();

  var src = base_path + "MultiLayerSearch/MultiLayerSearch.swf";

  var embd = getFlashAppAddScript(src, "fmultiLayerSearch", flashVars);

  jQuery("#divMultiLayerSearchPopup").html(embd);
  jQuery('#divMultiLayerSearchPopup').dialog({
    modal: false,
    width:525,
    height: 530,
    maxWidth: 800,
    maxHeight: 530,
    zIndex: 2004,
    position:[3,100],
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    close: function() {
      CurrentTabOption = old_opt;
      isMultiLayerSearchON = false;
      var tmpStr = "";
      removeBBOX();
      mls_RemoveFromMap();
      if(FABridge.FAB_MultiLayerSearch) {
        var f_MultiLayerSearch = FABridge.FAB_MultiLayerSearch.root();
        tmpStr = f_MultiLayerSearch.getchkdSrchLayersList(); // tmpStr stores a string as : layer_tablename#fid1,fid2;layer_tablename#fid1,fid2;..
        var oldLayersChecked = "";
        if(jQuery.cookie("layersChecked"))
          oldLayersChecked = jQuery.cookie("layersChecked").split(":");
        var temp1 = tmpStr.split(";");
        for (var i = 0; i < temp1.length; i++) {
          var temp2 = temp1[i].split("#");
          if("" != temp2[0]){
            if(jQuery.inArray(temp2[0], oldLayersChecked) == -1) {
              getData_Category(temp2[0], false, temp2[1]);
            } else {
              getData_Category(temp2[0], true);
            }
          }
        }
      } else {
        alert("Error connecting to the Multi Layer Search interface");
      }
    }
  });
}
