<?php //Retrieve Theme Options Data
global $options;
$options = get_option('p2h_theme_options'); 

$functions_path = TEMPLATEPATH . '/functions/';
//Theme Options
require_once ($functions_path . 'theme-options.php'); 


// Sets content and images width
if ( !isset($content_width) ) $content_width = 600;

// Add default posts and comments RSS feed links to head
if ( function_exists('add_theme_support') ) {
	add_theme_support('automatic-feed-links');
}


//Header Customization -- Remove Auto Feed URLif ( isset ($options['feedurl']) &&  ($options['feedurl']!="") ) {
	remove_action( 'wp_head', 'feed_links', 2);
}

// Remove the links to the extra feeds such as category feeds
if ( isset ($options['cleanfeedurls']) &&  ($options['cleanfeedurls']!="") ) {
	remove_action( 'wp_head', 'feed_links_extra', 3 ); 
}

// Enables the navigation menu ability
if ( function_exists('register_nav_menus')) {

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary-menu' => __( 'Header Navigation', 'shaan' ),
		'footer-menu' => __( 'Footer Navigation', 'shaan' ),

	) );
	}

// Enables post-thumbnail support
if ( function_exists('add_theme_support') ){
add_theme_support('post-thumbnails');
add_image_size('headThumb', 900, 400, true);
add_image_size('bigThumb', 600, 250, true);
}

// Adds callback for custom TinyMCE editor stylesheets 
if ( function_exists('add_editor_style') ) add_editor_style();

// This theme allows users to set a custom background
add_custom_background();

// Support for custom headers
define( 'HEADER_TEXTCOLOR', 'FFFFFF' );

// Don't support text inside the header image.
define( 'NO_HEADER_TEXT', false );

// Support for custom header image
define('HEADER_IMAGE', ''); 
// Header Image Size Depending on Layout	
define('HEADER_IMAGE_WIDTH', 960);
define('HEADER_IMAGE_HEIGHT', 100);
function p2h_header_style() { 
?>
<style type="text/css">
	#header {
	background: url(<?php header_image(); ?>) 0 0 no-repeat;}
<?php if ( 'blank' == get_header_textcolor() ) { ?>
	#header #site-title, #header #site-description{
	display: none;}
<?php } else { ?>
	#header #site-title a{
	color: #<?php header_textcolor(); ?>;}
<?php } ?>
</style>
<?php 
}

function p2h_admin_header_style() {
    ?><style type="text/css">
        #headimg {
            width: 960px !important;
			margin: 0;
			border: 0 none !important;
        }
		#headimg h1 {
			margin: 0;
			font-family: Arial, Helvetica, san-serif;
			font-size: 60px;
			line-height:100px;
			font-weight: normal;
			float:left;
		}
		#headimg a {
			color: #21759B;
			text-decoration: none;
		}
		#desc {
			float:right;
			text-transform:uppercase;
			font-size:14px;
			line-height:100px;
		}
    </style><?php 
}

if ( function_exists('add_custom_image_header') ) add_custom_image_header('p2h_header_style', 'p2h_admin_header_style');

// Registers a widgetized sidebar and replaces default WordPress HTML code with a better HTML
if ( function_exists('register_sidebar') )
    // Area 1, located at the top of the sidebar.
	register_sidebar( array(
		'name' => __( 'Top Sidebar Widget Area', 'shaan' ),
		'id' => 'top-sidebar-widget-area',
		'description' => __( 'The sidebar widget area. Leave blank to use default widgets.', 'shaan' ),
		'before_widget' => '<div id="%1$s" class="section widget-container %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );


// Sets the post excerpt length to 55 characters.
function p2h_excerpt_length( $length ) {
	return 55;
}
add_filter( 'excerpt_length', 'p2h_excerpt_length' );

/**
 * Returns a "Continue Reading" link for excerpts
 */
function p2h_continue_reading_link() {
	return '<p><a class="more-link" href="'. get_permalink() . '">' . __( 'Read more &raquo;', 'shaan' ) . '</a></p>';
}

function p2h_auto_excerpt_more( $more ) {
	return ' &hellip; '. p2h_continue_reading_link();
}

add_filter( 'excerpt_more', 'p2h_auto_excerpt_more' );

/**
 * Adds a pretty "Continue Reading" link to custom post excerpts.
 *
 * To override this link in a child theme, remove the filter and add your own
 * function tied to the get_the_excerpt filter hook.
 *
 * @since Twenty Ten 1.0
 * @return string Excerpt with a pretty "Continue Reading" link
 */
function p2h_custom_excerpt_more( $output ) {
	if ( has_excerpt() && ! is_attachment() ) {
		$output .= p2h_continue_reading_link();
	}
	return $output;
}
add_filter( 'get_the_excerpt', 'p2h_custom_excerpt_more' );

/**
 *Read More Jumps to Top of the Page.
*/
function remove_more_jump_link($link) { 
$offset = strpos($link, '#more-');
if ($offset) {
$end = strpos($link, '"',$offset);
}
if ($end) {
$link = substr_replace($link, '', $offset, $end-$offset);
}
return $link;
}
add_filter('the_content_more_link', 'remove_more_jump_link');

// Returns TRUE if more than one page exists.
//Useful for not echoing .post-navigation HTML when there aren't posts to page
function show_posts_nav() {
	global $wp_query;
	return ($wp_query->max_num_pages > 1);
}

/**
 * Remove inline styles printed when the gallery shortcode is used.
 * Galleries are styled by the theme in style.css.
 */
function p2h_remove_gallery_css( $css ) {
	return preg_replace( "#<style type='text/css'>(.*?)</style>#s", '', $css );
}
add_filter( 'gallery_style', 'p2h_remove_gallery_css' );


// Removes ugly inline CSS style for Recent Comments widget
function p2h_remove_recent_comments_style() {
	global $wp_widget_factory;
	remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
}
add_action( 'widgets_init', 'p2h_remove_recent_comments_style' );

//Enque scripts in header
function p2h_init_js() {

if ( !is_admin() ) { // instruction to only load if it is not the admin area
   // enqueue the script
   
	wp_enqueue_script('jquery-ui-core');
	   
}

}    
add_action('init', 'p2h_init_js');


// Remove the links to feed
//remove_action( 'wp_head', 'feed_links', 2);
// Remove the links to the extra feeds such as category feeds
//remove_action( 'wp_head', 'feed_links_extra', 3 ); 

//Redirect to theme options page on activation
if ( is_admin() && isset($_GET['activated'] ) && $pagenow =="themes.php" )
	wp_redirect( 'admin.php?page=theme-options.php' );


if ( ! function_exists( 'p2h_comment' ) ) :
/**
 * Template for comments and pingbacks.
 */
function p2h_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case '' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
		
		<div class="comment-meta">
		
			<div class="comment-avatar">
			<?php echo get_avatar( $comment, 40 ); ?>
			</div><!--.comment-avatar-->
			<div class="comment-author vcard">
				<cite class="fn"><?php comment_author_link(); ?></cite>
			</div><!-- .comment-author .vcard -->

			<div class="comment-date commentmetadata">
			<?php comment_date() ?> &ndash; <?php comment_time() ?> <?php edit_comment_link( __( '(Edit)', 'shaan' ), ' ' ); ?>
			</div><!-- .comment-date .commentmetadata -->

		</div><!--.comment-meta-->

		<?php if ( $comment->comment_approved == '0' ) : ?>
			<em><?php _e( 'Your comment is awaiting moderation.', 'shaan' ); ?></em>
		<?php endif; ?>

		<div class="comment-body"><?php comment_text(); ?></div>

		<div class="reply">
			<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
		</div><!-- .reply -->
	</div><!-- #comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="pingback">
		<?php comment_author_link(); ?><?php edit_comment_link( __('(Edit)', 'shaan'), ' ' ); ?>
	<?php
			break;
	endswitch;
}
endif;