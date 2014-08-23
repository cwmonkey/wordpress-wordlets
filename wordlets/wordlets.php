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
	public $text_domain = 'widget-text-domain';
	public $types = array();
	private $_plugin_dir_path = '';

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wordlets_widget', // Base ID
			__('Wordlets', $this->text_domain), // Name
			array( 'description' => __( 'Widgets for Developers', $this->text_domain ), ) // Args
		);

		$this->_plugin_dir_path = plugin_dir_path( __FILE__ );

		if ( is_admin() ) {
			wp_enqueue_script( 'wordlets_widget', plugins_url('wordlets-admin.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), self::VERSION );
			wp_enqueue_style( 'wordlets_widget', plugins_url('wordlets-admin.css', __FILE__), null, self::VERSION );

			// image input
			if ( ! did_action( 'wp_enqueue_media' ) ) wp_enqueue_media();
			wp_enqueue_script( 'custom-header' );

			// Allow themes to add admin scripts
			do_action('wordlets-admin-construct', $this);
		}

		$add_inputs = array('image', 'text', 'textarea', 'number', 'select', 'checkbox');

		$this->add_wordlet_type( 'object', null );

		foreach ( $add_inputs as $input ) {
			$this->add_wordlet_type( $input, $this->_plugin_dir_path . 'inputs/' . $input . '.php' );
		}

		// Allow themes to add inputs
		do_action('wordlets-construct', $this);

		self::$me = $this;
	}

	/**
	 * Add wordlet type/admin input
	 *
	 * @param string $name    Short name of type [a-z0-9_].
	 * @param string $file    Full file path of admin input template.
	 */

	function add_wordlet_type($name, $file) {
		$type = new stdClass();
		$type->name = $name;
		$type->file = $file;
		$this->types[$name] = $type;
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
		echo __( 'Hello, World!', $this->text_domain );*/

		extract($file['wordlets']);

		include($file['props']['file']);

		if ( current_user_can( 'manage_options' ) ) {
			echo '<a href="' . admin_url( 'widgets.php?' . $args['widget_id'] ) . '" target="_blank" class="wordlets-admin-link">' . __( 'Edit', $this->text_domain ) . '</a>';
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
		$wordlets_files = $this->_get_wordlet_files();

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} elseif ( count($wordlets_files) ) {
			$props = $wordlets_files[array_keys($wordlets_files)[0]]['props'];
			$title = __( $props['name'] . ((isset($props['description']))?' (' . $props['description'] . ')':''), $this->text_domain );
		} else {
			$title = __( 'New Wordlet', $this->text_domain );
		}
		?>
		<div class="wordlets-widget-wrapper">
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (not shown on widget):' ); ?></label> 
		<input class="widefat wordlet-widget-title" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 

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
			<fieldset class="wordlet-widget-set <?php echo ( (!isset($instance['template']) && $loops) || (isset($instance['template']) && $fname != $instance['template']) )?'':'active'; ?>" data-template="<?php echo esc_attr( $fname ); ?>">
			<?php
			foreach ( $info['wordlets'] as $wname => $wordlet ) {
				$friendly_name = $wname;
				if ( isset($wordlet->label) ) {
					$friendly_name = $wordlet->label;
				}

				if ( $wordlet->is_array ) {
					?>
					<fieldset class="wordlet-array">
					<legend><?php _e( $friendly_name ); ?></legend>
					<?php
					$value_prefix = $fname . '__' . $wname;
					for ( $i = 0; $i < 100; $i++ ) {
						$value_name = $value_prefix . '__' . $i;

						if ( $wordlet->type == 'object' ) {
							$names = $wordlet->default;
						} else {
							$names = array('value' => $wordlet->default);
						}

						$has_something = false;
						foreach ( $names as $name => $def ) {
							if ( isset($instance[$value_name . '__' . $name]) ) {
								$has_something = true;
								break;
							}
						}

						if ( $has_something ) {
							?><div class="wordlet-array-item"><?php
								$this->_input($value_name, $i, $wordlet, $instance, false, $i, $value_prefix);
							?></div><?php
						} else {
							?><div class="wordlet-array-item"><?php
								$this->_input($value_name, $i, $wordlet, $instance, true, $i, $value_prefix);
							?></div><?php
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

		if ( !isset($file['wordlets']) ) return $instance;

		foreach ( $file['wordlets'] as $wname => $wordlet ) {
			$value_prefix = $file['name'] . '__' . $wname;
			if ( $wordlet->is_array ) {
				$k = 0;
				for ( $i = 0; $i < 100; $i++ ) {
					$has_something = false;
					$value_name_prefix = $value_prefix; // . '__' . $i;

					if ( $wordlet->type == 'object' ) {
						$names = $wordlet->default;
					} else {
						$names = array('value' => $wordlet->default);
					}

					// If all values are empty, delete it
					foreach ( $names as $name => $def ) {
						$value_name = $value_name_prefix . '__' . $name;
						if ( ! empty( $new_instance[$value_name][$i] ) ) {
							$has_something = true;
							break;
						}
					}

					if ( $has_something ) {
						$new_value_name_prefix = $value_prefix . '__' . $k;
						$instance = $this->_update_instance( $instance, $new_instance, $value_name_prefix, $wordlet, $new_value_name_prefix, $i );
						$k++;
					}
				}
			} else {
				$instance = $this->_update_instance( $instance, $new_instance, $value_prefix, $wordlet );
			}
		}

		return $instance;
	}

	/* Custom methods */

	private function _update_instance( $instance, $new_instance, $value_prefix, $wordlet, $new_value_prefix = '', $i = null ) {
		$names = array();
		if ( $wordlet->type == 'object' ) {
			$names = $wordlet->default;
		} else {
			$names = array('value' => $wordlet->default);
		}

		foreach ( $names as $name => $def ) {
			$old_value_name = $value_prefix . '__' . $name;
			if ( $new_value_prefix ) {
				$new_value_name = $new_value_prefix . '__' . $name;
			} else {
				$new_value_name = $old_value_name;
			}

			if ( $i !== null ) {
				$instance[$new_value_name] = ( ! empty( $new_instance[$old_value_name][$i] ) ) ? $new_instance[$old_value_name][$i] : '';
			} else {
				$instance[$new_value_name] = ( ! empty( $new_instance[$old_value_name] ) ) ? $new_instance[$old_value_name] : '';
			}
		}

		return $instance;
	}

	private $_file;
	private $_instance;

	/**
	 * Render wordlet admin input values.
	 */

	private function _input($value_prefix, $friendly_name, $wordlet, $instance, $blank = false, $hide_labels = false, $array_value_prefix = '') {
		if ( $wordlet->type == 'object' ) {
			?>
			<fieldset class="wordlet-object">
			<?php
				if ( !$wordlet->is_array ) {
					?><legend><?php echo _e($friendly_name) ?></legend><?php
				}

				if ( $wordlet->description ) { ?>
					<label class="wordlet-description"><?=$wordlet->description ?></label><?php
				}

				foreach ( $wordlet->default as $name => $w ) {
					$this->_render_input($instance, $value_prefix, $w->label, $name, $w->type, $w->default, '', $array_value_prefix);
				}
			?>
			</fieldset>
			<?php
		} else {
			$description = '';
			if ( isset($wordlet->description) ) {
				$description = $wordlet->description;
			}

			$default = $wordlet->default;
			$type = $wordlet->type;

			if ( $type == 'image' ) $default = '';

			$this->_render_input($instance, $value_prefix, $friendly_name, 'value', $type, $default, $description, $array_value_prefix, $wordlet->is_array);
		}
	}

	/**
	 * Render wordlet admin input value.
	 */

	private function _render_input($instance, $value_prefix, $friendly_name, $name, $type, $default = '', $description = '', $array_value_prefix = '', $hide_labels = false) {
		$show_key = false; // TODO: See if I need to get rid of this.

		$value_name = $value_prefix . '__' . $name;

		if ( isset($instance[$value_name]) ) {
			$value = $instance[$value_name];
		} else {
			$value = $default;
		}

		$value_array = '';
		if ( $array_value_prefix ) {
			$value_array = '[]';
			$value_name = $array_value_prefix . '__' . $name;
		}

		$input_id = $this->get_field_id( $value_name );
		$input_name = $this->get_field_name( $value_name ) . $value_array;
		$text_domain = $this->text_domain;
		$options = $default;

		ob_start();
		?>
			<label for="<?php echo $input_id; ?>" class="wordlet-input-label">
				<?php echo __($friendly_name, $this->text_domain) . (( $type != 'checkbox' ) ? ':' : ''); ?>
			</label>
		<?php

		$default_label = ob_get_contents();
		ob_end_clean();

		?>
		<div class="wordlet-input wordlet-input-<?php echo $type ?>">
			<?php if ( isset( $this->types[$type] ) && file_exists($this->types[$type]->file) ) {
				include($this->types[$type]->file);
			} else {
				trigger_error('Could not find template for wordlet "' . $name . '".', E_USER_WARNING);
			} ?>
		</div>
		<?php
	}

	/**
	 * Echo a wordlet input value to the screen.
	 */

	public function get_wordlet($name) {
		if ( !isset($this->_file['wordlets']) || !isset($this->_file['wordlets'][$name]) ) return $values;

		$wordlet = $this->_file['wordlets'][$name];

		if ( $wordlet->type == 'object' ) {
			$names = $wordlet->default;
		} else {
			$names = array('value' => $wordlet->default);
		}

		$value_prefix = $this->_file['name'] . '__' . $name;
		$instance_values = array();
		foreach ( $names as $name => $def ) {
			$value_name = $value_prefix . '__' . $name;

			if ( isset($this->_instance[$value_name]) ) {
				$instance_values[$name] = __( $this->_instance[$value_name], $this->text_domain );
				$has_something = true;
			} else {
				$instance_values[$name] = '';
			}
		}

		if ( $has_something ) {
			return new Wordlets_Wordlet_Value( $instance_values );
		} else {
			return null;
		}
	}

	/**
	 * 
	 */

	public function get_value($wordlet, $name, $position) {
		if ( $wordlet->type == 'object' ) {
			$names = $wordlet->default;
		} else {
			$names = array('value' => $wordlet->default);
		}

		$value_prefix = $this->_file['name'] . '__' . $wordlet->_name . (($position !== null)? '__' . $position : '');
		$value_name = $value_prefix . (($name != null)?'__' . $name:'');

		if ( isset($this->_instance[$value_name]) ) {
			return $this->_instance[$value_name];
		}

		return '';
	}

	/**
	 * 
	 */

	public function has_values($wordlet, $position) {
		if ( $wordlet->type == 'object' ) {
			$names = $wordlet->default;
		} else {
			$names = array('value' => $wordlet->default);
		}

		$value_prefix = $this->_file['name'] . '__' . $wordlet->_name . '__' . $position;
		foreach ( $names as $name => $def ) {
			$value_name = $value_prefix . '__' . $name;

			if ( isset($this->_instance[$value_name]) ) {
				return true;
			}
		}

		return false;
	}

	public static $WordletFiles = null;

	/**
	 * Return information/wordlet settings in a single wordlet file or all wordlet files within theme.
	 */

	private function _get_wordlet_files( $template = null ) {
		$wordlets_files = array();
		$tpl_dir = get_template_directory();

		if ( $template ) {
			if ( has_action('wordlets-get-cache-file-parse') ) {
				$data = do_action('wordlets-get-cache-file-parse', 'wordlets-file-' . $name);
			} else {
				$data = wp_cache_get('wordlets-file-' . $template, 'wordlets-file');
			}

			if ( false !== $data ) return $data;

			if ( isset(self::$WordletFiles[$template]) ) return self::$WordletFiles[$template];

			$files = array(
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . $template . '.php',
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . 'wordlet-' . $template . '.php',
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets-' . $template . '.php',
			);

			foreach ( $files as $file ) {
				if ( is_readable($file) ) {
					$wf = $this->_parse_file( $file, $template );
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
					$wordlets_files[$name] = $this->_parse_file( $tpl_dir . DIRECTORY_SEPARATOR . $file, $name );
				}
			}

			// Then scan the wordlets dir (override root files)
			$wordlets_dir = $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets';
			if ( is_readable($wordlets_dir) && $files = scandir( $wordlets_dir ) ) {
				foreach ( $files as $file ) {
					if ( preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) || preg_match('/^(.+)\.php$/', $file, $matches) ) {
						$name = $matches[1];
						$wordlets_files[$name] = $this->_parse_file( $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . $file, $name );
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

	private function _parse_file( $file, $name ) {
		$file_props = array('file' => $file);
		$content = file_get_contents($file);
		$wordlets = array();

		// Get properties in first comment
		if ( preg_match('/\/\*\*(.*)\*\//is', $content, $matches) && !empty($matches[1]) ) {
			$comment = preg_replace('/([\r\n])*\s*\*\s*/', '$1', $matches[1]);

			if ( preg_match_all('/@((name)|(description))\s*(.+?)(?=[\r\n]@)/is', $comment, $properties) && !empty($properties[1]) ) {
				foreach ( $properties[1] as $key => $property ) {
					$file_props[strtolower(trim($property))] = trim($properties[4][$key]);
				}
			}

			//1 Array
			//2 type
			//3 name
			//4 default or attributes or options
			//8 Label
			//11 Description
			if ( preg_match_all('/@wordlet(Array)?\s*([a-z]+)\s+\$([a-z][a-z_0-9]+)\s*(([a-z0-9]+)|("[^"]*")|(\{[^\}]*\}))\s*(([a-z]+)|("[^"]*"))?\s*(([a-z]+)|("[^"]*"))?\s*/is', $comment, $w) ) {
				foreach ( $w[2] as $key => $type ) {
					if ( false === array_search($type, array_keys( $this->types ) ) ) continue;

					$wordlet = new Wordlets_Wordlet($w[3][$key], $w[2][$key], trim($w[4][$key], '"'), trim($w[8][$key], '"'), trim($w[11][$key], '"'), trim($w[1][$key], '"'));
					$wordlets[$w[3][$key]] = $wordlet;
				}
			}
		}

		$data = array('name' => $name, 'props' => $file_props, 'wordlets' => $wordlets);

		// Allow themes to define their own caching method
		if ( has_action('wordlets-set-cache-file-parse') ) {
			do_action('wordlets-set-cache-file-parse', 'wordlets-file-' . $name, $data);
		} else {
			wp_cache_set('wordlets-file-' . $name, $data, 'wordlets-file', 3600 * 24 * 365);
		}

		return $data;
	}

	static $me;

	/**
	 * See if current widget's wordlet has any values
	 */

	static function HasValues($wordlet, $position) {
		return self::$me->has_values($wordlet, $position);
	}

	/**
	 * Get current widget's value
	 */

	static function GetValue($wordlet, $name, $position = null) {
		return self::$me->get_value($wordlet, $name, $position);
	}
}

/**
 * Container for admin input settings.
 */

class Wordlets_Wordlet implements Iterator {
	public $_name;
	public $type;
	public $default;
	public $label;
	public $description;
	public $is_array;

	public function __construct($name, $type, $default = null, $label = null, $description = null, $is_array = false) {
		$this->position = 0;

		$this->_name = $name;
		$this->type = $type;

		if ( $type == 'object' && !is_array($default) ) {
			//1 type
			//3 name
			//4 default
			//7 Label
			preg_match_all('/([a-z]+)(\s*\$([a-z_0-9]+)\s*(([a-z]+)|("[^"]*"))?\s*(([a-z]+)|("[^"]*"))?\s*,?)+/is', $default, $matches);

			$default = array();
			foreach ( $matches[3] as $key => $val ) {
				$default[$val] = new Wordlets_Wordlet($val, $matches[1][$key], trim($matches[4][$key], '"'), trim($matches[7][$key], '"'));
			}
		} elseif ( $type == 'select' && !is_array($default) ) {
			//2 value
			//3 text
			//4 Text
			preg_match_all('/[\{\r\n\s]*(([^,]+?)\s*=\s*([^,\}\r\n]+))|([^,\{\}\=\r\n]+)\s*[,\}\r\n]/i', $default, $matches);

			$default = array();
			foreach ( $matches[2] as $key => $val ) {
				if ( !empty($val) ) {
					$default[$val] = $matches[3][$key];
				} else {
					$default[$matches[4][$key]] = trim($matches[4][$key]);
				}
			}
		}

		$this->default = $default;
		$this->label = ( $label ) ? $label : $name;
		$this->description = $description;

		$this->is_array = $is_array;
	}

	public function __get($name) {
		if ( $this->is_array ) {
			return Wordlets_Widget::GetValue($this, $name, $this->position);
		} else {
			$value = Wordlets_Widget::GetValue($this, $name);
			return $value;
		}
	}

	public function __toString() {
		if ( $this->is_array ) {
			$value = Wordlets_Widget::GetValue($this, 'value', $this->position);
		} else {
			$value = Wordlets_Widget::GetValue($this, 'value');
		}
		return '' . $value;
	}

	// Iterable
	private $position = 0;

	function rewind() {
		$this->position = 0;
	}

	function current() {
		if ( $this->is_array ) {
			$value = Wordlets_Widget::GetValue($this, 'value', $this->position);
		} else {
			$value = Wordlets_Widget::GetValue($this, 'value');
		}

		if ( preg_match('/^\[tags\]([0-9]+)$/', $value, $matches) ) {
			return get_tag($matches[1]);
		} elseif ( preg_match('/^\[categories\]([0-9]+)$/', $value, $matches) ) {
			return get_category($matches[1]);
		}

		return $this;
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		$valid = Wordlets_Widget::HasValues($this, $this->position);

		return $valid;
		//return isset($this->array[$this->position]);
	}	
}
