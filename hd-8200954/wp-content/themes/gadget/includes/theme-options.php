<?php
/*
NOTE: this file requires WordPress 2.7+ to function
*/
$settings = 'theme_mods_'.get_current_theme(); // do not change!

$defaults = array( // define our defaults
		'featuredcat' => '1',
		'box1' => 'Enable',
		'box2' => 'Enable',
		'box3' => 'Enable',
		'box4' => 'Enable',
		'box5' => 'Enable',
		'box6' => 'Enable',
		'box7' => 'Enable',
		'box8' => 'Enable',		
		'box1cat' => '1',
		'box2cat' => '1',
		'box3cat' => '1',
		'box4cat' => '1',
		'box5cat' => '1',
		'box6cat' => '1',
		'box7cat' => '1',
		'box8cat' => '1',
		'list' => '5',
		'list1' => '3',
		'list2' => '3',
		'featuredthumbw' => '260',
		'featuredthumbh' => '195',
		'boxleftthumbw' => '100',
		'boxleftthumbh' => '80',
		'boxrightthumbw' => '100',
		'boxrightthumbh' => '80',
		'feed' => 'feedburner',
		'track' => 'Yes',
		'ad468x60' => '',
		'ad300x250' => '',
		'showad468x60' => 'Yes',
		'showad300x250' => 'Yes'	
		 // <-- no comma after the last option
);

//	push the defaults to the options database,
//	if options don't yet exist there.
add_option($settings, $defaults, '', 'yes');


/*
///////////////////////////////////////////////
This section hooks the proper functions
to the proper actions in WordPress
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function registers our settings in the db
add_action('admin_init', 'register_theme_settings');
function register_theme_settings() {
	global $settings;
	register_setting($settings, $settings);
}
//	this function adds the settings page to the Appearance tab
add_action('admin_menu', 'add_theme_options_menu');
function add_theme_options_menu() {
	add_submenu_page('themes.php', __('Gadget Theme Options', 'themejunkie'), __('Gadget Theme Options', 'themejunkie'), 8, 'theme-options', 'theme_settings_admin');
}

function theme_settings_admin() { ?>
<?php theme_options_css_js(); ?>

<div class="wrap">
  <?php
	global $settings, $defaults;
	if(get_theme_mod('reset')) {
		echo '<div class="updated fade" id="message"><p>'.__('Theme Options', 'themejunkie').' <strong>'.__('Reset to defaults', 'themejunkie').'</strong></p></div>';
		update_option($settings, $defaults);
	} elseif($_REQUEST['updated'] == 'true') {
		echo '<div class="updated fade" id="message"><p>'.__('Theme Options', 'themejunkie').' <strong>'.__('Saved', 'themejunkie').'</strong></p></div>';
	}
	screen_icon('options-general');
?>
  <h2><?php echo get_current_theme() . ' '; _e('Theme Options', 'themejunkie'); ?></h2>
  <form method="post" action="options.php">
    <?php settings_fields($settings); // important! ?>
    <?php // begin first column ?>
    <div class="metabox-holder">
      <div class="postbox">
        <h3>
          <?php _e("Overview", 'themejunkie'); ?>
        </h3>
        <div class="inside">
          <p>
            <?php _e("Gadget is proudly designed by ", 'themejunkie'); ?>
            <a rel="nofollow" href="http://theme-junkie.com/" target="_blank">
            <?php _e("Theme Junkie", 'themejunkie'); ?>
            </a></p>
          <p style="text-decoration: none;"> <a href="http://theme-junkie.com/support/" target="_blank">
            <?php _e("Get Support", 'themejunkie'); ?>
            </a> | <a href="http://theme-junkie.com/themes/" target="_blank">
            <?php _e("Our Themes", 'themejunkie'); ?>
            </a> | <a href="http://theme-junkie.com/affiliates/" target="_blank">
            <?php _e("Become an Affiliate", 'themejunkie'); ?>
            </a></p>
        </div>
      </div>
      <!--end: dashboard-->
      <div class="postbox">
        <h3>
          <?php _e("Featured Content Slider", 'themejunkie'); ?>
        </h3>
        <div class="inside">
          <p>
            <?php _e("Category for Featured News:", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('featuredcat'), 'name' => $settings.'[featuredcat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
          </p>
          <p>
            <?php _e("Number of posts to show:", 'themejunkie'); ?>
            <br/>
            <select name="<?php echo $settings; ?>[list]">
              <option style="padding-right:10px;" value="1" <?php selected('1', get_theme_mod('list')); ?>>1</option>
              <option style="padding-right:10px;" value="2" <?php selected('2', get_theme_mod('list')); ?>>2</option>
              <option style="padding-right:10px;" value="3" <?php selected('3', get_theme_mod('list')); ?>>3</option>
              <option style="padding-right:10px;" value="4" <?php selected('4', get_theme_mod('list')); ?>>4</option>
              <option style="padding-right:10px;" value="5" <?php selected('5', get_theme_mod('list')); ?>>5</option>
              <option style="padding-right:10px;" value="6" <?php selected('6', get_theme_mod('list')); ?>>6</option>
              <option style="padding-right:10px;" value="7" <?php selected('7', get_theme_mod('list')); ?>>7</option>
              <option style="padding-right:10px;" value="8" <?php selected('8', get_theme_mod('list')); ?>>8</option>
              <option style="padding-right:10px;" value="9" <?php selected('9', get_theme_mod('list')); ?>>9</option>
              <option style="padding-right:10px;" value="10" <?php selected('10', get_theme_mod('list')); ?>>10</option>
            </select>
            <span style="margin-left:10px; color: #999999;">
            <?php _e("(default: 5)", 'themejunkie'); ?>
            </span> </p>
          <p>
            <?php _e("Thumbnail size (Width x Height):", 'themejunkie'); ?>
            <br/>
            <input type="text" name="<?php echo $settings; ?>[featuredthumbw]" value="<?php echo get_theme_mod('featuredthumbw'); ?>" size="4" />
            x
            <input type="text" name="<?php echo $settings; ?>[featuredthumbh]" value="<?php echo get_theme_mod('featuredthumbh'); ?>" size="4" />
            <span style="margin-left:10px; color: #999999;">
            <?php _e("(default: 260x195)", 'themejunkie'); ?>
            </span> </p>
        </div>
      </div>
      <!--end: featured news-->
      <div class="postbox">
        <h3>Content Left</h3>
        <div class="inside">
          <p>
            <?php _e("Category for Box #1", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box1cat'), 'name' => $settings.'[box1cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box1]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box1')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box1')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Category for Box #2", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box2cat'), 'name' => $settings.'[box2cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box2]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box2')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box2')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Category for Box #3", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box3cat'), 'name' => $settings.'[box3cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box3]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box3')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box3')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Category for Box #4", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box4cat'), 'name' => $settings.'[box4cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box4]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box4')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box4')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Number of posts to show on each box:", 'themejunkie'); ?>
            <br/>
            <select name="<?php echo $settings; ?>[list1]">
              <option style="padding-right:10px;" value="0" <?php selected('0', get_theme_mod('list1')); ?>>1</option>
              <option style="padding-right:10px;" value="1" <?php selected('1', get_theme_mod('list1')); ?>>2</option>
              <option style="padding-right:10px;" value="2" <?php selected('2', get_theme_mod('list1')); ?>>3</option>
              <option style="padding-right:10px;" value="3" <?php selected('3', get_theme_mod('list1')); ?>>4</option>
              <option style="padding-right:10px;" value="4" <?php selected('4', get_theme_mod('list1')); ?>>5</option>
              <option style="padding-right:10px;" value="5" <?php selected('5', get_theme_mod('list1')); ?>>6</option>
              <option style="padding-right:10px;" value="6" <?php selected('6', get_theme_mod('list1')); ?>>7</option>
              <option style="padding-right:10px;" value="7" <?php selected('7', get_theme_mod('list1')); ?>>8</option>
              <option style="padding-right:10px;" value="8" <?php selected('8', get_theme_mod('list1')); ?>>9</option>
              <option style="padding-right:10px;" value="9" <?php selected('9', get_theme_mod('list1')); ?>>10</option>
            </select>
            <span style="margin-left:10px; color: #999999;">
            <?php _e("(default: 4)", 'themejunkie'); ?>
            </span> </p>
          <p>
            <?php _e("Thumbnail size (Width x Height):", 'themejunkie'); ?>
            <br/>
            <input type="text" name="<?php echo $settings; ?>[boxleftthumbw]" value="<?php echo get_theme_mod('boxleftthumbw'); ?>" size="4" />
            x
            <input type="text" name="<?php echo $settings; ?>[boxleftthumbh]" value="<?php echo get_theme_mod('boxleftthumbh'); ?>" size="4" />
            <span style="margin-left:10px; color: #999999;">
            <?php _e("(default: 100x80)", 'themejunkie'); ?>
            </span> </p>
        </div>
      </div>
      <!--end: content left-->
      <div class="postbox">
        <h3>Content Right</h3>
        <div class="inside">
          <p>
            <?php _e("Category for Box #5", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box5cat'), 'name' => $settings.'[box5cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box5]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box5')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box5')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Category for Box #6", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box6cat'), 'name' => $settings.'[box6cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box6]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box6')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box6')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Category for Box #7", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box7cat'), 'name' => $settings.'[box7cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box7]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box7')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box7')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Category for Box #8", 'themejunkie'); ?>
            <br/>
            <?php wp_dropdown_categories(array('selected' => get_theme_mod('box8cat'), 'name' => $settings.'[box8cat]', 'orderby' => 'Name' , 'hierarchical' => 1, 'hide_empty' => '0' )); ?>
            <select name="<?php echo $settings; ?>[box8]" style="margin: 0 0 0 20px;">
              <option style="padding-right:10px;" value="Enable" <?php selected('Enable', get_theme_mod('box8')); ?>>Enable</option>
              <option style="padding-right:10px;" value="Disable" <?php selected('Disable', get_theme_mod('box8')); ?>>Disable</option>
            </select>
          </p>
          <p>
            <?php _e("Number of posts to show on each box:", 'themejunkie'); ?>
            <br/>
            <select name="<?php echo $settings; ?>[list2]">
              <option style="padding-right:10px;" value="0" <?php selected('0', get_theme_mod('list2')); ?>>1</option>
              <option style="padding-right:10px;" value="1" <?php selected('1', get_theme_mod('list2')); ?>>2</option>
              <option style="padding-right:10px;" value="2" <?php selected('2', get_theme_mod('list2')); ?>>3</option>
              <option style="padding-right:10px;" value="3" <?php selected('3', get_theme_mod('list2')); ?>>4</option>
              <option style="padding-right:10px;" value="4" <?php selected('4', get_theme_mod('list2')); ?>>5</option>
              <option style="padding-right:10px;" value="5" <?php selected('5', get_theme_mod('list2')); ?>>6</option>
              <option style="padding-right:10px;" value="6" <?php selected('6', get_theme_mod('list2')); ?>>7</option>
              <option style="padding-right:10px;" value="7" <?php selected('7', get_theme_mod('list2')); ?>>8</option>
              <option style="padding-right:10px;" value="8" <?php selected('8', get_theme_mod('list2')); ?>>9</option>
              <option style="padding-right:10px;" value="9" <?php selected('9', get_theme_mod('list2')); ?>>10</option>
            </select>
            <span style="margin-left:10px; color: #999999;">
            <?php _e("(default: 4)", 'themejunkie'); ?>
            </span> </p>
          <p>
            <?php _e("Thumbnail size (Width x Height):", 'themejunkie'); ?>
            <br/>
            <input type="text" name="<?php echo $settings; ?>[boxrightthumbw]" value="<?php echo get_theme_mod('boxrightthumbw'); ?>" size="4" />
            x
            <input type="text" name="<?php echo $settings; ?>[boxrightthumbh]" value="<?php echo get_theme_mod('boxrightthumbh'); ?>" size="4" />
            <span style="margin-left:10px; color: #999999;">
            <?php _e("(default: 100x80)", 'themejunkie'); ?>
            </span> </p>
        </div>
      </div>
      <!--end: content right-->
    </div>
    <?php // end first column ?>
    <?php // begin second column ?>
    <div class="metabox-holder">
      <div class="postbox">
        <h3>
          <?php _e("RSS feed - Menu bar", 'themejunkie'); ?>
        </h3>
        <div class="inside">
 <p>
            <?php _e("Type of RSS feed:", 'themejunkie'); ?>
            <br />
            <select name="<?php echo $settings; ?>[feed]">
              <option style="padding-right:10px;" value="rss" <?php selected('rss', get_theme_mod('feed')); ?>>Default RSS</option>            
              <option style="padding-right:10px;" value="feedburner" <?php selected('feedburner', get_theme_mod('feed')); ?>>FeedBurner RSS</option>
            </select>
          </p>        
          <p>
            <?php _e("Your FeedBurner ID:", 'themejunkie'); ?>
            <br />
            <input type="text" name="<?php echo $settings; ?>[feedburner_id]" value="<?php echo get_theme_mod('feedburner_id'); ?>" size="35" />
          </p>
        </div>
      </div>
      <!--end: subscribe-->
      <div class="postbox">
        <h3>
          <?php _e("468x60 Ad", 'themejunkie'); ?>
          -
          <?php _e("Header", 'themejunkie'); ?>
        </h3>
        <div class="inside">
          <p>
            <?php _e("Display 468x60 ad on the header?", 'themejunkie'); ?>
            <br />
            <select name="<?php echo $settings; ?>[showad468x60]">
              <option style="padding-right:10px;" value="Yes" <?php selected('Yes', get_theme_mod('showad468x60')); ?>>Yes</option>
              <option style="padding-right:10px;" value="No" <?php selected('No', get_theme_mod('showad468x60')); ?>>No</option>
            </select>
          </p>
          <p>
            <?php _e("Enter your ad code:", 'themejunkie'); ?>
            <br />
            <textarea name="<?php echo $settings; ?>[ad468x60]" cols=35 rows=7><?php echo stripslashes(get_theme_mod('ad468x60')); ?></textarea>
          </p>
        </div>
      </div>
      <!--end: 468x60 ad-->
      <div class="postbox">
        <h3>
          <?php _e("300x250 Ad", 'themejunkie'); ?>
          -
          <?php _e("Sidebar", 'themejunkie'); ?>
        </h3>
        <div class="inside">
          <p>
            <?php _e("Display 300x250 ad on the sidebar?", 'themejunkie'); ?>
            <br />
            <select name="<?php echo $settings; ?>[showad300x250]">
              <option style="padding-right:10px;" value="Yes" <?php selected('Yes', get_theme_mod('showad300x250')); ?>>Yes</option>
              <option style="padding-right:10px;" value="No" <?php selected('No', get_theme_mod('showad300x250')); ?>>No</option>
            </select>
          </p>
          <p>
            <?php _e("Enter your ad code:", 'themejunkie'); ?>
            <br />
            <textarea name="<?php echo $settings; ?>[ad300x250]" cols=35 rows=7><?php echo stripslashes(get_theme_mod('ad300x250')); ?></textarea>
          </p>
        </div>
      </div>
      <!--end: 300x250 ad-->
      <div class="postbox">
        <h3>
          <?php _e("Site Tracking", 'themejunkie'); ?>
        </h3>
        <div class="inside">
          <p>
            <?php _e("Include analytics/stat tracking code?", 'themejunkie'); ?>
            <br />
            <select name="<?php echo $settings; ?>[track]">
              <option style="padding-right:10px;" value="Yes" <?php selected('Yes', get_theme_mod('track')); ?>>Yes</option>
              <option style="padding-right:10px;" value="No" <?php selected('No', get_theme_mod('track')); ?>>No</option>
            </select>
            <br />
            <?php _e("Enter your analytics/stat tracking code:", 'themejunkie'); ?>
            <br />
            <textarea name="<?php echo $settings; ?>[track_code]" cols=35 rows=7><?php echo stripslashes(get_theme_mod('track_code')); ?></textarea>
          </p>
        </div>
      </div>
      <!--end: tracking-->
      <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'themejunkie') ?>" />
        <input type="submit" class="button-highlighted" name="<?php echo $settings; ?>[reset]" value="<?php _e('Reset Settings', 'themejunkie'); ?>" />
      </p>
    </div>
    <!--end: second column-->
  </form>
</div>
<?php }

// add CSS and JS if necessary
function theme_options_css_js() {
echo <<<CSS

<style type="text/css">
	.metabox-holder { 
		width: 350px; float: left;
		margin: 0; padding: 0 10px 0 0;
	}
	.metabox-holder .postbox .inside {
		padding: 0 10px;
	}
	input, textarea, select {
		margin: 5px 0 5px 0;
		padding: 1px;
	}
</style>

CSS;
echo <<<JS

<script type="text/javascript">
jQuery(document).ready(function($) {
	$(".fade").fadeIn(1000).fadeTo(1000, 1).fadeOut(1000);
});
</script>

JS;
}
?>
