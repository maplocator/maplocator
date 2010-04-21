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
*This file contains various utility functions used by various other parts od code files.
*
***/

// post-submit callback
function showResponse(responseText, statusText)  {
}

function submitForm(formid, target) {
  var options = {
    target:        target,   // target element(s) to be updated with server response
    success:       showResponse  // post-submit callback
  };

  $(formid).ajaxSubmit(options);
}

function AllowOnlyNumbers_KeyDownHandler(oTxtBox, e, bSigned, bAllowDecimal) {

  if(window.event) { // IE
    keynum = e.keyCode;
  } else if(e.which) { // Netscape/Firefox/Opera
    keynum = e.which;
  }

  switch(keynum) {
    case 45://minus
      if((oTxtBox.value != '') || (!bSigned))
        return false;
    break;
    case 46://period
      if((oTxtBox.value.indexOf('.')!=-1) || (!bAllowDecimal))
        return false;
    break;
    case 8://backspace
      return;
    break;
    default:
      if(keynum<48||keynum>57)
        return false;
    break;
  }
}

function db_validate(elem, type, length, is_null) {
  if(!is_null) {
    if(elem.value == '') {
      return false;
    }
  }
  switch(type) {
    case 'smallint':
    break;
    case 'bigint':
    break;
    case 'character varying':
      if(elem.value.length > length) {
      }
    break;
  }
}

/* Function to convert array-like object to array */
/* 'n' is the number of elements you want to skip. So, if you want to skip the first two elements of the collection, then n = 2, and if you do not want to skip any, then n = 0.*/
function convertToArray(obj, n) {
  if (! obj.length) {return [];} // length must be set on the object, or it is not iterable
  var a = [];

  try {
    a = Array.prototype.slice.call(obj, n);
  }
  // IE 6 and posssibly other browsers will throw an exception, so catch it and use brute force
  catch(e) {
    Core.batch(obj, function(o, i) {
      if (n <= i) {
        a[i - n] = o;
      }
    });
  }

  return a;
}

function maskInputs() {
  jQuery(".maskedDateInput").mask("9999-99-99");
  var yRange = '-' + (parseInt((new Date()).getFullYear()) - 1800).toString() + ':0';
  jQuery(".maskedDateInput").datepicker({dateFormat: jQuery.datepicker.W3C, yearRange: (yRange), maxDate: new Date()});
}

function blockUI() {
  jQuery.blockUI({
    overlayCSS: blockUI_overlayCSS,
    css: blockUI_css,
    baseZ: blockUI_z_index
  });
}

function addOption(selectbox, value, text) {
  var optn = document.createElement("OPTION");
  optn.text = text;
  optn.value = value;
  optn.onSelect="SetBaseLayer("+ value + ")";
  selectbox.options.add(optn);
}

// find if user is a validator for any layer. This is currently used to determine if validation tab is to be shown.
function isUserValidatorForAnyLayer(user_roles) {
  for(role in user_roles) {
    var re = new RegExp(".* validator$");
    if (role.match(re)) {
      return true;
    }
  }
  return false;
}

// find if user is a admin for any layer. This is currently used to determine if Inactive layers tab is to be shown.
function isUserAdminForAnyLayer(user_roles) {
  for(role in user_roles) {
    var re = new RegExp(".* admin$");
    if (role.match(re)) {
      return true;
    }
  }
  return false;
}

function getPopupIframe(mydiv, lnk) {
  var ht = jQuery(mydiv).height() - 3;
  mydiv.innerHTML = "<iframe src='"+lnk+"' FRAMEBORDER=0 id='ifrLayerPopup' name='ifrLayerPopup' width='100%' height='" + ht + "px'></iframe>";
}

function getPopupAjaxDiv(mydiv, lnk, onSuccessCallback) {
  blockUI();
  jQuery.ajax({
    url: lnk,
    type: 'GET',
    timeout: 30000,
    error: function(err){
      jQuery.unblockUI();
    },
    success: function(myhtml){
      jQuery.unblockUI();
      if(lnk.search(/getLayerDataSummary/) != -1)
         mydiv.innerHTML = getSummaryPopupHTML(myhtml);
      else if(lnk.search(/getLayerDataDetails/) != -1)
         mydiv.innerHTML = getLayerDetailsPopupHTML(myhtml);
      else if(lnk.search(/editLayerPermissions/) != -1)
         mydiv.innerHTML = getLayerPermissionsHTML(myhtml);
      else if(lnk.search(/getLinkTableEntries/) != -1) {
         mydiv.innerHTML = getLinkTbEntriesHTML(myhtml);
         if(mydiv.innerHTML.indexOf("<thead>") != -1) {
           jQuery("#linkedData").dataTable({
             "sDom": 'lpif<"dod_linked"t>r',
             "anLengthMenu" : new Array(10, 15, 20, 25),
             "fnRowCallback":function(nRow) {
                jQuery(nRow).click(function() {
                  jQuery('.tblRowHighlight').removeClass('tblRowHighlight');
                  jQuery(nRow).addClass('tblRowHighlight');
                });
                return nRow;
              }
           });
          }
      }
      else
         mydiv.innerHTML = myhtml;
      if(onSuccessCallback) onSuccessCallback(myhtml);
      jQuery(mydiv).linkize();
      maskInputs();

    }
  });
}

function popupTabClicked(divid, ulid, tab, lnk) {
  var cur_div = jQuery("#"+divid);
  var mydiv = cur_div[0];

  var menu_lis = jQuery("#"+ulid).find("li");
  var len = menu_lis.length;
  for (var i = 0; i < len; i++) {
    var menu_li = menu_lis[i];
    if(tab == menu_li.id) {
      jQuery("#" + menu_li.id).addClass("active");
    } else {
      jQuery("#" + menu_li.id).removeClass("active");
    }
  }
  if(tab.substring(0, 6) == "drupal") {
    getPopupIframe(mydiv, lnk);
  } else if(tab.substring(0, 10) == "linkedData") {
    getPopupAjaxDiv(mydiv, lnk, genDataTableForLinkedData);
  } else {
    getPopupAjaxDiv(mydiv, lnk);
  }
}

function uploadData(){
  //get list of participative layers
  if (user_id){
    var lnk = base_path+'ml_orchestrator.php?action=getParticipatoryLayers';
    jQuery.ajax({
      url: lnk,
      type: 'GET',
      timeout: 30000,
      error: function(err){
        jQuery.unblockUI();
      },
      success: function(resp){
        var popuphtml = getLayerListPopup(resp);
        jQuery("#divModalPopup").html(popuphtml);
     }
   });
  } else {
      jQuery("#divModalPopup").html("You have to login to upload data");
  }
  jQuery('#divModalPopup').dialog({
    modal: true,
    zIndex: 2004,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    title:"Upload Data"
  });

}

function getLayerListPopup(json_data,action) {
    // load the response
  var jsonObj = eval('(' + json_data + ')');
  var len  = jsonObj.length;
  var popuphtml = '';

  if ('load' == action){
     popuphtml = '<div>Select one of the following layers to add to <br/><br/> <select onChange = "loadSelectedLayer(this.value);">';
  } else {
     popuphtml = '<div>Select one of the following layers to add to <br/><br/> <select onChange = "getUploadForm(this.value);">';
  }
  popuphtml += '<option value="0">Select Layer</option>';
  for(layer in jsonObj){
    popuphtml += '<option value="' + layer + '">' + jsonObj[layer] + '</option>';
  }
  popuphtml += '</select>';
  return popuphtml;
}

function getUploadForm(layer_tablename) {
  var popuphtml = '<form enctype="multipart/form-data" target="uploadframe" action="upload.php" method="POST">';
  popuphtml += 'Please choose a file to upload (only KML,KMZ,GPX): <input name="uploadFile" type="file" /><br />';
  popuphtml += '<input type="hidden" name="layer_name" value="'+layer_tablename+'" />';
  popuphtml += '<iframe src="" name="uploadframe" id="uploadframe" onload="uploadStatus();" style="display:none"/>';
  popuphtml += '<input type="submit" value="Upload" /></form>';
  jQuery("#divModalPopup").html(popuphtml);
}

function uploadStatus() {
  var resp = document.getElementById('uploadframe').contentDocument.body.innerHTML;
  if ("" != resp){
    jQuery("#divModalPopup").html(resp);
  }
}

function showSearchResponse(jsonVar) {
  //treeViewEntryHTML implementation of php starts here
  var inputBox = '<td><div class="LayerTreeElem"><input type="checkbox" name="' + jsonVar.chkBxName + '" title = "Display ' + jsonVar.layer_name + '" id = "' + jsonVar.layer_name + '" value="' + jsonVar.layer_tablename + '" onclick="getData_Category(this.value,this.checked,this.name,this);" ' + jsonVar.checked + '></input></div></td>';
  var layer_info = '<td><div class="LayerTreeElem"><a href="#" title="' + jsonVar.layer_name + ' Information" onClick="javascript:getLayerMetadata(\'' + jsonVar.layer_tablename + '\');"><img alt="" src="' + jsonVar.info_imgUrl + '"/></a></div></td>';
  var prtcptn = "";
  if (jsonVar.prtcptn_imgUrl){
    prtcptn = '<td><div class="LayerTreeElem"><a style="text-decoration: none" title="Participation Info for ' + jsonVar.$layer_name + '" href="javascript:showParticipationInfo(\'' + jsonVar.layer_tablename + '\',' + jsonVar.p_nid + ');"><img id =picon_' + jsonVar.layer_tablename + ' alt="Participate" src="' + jsonVar.prtcptn_imgUrl +'"/></a></div></td>';
  }
  var download = "";
  if (jsonVar.downl_imgUrl){
    download  = '<td><div class="LayerTreeElem"><a href="#" title="Download ' + jsonVar.layer_name + '" onClick="javascript:getDownloadFormats(\'' + jsonVar.layer_tablename + '\');"><img alt="" src="' + jsonVar.downl_imgUrl + '"/></a></div></td>';
  }

  var extent = '<td><div id="img_' + jsonVar.layer_tablename +'" style="display:'+jsonVar.display_extent+'" class="LayerTreeElem"><a href="#" title="Zoom to layer extent" onclick="javascript:zoomToExtent(\''+ jsonVar.layer_extent + '\');"><img src="' + jsonVar.zoomtoext_imgUrl + '" alt="Zoom to layer extent"/></a></div></td>';
    var feature_count_text = "";
  if (jsonVar.feature_count) {
    feature_count_text = '&nbsp;<b color="blue"> (' + jsonVar.feature_count +')</b>';
  }

  var layerNameText = '<td><div class="LayerTreeElem"><a id = "anch_' + jsonVar.layer_tablename + '" href="#" title="Display ' + jsonVar.layer_name + '" onclick = "javascript:toggleLayer(\'' + jsonVar.layer_tablename + '\');">' + jsonVar.layer_name.replace(/ /, "&nbsp;") + feature_count_text + '</a></div></td>';
  var htmldata = '<table cellspacing="0" style="border-collapse:separate;"><tr>' + inputBox + layer_info + prtcptn + download + extent + layerNameText + '</tr></table>';
  // ends here

  return htmldata;
}

function getSummaryPopupHTML(resp) {
  var jsonObj = eval('(' + resp + ')');
  var element = document.getElementById('detailsPane');
  var summaryPopup = '';
  if(!element){
    summaryPopup += '<div id="mlocate_popup">  <div id="featureName">' ;
    summaryPopup += jsonObj['layer_name'] +': '+ jsonObj['feature_title'];
    summaryPopup += '</div>  <div class="popupActions"><ul>';
    summaryPopup += '<li class="last"><a href="javascript:showDetailsPopup(\''+jsonObj['layer_tablename']+'\',\''+jsonObj['feature_id']+'\', \''+jsonObj['layer_name']+': '+jsonObj['feature_title']+'\')">More details...</a></li>';
    summaryPopup += '</ul></div>  <!--<div class="attribution"><p> Created by'+jsonObj['created_by_user']+' on '+jsonObj['created_date'];
    summaryPopup += '<br/>Last modified by'+jsonObj['modified_by_user']+' on '+jsonObj['modified_date'];
    summaryPopup += '</p></div>--> ';
  }
  summaryPopup += '<div class="summary"> <table cellspacing="0"> <tbody>';

  var summary = jsonObj['summary'];
  for(key in summary) {
    summaryPopup += '<tr>';
    summaryPopup += '<td valign="top" class="key">'+key+'</td>';
    summaryPopup += '<td valign="top" class="value">'+summary[key]+'</td>';
    summaryPopup += '</tr>';
  }
  summaryPopup += '</tbody></table></div>';
  summaryPopup += '<!-- <div class="validationIcon"><a title="This data has been validated" href="#"><img alt="Validated" src="validated.png"/></a></div>';
  summaryPopup += '<div class="validation">This data has been validated by <a href="#">User x</a></div>';
  summaryPopup += ' <div class="validationIcon"><a href="#" title="This data has not been validated"><img src="not-validated.png" alt="Not Validated"/></a></div>';
  summaryPopup += '<div class="validation">This data is not validated</div> -->';
  summaryPopup += '<div title="'+jsonObj['isvalidated_msg']+'" class="'+jsonObj['isvalidated']+'">';
  summaryPopup += '</div> <div class="'+jsonObj['show_validate']+'_validate_control">';
  summaryPopup += '<span>Validate: <input type="checkbox" name="validate" value="Validate" onClick="validateFeature(this.checked, \''+jsonObj['layer_tablename']+'\',\''+jsonObj['feature_id']+'\')"></span></div>';
  summaryPopup += '<div class="clear"> </div>';
  summaryPopup += '<div class="layerAttribution">Attribution: <!--<a href="#">--><p title="'+jsonObj['fullattribution']+'">'+jsonObj['attribution']+'</p><!--</a>--> </div>';

  var license = jsonObj['license'];
  if(license['img_size']) {
    summaryPopup += '<div class="license"><!--CC--> <a title="Creative Commons License" href="http://creativecommons.org/licenses/'+license['license']+'/3.0/" target="_blank"><img src="http://i.creativecommons.org/l/'+license['license']+'/3.0/'+license['img_size']+'.png"></img></a><!-- Please get the corresponding link and image from creativecommons.org --></div> </div>';
  } else {
    summaryPopup += '<div class="license"><!--CC--> <b>License: </b>'+license['license']+'<!-- Please get the corresponding link and image from creativecommons.org --></div> </div>';
  }

  return summaryPopup;
}

function getLayerDetailsPopupMenuItemHTML(jsonObj, id, lnk, title) {
  var html = '';
  if(jsonObj[id])
    html = '<li id="'+jsonObj[id]+'"><a href="javascript:popupTabClicked(\''+jsonObj['divId']+'\',\''+jsonObj['ulId']+'\',\''+jsonObj[id]+'\',\''+jsonObj[lnk]+'\')">'+jsonObj[title]+'</a></li>';
  return html;
}

function getLayerDetailsPopupHTML(resp) {
  // To be modified in code restructure
  var jsonObj = eval('(' + resp + ')');
  var element = document.getElementById('detailsPane');
  var detailsPopup = '';
  if(!element){
  detailsPopup += '<div id="detailsPane">';
  detailsPopup += '<div class="tabs"> <ul id="ulPopupUIMenu">';
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'details_id', 'details_lnk', 'details_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'editDetails_id', 'editDetails_lnk', 'editDetails_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'moreDetails_id', 'moreDetails_lnk', 'moreDetails_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'editMoreDetails_id', 'editMoreDetails_lnk', 'editMoreDetails_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'addComments_id', 'addComments_lnk', 'addComments_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'addMoreDetails_id', 'addMoreDetails_lnk', 'addMoreDetails_title');
  if(DEPLOYMENT_FOR != 'IBP') {
    if(jsonObj['charts_id'])
      detailsPopup += '<li id="' + jsonObj['charts_id'] + '"><a href="#" onClick="javascript:jQuery(\'#' + jsonObj['ulId'] + ' li\').removeClass(\'active\');jQuery(\'#' + jsonObj['ulId'] + ' #' + jsonObj['charts_id'] + '\').addClass(\'active\');showCharts(\'' + jsonObj['layer_tablename'] + '\', \'' + jsonObj['layer_name'] + ': ' + jsonObj['feature_title'] + '\', \'row\', \'' + jsonObj['row_id'] + '\', \'' + jsonObj['divId'] + '\');">Plot Charts</a></li>';
  }
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'jpg_file_media_id', 'jpg_file_media_lnk', 'jpg_file_media_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'photos_media_id', 'photos_media_lnk', 'photos_media_title');
  detailsPopup += getLayerDetailsPopupMenuItemHTML(jsonObj, 'graph_media_id', 'graph_media_lnk', 'graph_media_title');
  if(jsonObj['linktable']){
    var linktable = jsonObj['linktable'];
    /* linktable is an array of objects */
    var linktable_len = linktable.length;
    var k=0;
    for(k=0;k<linktable_len;k++){
      detailsPopup += '<li id="linkedData_' + k + '"><a href="javascript:popupTabClicked(\''+jsonObj['divId']+'\',\''+jsonObj['ulId']+'\',\'linkedData_' + k + '\',\''+base_path+'ml_orchestrator.php?action=getLinkTableEntries&layerdata_id='+jsonObj['feature_id']+'&layer_tablename='+jsonObj['layer_tablename']+'&link_tablename='+linktable[k].tablename+'\')">'+ linktable[k].name+'</a></li>';
    }

  }
  detailsPopup += '</ul></div>';
  detailsPopup += '<div id="divPopupPane" class="pane">';
  }

  detailsPopup += '<h3>'+jsonObj['feature_title']+'</h3>';
  detailsPopup += '<div class="attribution"><p>Created by '+jsonObj['created_by_user']+' on '+jsonObj['created_date']+'.';
  detailsPopup += 'Last modified by'+jsonObj['modified_by_user']+' on '+jsonObj['modified_date']+'.</p></div>';
  detailsPopup += '<div class="content"><table cellspacing="0"><tbody>';
  var summary = jsonObj['content'];
  for(key in summary) {
    detailsPopup += '<tr>';
    detailsPopup += '<td valign="top" class="key">'+key+'</td>';
    detailsPopup += '<td valign="top" class="value">'+summary[key]+'</td>';
    detailsPopup += '</tr>';
  }
  detailsPopup += '</tbody></table>';
  detailsPopup += '<!--<div class="validationIcon"><a title="This data has been validated" href="#"><img alt="Validated" src="validated.png"/></a></div>';
  detailsPopup += '<div class="validation">This data has been validated by <a href="#">User x</a></div>-->';
  detailsPopup += '<div title="'+jsonObj['isvalidated_msg']+'" class="'+jsonObj['isvalidated']+'"></div>';
  detailsPopup += '<div class="clear"><!-- --></div>';
  detailsPopup += '</div> <div class="layerAttribution">Attribution:<p title="'+jsonObj['fullattribution']+'">'+jsonObj['attribution']+'</p></div>';

  var license = jsonObj['license'];
  if(license['img_size']) {
    detailsPopup += '<div class="license"><!--CC--> <a title="Creative Commons License" href="http://creativecommons.org/licenses/'+license['license']+'/3.0/" target="_blank"><img src="http://i.creativecommons.org/l/'+license['license']+'/3.0/'+license['img_size']+'.png"></img></a><!-- Please get the corresponding link and image from creativecommons.org --></div>';
  } else {
    detailsPopup += '<div class="license"><!--CC--> <b>License: </b>'+license['license']+'<!-- Please get the corresponding link and image from creativecommons.org --></div> ';
  }

  detailsPopup += '<div style="text-align:center; padding: 5px; text-align: center; font-weight: bold; border-top: 1px solid; border-bottom: 1px solid;"><a onclick="javascript:showAjaxLinkPopup(this.href, this.name);return false;" href="'+base_path+'external_media.php?layer_tablename='+jsonObj['layer_tablename']+'&row_id='+jsonObj['row_id']+'" name="External Media">Fetch Media</a> from image aggregation sites like Flickr and Panaramio</div>';
  detailsPopup += '</div>';
  return detailsPopup;

}

function getLayerPermissionsHTML(resp) {
  var jsonObj = eval('(' + resp + ')');
  if (jsonObj['error'])
    return '<b>'+jsonObj['error']+'</b>';
  var popuphtml = '';
  var roles;
  if(jsonObj['for_role']){
    popuphtml += '<form id="frmEditMLOCATEPerms" method="post" action="'+base_path+'ml_orchestrator.php?action=saveLayerPermissions">';
    popuphtml += '<div id="form_mlocate_error" class="error"></div>';
    popuphtml += '<table border="1" cellpadding="5">';
    popuphtml += '<tr><th>Permissions</th><th>&nbsp;</th></tr>';
    roles = jsonObj['roles'];
    for(key in roles){
      popuphtml += '<tr> <td>'+key+ '</td>';
      popuphtml += '<td><input type="checkbox" name="edit-'+key+'" '+roles[key]+'></td>';
    }
    popuphtml += '</table>';
    popuphtml += '<input type="hidden" name="for_role" value="'+jsonObj['for_role']+'">';
    popuphtml += '<input type="button" value="Submit" onClick="javascript:submitForm(\'#frmEditMLOCATEPerms\',\'#form_mlocate_error\')">';
    popuphtml += '</form>';
  } else {
     popuphtml += '<select onChange="javascript:getPopupAjaxDiv(jQuery(\'#divEditPerms\')[0], \''+jsonObj['lnk']+'\'+this.value);">';
     popuphtml += '<option value="0">Select Role</option>';
     roles= jsonObj['roles'];
     for(key in roles){
      popuphtml += '<option value="'+roles[key]+ '">'+roles[key]+'</option>';
     }
     popuphtml += '</select><br><br>';
     popuphtml += '<div id="divEditPerms"></div>';
  }
  return popuphtml;
}

function getLinkTbEntriesHTML(resp) {
  var jsonObj = eval('(' + resp + ')');
  var popuphtml = '';
  var new_row =0;
  var curr_row = 0;
  var arr = '';
  if (jsonObj['error'])
    return '<b>'+jsonObj['error']+'</b>';

  popuphtml += '<div id ="" class="content linkedContent" style="font:arial">';
  popuphtml += '<b><u>'+jsonObj['description']+'</u></b>';
  if (jsonObj['no_record'])
    popuphtml += '<table id="linkedData"/><center><strong>'+jsonObj['no_record']+'</strong></center>';
  else if(jsonObj['col_names']) {
    popuphtml += '<table id="linkedData">';
    popuphtml += '<thead><tr align=center>';

    /* add the column names to the first row of the table */
    var col_names = jsonObj['col_names'];
    for(key in col_names) {
      popuphtml += '<th align=center>'+ col_names[key] + '</th>';
    }
    popuphtml += '</tr></thead><tbody>';

    /* add the data to the columns row wise.Row count is maintained with key followed by '___' and the row number*/
    var data = jsonObj['data'];
    for(var i=0; i < parseInt(jsonObj['data_count']); i++) {
      var row = data[i];
      popuphtml += '<tr align=center>';
      for(var key in row) {
        popuphtml += '<td align=center>' + row[key] + '</td>';
      }
      popuphtml += '</tr>';
    }
    popuphtml += '</tbody></table>';
  }
  if (jsonObj['add_linked_data_lnk'])
    popuphtml += '<center><a name="Add linked data" href="'+base_path+'ml_orchestrator.php?action=getLinkTableSchema&link_tablename='+jsonObj['link_tablename']+'&linked_column='+jsonObj['linked_column']+'&linked_value='+jsonObj['linked_value']+'" onClick="javascript:showAjaxLinkPopup(this.href, this.name, reloadParentTab, new Array(\'ulPopupUIMenu\'));return false;">Add</a></center>';

  popuphtml += '</div>';

  return popuphtml;
}

function getFlashPopupHTML(layer_tablename, row_id) {
  var src = base_path + "FlashPopup/FlashPopup.swf";
  var flashVars = 'basePath=' + base_path;
  flashVars += '&dataFile=ml_data.php';
  flashVars += '&fileuploaderFile=ml_fileuploader.php'
  flashVars += '&layer_tablename=' + layer_tablename;
  flashVars += '&row_id=' + row_id;

  var html = getFlashAppAddScript(src, "fFlashPopup", flashVars);
  return html;
}

function copyArray(arr) {
  var newarr = new Array();
  for (var x in arr) {
    newarr[x] = arr[x];
  }
  return newarr;
}

function isUserSiteAdmin() {
  if (user_roles[SITE_ADMIN_ROLE]) {
    return true;
  } else {
    return false;
  }
}

function getThemeLayerMappingForLayer(layer_tablename) {
  var obj_mapping;

  var obj_params = new Object();
  obj_params.id = layer_tablename;

  var obj_JSON = new Object();
  obj_JSON.action = "getThemeLayerMapping";
  obj_JSON.params = obj_params;
  var mappingurl = base_path+"ml_json_data.php?json_request="+JSON.stringify(obj_JSON) ;
  jQuery.ajax({
    url:  mappingurl,
    type: 'GET',
    timeout: 30000,
    async: false,
    error: function(request,errstring){
      jQuery.unblockUI();
    },
    success: function(resp) {
      obj_mapping = eval('(' + resp + ')').mapping;
    }
  });
  return obj_mapping;
}

function getLayerInfoForPalette(layer_tablename) {
  var obj_lyr = getLayerInfo(layer_tablename);
  var obj_mapping = getThemeLayerMappingForLayer(layer_tablename);

  var obj = new Object();
  obj.city_id = parseInt(obj_mapping.geographical.theme_id);
  obj.nid = parseInt(obj_lyr.nid);
  obj.layer_type = obj_lyr.layer_type;
  obj.layer_name = obj_lyr.layer_name;

  return obj;
}
//Utility function to get url parameters/arguements
function getUrlParam(name)
{
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec( window.location.href );
  if( results == null )
    return "";
  else
    return results[1];
}
