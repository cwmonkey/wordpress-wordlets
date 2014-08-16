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

			if ( isset($info['props']['description']) ) $display .= ' (' . $info['props']['description'] . ')';

			$selected = '';
			if ( isset($instance['template']) && $fname == $instance['template'] ) $selected = 'selected="selected"';

			?><option value="<?php echo esc_attr( $fname ); ?>" <?php echo $selected ?>><?php echo esc_attr( $display ); ?></option><?php
		}
		?></select><p><?php

		foreach ( $wordlets_files as $fname => $info ) {
			?><fieldset class="wordlet_set"><?php
			foreach ( $info['wordlets'] as $wname => $wordlet ) {
				$friendly_name = $wname;
				if ( isset($wordlet->label) ) {
					$friendly_name = $wordlet->label;
				}

				if ( $wordlet->is_array ) {
					?><fieldset style="border: 1px solid #ccc; padding: 0 1em"><legend><?php _e( $friendly_name ); ?></legend><?php
					$value_prefix = $fname . '__' . $wname;
					for ( $i = 0; $i < 100; $i++ ) {
						$value_name = $value_prefix . '__' . $i . '__value';
						if ( isset($instance[$value_name]) ) {
							$this->_input($value_name, $i, $wordlet, $instance);
						} else {
							$this->_input($value_name, $i, $wordlet, $instance, false);
							break;
						}
					}
					?></fieldset><?php
				} else {
					$value_name = $fname . '__' . $wname . '__value';
					$this->_input($value_name, $friendly_name, $wordlet, $instance);
				}
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
			if ( $wordlet->is_array ) {
				$value_prefix = $file['name'] . '__' . $wname;
				$k = 0;
				for ( $i = 0; $i < 100; $i++ ) {
					$value_name = $value_prefix . '__' . $i . '__value';
					if ( isset($new_instance[$value_name]) ) {
						if ( $new_instance[$value_name] ) {
							$new_value_name = $value_prefix . '__' . $k . '__value';
							$instance[$new_value_name] = ( ! empty( $new_instance[$value_name] ) ) ? strip_tags( $new_instance[$value_name] ) : '';
							$k++;
						}
					} else {
						break;
					}
				}
			} else {
				$value_name = $file['name'] . '__' . $wname . '__value';
				$instance[$value_name] = ( ! empty( $new_instance[$value_name] ) ) ? strip_tags( $new_instance[$value_name] ) : '';
			}
		}

		return $instance;
	}

	/* Custom methods */

	private $_file;
	private $_instance;
	private $_keys = array('name', 'default', 'friendly_name', 'description');

	private function _input($value_name, $friendly_name, $wordlet, $instance, $use_default = true) {
		$value = '';
		$type = 'text';

		if ( isset($instance[$value_name]) ) {
			$value = $instance[$value_name];
		} elseif ( $use_default && isset($wordlet->default) ) {
			$value = $wordlet->default;
		}

		// Determine input type
		if ( is_array($wordlet->default) ) {
			$type = 'select';
		} elseif ( preg_match("/\n/s", $wordlet->default) ) {
			$type = 'textarea';
		} elseif ( is_int($wordlet->default) ) {
			$type = 'number';
		} elseif ( is_bool($wordlet->default) ) {
			$type = 'checkbox';
		}

		$description = '';
		if ( isset($wordlet->description) ) {
			$description = $wordlet->description;
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( $value_name ); ?>">
			<?php if ( $type == 'checkbox' ) { ?>
				<input type="checkbox" id="<?php echo $this->get_field_id( $value_name ); ?>" name="<?php echo $this->get_field_name( $value_name ); ?>" type="text" value="1" <?php echo ( $value ) ? 'checked':'' ?>>
			<?php } ?>
			<?php _e( $friendly_name . (( $type != 'checkbox' ) ? ':' : '') ); ?>
		</label>

		<?php if ( $type == 'text' || $type == 'number' ) { ?>
			<input class="widefat" id="<?php echo $this->get_field_id( $value_name ); ?>" name="<?php echo $this->get_field_name( $value_name ); ?>" type="<?php echo $type ?>" value="<?php echo esc_attr( $value ); ?>">
		<?php } elseif ( $type == 'textarea' ) { ?>
			<textarea class="widefat" id="<?php echo $this->get_field_id( $value_name ); ?>" name="<?php echo $this->get_field_name( $value_name ); ?>"><?php echo esc_attr( $value ); ?></textarea>
		<?php } elseif ( $type == 'select' ) { ?>
			<select  id="<?php echo $this->get_field_id( $value_name ); ?>" name="<?php echo $this->get_field_name( $value_name ); ?>">
				<?php foreach ( $wordlet->default as $key => $default ) { ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php echo ( $key == $value )?'selected':'' ?>><?php echo esc_attr( $default ); ?></option>
				<?php } ?>
			</select>
		<?php } ?>

		<?php if ( $description ) { ?>
			<i><?php echo $description ?></i>
		<?php } ?>
		</p>
		<?php
	}

	public function get_wordlet($name) {
		$value_name = $this->_file['name'] . '__' . $name . '__value';
		if ( isset($this->_instance[$value_name]) ) return __( $this->_instance[$value_name], 'text_domain' );
	}

	public function get_wordlet_array($name) {
		$values = array();
		$value_prefix = $this->_file['name'] . '__' . $name;
		for ( $i = 0; $i < 100; $i++ ) {
			$value_name = $value_prefix . '__' . $i . '__value';
			if ( isset($this->_instance[$value_name]) ) {
				$values[] = __( $this->_instance[$value_name], 'text_domain' );
			} else {
				return $values;
			}
		}
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
		$wordlets_dir = $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets';
		if ( is_readable($wordlets_dir) && $files = scandir( $wordlets_dir ) ) {
			foreach ( $files as $file ) {
				if ( (preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) || preg_match('/^(.+)\.php$/', $file, $matches)) && (!$template || $template == $matches[1]) ) {
					$name = $matches[1];
					$wordlets_files[$name] = $this->_parse_file($tpl_dir . DIRECTORY_SEPARATOR . $file);
					$wordlets_files[$name]['name'] = $name;
				}
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

		$wordlets = array();
		// Find wordlets
		if ( preg_match_all('/\bwordlet(_array)?\s*\(\s*(.+?)\s*\)\s*(\)|;|(\?>)|(&&)|(as)|(\|\|))/', $content, $matches) && !empty($matches[2]) ) {
			foreach ( $matches[2] as $key => $match ) {
				eval('$wordlet = new Wordlets_Wordlet(' . $match . ');');
				// Don't override wordlets
				// TODO: Check to see if wordlet has more info?
				if ( !isset($wordlets[$wordlet->name]) ) {
					if ( $matches[1][$key] == '_array' ) {
						$wordlet->is_array = 1;
					}
					$wordlets[$wordlet->name] = $wordlet;
				}
			}
		}

		return array('props' => $file_props, 'wordlets' => $wordlets);
	}

	static $me;
	static function GetWordlet($name) {
		return self::$me->get_wordlet($name);
	}

	static function GetWordletArray($name) {
		return self::$me->get_wordlet_array($name);
	}
}

class Wordlets_Wordlet {
	public $name;
	public $default;
	public $label;
	public $description;
	public $value;
	public $is_array = false;

	function __construct($name, $default = null, $label = null, $description = null) {
		$this->name = $name;
		$this->default = $default;
		$this->label = $label;
		$this->description = $description;
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
function wordlet_array($name, $default = null, $friendly_name = null, $description = null) {
	return Wordlets_Widget::GetWordletArray($name);
}

if ( !defined('wa') ) {
	function wa($name) {
		return wordlet_array($name);
	}
}