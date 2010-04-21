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

function getUrlParam(name, afterHash)
{
  if (afterHash == null) {
  	afterHash = false;
  }
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var queryString = window.location.href;
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  if (afterHash) {
  	var indx = queryString.indexOf("#");
  	queryString = queryString.substring(indx);
  	regexS = "[\\#&]"+name+"=([^&]*)";
  }
  var regex = new RegExp( regexS );
  var results = regex.exec( queryString );
  if( results == null )
    return "";
  else
    return results[1];
}
