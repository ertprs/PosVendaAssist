<?php
$themename = "Shaan";
$shortname = "p2h";
$version = "1.1.3";

$option_group = $shortname.'_theme_option_group';
$option_name = $shortname.'_theme_options';


// Load stylesheet and jscript
add_action('admin_init', 'p2h_add_init');

function p2h_add_init() {
	$file_dir = get_template_directory_uri();
	wp_enqueue_style("p2hCss", $file_dir."/functions/theme-options.css", false, "1.0", "all");
	wp_enqueue_script("p2hScript", $file_dir."/functions/theme-options.js", false, "1.0");
}

// Create custom settings menu
add_action('admin_menu', 'p2h_create_menu');

function p2h_create_menu() {
	global $themename;
	//create new top-level menu
	add_theme_page( __( $themename.' Theme Options' ), __( 'Theme Options' ), 'edit_theme_options', basename(__FILE__), 'p2h_settings_page' );
}

// Register settings
add_action( 'admin_init', 'register_settings' );

function register_settings() {
   global $themename, $shortname, $version, $p2h_options, $option_group, $option_name;
  	//register our settings
	register_setting( $option_group, $option_name);
}

//Automatically List StyleSheets in Folder/////////////////////////////////////////////

$alt_stylesheet_path = TEMPLATEPATH . '/styles/';
$alt_stylesheets = array();

if ( is_dir($alt_stylesheet_path) ) {
    if ($alt_stylesheet_dir = opendir($alt_stylesheet_path) ) { 
        while ( ($alt_stylesheet_file = readdir($alt_stylesheet_dir)) !== false ) {
            if((stristr($alt_stylesheet_file, ".css") !== false) && (stristr($alt_stylesheet_file, "default") == false)){
                $alt_stylesheets[] = $alt_stylesheet_file;
            }
        }    
    }
}
array_unshift($alt_stylesheets, "default.css"); 

// Create theme options
global $p2h_options;
$p2h_options = array (

array("name" => __('Theme Styles','shaan'),
		"type" => "section"),

array("name" => __('Choose a color scheme and add custom CSS styles.','shaan'),
		"type" => "section-desc"),
	
array("type" => "open"),

array("name" => __('Colour Scheme','shaan'), 
		"desc" => __('Select a colour scheme for the theme. Future versions will have multiple styles.','shaan'),
		"id" => "alt_stylesheet",
		"type" => "select",
		"options" => $alt_stylesheets,
		"std" => "default.css"),
		
array( "name" => __('Custom Styles','shaan'),
	"desc" => __('Want to add any custom CSS code? Put in here, and the rest is taken care of. This overrides any other stylesheets. eg: a.button{color:green}','shaan'),
	"id" => "custom_css",
	"type" => "textarea",
	"std" => ""),

array("type" => "close"),

array("name" => __('RSS Feeds/Facebook/Twitter','shaan'),
		"type" => "section"),

array("name" => __('Set up social links.','shaan'),
		"type" => "section-desc"),
	
array("type" => "open"),

array("name" => __('Custom Feed URL','shaan'),
		"desc" => __('You can use your own feed URL (<strong>with http://</strong>). Paste your Feedburner URL here to let readers see it in your website.','shaan'),
		"id" => "feedurl",
		"type" => "text",
		"std" => get_bloginfo('rss2_url')),
	
array("name" => __('Delete Extra Feeds','shaan'),
		"desc" => __('WordPress adds feeds for categories, tags, etc., by default. Check this box to remove them and reduce the clutter.','shaan'),
		"id" => "cleanfeedurls",
		"type" => "checkbox",
		"std" => ""),

array("name" => __('Twitter ID','shaan'),
		"desc" => __('Your Twitter user name, please. It will be shown in the navigation bar. Leaving it blank will keep the Twitter icon supressed.','shaan'),
		"id" => "twitterid",
		"type" => "text",
		"std" => ""),

array("name" => __('Facebook Page','shaan'),
		"desc" => __('Link to your Facebook page, <strong>with http://</strong>. It will be shown in the navigation bar. Leaving it blank will keep the Facebook icon supressed.','shaan'),
		"id" => "facebookid",
		"type" => "text",
		"std" => ""),
	
array("type" => "close"),

//FOOTER
array("name" => __('Footer','shaan'),
		"type" => "section"),

array("name" => __('Customize footer of your website.','shaan'),
		"type" => "section-desc"),
	
array("type" =>"open"),

array("name" => __('Footer Text','shaan'),
		"desc" => __('Enter your footer text or HTML here.','shaan'),
		"id" => "footer_text",
		"std" => "",
		"type" => "textarea"),

array("name" => __('Hide Footer Navigation Links','shaan'),
		"desc" => __('Select to hide the navigation bar in the footer. If you want to customize the footer navigation, go to Menus under the Appearance tab in the dashboard.','shaan'),
		"id" => "hide_footer_nav",
		"std" => "",
		"type" => "checkbox"),

array("name" => __('Hide Footer Credits','shaan'),
		"desc" => __('Select to hide the credit line in the footer. It is completely optional, but a <strong>generous contribution</strong> to support this theme will be highly appreciated.','shaan'),
		"id" => "hide_footer_credit",
		"std" => "",
		"type" => "checkbox"),

array( "type" => "close"),

//ADVERTISEMENTS --- POST ADS 
array("name" => __('Advertisements','shaan'),
	"type" => "section"),

array("name" => __('Show ads on your blog.','shaan'),
		"type" => "section-desc"),
	
array("type" => "open"),

array("name" => __('Header Display Ad','shaan'),
		"desc" => __('You can show a display ad in header. Paste the code here for 600px by 60px ad.','shaan'),
		"id" => "topbanner",
		"std" => "",
		"type" => "textarea"),

array("name" => __('Ad Above Posts','shaan'),
		"desc" => __('Enter your Adsense code or other ad network code here. This ad will be displayed at the beginning of posts, below title on Post Pages and Pages with ad-supporting template. It is very basic and effective option for putting ads on your blog. If you want more functionality, get a specialized Ad plugin.','shaan'),
		"id" => "posttop_adcode",
		"std" => "",
		"type" => "textarea"),

array("name" => __('Ad Below Posts','shaan'),
		"desc" => __('Enter your Adsense code (or other ad network code) here. This ad will be displayed at the end of post content on Post Pages and Pages with ad-supporting template. Please make sure that you do not activate more ads than what is allowed by your ad network. Adsense allows up to 3 on one page.','shaan'),
		"id" => "postend_adcode",
		"std" => "",
		"type" => "textarea"),

array("type" => "close"),

//Analytics Code
array("name" => __('Tracking & Other Codes','shaan'),
		"type" => "section"),

array("name" => __('Insert Web tracking & analytics and other codes here.','shaan'),
		"type" => "section-desc"),
	
array("type" => "open"),

array("name" => __('Analytics & Tracking Code','shaan'),
		"desc" => __('You can paste your Google Analytics or other codes in this box. The codes will be automatically added to the footer.','shaan'),
		"id" => "analytics_code",
		"type" => "textarea",
		"std" => ""),	

array("type" => "close")
);


function p2h_settings_page() {
   global $themename, $shortname, $version, $p2h_options, $option_group, $option_name;
?>

<div class="wrap">
<div class="options_wrap">
<?php screen_icon(); ?><h2><?php echo $themename; ?> <?php _e('Theme Options','shaan'); ?></h2>
<p class="top-notice"><?php _e('Customize your WordPress blog with these settings. ','shaan'); ?></p>
<?php if ( isset ( $_POST['reset'] ) ): ?>
<?php // Delete Settings
global $wpdb, $themename, $shortname, $version, $p2h_options, $option_group, $option_name;
delete_option('p2h_theme_options');
wp_cache_flush(); ?>
<div class="updated fade"><p><strong><?php _e( $themename. ' options reset.' ); ?></strong></p></div>

<?php elseif ( isset ( $_REQUEST['save'] ) ): ?>
<div class="updated fade"><p><strong><?php _e( $themename. ' options saved.' ); ?></strong></p></div>
<?php endif; ?>

<form method="post" action="options.php">

<?php settings_fields( $option_group ); ?>

<?php $options = get_option( $option_name ); ?>        

<?php foreach ($p2h_options as $value) {
if ( isset($value['id']) ) { $valueid = $value['id'];}
switch ( $value['type'] ) {
case "section":
?>
	<div class="section_wrap">
	<h3 class="section_title"><?php echo $value['name']; ?> 

<?php break; 
case "section-desc":
?>
	<span><?php echo $value['name']; ?></span></h3>
	<div class="section_body">

<?php 
break;
case 'text':
?>

	<div class="options_input options_text">
		<div class="options_desc"><?php echo $value['desc']; ?></div>
		<span class="labels"><label for="<?php echo $option_name.'['.$valueid.']'; ?>"><?php echo $value['name']; ?></label></span>
		<input name="<?php echo $option_name.'['.$valueid.']'; ?>" id="<?php echo $option_name.'['.$valueid.']'; ?>" type="<?php echo $value['type']; ?>" value="<?php if ( isset( $options[$valueid]) ){ esc_attr_e($options[$valueid]); } else { esc_attr_e($value['std']); } ?>" />
	</div>

<?php
break;
case 'textarea':
?>
	<div class="options_input options_textarea">
		<div class="options_desc"><?php echo $value['desc']; ?></div>
		<span class="labels"><label for="<?php echo $option_name.'['.$valueid.']'; ?>"><?php echo $value['name']; ?></label></span>
		<textarea name="<?php echo $option_name.'['.$valueid.']'; ?>" type="<?php echo $option_name.'['.$valueid.']'; ?>" cols="" rows=""><?php if ( isset( $options[$valueid]) ){ esc_attr_e($options[$valueid]); } else { esc_attr_e($value['std']); } ?></textarea>
	</div>

<?php 
break;
case 'select':
?>
	<div class="options_input options_select">
		<div class="options_desc"><?php echo $value['desc']; ?></div>
		<span class="labels"><label for="<?php echo $option_name.'['.$valueid.']'; ?>"><?php echo $value['name']; ?></label></span>
		<select name="<?php echo $option_name.'['.$valueid.']'; ?>" id="<?php echo $option_name.'['.$valueid.']'; ?>">
		<?php foreach ($value['options'] as $option) { ?>
				<option <?php if ($options[$valueid] == $option) { echo 'selected="selected"'; } ?>><?php echo $option; ?></option><?php } ?>
		</select>
	</div>

<?php
break;
case "radio":
?>
	<div class="options_input options_select">
		<div class="options_desc"><?php echo $value['desc']; ?></div>
		<span class="labels"><label for="<?php echo $option_name.'['.$valueid.']'; ?>"><?php echo $value['name']; ?></label></span>
		  <?php foreach ($value['options'] as $key=>$option) { 
			$radio_setting = $options[$valueid];
			if($radio_setting != ''){
				if ($key == $options[$valueid] ) {
					$checked = "checked=\"checked\"";
					} else {
						$checked = "";
					}
			}else{
				if($key == $value['std']){
					$checked = "checked=\"checked\"";
				}else{
					$checked = "";
				}
			}?>
			<input type="radio" id="<?php echo $option_name.'['.$valueid.']'; ?>" name="<?php echo $option_name.'['.$valueid.']'; ?>" value="<?php echo $key; ?>" <?php echo $checked; ?> /><?php echo $option; ?><br />
			<?php } ?>
	</div>

<?php
break;
case "checkbox":
?>
	<div class="options_input options_checkbox">
		<div class="options_desc"><?php echo $value['desc']; ?></div>
		<?php if( isset( $options[$valueid] ) ){ $checked = "checked=\"checked\""; }else{ $checked = "";} ?>
		<input type="checkbox" name="<?php echo $option_name.'['.$valueid.']'; ?>" id="<?php echo $option_name.'['.$valueid.']'; ?>" value="true" <?php echo $checked; ?> />
		<label for="<?php echo $option_name.'['.$valueid.']'; ?>"><?php echo $value['name']; ?></label>
	 </div>

<?php
break;
case "close":
?>
</div><!--#section_body-->
</div><!--#section_wrap-->

<?php 
break;
}
}
?>

<span class="submit">
<input class="button button-primary" type="submit" name="save" value="<?php _e('Save All Changes', 'shaan') ?>" />
</span>
</form>

<form method="post" action="">
<span class="button-right" class="submit">
<input class="button button-secondary" type="submit" name="reset" value="<?php _e('Reset/Delete Settings', 'shaan') ?>" />
<input type="hidden" name="action" value="reset" />
<span><?php _e('Caution: All entries will be deleted from database. Press when starting afresh or completely removing the theme.','shaan') ?></span>
</span>
</form>
</div><!--#options-wrap-->

<div class="sidebox">
	<h2>Support <?php echo $themename; ?>!</h2>
	<p>You are using <strong><?php echo $themename; ?> <?php echo $version; ?></strong>, a wordPress theme by <a href="http://www.speckygeek.com">Specky Geek</a>, a technology blog.</p>
	<p>If you find this theme helpful, please be generous and send me a reward. Be generous. Select a high amount from the dropdown.</p>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="hosted_button_id" value="6WPPE2PW6ERVC">
	<table>
	<tr><td><input type="hidden" name="on0" value="Reward for Shaan WP Theme">Reward for Shaan WP Theme</td></tr><tr><td><select name="os0">
		<option value="Twenty Five Dollars">Twenty Five Dollars $25.00</option>
		<option value="Ten Dollars">Ten Dollars $10.00</option>
		<option value="Fifteen Dollars">Fifteen Dollars $15.00</option>
		<option value="Twenty Dollars">Twenty Dollars $20.00</option>
		<option value="Twenty Five Dollars">Twenty Five Dollars $25.00</option>
		<option value="Thirty Five Dollars">Thirty Five Dollars $35.00</option>
		<option value="Fifty Dollars">Fifty Dollars $50.00</option>
		<option value="Hundred Dollars">Hundred Dollars $100.00</option>
	</select> </td></tr>
	</table>
	<input type="hidden" name="currency_code" value="USD">
	<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_paynow_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
	<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</form>
	<hr />
	<ul>
	<li><a href="http://www.speckygeek.com/">Specky Geek</a></li>
	<li><a href="http://www.speckygeek.com/wordpress-themes/">Free WordPress Themes</a></li>
	<li><a href="http://www.speckygeek.com/contact-us/">Contact Specky Geek</a></li>
	</ul>
	<p>PS: I cannot offer free support. Make a generous contribution before writing for help.</p>
</div>
</div>
<?php } ?>