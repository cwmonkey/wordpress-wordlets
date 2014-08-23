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

<input type="checkbox" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" type="text" value="1" <?php echo ( $value ) ? 'checked':'' ?>>

<?php echo $default_label; ?>

<?php if ( $description && !$hide_labels ) { ?>
	<label class="wordlet-description"><?php echo $description ?></label>
<?php } ?>