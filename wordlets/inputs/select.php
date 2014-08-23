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
		<?php } else { ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php echo ( $key == $value )?'selected':'' ?>><?php echo esc_attr( __( $val , $text_domain ) ); ?></option>
		<?php } ?>
	<?php } ?>
</select>

<?php if ( $description && !$hide_labels ) { ?>
	<label class="wordlet-description"><?php echo $description ?></label>
<?php } ?>