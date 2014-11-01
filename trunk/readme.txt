=== Plugin Name ===
Contributors: jondor
Donate link: 
Tags: featured image,gps,google maps,static map,widget
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin implements a widget and a shortcode for a static google map which shows the GPS coordinates if those are available in the featured image.

== Description ==

This plugin implements a widget with a static google map which shows the GPS coordinates if those are available in the featured image.

Besides the widget it also adds two shortcodes:

EXIF_locationmap 
	width: width of the mapimage in px. default 300px
	height: height of the mapimage in px. default 200px;
	zoom: googlemaps zoomlevel. Default 11
	Errors: Show error messages when no exif is available or when there's no featured image. Default is false for the shortcode

	Example: 
		[EXIF_locationmap width=750 height=300 zoom=11 errors=false]
		
EXIF_location
	part:  'latitude', 'longitude' or 'both'
	
	example:
		[EXIF_location part=both]
		
	will return  52.22935055,6.8737411

	
== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the widget to a widgetarea. You can set the widget title, width and height in pixels, and the zoomlevel.

== Frequently Asked Questions ==

= Why did you write this widget? =
As an first attempt to write a widget and because I couldn't find one that did what thisone does (and nothing more!)

= Why the shortcodes =
Guess you can use them for generating your own image from bing maps or alike. Putting a big map in the content has it's charm too.. 

== Screenshots ==

No screenshots are available. See http://www.funsite.eu/2014/10/dutch-prairies/ "GPS location" for an example.

== Changelog ==

= 1.1 =
* found coordinates are stored in the database. For now this is done whenever the plugin is shown and only for the post on screen.
  For those interested I use the meta_key "EXIF_location" containing an array with latitude,longitude and hasLocation. hasLocation is
  a boolean which is true when the lat/long fields are filled, but empty. (No EXIF info available in the image!)

* Added the shortcode EXIF_location and EXIF_locationmap  
  
= 1.0 =
* First release

== Upgrade Notice ==

After the first read the file on disk is left alone. This should speed things up a little and opens up posibilities for other, future, features.

