<?php

/**
* Name: Test
* Description: Test Wordlet Template
*/

?>

<div class="test <?=wordlet('style', array('small', 'medium', 'big'), 'Style', 'Adjusts the look of the form') ?>">
	<h2 class="headline"><?=wordlet('title', 'Sign up for things', 'Title', 'Above the form') ?></h2>

	<? if ( ($text = wordlet('greeting', "Thank you for taking the time to fill this form out\n", 'Greeting')) ): ?>
		<p><?=$text ?></p>
	<? endif ?>

	<form action="http://example.com/some-remote-site">
		<? foreach ( wordlet_array('tracking-vars', '', 'Tracking Variables', 'Key = name, Value = value, as in name="utm-source" value="blog"') as $input ): ?>
			<input type="hidden" name="<?=$input->key ?>" value="<?=$input->value ?>">
		<? endforeach ?>

		<p class="input text">
			<label for="test-name"><?=wordlet('required-label', '*', 'Required Label') ?> <?=wordlet('name-label', 'Input your name', 'Name Label') ?></label>
			<input type="text" name="name" id="test-name">
		</p>
		<p class="input text email">
			<label for="test-email"><?=wordlet('required-label') ?> <?=wordlet('email-label', 'Input your email', 'Email Label') ?></label>
			<input type="email" name="email" id="test-email">
		</p>
		<? if ( wordlet('show_hear', true, 'Show "Hear about us" dropdown') && ($options = wordlet_array('hear-options', 'Option', '"Hear about us" options')) ): ?>
			<p class="input select">
				<label for="test-hear"><?=wordlet('hear-label', 'How did you hear about us?', 'Hear about us Label') ?></label>
				<select name="email" id="test-hear">
					<? foreach ( $options as $option ): ?>
						<option><?=$option ?></option>
					<? endforeach ?>
				</select>
			</p>
		<? endif ?>
		<p class="input submit">
			<input type="submit" value="<?=wordlet('submit-label', 'Sign me up!', 'Submit Label') ?>">
		</p>
	</form>
</div>