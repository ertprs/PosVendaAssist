=== M-vSlider ===
Contributors: mamirulamin
Donate link: http://mamirulamin.wordpress.com/
Tags: banner, slider, sidebar, widget, plugin
Requires at least: 3.0.1
Tested up to: 3.1.2
Stable tag: 1.1.1

== Description ==

Implementing a featured image gallery into your WordPress theme has never been easier! Showcase your portfolio, animate your header or manage your banners with M-vSlider. M-vSlider by  Muhammad Amir Ul Amin. M-vSlider is multi sliders clone of vSlider (http://www.vibethemes.com/wordpress-plugins/vslider-wordpress-image-slider-plugin/)

= Widget =

In the Appearance -> Widgets in WordPress 3.0.1 you'll find the `M-vSlider - Image Slider` widget. After adding it to your sidebar you can enter an optional `Title` and select a `M-vSlider ID` (which you setup in M-vSlider Setup in WP Admin)

= Shortcode =

Insert the slider in your pages or posts with this shortcode

`[m-vslider id="x"]` (where `x` is slider ID)

= Use in Template/PHP code =

Insert this code in your template/theme pages. (if you have installed php-exec plugin then you can insert this code in your post or page too)

`<?php if (function_exists('rslider')) { rslider(x); }?>` (where `x` is slider ID)

= Special Mention =
You can copy the Shortcode and Template/PHP code from sliders listing table (Plugin Admin Home), mentioned for each slider.

== Installation ==

1. Upload `m-vslider` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

`1. What is slider ID?`
This is an auto-generated unique ID, assigned to a slider to uniquely identify it. 

You'll find it in the table at Plugin Home page, 2nd column of table. (Please see "Plugin Admin Home" screenshot in Screenshots section)

`Please post your comments and questions at http://mamirulamin.wordpress.com/2010/08/10/mvslider-multi-sliders-clone-of-vslider/`

== Screenshots ==

1. Plugin Admin Home
2. Options page where you setup a slider
3. Sidebar Widget Admin Options

== Changelog ==

= 1.0.0 =
First release

= 1.1.0 =
First upgrade
1. Added option to open slider/banner link in new window/tab.
2. In Widget, instead of typing a Slider ID, HTML select box given for easy selection, which lists all the sliders setup.
3. Two new columns added in slider listing table in admin area, for Shortcode and Template code.
4. Reduced columns in DB table.

== Upgrade Notice ==

N/A
