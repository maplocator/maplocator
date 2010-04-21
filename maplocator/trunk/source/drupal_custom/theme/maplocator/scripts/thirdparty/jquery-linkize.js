// create closure
(function($) {
  // plugin definition
  /* Function to parse HTML text and convert URLs to HTML links. */
  /* Author: Viraj Kanwade */
  /* Parameter: the root element under which all HTML is to be linkized. */
  /*
    The function parses child elements of root element.
      If the child node is an anchor, ignore it.
      If the child node is text element, getLinks function is called
        which parses HTML text and generated links.
      If the child node is any other node, the linkize function is called
        recurrsively.
  */
  $.fn.linkize = function() {
    var elems = this.contents().not($("a"));
    var len = elems.length;
    for(var i=0; i < len; i++) {
      var elem = elems[i];
      if(elem.nodeType == 3) {
        /* TODO: use regex
        var url_match = /https?:\/\/([-\w\.]+)+(:\d+)?(\/([\w/_\.]*(\?\S+)?)?)?/;
        var m = url_match.exec(elem.textContent);
        console.log(m);
        */
        $(elem).replaceWith(genLinks(elem.data));
      } else if(elem.tagName != "A") {
        $(elem).linkize();
      }
    }
  };

  /* Function to parse HTML text and convert URLs in text to HTML links */
  /* Author: Viraj Kanwade */
  /* Parameter: HTML text to be parsed */
  /* Returns: HTML text which has been linkized */
  /*
    The function searches for URLs in the text provided. If no URL is found,
      it returns the original text. If a URL is found, it is linkized and the
      remaining string is passed recurrsively to the function.
  */
  function genLinks(txt) {
    if(typeof txt == "undefined") {
      return "";
    }
    var http = "";
    var pos = -1;
    var pos1 = txt.indexOf("http://"); //get the first occurrence of HTTP:// in text
    var pos2 = txt.indexOf("https://"); //get the first occurrence of HTTPS:// in text
    var pos3 = txt.indexOf("www."); //get the first occurrence of WWW. in text

    // find which of the three occurs first
    if(pos1 > -1) {
      pos = pos1;
    }
    if(pos2 > -1) {
      if(pos == -1 || pos2 < pos) {
        pos = pos2;
      }
    }
    if(pos3 > -1) {
      if(pos == -1 || pos3 < pos) {
        pos = pos3;
        http = "http://"; // since HTTP:// is missing from the URL (www.), it needs to be added in anchor.
      }
    }

    if(pos != -1) { // URL found
      var txt2 = txt.substring(0, pos);
      pos1 = txt.indexOf(" ", pos); // Space is assumed to be the delimiter for URL
      if(pos1 == -1) {
        pos1 = txt.indexOf(String.fromCharCode(160), pos);
      }
      if(pos1 == -1) { // If space is not found, it can be assumed that it is the end of text.
        var txt3 = txt.substring(pos);
        txt2 += '<a href="' + http + txt3 + '" target="_blank">' + txt3 + '</a>';
      } else {
        /*
          Space is found. So it can be assumed that there is more text. Linkize the URL extracted and
          pass the remaining text for a recurrsive call to the function.
        */
        var txt3 = txt.substring(pos, pos1);
        txt2 += '<a href="' + http + txt3 + '" target="_blank">' + txt3 + '</a>';
        txt2 += genLinks(txt.substring(pos1));
      }
      return txt2; // Return linkized text.
    } else { // No URL found in text. Return original text.
      return txt;
    }
  }

// end of closure
})(jQuery);
