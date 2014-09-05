<?php

// Load the input on wordlets_input_construct
function load_wordlets_input_image() {
	register_wordlets_input( 'Wordlets_Widget_Input_Image' );
}

add_action( 'wordlets_input_construct', 'load_wordlets_input_image' );

class Wordlets_Widget_Input_Image implements Wordlets_Widget_Input {
	public $name = 'image';

	public function __construct() {
		wp_enqueue_script( 'wordlets_input_image', plugins_url('image.js', __FILE__), array( 'jquery' ) );
	}

	public function form_input($args) {
		extract($args);
		?>
		<div class="wordlet-float-label <?php echo (($value !== '')?'wordlet-filled':'') ?>">
			<?php echo $default_label; ?>

			<input class="widefat" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			<input type="button" id="<?php echo $input_id; ?>-set" value="<?php _e( 'Choose Image', $text_domain ); ?>" class="button wordlets-widget-image-set"
				data-target="#<?php echo $input_id; ?>"
				data-alt="#<?php echo $widget->get_field_id( $value_prefix . '__alt' ) . $id_extra; ?>"
				data-width="#<?php echo $widget->get_field_id( $value_prefix . '__width' ) . $id_extra; ?>"
				data-height="#<?php echo $widget->get_field_id( $value_prefix . '__height' ) . $id_extra; ?>"
				data-size="#<?php echo $widget->get_field_id( $value_prefix . '__size' ) . $id_extra; ?>"
				data-image="#<?php echo $input_id; ?>-image"
				>
			<img style="max-width:100%;max-height:100px;" id="<?php echo $input_id; ?>-image" src="<?php echo esc_attr( (($value)?wp_get_attachment_image_src( $value )[0]:'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7') ); ?>">

			<?php if ( $description && !$hide_labels ) { ?>
				<label class="wordlet-description"><?php echo $description ?></label>
			<?php } ?>
		</div>
		<?php
	}
}

