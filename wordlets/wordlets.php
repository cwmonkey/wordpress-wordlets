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
	const VERSION = '1.0';

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wordlets_widget', // Base ID
			__('Wordlets', 'text_domain'), // Name
			array( 'description' => __( 'A Widget for Developers', 'text_domain' ), ) // Args
		);

		if ( is_admin() ) {
			wp_enqueue_script( 'wordlets_widget', plugins_url('wordlets-admin.js', __FILE__), array( 'jquery' ), self::VERSION );
			wp_enqueue_style( 'wordlets_widget', plugins_url('wordlets-admin.css', __FILE__), null, self::VERSION );
		}

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

		if ( current_user_can( 'manage_options' ) ) {
			echo '<a href="' . admin_url( 'widgets.php#' . $args['widget_id'] ) . '" target="_blank" class="wordlets-admin-link">' . __( 'Edit', 'text_domain' ) . '</a>';
		}
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
		<div class="wordlets-widget-wrapper">
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (not shown on widget):' ); ?></label> 
		<input class="widefat wordlet-widget-title" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 

		$wordlets_files = $this->_get_wordlet_files();

		?>
		<p style="border-bottom:1px solid #ccc;padding-bottom:1em">
		<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template:' ); ?></label> 
		<select class="wordlets-widget-template" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>">
		<?php

		foreach ( $wordlets_files as $fname => $info ) {
			$display = $fname;
			if ( isset($info['props']['name']) ) {
				$display = $info['props']['name'];
			}

			if ( isset($info['props']['description']) ) {
				$display .= ' (' . $info['props']['description'] . ')';
			}

			$selected = '';
			if ( isset($instance['template']) && $fname == $instance['template'] ) {
				$selected = 'selected="selected"';
			}

			?>
			<option value="<?php echo esc_attr( $fname ); ?>" <?php echo $selected ?>><?php echo esc_attr( $display ); ?></option>
			<?php
		}

		?>
		</select>
		</p>
		<?php

		$loops = 0;
		foreach ( $wordlets_files as $fname => $info ) {
			?>
			<fieldset class="wordlet-widget-set <?php echo ( (!$instance['template'] && $loops) || ($fname != $instance['template']) )?'':'active'; ?>" data-template="<?php echo esc_attr( $fname ); ?>">
			<?php
			foreach ( $info['wordlets'] as $wname => $wordlet ) {
				$friendly_name = $wname;
				if ( isset($wordlet->label) ) {
					$friendly_name = $wordlet->label;
				}

				if ( $wordlet->is_array ) {
					?>
					<fieldset style="border: 1px solid #ccc; padding: 0 1em">
					<legend><?php _e( $friendly_name ); ?></legend>
					<?php
					$value_prefix = $fname . '__' . $wname;
					for ( $i = 0; $i < 100; $i++ ) {
						$value_name = $value_prefix . '__' . $i;
						if ( isset($instance[$value_name . '__value']) ) {
							$this->_input($value_name, $i, $wordlet, $instance, true, true, $i);
						} else {
							$this->_input($value_name, $i, $wordlet, $instance, false, true, $i);
							break;
						}
					}
					?>
					</fieldset>
					<?php
				} else {
					$value_name = $fname . '__' . $wname;
					$this->_input($value_name, $friendly_name, $wordlet, $instance);
				}
			}
			?>
			</fieldset>
			<?php
			$loops++;
		}

		?>
		</div>
		<?php
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
					$key_name = $value_prefix . '__' . $i . '__key';
					if ( isset($new_instance[$value_name]) ) {
						if ( $new_instance[$value_name] ) {
							$new_value_name = $value_prefix . '__' . $k . '__value';
							$instance[$new_value_name] = ( ! empty( $new_instance[$value_name] ) ) ? strip_tags( $new_instance[$value_name] ) : '';
							if ( isset($new_instance[$key_name]) ) {
								$new_key_name = $value_prefix . '__' . $k . '__key';
								$instance[$new_key_name] = ( ! empty( $new_instance[$key_name] ) ) ? strip_tags( $new_instance[$key_name] ) : '';
							}
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

	/**
	 * Render wordlet admin input value.
	 */

	private function _input($value_prefix, $friendly_name, $wordlet, $instance, $use_default = true, $show_key = false, $hide_labels = false) {
		$value_name = $value_prefix . '__value';
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
		<?php
		if ( $show_key ) {
			$key_name = $value_prefix . '__key';
			$key = $friendly_name;
			if ( isset($instance[$key_name]) ) {
				$key = $instance[$key_name];
			}
			?>
			<label for="<?php echo $this->get_field_id( $key_name ); ?>" style="<?php echo ($show_key)?'display:inline-block;width:30%;margin-right:1em':''; ?>">
				<?php if ( !$hide_labels ) echo __( 'Key:', 'text_domain' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( $key_name ); ?>" name="<?php echo $this->get_field_name( $key_name ); ?>" type="text" value="<?php echo esc_attr( $key ); ?>">
			</label>
		<?php
		}
		?>
		<label for="<?php echo $this->get_field_id( $value_name ); ?>" style="<?php echo ($show_key)?'display:inline-block;width:60%':''; ?>">
			<?php if ( $type == 'checkbox' ) { ?>
				<input type="checkbox" id="<?php echo $this->get_field_id( $value_name ); ?>" name="<?php echo $this->get_field_name( $value_name ); ?>" type="text" value="1" <?php echo ( $value ) ? 'checked':'' ?>>
			<?php } ?>
			<?php if ( !$hide_labels ) _e( (($show_key)?'Value':$friendly_name) . (( $type != 'checkbox' ) ? ':' : '') ); ?>

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

		</label>
		<?php if ( $description && !$hide_labels ) { ?>
			<i><?php echo $description ?></i>
		<?php } ?>
		</p>
		<?php
	}

	/**
	 * Echo a wordlet input value to the screen.
	 */

	public function get_wordlet($name) {
		$value_name = $this->_file['name'] . '__' . $name . '__value';
		if ( isset($this->_instance[$value_name]) ) return __( $this->_instance[$value_name], 'text_domain' );
	}

	/**
	 * Return array of Wordlet_Wordlets_Value.
	 */

	public function get_wordlet_array($name) {
		$values = array();
		$value_prefix = $this->_file['name'] . '__' . $name;
		for ( $i = 0; $i < 100; $i++ ) {
			$value_name = $value_prefix . '__' . $i . '__value';
			$key_name = $value_prefix . '__' . $i . '__key';
			if ( isset($this->_instance[$value_name]) && isset($this->_instance[$key_name]) ) {
				$values[] = new Wordlets_Wordlet_Value( __( $this->_instance[$key_name], 'text_domain' ), __( $this->_instance[$value_name], 'text_domain' ) );
			} elseif ( isset($this->_instance[$value_name]) ) {
				$values[] = __( $this->_instance[$value_name], 'text_domain' );
			} else {
				return $values;
			}
		}
	}

	public static $WordletFiles = null;

	/**
	 * Return information/wordlet settings in a single wordlet file or all wordlet files within theme.
	 */

	private function _get_wordlet_files($template = null) {
		$wordlets_files = array();
		$tpl_dir = get_template_directory();

		if ( $template ) {
			if ( isset(self::$WordletFiles[$template]) ) return self::$WordletFiles[$template];

			$files = array(
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . $template . '.php',
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . 'wordlet-' . $template . '.php',
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets-' . $template . '.php',
			);

			foreach ( $files as $file ) {
				if ( is_readable($file) ) {
					$wf = $this->_parse_file($file);
					$wf['name'] = $template;
					return $wf;
				}
			}
		} else {
			if ( self::$WordletFiles ) return self::$WordletFiles;

			// Check the root dir first
			$files = scandir( $tpl_dir );
			foreach ( $files as $file ) {
				if ( preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) ) {
					$name = $matches[1];
					$wordlets_files[$name] = $this->_parse_file($tpl_dir . DIRECTORY_SEPARATOR . $file);
					$wordlets_files[$name]['name'] = $name;
				}
			}

			// Then scan the wordlets dir (override root files)
			$wordlets_dir = $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets';
			if ( is_readable($wordlets_dir) && $files = scandir( $wordlets_dir ) ) {
				foreach ( $files as $file ) {
					if ( preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) || preg_match('/^(.+)\.php$/', $file, $matches) ) {
						$name = $matches[1];
						$wordlets_files[$name] = $this->_parse_file($tpl_dir . DIRECTORY_SEPARATOR . $file);
						$wordlets_files[$name]['name'] = $name;
					}
				}
			}

			self::$WordletFiles = $wordlets_files;

			return $wordlets_files;
		}

	}

	/**
	 * Return properties and wordlet objects within a wordlet file.
	 */

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
		if ( preg_match_all('/\b((wordlet_array)|(wa)|(wordlet)|(w))\s*\(\s*(.+?)\s*\)\s*(\)|;|(\?>)|(&&)|(as)|(\|\|))/', $content, $matches) && !empty($matches[6]) ) {
			foreach ( $matches[6] as $key => $match ) {
				eval('$wordlet = new Wordlets_Wordlet(' . $match . ');');
				// Don't override wordlets
				// TODO: Check to see if wordlet has more info?
				if ( !isset($wordlets[$wordlet->name]) ) {
					if ( $matches[1][$key] == 'wordlet_array' ) {
						$wordlet->is_array = 1;
					}
					$wordlets[$wordlet->name] = $wordlet;
				}
			}
		}

		return array('props' => $file_props, 'wordlets' => $wordlets);
	}

	static $me;

	/**
	 * Echo a wordlet input value to the screen.
	 */

	static function GetWordlet($name) {
		return self::$me->get_wordlet($name);
	}

	/**
	 * Get an array of Wordlets_Wordlet_Value objects.
	 */

	static function GetWordletArray($name) {
		return self::$me->get_wordlet_array($name);
	}
}

/**
 * Container for admin input settings.
 */

class Wordlets_Wordlet {
	public $name;
	public $default;
	public $label;
	public $description;
	public $value;
	public $is_array = false;

	public function __construct($name, $default = null, $label = null, $description = null) {
		$this->name = $name;
		$this->default = $default;
		$this->label = $label;
		$this->description = $description;
	}
}

/**
 * Class for wordlet array objects.
 *
 * $wa = wordlet_array('things');
 * <?=$wa->key; ?>: <?=$wa->value; ?>
 */

class Wordlets_Wordlet_Value {
	public $key;
	public $value;
	public function __construct($key, $value) {
		$this->key = $key;
		$this->value = $value;
	}

	public function __toString() {
		return '' . $this->value;
	}
}

// Template helper functions

/**
 * Echo a wordlet input value to the screen.
 */

function wordlet($name) {
	return Wordlets_Widget::GetWordlet($name);
}

/**
 * function wordlet alias
 */

if ( !defined('w') ) {
	function w($name) {
		return wordlet($name);
	}
}

/**
 * Get an array of Wordlets_Wordlet_Value objects.
 */

function wordlet_array($name) {
	return Wordlets_Widget::GetWordletArray($name);
}

/**
 * function wordlet_array alias.
 */

if ( !defined('wa') ) {
	function wa($name) {
		return wordlet_array($name);
	}
}