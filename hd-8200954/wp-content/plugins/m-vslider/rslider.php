<?php
/*
  Plugin Name: M-vSlider
  Plugin URI: http://www.seovalley.com.pk/m-vslider/
  Description: Implementing a featured image gallery into your WordPress theme has never been easier! Showcase your portfolio, animate your header or manage your banners with M-vSlider. M-vSlider by  Muhammad Amir Ul Amin. M-vSlider is multi sliders clone of vSlider (http://www.vibethemes.com/wordpress-plugins/vslider-wordpress-image-slider-plugin/)
  Author: M. Amir Ul Amin
  Version: 1.1.1

  M-vSlider is released under GPL:
  http://www.opensource.org/licenses/gpl-license.php
 */

// Load jQuery from WordPress
function rslider_loadJquery() {
    wp_enqueue_script('jquery-ui-tabs', 'js/ui.tabs.js', array('jquery'));
}

// M-vSlider Theme Head
function rslider_head() {
    global $wpdb;
    global $table_slider;
    $rslider_js = (WP_PLUGIN_URL . '/m-vslider/js/rslider.js');

    $mainsql = " SELECT * FROM $table_slider order by rs_id";

    if ($mainrows = $wpdb->get_results($mainsql)) {
?>
        <script type="text/javascript" src="<?php echo $rslider_js; ?>"></script>
    <?php
        foreach ($mainrows as $myslider) {
            $rs_id = $myslider->rs_id;
            $rs_name = $myslider->rs_name;
            $rs_width = $myslider->rs_width;
            $rs_height = $myslider->rs_height;
            $rs_speed = $myslider->rs_speed;
            $rs_animstyle = $myslider->rs_animstyle;
            $rs_css = $myslider->rs_css;
            $rs_timeout = $myslider->rs_timeout;
            $rs_type = $myslider->rs_type;
    ?>

            <!-- Start M-vSlider -->
            <style type="text/css">
                #sliderbody<?php echo $rs_id; ?>, #sliderbody<?php echo $rs_id; ?> img {width: <?php echo $rs_width; ?>px;height: <?php echo $rs_height; ?>px;}
                #rslider<?php echo $rs_id; ?> {<?php echo $rs_css; ?>}
                #rslider<?php echo $rs_id; ?> {height: <?php echo $rs_height; ?>px;overflow: hidden;}
                #rslider<?php echo $rs_id; ?> ul {list-style: none !important;margin: 0 !important;padding: 0 !important;}
                #rslider<?php echo $rs_id; ?> ul li {list-style: none !important;margin: 0 !important;padding: 0 !important;}
                #sliderbody<?php echo $rs_id; ?> {overflow: hidden !important;}
                #sliderbody<?php echo $rs_id; ?> img {-ms-interpolation-mode: bicubic;}
            </style>
            <script type="text/javascript">
                /*** M-vSlider Init ***/
                jQuery.noConflict();
                jQuery(document).ready(function(){
                    jQuery('ul#sliderbody<?php echo $rs_id; ?>').innerfade({
                        animationtype: '<?php echo $rs_animstyle; ?>',
                        speed: <?php echo $rs_speed; ?>,
                        timeout: <?php echo $rs_timeout; ?>000,
                        type: '<?php echo $rs_type; ?>',
                        containerheight: '<?php echo $rs_height; ?>px'
                    });
                });
            </script>
            <!-- End M-vSlider -->
            <?php
        }
    }
}

// M-vSlider Function
function rslider($atts = 0) {

    $rs_id = $atts;
    if ($atts["id"])
        $rs_id = $atts["id"];

    global $wpdb;
    global $table_slider;
    $rs_sql = " SELECT * FROM $table_slider WHERE rs_id ='$rs_id'";
    if ($rs_rows = $wpdb->get_results($rs_sql)) {
        ?>

        <div id="rslider<?php echo $rs_id; ?>">
            <ul id="sliderbody<?php echo $rs_id; ?>">
                <?php
                if ($rs_rows[0]) {
                    $rs_row = $rs_rows[0];
                    $rs_images = unserialize($rs_row->rs_images);
                    if (!empty($rs_images)) {
                        foreach ($rs_images as $rs_image) {
                            if ($rs_image['img']) {
                                ?>
                                <li>
                                    <a href="<?php echo stripslashes($rs_image['url']); ?>" <?php echo (($rs_image['blank']) ? ' target="_blank" ' : ''); ?>>
                                        <img src="<?php echo stripslashes($rs_image['img']); ?>" alt="featured" class="rsliderImg" />
                                    </a>
                                </li>
                                <?php
                            }
                        }//foreach
                    }//if
                }
                ?>
            </ul>
        </div>

        <?php
    }
}

// Register M-vSlider As Widget
add_action('widgets_init', create_function('', "register_widget('rslider_widget');"));

class rslider_widget extends WP_Widget {

    function rslider_widget() {
        $widget_ops = array('classname' => 'rslider-widget', 'description' => 'jQuery based image slider');
        $control_ops = array('width' => 200, 'height' => 250, 'id_base' => 'rslider-widget');
        $this->WP_Widget('rslider-widget', 'M-vSlider - Image Slider', $widget_ops, $control_ops);
    }

    function widget($args, $instance) {
        extract($args);

        echo $before_widget;

        if (!empty($instance['title']) && !empty($instance['rs_id']))
            echo $before_title . $instance['title'] . $after_title;

        rslider($instance['rs_id']);

        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        return $new_instance;
    }

    function form($instance) {

        global $wpdb;
        global $table_slider;
        $mainsql = " SELECT * FROM $table_slider order by rs_id";

        if ($mainrows = $wpdb->get_results($mainsql)) {
            ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e("Title"); ?>:</label>
                <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" /></p>
            <p><label for="<?php echo $this->get_field_id('rs_id'); ?>"><?php _e("M-vSlider ID"); ?>:</label>
                <select id="<?php echo $this->get_field_id('rs_id'); ?>" name="<?php echo $this->get_field_name('rs_id'); ?>" value="<?php echo $instance['rs_id']; ?>" style="width:95%;">
                    <?php
                    foreach ($mainrows as $myslider) {
                        $rs_id = $myslider->rs_id;
                        $rs_name = $myslider->rs_name;
                        ?>			
                        <option value="<?php echo $rs_id; ?>" <?php echo ($instance['rs_id'] == $rs_id ? " selected " : ""); ?>>[<?php echo $rs_id; ?>]-> <?php echo $rs_name; ?></option>
                        <?php
                    }
                    ?>				
                </select>
            </p>
            <?php
        }
    }

}

// Add M-vSlider Short Code
add_shortcode('m-vslider', 'rslider');

// Add The Option Page to WordPress Dashboard
function rslider_addPage() {
    add_menu_page('M-vSlider Setup', 'M-vSlider Setup', 8, __FILE__, 'rslider_page');
}

// rslide Options Page
function rslider_page() {

    global $wpdb;
    global $table_slider;
    if ($_GET['rs_action'] == 'rs_remove') {
        $wpdb->query("DELETE FROM $table_slider WHERE rs_id = '" . $_GET['rs_id'] . "'");
        $_GET['rs_id'] = '';
    }


    if ($_POST['rs_addnew'] && $_POST['rs_name']) {
        $rows_affected = $wpdb->insert($table_slider, array('rs_id' => '', 'rs_name' => $_POST['rs_name']));
        if ($rows_affected == 1) {
            $_GET['rs_id'] = $wpdb->insert_id;
        }
    }

    $currurl = $_SERVER['PHP_SELF'] . '?page=' . $_GET['page'];
    if (!$_GET['rs_id'] && !$_POST['rs_id']) {

        $sql = " 	SELECT * FROM $table_slider order by rs_id";

        $rows = $wpdb->get_results($sql);
        ?>
        <div class="wrap" id="rslider-panel">
            <div id="icon-options-general" class="icon32"><br /></div>
            <h2><?php _e("M-vSlider - Home"); ?></h2>		
            <div id='css_rs_main'><BR><BR>
                <table id='css_rs_table'>
                    <tr>
                        <th>Actions</th>
                        <th>ID</th>
                        <th>Shortcode</th>
                        <th>Use in Template/PHP code</th>
                        <th>Name</th>
                        <th>W x H (px)</th>
                        <th>Speed</th>
                        <th>Timeout</th>
                        <th>Anim. Style</th>
                        <th>Type</th>
                        <th>Custom CSS</th>
                    </tr>
                    <?php
                    $style_a['fade'] = 'Fade';
                    $style_a['slide'] = 'Slide';

                    $type_a['sequence'] = 'Sequence';
                    $type_a['random'] = 'Random';
                    $type_a['random_start'] = 'Random Start';

                    if ($rows = $wpdb->get_results($sql)) {
                        foreach ($rows as $row) {
                            ?>
                            <tr>
                                <td align="center"><a href="<?php echo $currurl; ?>&rs_id=<?php echo $row->rs_id; ?>&rs_action=rs_edit" title="Edit"><img src="<?php echo WP_PLUGIN_URL; ?>/m-vslider/edit.png"></a>&nbsp;&nbsp;<a href="<?php echo $currurl; ?>&rs_id=<?php echo $row->rs_id; ?>&rs_action=rs_remove" title="Delete" onclick="return confirm('Are you sure, you want to delete this slider?');"><img src="<?php echo WP_PLUGIN_URL; ?>/m-vslider/remove.png"></a></td>
                                <td><?php echo $row->rs_id; ?></td>
                                <td>
                                    <pre>[m-vslider id="<?php echo $row->rs_id; ?>"]</pre>
                                </td>
                                <td>
<pre>
&lt;?php if(function_exists('rslider')){
    rslider(<?php echo $row->rs_id; ?>);
} ?&gt;
</pre>
                                </td>
                                <td><?php echo $row->rs_name; ?></td>
                                <td><?php echo $row->rs_width . " x " . $row->rs_height; ?></td>
                                <td><?php echo $row->rs_speed . " ms"; ?></td>
                                <td><?php echo $row->rs_timeout . " sec"; ?></td>
                                <td><?php echo $style_a[$row->rs_animstyle]; ?></td>
                                <td><?php echo $type_a[$row->rs_type]; ?></td>
                                <td>
                                    <div id="screen-meta-links">
                                        <div id="screen-meta">
                                            <div class="hidden" id="contextual-css-wrap<?php echo $row->rs_id; ?>">
                                                <div class="metabox-prefs"><pre><?php echo $row->rs_css; ?></pre></div>
                                            </div>
                                            <div id="screen-meta-links">
                                                <div class="hide-if-no-js screen-meta-toggle" id="contextual-help-link-wrap">
                                                    <a class="show-settings" id="contextual-css-link<?php echo $row->rs_id; ?>" href="#" style="color:red">Show CSS</a>
                                                </div>
                                            </div>
                                        </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="11">No Slider Configured!</td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <div>
                    <form method='post' action='<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>'>
                        New Slider Name: <input type=text id='rs_name' name='rs_name'>
                        <input type=submit value='Add New Slider' id='rs_addnew'  name='rs_addnew' onclick="var val=this.form.rs_name.value;if (val== null || val == '' ) {alert('Pleae enter slider name first!');return false;}">
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        $cur_page = $_SERVER['PHP_SELF'] . '?page=' . $_GET['page'];
        $rs_id = $_GET['rs_id'];

        if ('process' == $_POST['tcOptions']) {

            $updatequery = "UPDATE $table_slider SET rs_name='" . $_POST['rs_name'] . "',";

            if ($_POST['rs_width']) {
                $updatequery .= " rs_width = '" . $_POST['rs_width'] . "',";
            }
            if ($_POST['rs_height']) {
                $updatequery .= " rs_height = '" . $_POST['rs_height'] . "',";
            }
            if ($_POST['rs_speed']) {
                $updatequery .= " rs_speed = '" . $_POST['rs_speed'] . "',";
            }
            if ($_POST['rs_animstyle']) {
                $updatequery .= " rs_animstyle = '" . $_POST['rs_animstyle'] . "',";
            }
            if ($_POST['rs_css']) {
                $updatequery .= " rs_css = '" . $_POST['rs_css'] . "',";
            }
            if ($_POST['rs_timeout']) {
                $updatequery .= " rs_timeout = '" . $_POST['rs_timeout'] . "',";
            }
            if ($_POST['rs_type']) {
                $updatequery .= " rs_type = '" . $_POST['rs_type'] . "',";
            }


            $rs_images = array();
            for ($i = 0; $i < 10; $i++) {
                if ($_POST["rs_img$i"]) {
                    $rs_images[] = array('img' => $_POST["rs_img$i"], 'url' => $_POST["rs_lnk$i"], 'blank' => array_key_exists("rs_bnk$i", $_POST));
                }
            }

            $updatequery .= " rs_images = '" . stripslashes(serialize($rs_images)) . "'";

            $updatequery .= " WHERE rs_id = " . $_POST['rs_id'];

            $wpdb->query($updatequery);
            $rs_id = $_POST['rs_id'];
        }

        $myslider = $wpdb->get_row("SELECT * FROM $table_slider WHERE rs_id = '" . $rs_id . "'");
        $rs_name = $myslider->rs_name;
        $rs_width = $myslider->rs_width;
        $rs_height = $myslider->rs_height;
        $rs_speed = $myslider->rs_speed;
        $rs_animstyle = $myslider->rs_animstyle;
        $rs_css = $myslider->rs_css;
        $rs_timeout = $myslider->rs_timeout;
        $rs_type = $myslider->rs_type;
        $rs_images = unserialize($myslider->rs_images);

        if ($rs_width == "") {
            $rs_width = 250;
        }
        if ($rs_height == "") {
            $rs_height = 250;
        }
        if ($rs_speed == "") {
            $rs_speed = 1000;
        }
        if ($rs_timeout == "") {
            $rs_timeout = 5;
        }
        if ($rs_css == "") {
            $rs_css = "margin: 0px 0px 0px 0px; padding: 0; border: none;";
        }
        ?>

        <div class="wrap" id="rslider-panel"><div id="icon-options-general" class="icon32"><br /></div>
            <h2><?php _e("<a href='$currurl'>M-vSlider</a> &raquo; Slider Options"); ?></h2>
            <?php if ($_REQUEST['save'])
                echo '<div id="message" class="updated fade" style="width:750px;"><p><strong>Slider Options Saved.</strong></p></div>'; ?>
            <form method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>&updated=true">
                <input type="hidden" name="tcOptions" value="process" />
                <input type="hidden" name="rs_id" value="<?php echo $rs_id ?>" />
                <!-- Start First Column -->
                <div class="metabox-holder">
                    <div class="postbox">
                        <h3><?php _e("Slider General Settings"); ?></h3>
                        <div class="inside">
                            <p>
                                <?php _e("Name:"); ?>&nbsp;<input type="text" name="rs_name" value="<?php echo $rs_name; ?>" size="35" />&nbsp;
                            </p>
                            <p>
                                <?php _e("Width:"); ?>&nbsp;<input type="text" name="rs_width" value="<?php echo $rs_width; ?>" size="5" />&nbsp;px&nbsp;&nbsp;
                                <?php _e("Height:"); ?>&nbsp;<input type="text" name="rs_height" value="<?php echo $rs_height; ?>" size="5" />&nbsp;px
                            </p>
                            <p>
                                <?php _e("Speed:"); ?>&nbsp;<input type="text" name="rs_speed" value="<?php echo $rs_speed; ?>" size="5" />&nbsp;<?php _e("milliseconds"); ?>&nbsp;&nbsp;
                                <?php _e("Animation:"); ?>&nbsp;
                                <select name="rs_animstyle">
                                    <option style="padding-right:10px;" value="fade" <?php selected('fade', $rs_animstyle); ?>><?php _e("Fade"); ?></option>
                                    <option style="padding-right:10px;" value="slide" <?php selected('slide', $rs_animstyle); ?>><?php _e("Slide"); ?></option>
                                </select>
                            </p>
                            <p><?php _e("Timeout:"); ?>&nbsp;<input type="text" name="rs_timeout" value="<?php echo $rs_timeout; ?>" size="5" />&nbsp;<?php _e("seconds"); ?>&nbsp;&nbsp;
                                <?php _e("Type:"); ?>&nbsp;
                                <select name="rs_type">
                                    <option style="padding-right:10px;" value="sequence" <?php selected('sequence', $rs_type); ?>><?php _e("Sequence"); ?></option>
                                    <option style="padding-right:10px;" value="random" <?php selected('random', $rs_type); ?>><?php _e("Random"); ?></option>
                                    <option style="padding-right:10px;" value="random_start" <?php selected('random_start', $rs_type); ?>><?php _e("Random Start"); ?></option>
                                </select>
                            </p>
                        </div>
                       <h3><?php _e("Custom CSS Settings"); ?></h3>
                        <div class="inside">
                            <p>Enter here custom CSS for this Slider:<br />
                                <textarea name="rs_css" style="width:350px;" rows="4"><?php echo stripslashes($rs_css); ?></textarea>
                            </p>
                            <p><input type="submit" class="button" name="save" value="<?php _e('Update Options') ?>" /></p>
                        </div>
                    </div>
                    <p><input type="submit" class="button-primary" name="save" value="<?php _e('Save Settings') ?>" /></p>
                </div>
                <!-- End First Column -->
                <!-- Start Second Column -->
                <div class="metabox-holder">
                    <div class="postbox">
                        <h3><?php _e("Images Setup"); ?></h3>
                        <div class="inside">
                            <?php
                            for ($i = 0; $i < 10; $i++) {
                                ?>
                                <p style="background-color:#<?php echo ($i%2?'E0E6ED;border: 1px dashed #888':'E6EDE0');?>; padding:10px;"><?php _e("Image " . ($i + 1) . " path:"); ?>
                                    <input type="text" name="rs_img<?php echo $i; ?>" value="<?php echo stripslashes($rs_images[$i]['img']); ?>" style="width:100%;" />
                                    <?php _e("Image " . ($i + 1) . " links to:"); ?>
                                    <input type="text" name="rs_lnk<?php echo $i; ?>" value="<?php echo stripslashes($rs_images[$i]['url']); ?>" style="width:100%;" />
                                    <input type="checkbox" name="rs_bnk<?php echo $i; ?>" id="rs_bnk<?php echo $i; ?>" <?php echo ($rs_images[$i]['blank'] ? ' checked ' : ''); ?> value="<?php echo $i; ?>" /> <label for="rs_bnk<?php echo $i; ?>"><em>Open link in New Tab/Window</em></label>
                                </p>
                                <?php
                            }
                            ?>
                            <p><input type="submit" class="button" name="save" value="<?php _e('Update Options') ?>" /></p>
                        </div>
                    </div>

                </div>
                <!-- End Second Column -->
            </form>
        </div>
        <?php
    }
}

function rslider_install() {
    global $wpdb;
    global $table_slider;
    global $rslider_db_version;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $cur_rslider_db_version = get_option("rslider_db_version", null);

    if ($wpdb->get_var("show tables like '$table_slider'") != $table_slider) {
        $create_table_sql = "CREATE TABLE `$table_slider` (
								`rs_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
								`rs_name` varchar(100) NOT NULL,
								`rs_css` varchar(255) NOT NULL DEFAULT 'margin: 0px 0px 0px 0px; padding: 0; border: none;',
								`rs_width` smallint(6) NOT NULL DEFAULT '250',
								`rs_height` smallint(6) NOT NULL DEFAULT '250',
								`rs_speed` int(11) NOT NULL DEFAULT '1000',
								`rs_animstyle` varchar(10) NOT NULL DEFAULT 'fade',
								`rs_timeout` smallint(6) NOT NULL DEFAULT '5',
								`rs_type` varchar(15) NOT NULL DEFAULT 'sequence',
								`rs_images` blob NOT NULL,
								`rs_options` blob NOT NULL,
								PRIMARY KEY (`rs_id`),
								UNIQUE KEY `rs_name` (`rs_name`)
								) ENGINE=MyISAM DEFAULT CHARSET=latin1";

        $wpdb->query($create_table_sql);
        $rows_affected = $wpdb->insert($table_slider, array('rs_id' => '', 'rs_name' => 'Default Slider'));
        update_option("rslider_db_version", $rslider_db_version);
    } else {
        if ($cur_rslider_db_version == "1.0") { // If old DB version found then upgrade it
            // #1 -> add rs_images, and rs_options columns
            $alterquery = " ALTER TABLE $table_slider ADD `rs_images` BLOB NOT NULL, ADD `rs_options` BLOB NOT NULL";
            $wpdb->query($alterquery);

            // #2 -> update rs_images column from current image columns
            $mainsql = " SELECT * FROM $table_slider order by rs_id";

            if ($mainrows = $wpdb->get_results($mainsql, ARRAY_A)) {
                foreach ($mainrows as $myslider) {
                    $rs_images = array();
                    for ($i = 0; $i < 10; $i++) {
                        if ($myslider["rs_img$i"]) {
                            $rs_images[] = array('img' => $myslider["rs_img$i"], 'url' => $myslider["rs_lnk$i"], 'blank' => '');
                        }
                    }
                }
		$wpdb->update( 	$table_slider, 
						array( 'rs_images' => serialize($rs_images) ), 
						array( 'rs_id' => $myslider['rs_id'] ), 
						array( '%s' ), 
						array( '%d' ) );
            }

            // #3 -> remove all current image columns
            $alterquery = " ALTER TABLE $table_slider
							DROP `rs_img1`,
							DROP `rs_lnk1`,
							DROP `rs_img2`,
							DROP `rs_lnk2`,
							DROP `rs_img3`,
							DROP `rs_lnk3`,
							DROP `rs_img4`,
							DROP `rs_lnk4`,
							DROP `rs_img5`,
							DROP `rs_lnk5`,
							DROP `rs_img6`,
							DROP `rs_lnk6`,
							DROP `rs_img7`,
							DROP `rs_lnk7`,
							DROP `rs_img8`,
							DROP `rs_lnk8`,
							DROP `rs_img9`,
							DROP `rs_lnk9`,
							DROP `rs_img10`,
							DROP `rs_lnk10`";
            $wpdb->query($alterquery);
            update_option("rslider_db_version", $rslider_db_version);
        }
    }
}

function rslider_adminCSS() {
    ?>
    <style type='text/css'>
        #rslider-panel .metabox-holder {float: left;width: 380px;margin: 0px; padding:0px 10px 0px 0px;}
        #rslider-panel .metabox-holder .postbox .inside {padding: 0 10px;background-color: #E6EDE0;}
        #rslider-panel .metabox-holder .postbox .inside p{margin: 0px;padding: 1em 0;}
        .red {font-weight: normal;color: #B80028;}
        #css_rs_main, #css_rs_main div {border: 0;margin-top:15px}
        #css_rs_main #screen-meta-links, #css_rs_main #screen-meta-links div {border: 0;margin-top:0px}
        #css_rs_table {border-collapse:collapse;width:98%;font-size:11px;font-family:Arial;}
        #css_rs_table, #css_rs_table th, #css_rs_table td{border: 1px solid #DDD;padding: 2px 2px 2px 5px;font-size:11px;font-family:Arial;vertical-align:middle;}
        #css_rs_table td pre{font-size:10px;background-color:#FFB;color:#F00;}
        #css_rs_table td {background-color: #FFF;}
        #css_rs_table th {background-color: #EEE;}
    </style>
    <script type="text/javascript">
        /*** M-vSlider Init ***/
        jQuery.noConflict();
        jQuery(document).ready(function(){
    <?php
    global $wpdb;
    global $table_slider;
    $mainsql = " SELECT * FROM $table_slider order by rs_id";

    if ($mainrows = $wpdb->get_results($mainsql)) {
        foreach ($mainrows as $myslider) {
            $rs_id = $myslider->rs_id;
            ?>
                            jQuery('#contextual-css-link<?php echo $rs_id; ?>').click(function(e) {
                                e.preventDefault();
                                if (jQuery('#contextual-css-wrap<?php echo $rs_id; ?>').hasClass('contextual-help-open'))
                                {
                                    jQuery(this).text('Show CSS');
                                    jQuery(this).css('background-position', 'right top');
                                    jQuery('#contextual-css-wrap<?php echo $rs_id; ?>').removeClass('contextual-help-open');
                                    jQuery('#contextual-css-wrap<?php echo $rs_id; ?>').hide();
                                }
                                else
                                {
                                    jQuery(this).text('Hide CSS');
                                    jQuery(this).css('background-position', 'right bottom');
                                    jQuery('#contextual-css-wrap<?php echo $rs_id; ?>').addClass('contextual-help-open');
                                    jQuery('#contextual-css-wrap<?php echo $rs_id; ?>').show();
                                }
                            });
            <?php
        } //foreach
    }//if
    ?>
                    		
        });
    </script>
    <?php
}

global $wpdb;
global $rslider_db_version;
global $table_slider;
$rslider_db_version = "1.1";
$table_slider = $wpdb->prefix . 'rs_slider';

register_activation_hook(__FILE__, 'rslider_install');
add_action('wp_print_scripts', 'rslider_loadJquery');
add_action('wp_head', 'rslider_head');
add_action('admin_head', 'rslider_adminCSS');
add_action('admin_menu', 'rslider_addPage');
?>
