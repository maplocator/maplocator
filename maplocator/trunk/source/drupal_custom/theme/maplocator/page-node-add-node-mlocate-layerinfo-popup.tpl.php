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
?>

<?php global $base_url;
/***
 Contains code to generate UI for create node - pop-up layer info
***/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
	<head>
		<title><?php print $head_title ?></title>
		<?php print $head ?>
		<?php print $styles ?>
		<?php print $scripts ?>
	</head>
	<body id="popup">
		<div id="popupWrapper">
      <div id="divMessage"></div>
			<?php if($title):?>
				<h4><?php print $title; ?></h4>
			<?php endif; ?>
			<div id="popupContentArea">
				<?php print $content;?>
			</div>
		</div>
		<?php print $closure;?>
    <script type="text/javascript">
      // wait for the DOM to be loaded
      $(document).ready(function() {
        // bind 'myForm' and provide a simple callback function
        var options = {
          target:        '#divMessage',   // target element(s) to be updated with server response
          //beforeSubmit:  showRequest,  // pre-submit callback
          success:       onSuccess  // post-submit callback

          // other available options:
          //url:       url         // override for form's 'action' attribute
          //type:      type        // 'get' or 'post', override for form's 'method' attribute
          //dataType:  null        // 'xml', 'script', or 'json' (expected server response type)
          //clearForm: true        // clear all form fields after successful submit
          //resetForm: true        // reset the form after successful submit

          // $.ajax options can be used here too, for example:
          //timeout:   3000
        };

        // bind form using 'ajaxForm'
        $('#form-node').ajaxForm(options);
      });

      function onSuccess(responseText)  {
        $('.ui-dialog-titlebar-close').click();
      }
    </script>
	</body>
</html>
