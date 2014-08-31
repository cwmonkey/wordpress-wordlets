<?php

// Load the input on wordlets_input_construct
function load_wordlets_input_number() {
	register_wordlets_input( 'Wordlets_Widget_Input_Number' );
}

add_action( 'wordlets_input_construct', 'load_wordlets_input_number' );

class Wordlets_Widget_Input_Number implements Wordlets_Widget_Input {
	public $name = 'number';

	public function __construct() {
		// No special admin scripts for this input
	}

	public function form_input($args) {
		extract($args);
		?>
		<div class="wordlet-float-label <?php echo (($value !== '')?'wordlet-filled':'') ?>">
			<?php echo $default_label; ?>

			<input class="widefat" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" type="number" value="<?php echo esc_attr( $value ); ?>">

			<?php if ( $description && !$hide_labels ) { ?>
				<label class="wordlet-description"><?php echo $description ?></label>
			<?php } ?>
		</div>

		<?php
	}
}
