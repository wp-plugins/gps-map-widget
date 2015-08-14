<?php
/**
 * @package GPS_MAP_Widget
 * @version 1.6
 */
/*
Plugin Name: GPS MAP Widget
Plugin URI: http://plugins.funsite.eu/gps-map-widget/
Description: Shows a static google map with the GPS location of the featured image.
Author: Gerhard Hoogterp
Version: 1.6
Author URI: http://plugins.funsite.eu/gps-map-widget/
*/
if (!class_exists('basic_plugin_class')) {
	require(plugin_dir_path(__FILE__).'basics/basic_plugin.class');
}

class GPS_MAP_Widget extends WP_Widget {

	const FS_TEXTDOMAIN = gps_map_box_class::FS_TEXTDOMAIN;

	// constructor
	public function __construct() {
		parent::WP_Widget(false, 
					$name = __('GPS MAP Widget', self::FS_TEXTDOMAIN),
					array('description' => __('Shows a static google map with the GPS location of the featured image',self::FS_TEXTDOMAIN))
				);
	}

	// widget form creation
	function form($instance) {
	    // Check values
	    if( $instance) {
			$title = esc_attr($instance['title']);
			$width = esc_attr($instance['width']);
			$height = esc_textarea($instance['height']);
			$zoom = esc_textarea($instance['zoom']);
	    } else {
			$title = __('GPS location',self::FS_TEXTDOMAIN);
			$width = 300;
			$height = 200;
			$zoom = 11;
	    }
	    ?>

	    <p>
	    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', self::FS_TEXTDOMAIN); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	    </p>
	    <p>
	    <label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('gmap width', self::FS_TEXTDOMAIN); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" />
	    </p>
	    
   	    <p>
	    <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('gmap height', self::FS_TEXTDOMAIN); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" />
	    </p>
	    
	    <p>
	    <label for="<?php echo $this->get_field_id('zoom'); ?>"><?php _e('gmap zoom', self::FS_TEXTDOMAIN); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('zoom'); ?>" name="<?php echo $this->get_field_name('zoom'); ?>" type="text" value="<?php echo $zoom; ?>" />
	    </p>
	    
	    <?php
	}

	// widget update
	function update($new_instance, $old_instance) {
	    $instance = $old_instance;
	    // Fields
	    $instance['title'] = strip_tags($new_instance['title']);
	    $instance['width'] = strip_tags($new_instance['width']);
	    $instance['height'] = strip_tags($new_instance['height']);
	    $instance['zoom'] = strip_tags($new_instance['zoom']);
	    return $instance;
	}

	// widget display
	function widget($args, $instance) {
		global $gps_map_box;
		extract( $args );

		// these are the widget options
		$title = apply_filters('widget_title', $instance['title']);
		$title=$title?$title:'GPS location';
		
		$width = apply_filters('widget_title', $instance['width']);
		$height = apply_filters('widget_title', $instance['height']);
		$zoom = apply_filters('widget_title', $instance['zoom']);
	    $errors = true;
	    
		echo $before_widget;
	  
		// Display the widget
		echo '<div class="widget-text wp_widget_plugin_box gps_map_widget_class">';

		// Check if title is set
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		
		// When called, the gps_map_box object should already be initialized. So it's save to call.. I guess.. Hope..
		print $gps_map_box->custom_EXIF_locationmap(array(
				'width'		=> $width,
				'height'	=> $height,
				'zoom'		=> $zoom,
				'errors'	=> true		// in the widget we always some content
				)
		);

	echo '</div>';
	echo $after_widget;
	}


}


class gps_map_box_class  extends basic_plugin_class {

	function getPluginBaseName() { return plugin_basename(__FILE__); }
	function getChildClassName() { return get_class($this); }

	public function __construct() {
		parent::__construct();
		
		add_shortcode( 'EXIF_locationmap', array($this,'custom_EXIF_locationmap' ));
		add_shortcode( 'EXIF_location', array($this,'custom_EXIF_location' ));

	// register widget
		add_action('widgets_init', create_function('', 'return register_widget("GPS_MAP_Widget");'));
		add_action('wp_head', array($this,'add_header_code'),false,false,true);
		add_action('admin_init', array($this,'add_header_code'),false,false,true);

	// map on the attachment edit page
		add_action( 'admin_menu', array($this,'create_gps_map_box') );
	}
	
	function pluginInfoRight($info) {  }

	const FS_TEXTDOMAIN = 'gpsmapwidget';
	const FS_PLUGINNAME = 'gps-map-widget';
	
	// Add Shortcode


	function exif_gps($coordinate, $hemisphere) {
		for ($i = 0; $i < 3; $i++) {
		$part = explode('/', $coordinate[$i]);
		if (count($part) == 1) {
			$coordinate[$i] = $part[0];
		} else if (count($part) == 2) {
			$coordinate[$i] = floatval($part[0])/floatval($part[1]);
		} else {
			$coordinate[$i] = 0;
		}
		}
		list($degrees, $minutes, $seconds) = $coordinate;
		$sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
		return $sign * ($degrees + $minutes/60 + $seconds/3600);
	}


	function getLocationFromDBorExif($post_thumbnail_id) {
		// Check if the location is already stored in the database
		// if not, try to get it from the EXIF information and store it.
		$location = get_post_meta($post_thumbnail_id,'EXIF_location',true);
		if (empty($location)) {

			$thumbnail=get_attached_file( $post_thumbnail_id, true );
			$exif = exif_read_data($thumbnail);
			if (is_array($exif["GPSLatitude"]) && is_array($exif["GPSLongitude"])) {
				$location['latitude'] = $this->exif_gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
				$location['longitude'] = $this->exif_gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
				$location['hasLocation'] = true;

				} else {
				$location['hasLocation'] = false;
			}

			add_post_meta($post_thumbnail_id,'EXIF_location',$location) || update_post_meta($post_thumbnail_id,'EXIF_location',$location);
		}
	return $location;
	}

	function DEC2DMS($coord) {
		$isnorth = $coord>=0;
		$coord = abs($coord);
		$deg = floor($coord);
		$coord = ($coord-$deg)*60;
		$min = floor($coord);
		$sec = floor(($coord-$min)*60);
		return sprintf("%d&deg;%d'%d\"%s", $deg, $min, $sec, $isnorth ? 'N' : 'S');
	}
	
	
		function custom_EXIF_location( $atts) {
		$res = '';
		
		// Attributes
		extract( shortcode_atts(
			array(
				'part' => 'both',
				'form' => 'dec',  // or DMS
			), $atts )
		);

		$post_thumbnail_id = get_post_thumbnail_id( $GLOBALS['post']->ID );
		if ($post_thumbnail_id) {
			$location = $this->getLocationFromDBorExif($post_thumbnail_id);
		}

		if (!$location['hasLocation']) {
			$location['latitude']='?';
			$location['longitude']='?';
		} elseif (strtoupper($form)=='DMS') {
			$location['latitude']=$this->DEC2DMS($location['latitude']);
			$location['longitude']=$this->DEC2DMS($location['longitude']);
		}
		

		switch ($part) {
			case 'latitude': $res = $location['latitude']; break;
			case 'longitude': $res = $location['longitude']; break;
			default: $res = $location['latitude'].' , '.$location['longitude'];
		}
		return $res;	
	}	

	function custom_EXIF_locationmap( $atts ) {
		$res = '';
		// Attributes
		extract( shortcode_atts(
			array(
				'width' => '300',
				'height' => '200',
				'zoom' => '11',
				'errors' => '0',
			), $atts )
		);

		// Code
		$mapsize = $width.'x'.$height;	

		$post_thumbnail_id = get_post_thumbnail_id( $GLOBALS['post']->ID );
		if ($post_thumbnail_id) {

			$location = $this->getLocationFromDBorExif($post_thumbnail_id);

			if ($location['hasLocation']) {
				$res  = '<a href="https://www.google.nl/maps/?q='.$location['latitude'].','.$location['longitude'].'&amp;zoom='.$zoom.'" rel="external" title="'.__('click to open a new tab or window with google maps',self::FS_TEXTDOMAIN).'">';
				$res .=	'<img src="https://maps.googleapis.com/maps/api/staticmap?zoom='.$zoom.'&size='.$mapsize.'&markers=size:mid|'.$location['latitude'].','.$location['longitude'].'" style="width:100%">';
				$res .= '</a>';
			} else {
					if ($errors) {
						$res = '<p>'.__('There is no GPS information available',self::FS_TEXTDOMAIN).'</p>';
					}
			}
		} else {
			if ($errors) {
				$res ='<p>'.__('There is no featured image available',self::FS_TEXTDOMAIN).'</p>';
			}
		}
		
	return $res;
	}

	function create_gps_map_box() {
		add_meta_box( 'gps_map_box', __('GPS location',self::FS_TEXTDOMAIN), array($this,'gps_map_box'), 'attachment', 'side', 'default' );
	}

	function gps_map_box( $object, $box ) { 
		$location = $this->getLocationFromDBorExif($GLOBALS['post']->ID);
		
		$width = '300';
		$height = '200';
		$zoom = '11';
		$mapsize = $width.'x'.$height;	

		if ($location['hasLocation']) {
			$res  = '<a style="display: block;" href="https://www.google.nl/maps/?q='.$location['latitude'].','.$location['longitude'].'&amp;zoom='.$zoom.'" rel="external" title="'.__('click to open a new tab or window with google maps',self::FS_TEXTDOMAIN).'">';
			$res .=	'<img src="https://maps.googleapis.com/maps/api/staticmap?zoom='.$zoom.'&size='.$mapsize.'&markers=size:mid|'.$location['latitude'].','.$location['longitude'].'" style="width:100%">';
			$res .= '</a>';
		} else {
			$res = '<p>'.__('There is no GPS information available',self::FS_TEXTDOMAIN).'</p>';
		}
		echo $res;
	}	

	function add_header_code () {
		wp_enqueue_script('GPS_MAP_Widget_js_handler', plugins_url('/js/scripts.js', __FILE__ ), array( 'jquery' ));
	}		
}

$gps_map_box = new gps_map_box_class();
?>