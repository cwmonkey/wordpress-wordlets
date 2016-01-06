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
	register_widget( 'Wordlets_Widget' );
}

add_action( 'widgets_init', 'load_wordlets_widget' );

function enqueue_widget_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_script( 'jquery_floatLabels', plugins_url('js/floatLabels.js', __FILE__), array( 'jquery' ), Wordlets_Widget::VERSION );
	wp_enqueue_script( 'wordlets_widget', plugins_url('js/wordlets-admin.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), Wordlets_Widget::VERSION );

	wp_enqueue_style( 'wordlets_widget', plugins_url('wordlets-admin.css', __FILE__), null, Wordlets_Widget::VERSION );
	wp_enqueue_style("wp-jquery-ui-dialog");

	// image input
	if ( ! did_action( 'wp_enqueue_media' ) ) wp_enqueue_media();
	wp_enqueue_script( 'custom-header' );
}

add_action( 'widgets_admin_page', 'enqueue_widget_scripts' );

/**
 * Adds allowable input types to wordlets.
 */
function register_wordlets_input( $name ) {
	$input = new $name();
	Wordlets_Widget::$Types[$input->name]->object = $input;
}

/**
 * Wordlets_Widget class
 **/
class Wordlets_Widget extends WP_Widget {
	/********************************
	 * CONSTANTS:
	 ********************************/

	const VERSION = '1.0';

	/********************************
	 * STATIC:
	 ********************************/

	/**
	 * Wordpress text domain for translations.
	 */
 	public static $TextDomain = 'widget-text-domain';

	/**
	 * Page types for wordlet show/hide panel in admin.
	 */
	public static $PageTypes = array(
		/*'default' => array('title' => 'Default Homepage', 'description' => 'is_front_page() && is_home()'),
		'static' => array('title' => 'Static Homepage', 'description' => 'is_front_page()'),
		'blog' => array('title' => 'Blog Homepage', 'description' => 'is_home()'),*/
		'home' => array( 'title' => 'Homepage', 'description' => 'is_front_page() || is_home()' ),
		'page' => array( 'title' => 'Custom Page', 'description' => '' ),
		'archive' => array( 'title' => 'Any Archive page', 'description' => 'category, tag, tax, date, author etc' ),
		'search' => array( 'title' => 'Search', 'description' => '' ),
		'404' => array( 'title' => '404', 'description' => '' ),
		'single' => array( 'title' => 'Individual Post', 'description' => '' )
	);

	/**
	 * Wordlet show/hide panel verbiage in admin.
	 */
	public static $ShowHides = array(
		'show' => 'Show widget on these pages',
		'hide' => 'Hide widget on these pages'
	);

	/**
	 * Default Wordlet admin input types.
	 */
	public static $DefaultWordletTypes = array( 'image', 'text', 'textarea', 'number', 'select', 'checkbox' );

	/**
	 * Set up wordlet types.
	 */
	public static $Types = array();

	/**
	 * Cached parsed wordlet file objects.
	 */
	public static $WordletFiles = null;

	/**
	 * Add wordlet type/admin input.
	 *
	 * Used for adding custom inputs within your {theme}/wordlets/inputs directory.
	 *
	 * @param string $name    Short name of type [a-z0-9_].
	 * @param string $file    Full file path of admin input template.
	 */
	public static function AddWordletType( $name, $file ) {
		if ( $name !== 'object' && !is_readable($file) ) {
			trigger_error("Unable to read wordlet input $file", E_USER_WARNING);
			return false;
		} 

		$type = new stdClass();
		$type->name = $name;
		$type->file = $file;
		self::$Types[$name] = $type;

		if ( $name !== 'object' ) {
			include ( $file );
		}

		return true;
	}

	/**
	 * Returns the page type currently being displayed.
	 */
	public static function GetPageType() {
		$type = null;		

		if ( is_front_page() && is_home() ) {
			// Default homepage
			// $type = 'default';
			$type = 'home';
		} elseif ( is_front_page() ) {
			// static homepage
			// $type = 'static';
			$type = 'home';
		} elseif ( is_home() ) {
			// blog page
			// $type = 'blog';
			$type = 'home';
		} elseif ( is_page_template() ) {
			// returns true or false depending on whether a custom page template was used to render the Page.
			$type = 'page';
		} elseif ( is_archive() ) {
			// category, tag, tax, date, author etc
			$type = 'archive';
		} elseif ( is_search() ) {
			$type = 'search';
		} elseif ( is_404() ) {
			$type = 'is_404';
		} elseif ( is_single() ) {
			// post page
			$type = 'single';
		}

		return $type;
	}

	/**
	 * Can widget display on this page?
	 */
	public static function CanShowWidget( &$instance ) {
		// Look for pagination constraints
		if ( !empty( $instance['paged'] ) ) {
			$paged = is_paged();

			if ( $instance['paged'] === 'first' && $paged ) {
				return false;
			} elseif ( $instance['paged'] !== 'first' && !$paged ) {
				return false;
			}
		}

		// Look for login constraints
		$logged = '';
		if ( !empty( $instance['logged'] ) ) {
			$logged = $instance['logged'];
		}

		$is_user_logged_in = is_user_logged_in();
		if ( $logged && ( ( $logged === 'yes' && !$is_user_logged_in ) || ($logged === 'no' && $is_user_logged_in) ) ) {
			return false;
		}

		// Look for page type constraints
		$page_type = self::GetPageType();

		$showhides = array('hide' => false, 'show' => true);

		foreach ( $showhides as $sh => $ret ) {
			// See if show/hide checkbox was checked
			if ( !empty( $instance['showhide_' . $sh] ) ) {
				// Page type was blanket shown/hidden
				if ( !empty( $instance['page_type_' . $sh . '_' . $page_type] ) ) {
					if ( $page_type === 'archive' && is_category() && !empty( $instance['usage_category_' . $sh] ) ) {
					} elseif ( $page_type === 'single' && ( !empty( $instance['usage_category_' . $sh] ) || !empty( $instance['ids_' . $sh] ) ) ) {
					} elseif ( $page_type === 'page' && !empty( $instance['template_' . $sh . '_' . $page_template] ) ) {
						// do nothing for the above since they should be inclusive
					} else {
						return $ret;
					}
				}

				// If current page is an archive type, check for category constraints
				// TODO: Add tag constraints?
				if ( $page_type === 'archive' ) {
					if ( is_category() && !empty( $instance['usage_category_' . $sh] ) ) {
						$cat = get_query_var('cat');
						$category = get_category($cat);

						if ( !empty( $instance['category_' . $sh . '_' . $category->term_id] ) ) {
							return $ret;
						}
					}
				// If current page is a post, check for categories and specific post id constraints
				} elseif ( $page_type === 'single' ) {
					if ( !empty( $instance['usage_category_' . $sh] ) ) {
						$categories = get_categories( array( 'hide_empty' => 0 ) );

						foreach ( $categories as $category ) {
							$in_category = in_category( $category->term_id );

							if ( $in_category ) {
								if ( !empty( $instance['category_' . $sh . '_' . $category->term_id] ) ) {
									return $ret;
								}
							}
						}
					}

					if ( !empty( $instance['ids_' . $sh] ) ) {
						$ids = explode( ', ', $instance['ids_' . $sh] );
						$found = ( array_search( get_the_ID(), $ids ) !== false );

						if ( $found ) {
							return $ret;
						}
					}
				// If current page is a custom page, check for custom page template constraints
				} elseif ( $page_type === 'page' ) {
					$page_template = get_page_template();

					if ( !empty( $instance['template_' . $sh . '_' . $page_template] ) ) {
						return $ret;
					}
				}

				// If we're on the show loop (last), return a false if no show constraints matched
				// TODO: Word that comment better :P
				if ( $sh === 'show' ) return false;
			}
		}

		return true;
	}

	/**
	 * Update wordlet values in widget instance.
	 */
	public static function UpdateInstance( $instance, $new_instance, $value_prefix, $wordlet, $new_value_prefix = '', $i = null ) {
		$names = array();
		if ( $wordlet->_type == 'object' ) {
			$names = $wordlet->_default;
		} else {
			$names = array('value' => $wordlet->_default);
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

	/**
	 * Return information/wordlet settings in a single wordlet file or all wordlet files within theme.
	 */
	public static function GetWordletFiles( &$instance, $template = null ) {
		$wordlets_files = array();
		$tpl_dir = get_template_directory();

		if ( $template ) {
			if ( has_action('wordlets-get-cache-file-parse') ) {
				$data = do_action('wordlets-get-cache-file-parse', 'wordlets-file-' . $name);
			} else {
				$data = wp_cache_get('wordlets-file-' . $template, 'wordlets-file');
			}

			if ( false !== $data ) return $data;

			if ( isset( self::$WordletFiles[$template] ) ) return self::$WordletFiles[$template];

			$files = array(
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . $template . '.php',
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . 'wordlet-' . $template . '.php',
				$tpl_dir . DIRECTORY_SEPARATOR . 'wordlets-' . $template . '.php',
			);

			foreach ( $files as $file ) {
				if ( is_readable($file) ) {
					$wf = self::ParseFile( $file, $instance, $template );
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
					$wordlets_files[$name] = self::ParseFile( $tpl_dir . DIRECTORY_SEPARATOR . $file, $instance, $name );
				}
			}

			// Then scan the wordlets dir (override root files)
			$wordlets_dir = $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets';
			if ( is_readable($wordlets_dir) && $files = scandir( $wordlets_dir ) ) {
				foreach ( $files as $file ) {
					if ( preg_match('/^wordlets\-(.+)\.php$/', $file, $matches) || preg_match('/^(.+)\.php$/', $file, $matches) ) {
						$name = $matches[1];
						$wordlets_files[$name] = self::ParseFile( $tpl_dir . DIRECTORY_SEPARATOR . 'wordlets' . DIRECTORY_SEPARATOR . $file, $instance, $name );
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
	public static function ParseFile( $file, &$instance, $name ) {
		$file_props = array('file' => $file);
		$content = file_get_contents( $file );
		$wordlets = array();
		$data = array();

		// Get properties in first comment
		if ( preg_match( '/\/\*\*(.*)\*\//is', $content, $matches ) && !empty( $matches[1] ) ) {
			$comment = preg_replace( '/([\r\n]+)*[ \t]*\*[ \t]*/is', '$1', $matches[1] );

			if ( preg_match_all( '/@((name)|(description)|(class))\s*([^@]+?)(?=[\r\n]*@)/is', $comment, $properties ) && !empty( $properties[1] ) ) {
				foreach ( $properties[1] as $key => $property ) {
					$file_props[strtolower( trim( $property ) )] = trim( $properties[5][$key] );
				}
			}

			//1 Array
			//2 type
			//3 name
			//4 default or attributes or options
			//8 Label
			//11 Description
			if ( preg_match_all( '/@wordlet(Array)?\s*([a-z]+)\s+\$([a-z][a-z_0-9]+)\s*(([a-z0-9]+)|("[^"]*")|(\{[^\}]*\}))\s*(([a-z]+)|("[^"]*"))?\s*(([a-z]+)|("[^"]*"))?\s*/is', $comment, $w ) ) {
				foreach ( $w[2] as $key => $type ) {
					if ( false === array_search( $type, array_keys( self::$Types ) ) ) continue;

					$wordlet = new Wordlets_Wordlet( $name, $instance, $w[3][$key], $w[2][$key], trim( $w[4][$key], '"' ), trim( $w[8][$key], '"' ), trim( $w[11][$key], '"' ), trim( $w[1][$key], '"' ) );
					$wordlets[$w[3][$key]] = $wordlet;
				}
			}
		}

		$data['name'] = $name;
		$data['props'] = $file_props;
		$data['wordlets'] = $wordlets;

		// Allow themes to define their own caching method
		if ( has_action('wordlets-set-cache-file-parse') ) {
			do_action('wordlets-set-cache-file-parse', 'wordlets-file-' . $name, $data);
		} else {
			wp_cache_set('wordlets-file-' . $name, $data, 'wordlets-file', 3600 * 24 * 365);
		}

		return $data;
	}

	/**
	 * Set wordlet values to current widget instance.
	 */
	public static function SetInstance( &$instance, &$wordlets ) {
		foreach ( $wordlets as $wordlet ) {
			$wordlet->set_instance( $instance );
		}
	}

	/********************************
	 * INSTANCED:
	 ********************************/

	/****************
	 * WordPress Defaults:
	 ****************/

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wordlets_widget', // Base ID
			__( 'Wordlets', self::$TextDomain ), // Name
			array( 'description' => __( 'Widgets for Developers', self::$TextDomain ), ) // Args
		);

		$this->_plugin_dir_path = plugin_dir_path( __FILE__ );

		//if ( is_admin() ) {
			// Allow themes to add admin scripts
			// do_action( 'wordlets_admin_construct', $this );
		//}

		// Only do this once per page
		if ( !count( self::$Types ) ) {
			// "object" is a wordlet type with no associated input file
			self::AddWordletType( 'object', null );

			foreach ( self::$DefaultWordletTypes as $input ) {
				self::AddWordletType( $input, $this->_plugin_dir_path . 'inputs/' . $input . '.php' );
			}

			// Allow themes to add inputs
			do_action( 'wordlets_construct', $this );

			// This is when all the input objects are created
			do_action( 'wordlets_input_construct', $this );
		}
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
		$show = self::CanShowWidget( $instance );

		if ( !$show ) return false;

		$this->_file = $file = self::GetWordletFiles( $instance, $instance['template'] );
		$this->_instance = $instance;

		self::SetInstance( $instance, $file['wordlets'] );

		extract($file['wordlets']);

		ob_start();

		/**
		 * Include the wordlet file from {theme}/wordlet-{name}.php or {theme}/wordlets/{name}.php
 		 */
		$retval = include($file['props']['file']);

		$output = ob_get_clean();

		if ( $retval ) {
			$pathinfo = pathinfo($file['props']['file']);
			$filename = $pathinfo['filename'];
			$classname = ( !empty($file['props']['class']) ) ? $file['props']['class'] . ' ' : '' ;

			echo preg_replace('/class="/', 'class="wordlet-' . $filename . ' ' . $classname, $args['before_widget'], 1);

			echo $output;
		}

		if ( current_user_can( 'manage_options' ) ) {
			echo '<a href="' . admin_url( 'widgets.php?' . $args['widget_id'] ) . '" target="_blank" class="wordlets-admin-link">' . __( 'Edit', self::$TextDomain ) . '</a>';
		}

		if ( $retval ) {
			echo $args['after_widget'];
		}
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$wordlets_files = self::GetWordletFiles( $instance );

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} elseif ( count($wordlets_files) ) {
			$array_keys_wordlets_files = array_keys($wordlets_files);
			$props = $wordlets_files[$array_keys_wordlets_files[0]]['props'];
			$title = __( $props['name'] . ((isset($props['description']))?' (' . $props['description'] . ')':''), self::$TextDomain );
		} else {
			$title = __( 'New Wordlet', self::$TextDomain );
		}

		if ( isset( $instance[ 'class' ] ) ) {
			$class = $instance[ 'class' ];
		}

		$pages = wp_get_theme()->get_page_templates();
		$categories = get_categories(array('hide_empty' => 0));

		?>
		<div class="wordlets-widget-wrapper">
			<fieldset class="wordlets-widget-setup <?php echo ( !empty($instance['hide']) ) ? 'wordlets-widget-hide' : '' ?>">
				<label class="wordlet-widget-headline"><?=__( 'Wordlet Setup' ) ?>:</label>
				<p>
					<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template:' ); ?></label> 
					<select class="wordlets-widget-template" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>">
					<?php

					foreach ( $wordlets_files as $fname => $info ) {
						$display = $fname;
						if ( isset($info['props']['name']) ) {
							$display = $info['props']['name'];
						}

						if ( !empty($info['props']['description']) ) {
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
				<p>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (not shown on widget):' ); ?></label> 
					<input class="widefat wordlet-widget-title" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
				</p>

				<fieldset class="wordlets-widget-limit">
					<legend><?php echo __('Display widget for') ?>:</legend>
					<p>
					<select name="<?php echo $this->get_field_name( 'logged' ); ?>">
						<option value=""><?php echo __('All users') ?></option>
						<option value="yes" <?php echo ( !empty($instance['logged']) && 'yes' == $instance['logged'] ) ? 'selected="selected"' : '' ?>><?php echo __('Logged in users') ?></option>
						<option value="no" <?php echo ( !empty($instance['logged']) && 'no' == $instance['logged'] ) ? 'selected="selected"' : '' ?>><?php echo __('Logged out users') ?></option>
					</select>
					</p>
					<p>
					<select name="<?php echo $this->get_field_name( 'paged' ); ?>">
						<option value=""><?php echo __('All paginated pages') ?></option>
						<option value="first" <?php echo ( !empty($instance['paged']) && 'first' == $instance['paged'] ) ? 'selected="selected"' : '' ?>><?php echo __('First page only'); ?></option>
						<option value="paged" <?php echo ( !empty($instance['paged']) && 'paged' == $instance['paged'] ) ? 'selected="selected"' : '' ?>><?php echo __('All pages after the first'); ?></option>
					</select>
					</p>
					<?php foreach ( self::$ShowHides as $sh => $title ) { ?>
						<p class="wordlets-widget-pages-showhide">
							<input type="checkbox" name="<?php echo $this->get_field_name( 'showhide_' . $sh ); ?>" id="<?php echo $this->get_field_id( 'showhide_' . $sh ); ?>" <?php echo ( !empty($instance['showhide_' . $sh]) ) ? 'checked="checked"' : ''; ?>>
							<label for="<?php echo $this->get_field_id( 'showhide_' . $sh ); ?>">
								<?php echo __($title) ?>:
							</label>
						</p>
						<fieldset class="wordlets-widget-pages <?php echo ( empty( $instance['showhide_' . $sh] ) ) ? 'wordlets-widget-hide' : ''; ?>">
							<fieldset class="wordlets-widget-categories">
								<legend><?php echo __('Categories') ?></legend>
								<?php foreach ( $categories as $category ) { ?>
									<p>
									<input type="checkbox" name="<?php echo $this->get_field_name( 'category_' . $sh . '_' . $category->term_id ); ?>" id="<?php echo $this->get_field_id( 'category_' . $sh . '_' . $category->term_id ); ?>" <?php echo ( !empty($instance['category_' . $sh . '_' . $category->term_id]) ) ? 'checked="checked"' : ''; ?>>
									<label for="<?php echo $this->get_field_id( 'category_' . $sh . '_' . $category->term_id ); ?>"><?php echo $category->name; ?>:</label>
									</p>
								<?php } ?>
							</fieldset>
							<fieldset class="wordlets-widget-page-types">
								<legend><?php echo __('Page Types') ?></legend>
								<?php foreach ( self::$PageTypes as $key => $type ) { ?>
									<p>
									<input type="checkbox" name="<?php echo $this->get_field_name( 'page_type_' . $sh . '_' . $key ); ?>" id="<?php echo $this->get_field_id( 'page_type_' . $sh . '_' . $key ); ?>" <?php echo ( !empty($instance['page_type_' . $sh . '_' . $key]) ) ? 'checked="checked"' : ''; ?>>
									<label for="<?php echo $this->get_field_id( 'page_type_' . $sh . '_' . $key ); ?>" title="<?php echo esc_attr( $type['description'] ); ?>"><?php echo $type['title']; ?></label>
									</p>
								<?php } ?>
							</fieldset>
							<fieldset class="wordlets-widget-templates">
								<legend><?php echo __('Custom Pages') ?></legend>
								<?php foreach ( $pages as $key => $page ) { ?>
									<p>
									<input type="checkbox" name="<?php echo $this->get_field_name( 'template_' . $sh . '_' . $key ); ?>" id="<?php echo $this->get_field_id( 'template_' . $sh . '_' . $key ); ?>" <?php echo ( !empty($instance['template_' . $sh . '_' . $key]) ) ? 'checked="checked"' : ''; ?>>
									<label for="<?php echo $this->get_field_id( 'template_' . $key ); ?>"><?php echo $page; ?></label>
									</p>
								<?php } ?>
							</fieldset>
							<p>
							<label for="<?php echo $this->get_field_id( 'ids_' . $sh ); ?>"><?php echo __('Comma Separated list of post IDs'); ?>:</label>
							<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'ids_' . $sh ); ?>" id="<?php echo $this->get_field_id( 'ids_' . $sh ); ?>" value="<?php echo ( !empty($instance['ids_' . $sh]) ) ? esc_attr( $instance['ids_' . $sh] ) : ''; ?>">
							</p>
						</fieldset>
					<?php } ?>
				</fieldset>
			</fieldset>
			<p class="wordlets-widget-showhide">
				<input type="checkbox" name="<?php echo $this->get_field_name( 'hide' ); ?>" id="<?php echo $this->get_field_id( 'hide' ); ?>" <?php echo ( !empty($instance['hide']) ) ? 'checked="checked"' : ''; ?>>
				<label for="<?php echo $this->get_field_id( 'hide' ); ?>">
					<?php echo __('Hide Setup Options'); ?>
				</label>
			</p>
		<?php

		$loops = 0;
		foreach ( $wordlets_files as $fname => $info ) {
			?>
			<fieldset class="wordlet-widget-set <?php echo ( (!isset($instance['template']) && $loops) || (isset($instance['template']) && $fname != $instance['template']) )?'':'active'; ?>" data-template="<?php echo esc_attr( $fname ); ?>">
			<?php
			foreach ( $info['wordlets'] as $wname => $wordlet ) {
				$friendly_name = $wname;
				if ( isset($wordlet->_label) ) {
					$friendly_name = $wordlet->_label;
				}

				if ( $wordlet->_is_array ) {
					?>
					<fieldset class="wordlet-array">
					<legend><?php _e( $friendly_name ); ?></legend>
					<?php
					$value_prefix = $fname . '__' . $wname;
					for ( $i = 0; $i < 100; $i++ ) {
						$value_name = $value_prefix . '__' . $i;

						$id_extra = '_' . preg_replace('/[\.\s]/', '_', microtime());

						if ( $wordlet->_type == 'object' ) {
							$names = $wordlet->_default;
						} else {
							$names = array('value' => $wordlet->_default);
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
								$this->_input($value_name, $i, $wordlet, $instance, false, $i, $value_prefix, $id_extra);
							?></div><?php
						} else {
							?><div class="wordlet-array-item"><?php
								$this->_input($value_name, $i, $wordlet, $instance, true, $i, $value_prefix, $id_extra);
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
			<div class="wordlet-widget-code">
				<label class="wordlet-widget-headline" for="widget-wordlet-<?=$this->id ?>"><?php echo __( 'Shortcode' ) ?>:</label>
				<p><?php echo ( ( $this->number == '__i__' ) ? __( 'Please save this first.' ) : '<input type="text" readonly class="widget-wordlet-code-input" id="widget-wordlet-<?=$this->id ?>" value="[wordlet id=&quot;' . $instance['template'] . '-' . $this->number .'&quot;]">' ) ?></p>
			</div>
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
		$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['template'] = ( !empty( $new_instance['template'] ) ) ? $new_instance['template'] : '';
		$instance['paged'] = ( !empty( $new_instance['paged'] ) ) ? $new_instance['paged'] : '';
		$instance['logged'] = ( !empty( $new_instance['logged'] ) ) ? $new_instance['logged'] : '';
		$instance['hide'] = ( !empty( $new_instance['hide'] ) ) ? 1 : '';

		foreach ( self::$ShowHides as $sh => $title ) {
			$instance['showhide_' . $sh] = ( !empty( $new_instance['showhide_' . $sh] ) ) ? 1 : '';

			if ( !empty( $new_instance['ids_' . $sh] ) ) {
				$ids = explode(',', $new_instance['ids_' . $sh]);
				$instance['ids_' . $sh] = implode(', ', $ids);
			}

			foreach ( self::$PageTypes as $key => $page ) {
				$instance['page_type_' . $sh . '_' . $key] = ( !empty( $new_instance['page_type_' . $sh . '_' . $key] ) ) ? $new_instance['page_type_' . $sh . '_' . $key] : '';
			}

			$tpls = wp_get_theme()->get_page_templates();
			$tpl_found = false;
			foreach ( $tpls as $key => $tpl ) {
				if ( !empty( $new_instance['template_' . $sh . '_' . $key] ) ) {
					$instance['template_' . $sh . '_' . $key] = $new_instance['template_' . $sh . '_' . $key];
					$tpl_found = true;
				} else {
					$instance['template_' . $sh . '_' . $key] = '';
				}
			}

			$instance['usage_template_' . $sh] = ( $tpl_found ) ? 1 : '';

			$categories = get_categories(array('hide_empty' => 0));
			$category_found = false;
			foreach ( $categories as $category ) {
				if ( !empty( $new_instance['category_' . $sh . '_' . $category->term_id] ) ) {
					$instance['category_' . $sh . '_' . $category->term_id] = $new_instance['category_' . $sh . '_' . $category->term_id];
					$category_found = true;
				} else {
					$instance['category_' . $sh . '_' . $category->term_id] = '';
				}
			}

			$instance['usage_category_' . $sh] = ( $category_found ) ? 1 : '';
		}

		$file = self::GetWordletFiles( $instance, $instance['template'] );

		if ( !isset($file['wordlets']) ) return $instance;

		foreach ( $file['wordlets'] as $wname => $wordlet ) {
			$value_prefix = $file['name'] . '__' . $wname;
			if ( $wordlet->_is_array ) {
				$k = 0;
				for ( $i = 0; $i < 100; $i++ ) {
					$has_something = false;
					$value_name_prefix = $value_prefix;

					if ( $wordlet->_type == 'object' ) {
						$names = $wordlet->_default;
					} else {
						$names = array('value' => $wordlet->_default);
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
						$instance = self::UpdateInstance( $instance, $new_instance, $value_name_prefix, $wordlet, $new_value_name_prefix, $i );
						$k++;
					}
				}
			} else {
				$instance = self::UpdateInstance( $instance, $new_instance, $value_prefix, $wordlet );
			}
		}

		return $instance;
	}

	/****************
	 * Custom:
	 ****************/

	/**
	 * Path to Wordlet plugin directory.
	 */
	private $_plugin_dir_path = '';

	/**
	 * Current wordlet set parsed file.
	 */
	private $_file;

	/**
	 * Current widget instance.
	 */
	private $_instance;

	/**
	 * Alias self::AddWordletType to be a little less weird for theme actions.
	 */
	public function add_wordlet_type( $name, $file ) {
		self::AddWordletType( $name, $file );
	}

	/**
	 * Render wordlet admin input values.
	 */
	private function _input( $value_prefix, $friendly_name, $wordlet, &$instance, $blank = false, $hide_labels = false, $array_value_prefix = '', $id_extra = '' ) {
		if ( $wordlet->_type == 'object' ) {
			?>
			<fieldset class="wordlet-object">
			<?php
				if ( !$wordlet->_is_array ) {
					?><legend><?php echo __( $friendly_name ) ?></legend><?php
				}

				if ( $wordlet->_description ) { ?>
					<label class="wordlet-description"><?php echo $wordlet->_description ?></label><?php
				}

				foreach ( $wordlet->_default as $name => $w ) {
					$this->_render_input( $instance, $value_prefix, $w->_label, $name, $w->_type, $w->_default, $w->_description, $array_value_prefix, false, $id_extra );
				}
			?>
			</fieldset>
			<?php
		} else {
			$description = '';
			if ( isset($wordlet->_description) ) {
				$description = $wordlet->_description;
			}

			$default = $wordlet->_default;
			$type = $wordlet->_type;

			if ( $type == 'image' ) $default = '';

			$this->_render_input( $instance, $value_prefix, $friendly_name, 'value', $type, $default, $description, $array_value_prefix, $wordlet->_is_array, $id_extra );
		}
	}

	/**
	 * Render wordlet admin input value.
	 */
	private function _render_input( &$instance, $value_prefix, $friendly_name, $name, $type, $default = '', $description = '', $array_value_prefix = '', $hide_labels = false, $id_extra = '' ) {
		$show_key = false; // TODO: See if I need to get rid of this.

		$value_name = $value_prefix . '__' . $name;

		if ( isset( $instance[$value_name] ) ) {
			$value = $instance[$value_name];
		} else {
			$value = $default;
		}

		$value_array = '';
		if ( $array_value_prefix ) {
			$value_array = '[]';
			$value_prefix = $array_value_prefix;
			$value_name = $array_value_prefix . '__' . $name;
		}

		$input_id = $this->get_field_id( $value_name ) . $id_extra;
		$input_name = $this->get_field_name( $value_name ) . $value_array;
		$text_domain = self::$TextDomain;
		$options = $default;
		$widget = $this;

		ob_start();
		?>
			<label for="<?php echo $input_id; ?>" class="wordlet-input-label">
				<?php echo __( $friendly_name, self::$TextDomain ) . ( ( $type != 'checkbox' ) ? ':' : '' ); ?>
			</label>
		<?php

		$default_label = ob_get_contents();
		ob_end_clean();

		?>
		<div class="wordlet-input wordlet-input-<?php echo $type ?>">
			<?php if ( isset( self::$Types[$type] ) ) {
				self::$Types[$type]->object->form_input( get_defined_vars() );
			} else {
				trigger_error( 'Could not find template for wordlet "' . $name . '".', E_USER_WARNING );
			} ?>
		</div>
		<?php
	}
}

/**
 * Interface for adding wordlet inputs.
 */
interface Wordlets_Widget_Input {
	/**
	 * Put your admin scripts etc here.
	 */
	public function __construct();

	/**
	 * displaying the input in the admin widget form.
	 */
	public function form_input($args);
}

/**
 * Container for admin input settings.
 */
class Wordlets_Wordlet implements Iterator {
	public $_name;
	public $_type;
	public $_default;
	public $_label;
	public $_description;
	public $_is_array;
	public $_instance;
	public $_file_name;

	public function __construct( $file_name, &$instance, $name, $type, $default = null, $label = null, $description = null, $is_array = false ) {
		$this->position = 0;

		$this->_name = $name;
		$this->_type = $type;

		if ( $type == 'object' && !is_array( $default ) ) {
			//1 type
			//2 name
			//3 default or attributes or options
			//7 Label
			//10 Description

			if ( preg_match_all( '/([a-z]+)\s+\$([a-z][a-z_0-9]+\s*)(([a-z0-9]+)|("[^"]*")|(\([^\)]*\))\s*)?(([a-z]+)|("[^"]*")\s*)?(([a-z]+)|("[^"]*")\s*)?,?/is',  $default, $matches ) ) {
				$default = array();
				foreach ( $matches[2] as $key => $val ) {
					$val = trim($val);
					$matches[3][$key] = preg_replace( '/^\(([^\)]+)\)$/', '{$1}', trim( $matches[3][$key] ) );

					$default[$val] = new Wordlets_Wordlet( $file_name, $instance, $val, $matches[1][$key], trim( trim( $matches[3][$key] ), '"' ), trim( trim( $matches[7][$key] ), '" ' ), trim( trim( $matches[10][$key] ), '" ' ) );
				}
			}
		} elseif ( $type == 'select' && !is_array( $default ) ) {
			//2 name
			//3 value
			//6 Text
			preg_match_all( '/[\{\r\n\s]*(([^,]+?)\s*=\s*(("[^"]*")|([^,\}\r\n]+)))|([^,\{\}\=\r\n]+)\s*[,\}\r\n]/i', $default, $matches );

			$default = array();
			foreach ( $matches[2] as $key => $val ) {
				$val = trim( $val );
				if ( !empty( $val ) ) {
					$default[$val] = trim( $matches[3][$key], '"' );
				} elseif ( trim( $matches[6][$key] ) ) {
					$default[$matches[6][$key]] = trim( $matches[6][$key] );
				}
			}
		}

		$this->_default = $default;
		$this->_file_name = $file_name;
		$this->_instance = &$instance;
		$this->_label = ( $label ) ? $label : $name;
		$this->_description = $description;

		$this->_is_array = $is_array;
	}

	public function __get( $name ) {
		// TODO: similar logic to current()
		if ( $this->_is_array ) {
			$value = $this->get_value( $name, $this->position );
		} else {
			$value = $this->get_value( $name );
		}

		if ( preg_match( '/^\[tags\]([0-9]+)$/', $value, $matches ) ) {
			return get_tag( $matches[1] );
		} elseif ( preg_match( '/^\[categories\]([0-9]+)$/', $value, $matches ) ) {
			return get_category( $matches[1] );
		/*} elseif ( preg_match( '/^\[image_sizes\]([^)]+)$/', $value, $matches ) ) {
			return $matches[1];*/
		}

		return $value;
	}

	public function __toString() {
		if ( $this->_is_array ) {
			$value = $this->get_value( 'value', $this->position );
		} else {
			$value = $this->get_value( 'value' );
		}

		return '' . $value;
	}

	/**
	 * Get current widget's value
	 */
	public function set_instance( &$instance ) {
		$this->_instance = &$instance;
	}

	/**
	 * Get current widget's value
	 */
	public function get_value( $name, $position = null ) {
		if ( $this->_type == 'object' ) {
			$names = $this->_default;
		} else {
			$names = array( 'value' => $this->_default );
		}

		$value_prefix = $this->_file_name . '__' . $this->_name . ( ( $position !== null ) ? '__' . $position : '' );
		$value_name = $value_prefix . ( ( $name != null ) ? '__' . $name : '' );

		if ( isset( $this->_instance[$value_name] ) ) {
			if ( is_object( $this->_instance[$value_name] ) ) {
				$this->_instance[$value_name]->set_instance( $this->_instance );
			}

			return $this->_instance[$value_name];
		}

		return '';
	}

	// Iterable
	private $position = 0;

	function rewind() {
		$this->position = 0;
	}

	function current() {
		// TODO: similar logic to __get()
		if ( $this->_is_array ) {
			$value = $this->get_value( 'value', $this->position );
		} else {
			$value = $this->get_value( 'value' );
		}

		if ( preg_match( '/^\[tags\]([0-9]+)$/', $value, $matches ) ) {
			return get_tag( $matches[1] );
		} elseif ( preg_match( '/^\[categories\]([0-9]+)$/', $value, $matches ) ) {
			return get_category( $matches[1] );
		/*} elseif ( preg_match( '/^\[image_sizes\]([^)]+)$/', $value, $matches ) ) {
			return $matches[1];*/
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
		if ( $this->_type == 'object' ) {
			$names = $this->_default;
		} else {
			$names = array( 'value' => $this->_default );
		}

		$value_prefix = $this->_file_name . '__' . $this->_name . '__' . $this->position;
		foreach ( $names as $name => $def ) {
			$value_name = $value_prefix . '__' . $name;

			if ( isset( $this->_instance[$value_name] ) ) {
				return true;
			}
		}

		return false;
	}	
}

/**
 * Wordlets shortcode -----------------------------------------------------------------------------------------
 */

add_shortcode( 'wordlet', 'wordlet_shortcode' );
add_action( 'widgets_init', 'wordlet_shortcode_sidebar', 30 );

/**
 * Shortcode rendering function
 */
function wordlet_shortcode( $args, $content = null ) {
	global $_wp_sidebars_widgets, $wp_registered_widgets, $wp_registered_sidebars;

	$shortcode_atts = shortcode_atts( array(
		'id' => '',
	), $args, 'widget' );

	$id = @$shortcode_atts['id'];

	if ( empty($id) ) {
		return;
	}

	preg_match( '/(\d+)$/', $id, $matches );
	$id = 'wordlets_widget-' . $matches[0];

	if ( ! isset( $wp_registered_widgets[$id] ) ) {
		return;
	}

	// get the widget instance options
	$options = get_option( $wp_registered_widgets[$id]['callback'][0]->option_name );
	$instance = $options[$matches[0]];
	$class = get_class( $wp_registered_widgets[$id]['callback'][0] );
	$sidebars_widgets = wp_get_sidebars_widgets();

	$widgets_map = array();
	if ( ! empty( $sidebars_widgets ) ) {
		foreach( $sidebars_widgets as $position => $widgets ) {
			if ( ! empty( $widgets) ) {
				foreach( $widgets as $widget ) {
					$widgets_map[$widget] = $position;
				}
			}
		}
	}

	$_original_widget_position = $widgets_map[$id];

	// maybe the widget is removed or deregistered
	if( ! $class ) {
		return;
	}

	// build the widget args that needs to be filtered through dynamic_sidebar_params
	$params = array(
		0 => array(
			'name' => $wp_registered_sidebars[$_original_widget_position]['name'],
			'id' => $wp_registered_sidebars[$_original_widget_position]['id'],
			'description' => $wp_registered_sidebars[$_original_widget_position]['description'],
			'widget_id' => $id,
			'widget_name' => $wp_registered_widgets[$id]['name']
		),
		1 => array(
			'number' => $matches[0]
		)
	);
	$params = apply_filters( 'dynamic_sidebar_params', $params );

	// render the widget
	ob_start();
	the_widget( $class, $instance, $params[0] );
	return ob_get_clean();
}

/**
 * Add an optional widget area for wordlet shortcodes
 */
function wordlet_shortcode_sidebar() {
	register_sidebar( array(
		'name' => __( 'Wordlet Shortcodes (Not Shown)' ),
		'id' => 'wordlet_shortcodes',
		'description'	=> __( 'This area can be used for [wordlet] shortcodes.' ),
		'before_widget' => '',
		'after_widget'	=> '',
	) );
}
