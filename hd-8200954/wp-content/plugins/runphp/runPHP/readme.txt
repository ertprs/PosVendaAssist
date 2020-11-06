=== runPHP ===

Contributors: JamesVL, mikeage, pcorbes, dahead
Donate link: http://www.nosq.com/blog/runphp/donate
Tags: php, exec
Requires at least: 2.0
Tested up to: 2.2.1

Allow users to embed PHP code in their posts or pages.

== Description ==

  * Permission to use runPHP is controlled by Roles and Capabilities
  * Configure those permissions in the new runPHP Options page
  * Also works in your feeds (RSS, RSS2, Atom, & RDF)
  * Integrated with WordPress 2.0 administrative UI
  * Internationalization support: English (default), German, and French so far
  * Works on PHP4 servers as well

== Installation ==

  * Unzip the contents of this file (the entire `runPHP` directory)
    into: `/wp-content/plugins/`
  * In "Options > Writing"
    * **Turn off** "WordPress should correct invalidly nested XHTML
                    automatically"
    * Do *not* use this with the visual rich editor
  * Visit the "Options > runPHP" page and set who can use the plugin
    (default is Administrators)
  * Create a post and check the "run PHP code?" checkbox to enable PHP code
    to be dropped in to your post
  * I10n support is available only if WP_LANG is defined in your
    wp-config.php file (English, French, and German are included. You can
    use [POEdit](http://www.poedit.org/) to add your own.)


== Caveats and Gotchas ==

  * Code executed by runPHP is *not* in the global scope. It is executed as if
    it were inside a function block and so you'll need to declare your
    globals accordingly. (This is a limitation of PHP's `eval()` function, not
    the plugin itself.)
  * Some people have reported seeing an error message of
    "unexpected T_OBJECT_OPERATOR" in the `runPHP_options_ui.inc.php` file.
    I *think* this is because of how PHP4 handles objects - will you add a
    comment (preferably in
    [this forum thread](http://www.nosq.com/forum/index.php?topic=3.0)) about
    what version of PHP your website is using?
  * With few badly written WordPress themes, the plugin does not work. This is
    caused by a missing `wp_head()` call in the theme's header template (often
    called `header.php`).
    If that is the case, fixing it is simply a matter of inserting
    `<?php wp_head(); ?>` at the right point in the php template. 
    See (http://squio.nl/blog/2006/11/08/wp-themes-and-microsummary-plugin/)
    for more details.

----

Thanks for using this plugin - I hope it helps!

