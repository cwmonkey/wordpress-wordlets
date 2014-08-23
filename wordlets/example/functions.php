<?php

/**
 * Adding your own wordlet types.
 */

function mytheme_wordlets_admin_construct($object) {
	$tpl_dir = get_template_directory();
	$object->add_wordlet_type( 'email', $tpl_dir . '/wordlets/inputs/email.php' );
}

add_action( 'wordlets-admin-construct', 'mytheme_wordlets_admin_construct' );
