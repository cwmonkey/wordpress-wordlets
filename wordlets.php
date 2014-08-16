<?php
/**
 * Plugin Name: Wordlets
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Generic widget content/settings plugin for developers
 * Version: 1.0
 * Author: Gerald Burns (cwmonkey)
 * Author URI: http://cwmonkey.com
 * License: GPL2
 */

/*  Copyright 2014  Gerald Burns  (email : geraldb@gmail.com)

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

defined('ABSPATH') or die();

// Load the widget on widgets_init
function load_wordlets_widget() {
	register_widget('Wordlets_Widget');
}

add_action('widgets_init', 'load_wordlets_widget');

/**
 * Wordlets_Widget class
 **/
class Wordlets_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wordlets_widget', // Base ID
			__('Wordlets', 'text_domain'), // Name
			array( 'description' => __( 'A Widget for Developers', 'text_domain' ), ) // Args
		);

		self::$me = $this;
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$this->_file = $file = $this->_get_wordlet_files($instance['template']);
		$this->_instance = $instance;
		//$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		/*if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo __( 'Hello, World!', 'text_domain' );*/
		include($file['props']['file']);
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'New Wordlet', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 

		$wordlets_files = $this->_get_wordlet_files();

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template:' ); ?></label> 
			<select id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>">
		<?php

		foreach ( $wordlets_files as $fname => $info ) {
			$display = $fname;
			if ( isset($info['props']['name']) ) $display = $info['props']['name'];

			$selected = '';
			if ( isset($instance['template']) && $fname == $instance['template'] ) $selected = 'selected="selected"';

			?><option value="<?php echo esc_attr( $fname ); ?>" <?php echo $selected ?>><?php echo esc_attr( $display ); ?></option><?php
		}
		?></select><p><?php

		foreach ( $wordlets_files as $fname => $info ) {
			?><fieldset class="wordlet_set"><?php
			foreach ( $info['wordlets'] as $wname => $wordlet ) {
				$value_name = $fname . '__' . $wname . '__value';
				$value = '';
				if ( isset($instance[$value_name]) ) {
					$value = $instance[$value_name];
				} elseif ( isset($wordlet['default']) ) {
					$value = $wordlet['default'];
				}

				$friendly_name = $wname;
				if ( isset($wordlet['friendly_name']) ) {
					$friendly_name = $wordlet['friendly_name'];
				}

				$description = $description;
				if ( isset($wordlet['description']) ) {
					$description = $wordlet['description'];
				}

				?>
				<p>
				<label for="<?php echo $this->get_field_id( $value_name ); ?>"><?php _e( $friendly_name . ':' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( $value_name ); ?>" name="<?php echo $this->get_field_name( $value_name ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
				<?php if ( $description ) { ?>
					<i><?php echo $description ?></i>
				<?php } ?>
				</p>
				<?php
			}
			?></fieldset><?php
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['template'] = ( ! empty( $new_instance['template'] ) ) ? strip_tags( $new_instance['template'] ) : '';

		$file = $this->_get_wordlet_files($instance['template']);

		foreach ( $file['wordlets'] as $wname => $wordlet ) {
			$value_name = $file['name'] . '__' . $wname . '__value';
			$instance[$value_name] = ( ! empty( $new_instance[$value_name] ) ) ? strip_tags( $new_instance[$value_name] ) : '';
		}

		return $instance;
	}

	private $_file;
	private $_instance;
	private $_keys = array('name', 'default', 'friendly_name', 'description');

	public function get_wordlet($name) {
		$value_name = $this->_file['name'] . '__' . $name . '__value';
		if ( isset($this->_instance[$value_name]) ) return __( $this->_instance[$value_name], 'text_domain' );
	}

	private function _get_wordlet_files($template = null) {
		$wordlets_files = array();
		$tpl_dir = get_template_directory();

		// Check the root dir first
		$files = scandir( $tpl_dir );
		foreach ( $files as $file ) {
			if ( preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) && (!$template || $template == $matches[1]) ) {
				$name = $matches[1];
				$wordlets_files[$name] = $this->_parse_file($tpl_dir . DIRECTORY_SEPARATOR . $file);
				$wordlets_files[$name]['name'] = $name;
			}
		}

		// Then scan the wordlets dir (override root files)
		$files = scandir( $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' );
		foreach ( $files as $file ) {
			if ( (preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) || preg_match('/^(.+)\.php$/', $file, $matches)) && (!$template || $template == $matches[1]) ) {
				$name = $matches[1];
				$wordlets_files[$name] = $this->_parse_file($tpl_dir . DIRECTORY_SEPARATOR . $file);
				$wordlets_files[$name]['name'] = $name;
			}
		}

		if ( $template ) return array_pop($wordlets_files);

		return $wordlets_files;
	}

	private function _parse_file($file) {
		$file_props = array();
		$content = file_get_contents($file);

		// Get properties in first comment
		if ( preg_match('/\/\*\*(.*)\*\//is', $content, $matches) && !empty($matches[1]) ) {
			if ( preg_match_all('/\* (.+):(.*)/', $matches[1], $properties) && !empty($properties[1]) ) {
				foreach ( $properties[1] as $key => $property ) {
					$file_props[strtolower(trim($property))] = trim($properties[2][$key]);
				}
				$file_props['file'] = $file;
			}
		}

		$ws = array();
		// Find wordlets
		if ( preg_match_all('/\b((wordlet)|(w))\((.*?)\)/is', $content, $matches) && !empty($matches[4]) ) {
			foreach ( $matches[4] as $match ) {
				if ( preg_match_all("/('((\\\')|[^'])*')|(\"((\\\\\")|[^\"])*\")/s", $match, $wordlets) && !empty($wordlets[0]) ) {
					$w = array();
					foreach ( $wordlets[0] as $k => $wordlet ) {
						eval('$wval = ' . $wordlet . ';');
						$w[$this->_keys[$k]] = $wval;
					}
					$ws[$w['name']] = $w;
				}
			}
		}

		return array('props' => $file_props, 'wordlets' => $ws);
	}

	static $me;
	static function GetWordlet($name) {
		return self::$me->get_wordlet($name);
	}
}

function wordlet($name, $default = null, $friendly_name = null, $description = null) {
	return Wordlets_Widget::GetWordlet($name);
}

if ( !defined('w') ) {
	function w($name) {
		return wordlet($name);
	}
}