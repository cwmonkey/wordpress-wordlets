<?php

/**
 * Wordlet Image input view.
 *
 * @var string $default_label   Default HTML output for input <label>.
 * @var string $description     Wordlet description field.
 * @var bool   $hide_labels     true if within an array and labels have already been displayed
 * @var string $input_name      Input name attribute.
 * @var array  $options         List of options for select.
 * @var string $text_domain     Wordlet text domain
 * @var string $value           Input value
 * @var string $value_id        Input id attribute
 */

?>

<div class="wordlet-float-label <?php echo (($value !== '')?'wordlet-filled':'') ?>">
	<?php echo $default_label; ?>

	<input class="widefat" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
	<input type="button" id="<?php echo $input_id; ?>-set" value="<?php _e( 'Choose Image', $text_domain ); ?>" class="button wordlets-widget-image-set"
		data-target="#<?php echo $input_id; ?>"
		data-alt="#<?php echo $this->get_field_id( $value_prefix . '__alt' ) . $id_extra; ?>"
		data-width="#<?php echo $this->get_field_id( $value_prefix . '__width' ) . $id_extra; ?>"
		data-height="#<?php echo $this->get_field_id( $value_prefix . '__height' ) . $id_extra; ?>"
		data-image="#<?php echo $input_id; ?>-image">
	<img style="max-width:100%;max-height:100px;" id="<?php echo $input_id; ?>-image" src="<?php echo esc_attr( (($value)?$value:'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7') ); ?>">

	<?php if ( $description && !$hide_labels ) { ?>
		<label class="wordlet-description"><?php echo $description ?></label>
	<?php } ?>
</div>