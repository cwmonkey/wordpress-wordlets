=== Plugin Name ===
Contributors: cwmonkey
Donate link: http://cwmonkey.com/
Tags: widget
Requires at least: 3.9.2
Tested up to: 3.9.2
Stable tag:
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin for creating generic widgets with content/settings.

== Description ==

This plugin allows you to do the following:
* Keep your HTML and display logic in templates
* Keep your widget content and settings in the CMS
* Allow you to make new widgets without having to make or install new plugins

The templates for each widget are named in a specific manner (wordlets-{name}.php or wordlets/{name}.php).

In the top comment block of the template you define all your wordlet values something like this:

     * @wordlet text $title "Some Title" Title

This will do the following:

1. Make an text input on your widget admin form with the label "Title" and default value "Some Title".

2. Create a variable you can echo within the template like so: <?php echo $title ?>

Note: you can use @wordletArray rather than @wordlet if you wish to allow multiple values for the wordlet

The Widget will automatically know when templates have been added and it will build the admin wudget interface.

Wordlet values can have the following types:

text: A text input
textarea: A textarea input
checkbox: A checkbox input for boolean settings
number: An input with a "number" type
image: An image input which hooks into Wordpress's media thing

select: A dropdown. The description param will be used as the default no-value option. Use the following syntax for the "default" param to add options:
 * @wordlet select $thing {
        some_value = "Some Text",
        "Just Some Text",
        [categories] // This will add all categories to the dropdown. The value will be a Wordpress category object
        [tags] // This will add all tags to the dropdown. The value will be a Wordpress tag object
    } Thing "Pick a Thing"

object: A wordlet with sub-wordlets. For the default value use the following syntax to define attributes on the wordlet object:
 * @wordlet object $obj {
        text   $name "Some Name" Name,
        select $type ( red = Red, blue = Blue ) 
    } "Some Object"

You may also add your own inputs by following these steps (in the example we will be adding an "email" input):

1. In your Theme's functions.php add the following:
    function mytheme_wordlets_admin_construct($object) {
        $tpl_dir = get_template_directory();
        $object->add_wordlet_type( 'email', $tpl_dir . '/wordlets/inputs/email.php' );
    }

    add_action( 'wordlets_admin_construct', 'mytheme_wordlets_admin_construct' );
2. In your theme's directory make a folder called /wordlets/inputs
3. Make a file within the inputs directory called email.php and put the following contents in it:
    <?php

    // Load the input on wordlets_construct
    function load_wordlets_input_email() {
        register_wordlets_input( 'Wordlets_Widget_Input_Email' );
    }

    add_action( 'wordlets_construct', 'load_wordlets_input_email' );

    class Wordlets_Widget_Input_Email implements Wordlets_Widget_Input {
        // A unique name for your input, must match the names used in the steps above
        public $name = 'email';

        public function __construct() {
            // No special admin scripts for this input
            // If you needed to add a js file for functionality specific to this input you would do like so:
            // wp_enqueue_script( 'wordlets_input_image', dirname( __FILE__ ) . '/email.js', array( 'jquery' ) );
        }

        public function form_input($args) {
            extract($args);
            ?>
            <div class="wordlet-float-label <?php echo (($value !== '')?'wordlet-filled':'') ?>">
                <?php echo $default_label; ?>

                <input class="widefat" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" type="email" value="<?php echo esc_attr( $value ); ?>">

                <?php if ( $description && !$hide_labels ) { ?>
                    <label class="wordlet-description"><?php echo $description ?></label>
                <?php } ?>
            </div>

            <?php
        }
    }


Example:

Assume you have a file called wordlets-test.php and in it you have these contents:

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
     *                               number $height "" "Height",
     *                               select $category ( [categories] ) "Category" "Select a Category"
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
        <?=$image->category->name ?>
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
                <input type="hidden" name="<?=$input->name ?>" value="<?=$input->value ?>">
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

After saving the file you will now have an option in the Wordlet Widget to use "Test (Test Wordlet Template)".

Once selected you will be presented with a form with various labels/input fields.

== Installation ==

1. Upload the `wordlets` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Admin interface generated by the above template
2. Rendered custom widget

== Changelog ==

= 1.0 =
* First release

== Upgrade Notice ==

== Arbitrary section ==
