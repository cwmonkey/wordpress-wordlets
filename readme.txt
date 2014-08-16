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

This plugin allows you to create widgets which reference templates. It would be used when you don't want to roll your own full-blown plugin, need a highly customized widget and still want to be able to edit content via the CMS.

The templates are named in a specific manner (wordlets-{name}.php or wordlets/{name}.php).

Within each template you print content like so: <?php echo wordlet('some_name') ?>.

After creating a template and filling it with html and references to content, you can add a widget which references that template in the admin interface.

The Widget will automatically know when templates have been added and it will build the dmin interface based on the wordlet($name) tags in the templates.

You can add additional information to the wordlet() call to make the admin interface a little nicer. The full parameters are

    wordlet({name} [, {default value} [, {label text} [, {extra description under input} ]]] )

The input type will be inferred from the "default value":
- Boolean values (true/false), will be checkboxes
- Anything with a return ("\n") will be a textarea
- Arrays will be a select box
- Integers will be a number box
- Everything else is a text box

Example:

Assume you have a file called wordlets-test.php and in it you have these contents:

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

After saving the file you will now have an option in the Wordlet Widget to use "Test (Test Wordlet Template)".

Once selected you will be presented with a form with 4 labels/text fields.

== Installation ==

1. Upload the `wordlets` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets 
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png` 
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`