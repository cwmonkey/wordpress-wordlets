<?php

/**
 * Test Wordlet Template.
 * 
 * Example of how to set up and use wordlets.
 *
 * @name        Test
 * @description Test Wordlet Template
 *
 * @wordlet      type     $name ("Default" | { type $property "Default", ... } | { value = Option | Option | [tags|categories] } ) "Label" "Long Description"
 *
 * @wordletArray select   $tags { [tags], small = Small, Thing } Tags "Select a tag"
 * @wordletArray select   $categories { [categories] } Categories "Select a category"
 * @wordlet      object   $main_image {
 *                                image  $src "" "Image URL",
 *                                text   $alt "" "Alt",
 *                                number $width "" "Width",
 *                                number $height "" "Height"
 *                            } "Main Image" "Appears above form"
 * @wordletArray object   $images {
 *                               image  $src "" "Image URL",
 *                               text   $alt "" "Alt",
 *                               text   $link "" "Link Href",
 *                               number $width "" "Width",
 *                               number $height "" "Height"
 *                            } Images
 * @wordlet      select   $style {
 *                                small = Small,
 *                                medium = Medium,
 *                                Large,
 *                                big = Big
 *                            } Style "Adjusts the look of the form"
 * @wordlet      text     $title "Sign up for things" Title
 * @wordlet      textarea $greeting "Thank you for taking the time to fill this form out" Greeting
 * @wordletArray object   $tracking_vars {
 *                                text $name "" "Name",
 *                                text $value "" "Value"
 *                            } "Tracking Variables"
 * @wordlet      text     $required_label "*" "Required Label"
 * @wordlet      text     $name_label "Input your name" "Name Label"
 * @wordlet      text     $email_label "Input your email" "Email Label"
 * @wordlet      checkbox $show_hear 1 "Show 'hear about us' dropdown"
 * @wordlet      text     $hear_label "How did you hear about us?" "'Hear about us' label"
 * @wordletArray text     $hear_options "" "'Hear about us' options"
 * @wordlet      text     $submit_label "Sign me up!" "Submit label"
 * 
 */

?>

<? foreach ( $categories as $category ): ?>
	<p>Category: <?=$category->name ?></p>
<? endforeach ?>

<? foreach ( $tags as $tag ): ?>
	<p>Tag: <?=$tag->name ?></p>
<? endforeach ?>

<img src="<?=$main_image->src ?>" alt="<?=$main_image->alt ?>" width="<?=$main_image->width ?>">
<hr>
<? foreach ( $images as $image): ?>
	<? if ( $image->link ): ?>
		<a href="<?=$image->link ?>">
			<img src="<?=$image->src ?>" alt="<?=$image->alt ?>" width="<?=$image->width ?>">
		</a>
	<? else: ?>
		<img src="<?=$image->src ?>" alt="<?=$image->alt ?>" width="<?=$image->width ?>">
	<? endif ?>
<? endforeach ?>

<div class="test <?=$style ?>">
	<h2 class="headline"><?=$title ?></h2>

	<? if ( $greeting ): ?>
		<p><?=$greeting ?></p>
	<? endif ?>

	<form action="http://example.com/some-remote-site">
		<? foreach ( $tracking_vars as $input ): ?>
			<input type="hidden" name="<?=$input->key ?>" value="<?=$input->value ?>">
		<? endforeach ?>

		<p class="input text">
			<label for="test-name"><?=$required_label ?> <?=$name_label ?></label>
			<input type="text" name="name" id="test-name">
		</p>
		<p class="input text email">
			<label for="test-email"><?=$required_label ?> <?=$email_label ?></label>
			<input type="email" name="email" id="test-email">
		</p>
		<? if ( $show_hear && $hear_options ): ?>
			<p class="input select">
				<label for="test-hear"><?=$hear_label ?></label>
				<select name="email" id="test-hear">
					<? foreach ( $hear_options as $option ): ?>
						<option><?=$option ?></option>
					<? endforeach ?>
				</select>
			</p>
		<? endif ?>
		<p class="input submit">
			<input type="submit" value="<?=$submit_label ?>">
		</p>
	</form>
</div>