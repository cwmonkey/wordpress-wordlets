<?php

// Load the input on wordlets_input_construct
function load_wordlets_input_select() {
	register_wordlets_input( 'Wordlets_Widget_Input_Select' );
}

add_action( 'wordlets_input_construct', 'load_wordlets_input_select' );

class Wordlets_Widget_Input_Select implements Wordlets_Widget_Input {
	public $name = 'select';

	public function __construct() {
		// No special admin scripts for this input
	}

	public function form_input($args) {
		extract($args);
		?>
		<?php echo $default_label; ?>

		<select  id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>">
			<option value=""><?php echo esc_attr( __( (($description)?'- ' . $description . ' -': '- Select One -' ) , $text_domain ) ); ?></option>
			<?php foreach ( $options as $key => $val ) { ?>
				<?php if ( $val == '[tags]' ) { ?>
					<?php foreach ( get_tags() as $tag ) { ?>
						<option value="<?php echo esc_attr( $val . $tag->term_id ); ?>" <?php echo ( $val . $tag->term_id == $value )?'selected':'' ?>><?php echo esc_attr( $tag->name ); ?></option>
					<?php } ?>
				<?php } elseif ( $val == '[categories]' ) { ?>
					<?php foreach ( get_categories() as $category ) { ?>
						<option value="<?php echo esc_attr( $val . $category->term_id ); ?>" <?php echo ( $val . $category->term_id == $value )?'selected':'' ?>><?php echo esc_attr( $category->name ); ?></option>
					<?php } ?>
				<?php } elseif ( $val == '[image_sizes]' ) { ?>
					<?php foreach ( get_intermediate_image_sizes() as $image_size ) { ?>
						<option value="<?php echo esc_attr( $image_size ); ?>" <?php echo ( $image_size == $value )?'selected':'' ?>><?php echo esc_attr( $image_size ); ?></option>
					<?php } ?>
				<?php } else { ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php echo ( $key == $value )?'selected':'' ?>><?php echo esc_attr( __( $val , $text_domain ) ); ?></option>
				<?php } ?>
			<?php } ?>
		</select>

		<?php
	}
}


