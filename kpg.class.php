<?php

if ( !class_exists('KPicasaGallery') )
{
	class KPicasaGallery
	{
		function KPicasaGallery ($username, $showOnlyAlbums)
		{
			$this->username        = $username != null ? $username : get_option( 'kpg_username' );
			$this->nbAlbumsPerPage = get_option( 'kpg_albumPerPage' );
			$this->nbAlbumsPerRow  = get_option( 'kpg_albumPerRow' );
			$this->nbPhotosPerPage = get_option( 'kpg_photoPerPage' );
			$this->nbPhotosPerRow  = get_option( 'kpg_photoPerRow' );
			$this->picEngine       = get_option( 'kpg_picEngine' );
			$this->thumbnailSize   = get_option( 'kpg_thumbnailSize' );
			$this->showOnlyAlbums  = is_array( $showOnlyAlbums ) ? $showOnlyAlbums : array();
			$this->cacheTimeout    = 60 * 60 * 1;

			if ( !strlen( $this->username ) )
			{
				if ( $this->checkError( new WP_Error('kpicasa_gallery-username-required', "<strong>Error:</strong> you must go to the admin section and set your Picasa Web Album Username in the Options section.") ) )
				{
					return false;
				}
			}

			// Set default values, if necessary
			if ( intval( $this->nbAlbumsPerPage ) < 0 )
			{
				$this->nbAlbumsPerPage = 0;
			}
			if ( intval( $this->nbAlbumsPerRow ) < 1 )
			{
				$this->nbAlbumsPerRow = 1;
			}
			if ( intval( $this->nbPhotosPerPage ) < 0 )
			{
				$this->nbPhotosPerPage = 0;
			}
			if ( intval( $this->nbPhotosPerRow ) < 1 )
			{
				$this->nbPhotosPerRow = 2;
			}

			if ( count($this->showOnlyAlbums) == 1 || (isset($_GET['album']) && strlen($_GET['album'])) )
			{
				if ( count($this->showOnlyAlbums) == 1 )
				{
					$album = $this->showOnlyAlbums[0];
					$direct = true;
				}
				else
				{
					$album = $_GET['album'];
					$direct = false;
				}

				if ( $this->checkError( $this->displayPictures($album, $direct) ) )
				{
					return false;
				}
			}
			else
			{
				if ( $this->checkError( $this->displayAlbums() ) )
				{
					return false;
				}
			}
		}

		function displayAlbums()
		{
			//----------------------------------------
			// Get the XML
			//----------------------------------------
			$data = wp_cache_get('kPicasaGallery', 'kPicasaGallery');
			if ( false === $data )
			{
				$url  = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."?kind=album";
				$data = $this->fetch($url);
				if ( is_wp_error($data) )
				{
					return $data;
				}
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery', $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			
			//$xml = @simplexml_load_string($data);
			$xmlparse = &new ParseXML;
            $xml = $xmlparse->GetXMLTree($data);
            
			if ( $xml === false )
			{
				return new WP_Error( 'kpicasa_gallery-invalid-response', "<strong>Error:</strong> the communication with Picasa Web Albums didn't go as expected. Here's what Picasa Web Albums said:<br /><br />".$data );
			}
			
			$xml = $xml["FEED"]["0"];

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpgp']) && intval($_GET['kpgp']) > 1 ? intval($_GET['kpgp']) : 1; // kpgp = kPicasa Gallery Page

			$url = get_permalink();
			if ($page > 1)
			{
				$url = add_query_arg('kpgp', $page, $url);
			}

			if ($this->nbAlbumsPerPage > 0)
			{
				$start = ($page - 1) * $this->nbAlbumsPerPage;
				$stop  = $start + $this->nbAlbumsPerPage - 1;
			}
			else
			{
				$start = 0;
				$stop = count( $xml["ENTRY"] ) - 1;
				if ($stop < 0) { $stop = 0; }
			}

			// Set the class, depending on how many albums per row
			$class = $this->nbAlbumsPerRow == 1 ? 'kpg-thumb-onePerRow' : 'kpg-thumb-multiplePerRow';

			//----------------------------------------
			// Loop through the albums
			//----------------------------------------
			print '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="kpg-albums">';
			$i = 0; $j = 0;
			foreach( $xml["ENTRY"] as $album )
			{
				if ($i >= $start && $i <= $stop &&
				( !count($this->showOnlyAlbums) || in_array((string) $album["GPHOTO_NAME"]["0"]["VALUE"], $this->showOnlyAlbums) ))
				{
					if ($j % $this->nbAlbumsPerRow == 0)
					{
						$remainingWidth = 100;
						if ($j > 0)
						{
							print '</tr>';
						}
						print '<tr>';
					}

					// if last cell of the row, simply put remaining width
					$width = ( ($j+1) % $this->nbAlbumsPerRow == 0 ) ? $remainingWidth : round( 100 / $this->nbAlbumsPerRow );
					$remainingWidth -= $width;
					print "<td width='$width%'>";

					$name      = (string) $album["GPHOTO_NAME"]["0"]["VALUE"];
					$title     = wp_specialchars( (string) $album["TITLE"]["0"]["VALUE"] );
					$summary   = wp_specialchars( (string) $album["SUMMARY"]["0"]["VALUE"] );
					$location  = wp_specialchars( (string) $album["GPHOTO_LOCATION"]["0"]["VALUE"] );
					$nbPhotos  = (string) $album["GPHOTO_NUMPHOTOS"]["0"]["VALUE"];
					$albumURL  = add_query_arg('album', $name);
					$thumbURL  = (string) $album["MEDIA_GROUP"]["0"]["MEDIA_THUMBNAIL"]["0"]["ATTRIBUTES"]['URL'];
					$thumbH    = (string) $album["MEDIA_GROUP"]["0"]["MEDIA_THUMBNAIL"]["0"]["ATTRIBUTES"]['HEIGHT'];
					$thumbW    = (string) $album["MEDIA_GROUP"]["0"]["MEDIA_THUMBNAIL"]["0"]["ATTRIBUTES"]['WIDTH'];

					print "<a href='$albumURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $title)."' class='kpg-thumb $class' /></a>";
					print "<div class='kpg-title'><a href='$albumURL'>$title</a></div>";
					if ( strlen($summary) )
					{
						print "<div class='kpg-summary'>$summary</div>";
					}
					if ( strlen($location) )
					{
						print "<div class='kpg-location'>$location</div>";
					}
					print "<div class='kpg-nbPhotos'>$nbPhotos photo".($nbPhotos > 1 ? 's' : '').'</div>';
					print '</td>';
					$j++;
				}
				$i++;
			}

			// never leave the last row with insuficient cells
			if ($this->nbPhotosPerRow > 0)
			{
				while ($j % $this->nbPhotosPerRow > 0)
				{
					print '<td>&nbsp;</td>';
					$j++;
				}
			}

			print '</tr>';
			print '</table>';
			print '<div style="clear: both;" />&nbsp;</div>';

			//----------------------------------------
			// Paginator
			//----------------------------------------
			$nbItems = count($this->showOnlyAlbums) > 0 ? count($this->showOnlyAlbums) : count($xml["ENTRY"]);
			$this->paginator( $page, 'kpgp', $this->nbAlbumsPerPage, $nbItems );
			return true;
		}

		function displayPictures($album, $direct = false)
		{
			//----------------------------------------
			// Get the XML
			//----------------------------------------
			$data = wp_cache_get('kPicasaGallery_'.$album, 'kPicasaGallery');
			if ( false === $data )
			{
				$url = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."/album/".urlencode($album)."?kind=photo";
				$data = $this->fetch($url);
				if ( is_wp_error($data) )
				{
					return $data;
				}
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery_'.$album, $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			
			//$xml = @simplexml_load_string($data);
			$xmlparse = &new ParseXML;
            $xml = $xmlparse->GetXMLTree($data);
            
			if ( $xml === false )
			{
				return new WP_Error( 'kpicasa_gallery-invalid-response', __("<strong>Error:</strong> the communication with Picasa Web Albums didn't go as expected. Here's what Picasa Web Albums said:<br /><br /> ".$data) );
			}

		 	$xml = $xml["FEED"]["0"];
            array_splice($xml,"ATTRIBUTES",1);
            
			//----------------------------------------
			// Display "back" link
			//----------------------------------------
			if ( !$direct )
			{
				$backURL = remove_query_arg('album');
				$backURL = remove_query_arg('kpap', $backURL);
				print "<div id='kpg-backLink'><a href='$backURL'>&laquo; Back to album list</a></div>";
			}

			//----------------------------------------
			// Display album information
			//----------------------------------------			
			$albumTitle    = wp_specialchars( (string) $xml["TITLE"]["0"]["VALUE"] );
			$albumSummary  = wp_specialchars( (string) $xml["SUBTITLE"]["0"]["VALUE"] );
			$albumLocation = wp_specialchars( (string) $xml["GPHOTO_LOCATION"]["0"]["VALUE"] );
			$albumNbPhotos = (string) $xml["GPHOTO_NUMPHOTOS"]["0"]["VALUE"];
			

			print '<div id="kpg-album-description">';
			print "<div id='kpg-title'>$albumTitle</div>";
			if ( strlen($albumSummary) )
			{
				print "<div id='kpg-summary'>$albumSummary</div>";
			}
			if ( strlen($albumLocation) )
			{
				print "<div id='kpg-location'>$albumLocation</div>";
			}
			print "<div id='kpg-nbPhotos'>$albumNbPhotos photo".($albumNbPhotos > 1 ? 's' : '').'</div>';
			print '</div>';

			//----------------------------------------
			// Prepare Highslide if needed
			//----------------------------------------
			if ( $this->picEngine == 'highslide' )
			{
				print '<div id="controlbar" class="highslide-overlay controlbar">';
				print '<a href="#" class="previous" onclick="return hs.previous(this)" title="Previous (left arrow key)"></a>';
				print '<a href="#" class="next" onclick="return hs.next(this)" title="Next (right arrow key)"></a>';
				print '<a href="#" class="highslide-move" onclick="return false" title="Click and drag to move"></a>';
				print '<a href="#" class="close" onclick="return hs.close(this)" title="Close"></a>';
				print '</div>';
			}

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpap']) && intval($_GET['kpap']) > 1 ? intval($_GET['kpap']) : 1; // kpap = kPicasa Album Page

			if ($this->nbPhotosPerPage > 0)
			{
				$start = ($page - 1) * $this->nbPhotosPerPage;
				$stop  = $start + $this->nbPhotosPerPage - 1;
			}
			else
			{			
				$start = 0;
				$stop = count( $xml["ENTRY"] ) - 1;
				if ($stop < 0) { $stop = 0; }
			}

			//----------------------------------------
			// Loop through the pictures
			//----------------------------------------
			print '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="kpg-pictures">';
			$i = 0; $j = 0;
			foreach( $xml["ENTRY"] as $photo )
			{
				if ($i >= $start && $i <= $stop)
				{
					if ($j % $this->nbPhotosPerRow == 0)
					{
						$remainingWidth = 100;
						if ($j > 0)
						{
							print '</tr>';
						}
						print '<tr>';
					}

					// if last cell, simply put remaining width
					$width = ( ($j+1) % $this->nbPhotosPerRow == 0 ) ? $remainingWidth : round( 100 / $this->nbPhotosPerRow );
					$remainingWidth -= $width;
					print "<td width='$width%'>";

					// determine xml entry for thumbnail size
					if ($this->thumbnailSize == 72) { $thumbIndex = "0"; }
					if ($this->thumbnailSize == 144) { $thumbIndex = "1"; }
					if ($this->thumbnailSize == 288) { $thumbIndex = "2"; }
					
					$summary  = wp_specialchars( (string) $photo["SUMMARY"]["0"]["VALUE"] );			
					$thumbURL = (string) $photo["MEDIA_GROUP"]["0"]["MEDIA_THUMBNAIL"][$thumbIndex]["ATTRIBUTES"]['URL'];
					$thumbH   = (string) $photo["MEDIA_GROUP"]["0"]["MEDIA_THUMBNAIL"][$thumbIndex]["ATTRIBUTES"]['HEIGHT'];
					$thumbW   = (string) $photo["MEDIA_GROUP"]["0"]["MEDIA_THUMBNAIL"][$thumbIndex]["ATTRIBUTES"]['WIDTH'];
					$fullURL  = str_replace('s'.$this->thumbnailSize, 's800', $thumbURL);
					
					if ( $this->picEngine == 'lightbox' )
					{
						if ( strlen($summary) )
						{
							print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
							print "<div class='kpg-summary'>$summary</div>";
						}
						else
						{
							print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
						}
					}
					elseif ( $this->picEngine == 'highslide' )
					{
						if ( strlen($summary) )
						{
							print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' title='".str_replace("'", "&#39;", $summary)."' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
							print "<div class='kpg-summary'>$summary</div>";
							print "<div class='highslide-caption'>$summary</div>";
						}
						else
						{
							print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
						}
					}
					else
					{
						if ( strlen($summary) )
						{
							print "<a href='$fullURL' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
							print "<div class='kpg-summary'>$summary</div>";
						}
						else
						{
							print "<a href='$fullURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
						}
					}
					print '</td>';
					$j++;
				}
				$i++;
			}

			// never leave the last row with insuficient cells
			if ($this->nbPhotosPerRow > 0)
			{
				while ($j % $this->nbPhotosPerRow > 0)
				{
					print '<td>&nbsp;</td>';
					$j++;
				}
			}

			print '</tr>';
			print '</table>';
			print '<div style="clear: both;" />&nbsp;</div>';

			//----------------------------------------
			// Paginator
			//----------------------------------------
			$extraArgs = array('album' => $album);
			if (isset($_GET['kpgp']) && intval($_GET['kpgp']) > 1)
			{
				$extraArgs['kpgp'] = intval($_GET['kpgp']);
			}
			$this->paginator( $page, 'kpap', $this->nbPhotosPerPage, count($xml["ENTRY"]), $extraArgs );
			return true;
		}

		function paginator ($page, $argName, $perPage, $nbItems, $extraArgs = array())
		{
			if ($perPage > 0)
			{
				$nbPage = ceil( $nbItems / $perPage );
				if ($nbPage > 1)
				{
					$url = get_permalink();
					foreach($extraArgs as $key => $value)
					{
						$url = add_query_arg($key, $value, $url);
					}

					print '<div id="kpg-paginator">Page:&nbsp;&nbsp;';
					for($i = 1; $i <= $nbPage; $i++)
					{
						$pageUrl = add_query_arg($argName, $i, $url);
						if ($i == $page)
						{
							print " <span class='kpg-on'>$i</span>";
						}
						else
						{
							print " <a href='$pageUrl'>$i</a>";
						}
					}
					print '</div>';
				}
			}
		}

		function fetch( $url )
		{
			$fopen_failed = false;
			$curl_failed  = false;
			
			// todo: add timeouts?
			
			if ( ini_get('allow_url_fopen') == '1' )
			{
				$data = file_get_contents($url);
				if ($data !== false)
				{
					return $data;
				}
				$fopen_failed = true;
			}
			if ( function_exists('curl_init') )
			{
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec($ch);
				curl_close($ch);
				if ($data !== false)
				{
					return $data;
				}
				$curl_failed = true;
			}
			
			if ($fopen_failed && $curl_failed)
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> your PHP configuration reports that it allows kPicasa Gallery to connect to Picasa Web Albums, but in fact it seems your web host is blocking outgoing requests. Please ask your administrator to allow outgoing requests via file_get_contents() or cURL.") );
			}
			elseif ($fopen_failed)
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> kPicasa Gallery tried to connect to Picasa Web Albums using file_get_contents() and failed. Your web host is probably blocking outgoing requests.") );
			}
			elseif ($curl_failed)
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> kPicasa Gallery tried to connect to Picasa Web Albums using cURL and failed. Your web host is probably blocking outgoing requests.") );
			}
			else
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> your PHP configuration does not allow kPicasa Gallery to connect to Picasa Web Albums. Please ask your administrator to enable allow_url_fopen or cURL.") );
			}
		}

		function checkError( $error )
		{
			if ( is_wp_error($error) )
			{
				//wp_die( $error, 'kPicasa Gallery Error' );
				foreach( $error->get_error_messages() as $message )
				{
					print $message;
				}
				print '<br /><br />';
				return true;
			}
			return false;
		}
	}
}

?>
