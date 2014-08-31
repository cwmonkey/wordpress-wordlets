<?php

// Load the input on wordlets_input_construct
function load_wordlets_input_checkbox() {
	register_wordlets_input( 'Wordlets_Widget_Input_Checkbox' );
}

add_action( 'wordlets_input_construct', 'load_wordlets_input_checkbox' );

class Wordlets_Widget_Input_Checkbox implements Wordlets_Widget_Input {
	public $name = 'checkbox';

	public function __construct() {
		// No special admin scripts for this input
	}

	public function form_input($args) {
		extract($args);
		?>
		<input type="checkbox" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" type="text" value="1" <?php echo ( $value ) ? 'checked':'' ?>>

		<?php echo $default_label; ?>

		<?php if ( $description && !$hide_labels ) { ?>
			<label class="wordlet-description"><?php echo $description ?></label>
		<?php } ?>

		<?php
	}
}


