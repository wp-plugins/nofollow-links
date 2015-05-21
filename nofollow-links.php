<?php
/*
Plugin Name: Nofollow Links
Plugin URI: http://blog.andrewshell.org/nofollow-links/
Description: Select which links in your blogroll you want to nofollow.
Version: 1.0.9
Author: Andrew Shell
Author URI: http://blog.andrewshell.org/
Text Domain: nofollow-links
Domain Path: /languages/
License: GPL2

Copyright (c) 2013 Andrew Shell  (email : andrew@andrewshell.org)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Define
if (!function_exists('array_combine')) {
	require_once dirname(__FILE__) . '/array_combine.php';

	function array_combine($keys, $values)
	{
		return php_compat_array_combine($keys, $values);
	}
}

function nofollow_links_init()
{
	load_plugin_textdomain( 'nofollow-links', false, 'nofollow-links/languages' );
	add_action( 'admin_menu', 'nofollow_links_admin_menu' );
	add_filter( 'get_bookmarks', 'nofollow_links_get_bookmarks', 10, 2 );
	add_filter( 'pre_option_link_manager_enabled', '__return_true' );
}
add_action( 'plugins_loaded', 'nofollow_links_init' );

function nofollow_links_admin_menu()
{
	add_management_page(
		__( 'Nofollow Links', 'nofollow-links' ),
		__( 'Nofollow Links', 'nofollow-links' ),
		'manage_options',
		'link-nofollow',
		'nofollow_links_manage'
	);
	add_submenu_page(
		'link-manager.php',
		__( 'Nofollow Links', 'nofollow-links' ),
		__( 'Nofollow Links', 'nofollow-links' ),
		'manage_options',
		'link-nofollow',
		'nofollow_links_manage'
	);
}

function nofollow_links_manage()
{
	if (isset($_POST['nofollowbookmarks'])) {
		check_admin_referer('nofollow_links_manage');

		if (isset($_POST['linkcheck']) && is_array($_POST['linkcheck'])) {
			$nofollow = array_combine($_POST['linkcheck'], $_POST['linkcheck']);
		} else {
			$nofollow = array();
		}
		update_option('nofollow_links', serialize($nofollow));
		echo '<div style="background-color: rgb(207, 235, 247);" id="message" class="updated fade"><p>' . sprintf( _n( '%d link marked nofollow.', '%d links marked nofollow.', count( $nofollow ), 'nofollow-links' ), count( $nofollow ) ) . '</p></div>' . "\n";
	}

	$sNofollowLinks = get_option("nofollow_links");
	if (!$sNofollowLinks) {
		$sNofollowLinks = serialize(array());
	}
	if (is_string($sNofollowLinks)) {
		$uNofollowLinks = unserialize($sNofollowLinks);
	} elseif (is_array($sNofollowLinks)) {
		$uNofollowLinks = $sNofollowLinks;
	}

	$links = get_bookmarks();
	?>
	<script type="text/javascript">
	<!--
	function checkAll(form)
	{
		var checkAllChecked = document.getElementById('check-all').checked;
		for (i = 0, n = form.elements.length; i < n; i++) {
			if(form.elements[i].type == "checkbox") {
				form.elements[i].checked = checkAllChecked;
			}
		}
	}
	//-->
	</script>

	<div class="wrap nosubsub">

	<div id="icon-link-manager" class="icon32"><br></div>
	<h2><?php _e( 'Nofollow Links', 'nofollow-links') ?></h2>
	<form id="links" name="pages-form" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=link-nofollow" method="post">

	<p class="search-box">&nbsp;</p>
	<?php if (function_exists('wp_nonce_field')) { wp_nonce_field('nofollow_links_manage'); } ?>

	<div class="tablenav top">
		<div class="alignleft actions">
		<input type="submit" class="button action" name="nofollowbookmarks" id="nofollowbookmarks" value="<?php _e( 'Mark Links Nofollow &raquo;', 'nofollow-links') ?>" />
		</div>
		<br class="clear">
	</div>

	<table class="widefat">
	<thead>
	<tr>
		<th class="manage-column check-column" scope="col"><input type="checkbox" onclick="checkAll(document.getElementById('links'));" id="check-all" /></th>
		<th class="manage-column" width="45%" scope="col"><?php _e( 'Name', 'nofollow-links') ?></th>
		<th class="manage-column" scope="col"><?php _e( 'URL', 'nofollow-links') ?></th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<th class="manage-column check-column" scope="col"><input type="checkbox" onclick="checkAll(document.getElementById('links'));" id="check-all" /></th>
		<th class="manage-column" width="45%" scope="col"><?php _e( 'Name', 'nofollow-links') ?></th>
		<th class="manage-column" scope="col"><?php _e( 'URL', 'nofollow-links') ?></th>
	</tr>
	</tfoot>
	<tbody><?php
	$alt = false;
	foreach ($links as $link) {
		$short_url = str_replace('http://', '', $link->link_url);
		$short_url = str_replace('www.', '', $short_url);
		if ('/' == substr($short_url, -1)) {
			$short_url = substr($short_url, 0, -1);
		}
		if (strlen($short_url) > 35) {
			$short_url = substr($short_url, 0, 32).'...';
		}

		echo "    <tr valign=\"middle\"" . ($alt ? ' class="alternate"' : '') . ">\n";
		echo "        <th class=\"check-column\"><input type=\"checkbox\" name=\"linkcheck[]\" value=\"{$link->link_id}\"" . (isset($uNofollowLinks[$link->link_id]) ? ' checked="checked"' : '') . " /></th>\n";
		echo "        <td><strong>{$link->link_name}</strong><br />" . $link->link_description . "</td>\n";
		echo "        <td><a href=\"{$link->link_url}\" title=\"".sprintf(__( 'Visit %s', 'nofollow-links' ), $link->link_name)."\">{$short_url}</a></td>\n";
		echo "    </tr>\n";

		$alt = !$alt;
	}
	?>
	</tbody>
	</table>

	</form>

	</div>
	<?php
}

function nofollow_links_get_bookmarks($links, $args)
{
	$sNofollowLinks = get_option("nofollow_links");
	if (!$sNofollowLinks) {
		$sNofollowLinks = serialize(array());
	}
	if (is_string($sNofollowLinks)) {
		$uNofollowLinks = unserialize($sNofollowLinks);
	} elseif (is_array($sNofollowLinks)) {
		$uNofollowLinks = $sNofollowLinks;
	}

	if (is_array($links)) {
		foreach (array_keys($links) as $i) {
			if (isset($uNofollowLinks[$links[$i]->link_id])) {
				$links[$i]->link_rel .= ' nofollow';
				$links[$i]->link_rel = trim($links[$i]->link_rel);
			}
		}
	}

	return $links;
}
