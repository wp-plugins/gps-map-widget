<?php
/**
 * @package GPS_MAP_Widget
 * @version 1.0
 */
/*
Plugin Name: GPS_MAP_Widget
Plugin URI: 
Description: Shows a static google map with the GPS location of the featured image.
Author: Gerhard Hoogterp
Version: 1.0
Author URI: http://www.funsite.eu/
*/

class GPS_MAP_Widget extends WP_Widget {

	// constructor
	function GPS_MAP_Widget() {
		parent::WP_Widget(false, 
							$name = __('GPS_MAP_Widget', 'GPS_MAP_Widget_plugin'),
							array('description' => __('Shows a static google map with the GPS location of the featured image','GPS_MAP_Widget_plugin'))
								);
	}

	
	function gps($coordinate, $hemisphere) {
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

	// widget form creation
	function form($instance) {
	    // Check values
	    if( $instance) {
		$title = esc_attr($instance['title']);
		$width = esc_attr($instance['width']);
		$height = esc_textarea($instance['height']);
		$zoom = esc_textarea($instance['zoom']);
	    } else {
		$title = 'GPS location';
		$width = 300;
		$height = 200;
		$zoom = 11;
	    }
	    ?>

	    <p>
	    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	    </p>
	    <p>
	    <label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('gmap width', 'wp_widget_plugin'); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" />
	    </p>
	    
   	    <p>
	    <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('gmap height', 'wp_widget_plugin'); ?></label>
	    <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" />
	    </p>
	    
	    <p>
	    <label for="<?php echo $this->get_field_id('zoom'); ?>"><?php _e('gmap zoom', 'wp_widget_plugin'); ?></label>
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
		extract( $args );

		// these are the widget options
		$title = apply_filters('widget_title', $instance['title']);
		$title=$title?$title:'GPS location';
		
		$width = apply_filters('widget_title', $instance['width']);
		$height = apply_filters('widget_title', $instance['height']);
		$zoom = apply_filters('widget_title', $instance['zoom']);
		$mapsize = $width.'x'.$height;		

		echo $before_widget;
	  
		// Display the widget
		echo '<div class="widget-text wp_widget_plugin_box">';

		// Check if title is set
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
	  
		$post_thumbnail_id = get_post_thumbnail_id( $GLOBALS['post']->ID );
		if ($post_thumbnail_id) {
			$thumbnail=get_attached_file( $post_thumbnail_id, true );
			$exif = exif_read_data($thumbnail);

			if (is_array($exif["GPSLatitude"]) && is_array($exif["GPSLongitude"])) {
				$latitude = $this->gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
				$longitude = $this->gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
				?>
				
				<a href="https://www.google.nl/maps/?q=<?php echo $latitude?>,<?php echo $longitude?>&amp;zoom=15" rel="external" title="<?php _e('click to open a new tab or window with google maps','GPS_MAP_Widget_plugin') ?>">
					<img src="https://maps.googleapis.com/maps/api/staticmap?zoom=<?php echo $zoom ?>&size=<?php echo $mapsize ?>&markers=size:mid|<?php echo $latitude?>,<?php echo $longitude?>" style="width:100%">
				</a>

				<?php
			} else {
				 print '<p>'.__('There is no GPS information available','GPS_MAP_Widget_plugin').'</p>';
			}
		} else {
			print '<p>'.__('There is no featured image available','GPS_MAP_Widget_plugin').'</p>';
		}

	echo '</div>';
	echo $after_widget;
	}
}

function add_header_code () {
  wp_enqueue_script('GPS_MAP_Widget_js_handler', plugins_url('/js/scripts.js', __FILE__ ), array( 'jquery' ));
}		

// register widget
add_action('widgets_init', create_function('', 'return register_widget("GPS_MAP_Widget");'));
add_action('wp_head', 'add_header_code',false,false,true);
?>