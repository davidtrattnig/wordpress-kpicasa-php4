=== kPicasa Gallery PHP4MOD ===
Tags: picasa, gallery, photo, image, picture, php4
Requires at least: 2.2
Tested up to: 2.3.2
Stable tag: trunk

Display your Picasa Web Galleries in a post or in a page.

== Description ==

This plugin displays your Picasa Web Galleries in a post or in a page simply by
creating a post or a page with a special keyword. All the images are kept on the
Picasa Web Gallery server.

**This is a modified version of kPicasa Gallery working with PHP4**

If enabled and properly configured, kPicasa Gallery will use the WP-Cache 
mechanism.

You have to choice of using either Lightbox or Highslide for displaying the
large version of your photos.

== Installation ==

1. Unzip the archive to your `wp-content/plugins/` folder.
2. Activate the plugin through the `Plugins` menu in WordPress
3. Go in the `Options` menu and select `kPicasa Gallery`. Set your parameters.
4. Create post or a page with `KPICASA_GALLERY` as the only content.
5. Browse to that post or page, voilà!

== Advanced usage ==

Optionally, you can decide to only display specific albums. For this, you 
will have to go to the Picasa Web Albums and log into your account. You will 
then need to find the internal album name, as explained below.

As an example, for an album named "Trip to Europe", the URL of that album
will probably look like this: `http://picasaweb.google.com/YourPicasaUsername/TripToEurope`.
The internal album name is the last portion (`TripToEurope`). You will need to 
find the internal album name for every album you want to display.

You will then need to call kPicasa Gallery like this (this will show 3 specific albums):
`KPICASA_GALLERY(TripToEurope, TripToAsia, TripToAustralia)`
