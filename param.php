<?php

$username     = get_option( 'kpg_username' );
$picEngine    = get_option( 'kpg_picEngine' );
$albumPerPage = get_option( 'kpg_albumPerPage' );
$albumPerRow  = get_option( 'kpg_albumPerRow' );
$photoPerPage = get_option( 'kpg_photoPerPage' );
$photoPerRow  = get_option( 'kpg_photoPerRow' );
$thumbnailSize = get_option( 'kpg_thumbnailSize' );

// See if the user has posted us some information
// If they did, this hidden field will be set to 'Y'
if( $_POST[ 'kpg_save' ] == 'Y' )
{
	// Read their posted value
	$username     = $_POST[ 'kpg_username' ];
	$picEngine    = $_POST[ 'kpg_picEngine' ];
	$albumPerPage = intval( $_POST[ 'kpg_albumPerPage' ] );
	$albumPerRow  = intval( $_POST[ 'kpg_albumPerRow' ] );
	$photoPerPage = intval( $_POST[ 'kpg_photoPerPage' ] );
	$photoPerRow  = intval( $_POST[ 'kpg_photoPerRow' ] );
	$thumbnailSize  = intval( $_POST[ 'kpg_thumbnailSize' ] );
	
	$picEngine    = in_array($picEngine, array('lightbox', 'highslide', '')) ? $picEngine : 'highslide';
	$albumPerPage = $albumPerPage > 0 ? $albumPerPage : 0;
	$albumPerRow  = $albumPerRow  > 0 ? $albumPerRow  : 1;
	$photoPerPage = $photoPerPage > 0 ? $photoPerPage : 0;
	$photoPerRow  = $photoPerRow  > 0 ? $photoPerRow  : 2;

	// Save the posted value in the database
	update_option( 'kpg_username',     $username );
	update_option( 'kpg_picEngine',    $picEngine );
	update_option( 'kpg_albumPerPage', $albumPerPage );
	update_option( 'kpg_albumPerRow',  $albumPerRow );
	update_option( 'kpg_photoPerPage', $photoPerPage );
	update_option( 'kpg_photoPerRow',  $photoPerRow );
	update_option( 'kpg_thumbnailSize',  $thumbnailSize );
	
	// Put an options updated message on the screen
	print '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';
}

$albumPerPage = $albumPerPage > 0 ? $albumPerPage : '';
$photoPerPage = $photoPerPage > 0 ? $photoPerPage : '';

// Now display the options editing screen
print '<div class="wrap">';
print '<h2>kPicasa Gallery Plugin Options</h2>';

// Form
print '<form name="form1" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
print '<input type="hidden" name="kpg_save" value="Y">';

// General settings
print '<h3>General settings</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">Picasa Web Albums Username:</th>';
print '<td><input name="kpg_username" type="text" id="kpg_username" value="'.htmlentities($username).'" size="40" /></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">Engine to show full-sized pictures:</th>';
$chk = $picEngine == 'highslide' ? ' checked="checked"' : '';
print '<td><input type="radio" name="kpg_picEngine" value="highslide" id="kpg_picEngine_highslide"'.$chk.'> <label for="kpg_picEngine_highslide">Highslide</label> (<a href="http://vikjavev.no/highslide/" target="_blank">visit homepage</a>)<br/>';
$chk = $picEngine == 'lightbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="lightbox" id="kpg_picEngine_lightbox"'.$chk.'> <label for="kpg_picEngine_lightbox">Lightbox</label> (<a href="http://www.huddletogether.com/projects/lightbox2/" target="_blank">visit homepage</a>)<br />';
$chk = $picEngine == '' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="" id="kpg_picEngine_none"'.$chk.'> <label for="kpg_picEngine_none">None</label> (I already have some other kind of mechanism)</td>';
print '</tr>';

print '</table>';

// Album List
print '<h3>Album List</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">Number of albums to show per page:</th>';
print '<td><input name="kpg_albumPerPage" type="text" id="kpg_albumPerPage" value="'.$albumPerPage.'" size="3" />';
print '<br/>Leave empty to show all albums on the same page</td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">Number of albums to show per row:</th>';
print '<td><input name="kpg_albumPerRow" type="text" id="kpg_albumPerRow" value="'.$albumPerRow.'" size="3" /></td>';
print '</tr>';

print '</table>';

// Picture List
print '<h3>Picture List</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">Number of pictures to show per page:</th>';
print '<td><input name="kpg_photoPerPage" type="text" id="kpg_photoPerPage" value="'.$photoPerPage.'" size="3" />';
print '<br/>Leave empty to show all pictures on the same page</td>';
print '</tr>';
print '<tr valign="top">';
print '<th scope="row">Number of pictures to show per row:</th>';
print '<td><input name="kpg_photoPerRow" type="text" id="kpg_photoPerRow" value="'.$photoPerRow.'" size="3" /></td>';
print '</tr>';

print '</table>';

// Thumbnail size
print '<h3>Thumbnail size</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">Select the size for photo thumbnails:</th>';
print '<td>';
print '<select name="kpg_thumbnailSize" id="kpg_thumbnailSize" size="1">';
if ($thumbnailSize != null && $thumbnailSize != "") { 
	print '<option value="'.$thumbnailSize.'">'.$thumbnailSize.'px height</option>'; 
}
if ($thumbnailSize != "72") { print '<option value="72">72px height</option>'; }
if ($thumbnailSize != "144") { print '<option value="144">144px height</option>'; }
if ($thumbnailSize != "288") { print '<option value="288">288px height</option>'; }
print '</select>';
print '</td>';
print '</tr>';

print '</table>';
print '<p class="submit">';
print '<input type="submit" name="Submit" value="Save Changes" />';
print '</p>';

print '</form>';
print '</div>';

?>
