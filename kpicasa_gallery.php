<?php
/*
Plugin Name: kPicasa Gallery PHP4MOD
Description: Display your Picasa Web Galleries in a post or in a page.
Version: 0.1.3
Author: David Trattnig
Author URI: http://www.bitfusion.org/blog

Version History
---------------------------------------------------------------------------
2007-12-19	0.0.4	Modifications for PHP4 (based on kPicasa Gallery 0.0.4)
2008-01-12  0.0.5	Modifications for PHP4 (based on kPicasa Gallery 0.0.5)
2008-01-24  0.0.6	Removed obsolete debug information, fixed counter issue noticed by Darin
2008-02-13  0.1.3   Modifications for PHP4 (based on kPicasa Gallery 0.1.3),
                    Added Configuration Property to set thumbnail size.
2008-02-26	0.1.4	Upgrade to kPicasa Gallery 0.1.4
					Fixed Image Summary Issue
					Fixed Advanced Album Range Feature

TODO
---------------------------------------------------------------------------


Licence
---------------------------------------------------------------------------
    Copyright 2007  David Trattnig (david -at- bitfusion.org), based on kPicasa Gallery from Guillaume Hébert  (email : kag -at- boloxe.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


require_once("clsParseXML.php");

if ( !defined(KPICASA_GALLERY_DIR) )
{
	define('KPICASA_GALLERY_DIR', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)));
}

$kpg_picEngine = get_option( 'kpg_picEngine' );

if ( function_exists('is_admin') )
{
	if ( !is_admin() )
	{
		if ( function_exists('add_action') )
		{
			add_action('wp_head', 'initKPicasaGallery');
		}
		if ( function_exists('add_filter') )
		{
			add_filter('the_content', 'loadKPicasaGallery');
		}
		
		if ( function_exists('wp_enqueue_script') )
		{
			if ( $kpg_picEngine == 'lightbox' )
			{
				wp_enqueue_script('lightbox2', KPICASA_GALLERY_DIR.'/lightbox2/js/lightbox.js', array('prototype', 'scriptaculous-effects'), '2.03.3');
			}
			elseif ( $kpg_picEngine == 'highslide' )
			{
				wp_enqueue_script('highslide', KPICASA_GALLERY_DIR.'/highslide/highslide.js', array(), '1.0');
			}
		}
	}
	else
	{
		if ( function_exists('add_action') )
		{
			add_action('admin_menu', 'adminKPicasaGallery');
		}
	}
}

function initKPicasaGallery()
{
	global $kpg_picEngine;
	$baseDir = get_bloginfo('wpurl').KPICASA_GALLERY_DIR;

	print "<link rel='stylesheet' href='$baseDir/kpicasa_gallery.css' type='text/css' media='screen' />";

	if ( $kpg_picEngine == 'lightbox' )
	{
		$lightboxDir = "$baseDir/lightbox2";
		print "<link rel='stylesheet' href='$lightboxDir/css/lightbox.css' type='text/css' media='screen' />";

		print '<script type="text/javascript">';
		print "	fileLoadingImage = '$lightboxDir/images/loading.gif';";
		print "	fileBottomNavCloseImage = '$lightboxDir/images/closelabel.gif';";
		print '</script>';
	}
	elseif ( $kpg_picEngine == 'highslide' )
	{
		$highslideDir = "$baseDir/highslide";
		print '<script type="text/javascript">';
		print "	hs.graphicsDir = '$highslideDir/graphics/';";
		print "	hs.showCredits = false;";
		print "	hs.outlineType = 'rounded-white';";
		print "if (hs.registerOverlay) {";
			print "	hs.registerOverlay( { thumbnailId: null, overlayId: 'controlbar', position: 'top right', hideOnMouseOut: true } );";
		print "}";
		print '</script>';
		print "<link rel='stylesheet' href='$highslideDir/highslide.css' type='text/css' media='screen' />";
	}
}

function loadKPicasaGallery ( $content = '' )
{
	$tmp = strip_tags(trim($content));
	$regex = '/^KPICASA_GALLERY[\s]*(\(.*\))?$/';

	if ( 'KPICASA_GALLERY' == substr($tmp, 0, 15) && preg_match($regex, $tmp, $matches) )
	{
		ob_start();
		$showOnlyAlbums = array();
		$username = null;
		if ( isset($matches[1]) )
		{
			$args = explode(',', substr( substr($matches[1], 0, strlen($matches[1])-1), 1 ));
			
			
			if ( count($args) > 0 )
			{
				foreach( $args as $value )
				{
					$value = str_replace(' ', '', $value);
					if ($username == null && 'username:' == substr($value, 0, 9) && strlen($value) > 9)
					{
						$username = substr($value, 9);
					}
					else
					{
						$showOnlyAlbums[] = $value;
					}
				}
			}
		}

		require_once('kpg.class.php');
		$gallery = new KPicasaGallery($username, $showOnlyAlbums);
		return ob_get_clean();
	}

	return $content;
}

function adminKPicasaGallery()
{	
	if ( function_exists('add_options_page') )
	{
		add_options_page('kPicasa Gallery Plugin Options', 'kPicasa Gallery', 8, KPICASA_GALLERY_DIR.'/param.php');
	}
}

?>
