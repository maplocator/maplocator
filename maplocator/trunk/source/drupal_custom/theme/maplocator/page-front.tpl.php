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
 Contains code to generate UI for front page
***/

// $Id: page.tpl.php,v 1.18 2008/01/24 09:42:53 goba Exp $
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
	<head>
		<title>Map Locator</title>
    <meta name="robots" content="noarchive"> <!-- No caching of page in Google. http://www.google.com/support/webmasters/bin/answer.py?answer=35306&topic=13511 -->
		<?php print $head; ?>
		<?php print $styles; ?>
		<?php print $scripts; ?>
		<?php flush(); ?>
	</head>

	<body id="home">
		<!-- Wrapper -->
		<div id="homeWrapper">

			<!-- Main menu -->
			<div id="menus">
				<div id="homeMainMenu">
					<?php if($main_menu): ?>
					<?php print $main_menu; ?>
					<?php endif; ?>
				</div>
				<div id="homeUserMenu">
					<ul>
					<?php global $user; ?>
					<?php if($user->uid): ?>
					<li>Welcome <?php echo l($user->name, "user");?></li>
						<?php if($is_admin): ?>
						<li><a href="<?php print check_url($front_page);?>admin">Administration</a></li>
						<?php endif; ?>
					<li class="last"><a href="<?php print check_url($front_page);?>logout">Logout</a></li>
					<?php else: ?>
					<li><a href="<?php print check_url($front_page);?>user">Login</a></li>
					<li class="last"><a href="<?php print check_url($front_page);?>user/register">Register</a></li>
					<?php endif; ?>
					</ul>
				</div>
			</div>
			<!-- Main menu ends -->

			<!-- Branding -->
			<div id="homeBranding" style="width:0px;">
				<!-- Logo -->
				<div id="logo">
					<a href="<?php print check_url($front_page)?>">
						<!--<img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/map-logo.gif" alt="logo"/>-->
						<div></div>
					</a>
				</div>
				<!-- Logo ends -->
			</div>
			<!-- Branding ends -->


			<!-- Home Container -->
			<div id="homeContainer">


				<!-- Left Sidebar -->
				<div id="homeLeft" class="column">
					<?php if($home_left): ?>
						<?php print $home_left; ?>
					<?php endif;?>
				</div>
				<!-- Left Sidebar ends -->

				<!-- Content -->
				<div id="homeContent" class="column">

					<div id="explore">
						<a href="<?php print check_url($front_page)?>map" class="exploreMap">
							<!--<img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/begin-exploring.gif" alt="Explore"/>-->
							<div></div>
						</a>
					</div>

					<?php if ($tabs): print '<div id="tabs-wrapper" class="clear-block">'; endif; ?>
					<?php if ($title): print '<h2'. ($tabs ? ' class="with-tabs"' : '') .'>'. $title .'</h2>'; endif; ?>
					<?php if ($tabs): print '<ul class="tabs primary">'. $tabs .'</ul></div>'; endif; ?>
					<?php if ($tabs2): print '<ul class="tabs secondary">'. $tabs2 .'</ul>'; endif; ?>
					<?php if ($show_messages && $messages): print $messages; endif; ?>
					<?php print $help; ?>

					<?php print $content;?>
				</div>
				<!-- Content ends -->

				<!-- Right sidebar -->
				<div id="homeRight" class="column">
					<?php if($home_right): ?>
						<?php print $home_right;?>
					<?php endif; ?>
				</div>
				<!-- Right Sidebar ends -->

			</div>
			<!-- Home Container ends -->

			<!-- Footer -->
			<div id="pageFooter">
				<?php if($footer): ?>
				<?php print $footer;?>
				<?php endif; ?>
			</div>
			<!-- Footer ends -->

		</div>
		<!-- Wrapper end -->
		<?php print $closure;?>
    <script language="javascript">
      // Only for the home page
      if(jQuery(window).width() > 1000) {
        jQuery("#homeContent").css("width",(jQuery(window).width() - 537));
      } else {
        jQuery("#homeContent").css("width", 500);
      }

      jQuery(document).ready(function(){
        setMainDivSize();
        chkFlashVersion();
      });
    </script>
	</body>
</html>
