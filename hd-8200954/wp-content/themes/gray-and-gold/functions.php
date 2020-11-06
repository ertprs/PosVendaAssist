<?php
/* Sidebar */
if(!isset($content_width)) $content_width = 500;

    register_sidebar(array(
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));

if (function_exists('wp_nav_menu')) {
add_action( 'init', 'register_my_menus' );
function register_my_menus() {
register_nav_menus(
array(
			'header-menu' => __( 'header-menu' ),
			'extra-menu' => __( 'Extra Menu' ),
));
}
}

// Make theme available for translation
// Translations can be filed in the /languages/ directory
	load_theme_textdomain( 'Grayandgold', TEMPLATEPATH . '/languages' );
	$locale = get_locale();
	$locale_file = TEMPLATEPATH . "/languages/$locale.php";
	if ( is_readable( $locale_file ) )
		require_once( $locale_file ); 

if ( ! function_exists( 'Grayandgold_comment' ) ) :
/*Comment function */
function Grayandgold_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case '' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
		<div class="comment-author vcard">
			<?php echo get_avatar( $comment, 40 ); ?>
						<?php printf( __( '%s <span class="says">says:</span>', 'Grayandgold' ), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?>
		</div><!-- .comment-author .vcard -->
		<?php if ( $comment->comment_approved == '0' ) : ?>
			<em><?php _e( 'Your comment is awaiting moderation.', 'Grayandgold' ); ?></em>
			<br />
		<?php endif; ?>

		<div class="comment-meta commentmetadata"><a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
			<?php
				/* translators: 1: date, 2: time */
				printf( __( '%1$s at %2$s', 'Grayandgold' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( 'Edit', 'Grayandgold' ), ' ' );
			?>
		</div><!-- .comment-meta .commentmetadata -->

		<div class="comment-body"><?php comment_text(); ?></div>

		<div class="reply">
			<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'title_reply' => 'Leave a comment',  'reply_text' => __('Reply','Grayandgold'), 'max_depth' => $args['max_depth'] ) ) ); ?>
            
            
		</div><!-- .reply -->
	</div><!-- #comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', 'Grayandgold' ); ?> <?php comment_author_link(); ?> <?php edit_post_link(__('Edit', 'Grayandgold' )); ?></p>
	<?php
			break;
	endswitch;
	
}
endif;

if(function_exists('add_theme_support')) {
    add_theme_support('automatic-feed-links');
    add_theme_support('post-thumbnails');
	add_editor_style();

}

define('HEADER_TEXTCOLOR', 'f6aa16');//  Default text color
define('HEADER_IMAGE', '%s/design/header.png');  // %s is theme dir uri, set a default image
define('HEADER_IMAGE_HEIGHT', 150);  // Same for height
define('HEADER_IMAGE_WIDTH', 1600); //  Default image width is actually the div's width

function header_style() {
?>
<style type="text/css">
#header{
background: url(<?php header_image() ?>) repeat-x;
/*height: <?php echo HEADER_IMAGE_HEIGHT; ?>px;*/
height: 150px;
margin: 0 0;
padding: 0 0;
}
</style>
<?php
}
function admin_header_style() {
?>
<style type="text/css">
#headimg{
background: url(<?php header_image() ?>) repeat-x;
height: <?php echo HEADER_IMAGE_HEIGHT; ?>px;
width:<?php echo HEADER_IMAGE_WIDTH; ?>px;
padding:0 0 0 18px;
}

#headimg h1{
padding-top:40px;
margin: 0;
}
#headimg h1 a{
color:#<?php header_textcolor() ?>;
text-decoration: none;
border-bottom: none;
}
#headimg #desc{
color:#<?php header_textcolor() ?>;
font-size:1em;
margin-top:-0.5em;
}

#desc {
display: none;
}

<?php if ( 'blank' == get_header_textcolor() ) { ?>
#headimg h1, #headimg #desc {
display: none;
}
#headimg h1 a, #headimg #desc {
color:#<?php echo HEADER_TEXTCOLOR ?>;
}
<?php } ?>


</style>
<?php
}
add_custom_image_header('header_style', 'admin_header_style');
add_custom_background();
?>