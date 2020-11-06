<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> &raquo; Blog Archive <?php } ?> <?php wp_title(); ?></title>

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />


<?php if ( is_singular() ) wp_enqueue_script( "comment-reply" ); ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="wrapper-top-navigation">
	<div class="center_wrapper">
<?php wp_nav_menu( array('fallback_cb' => '', 'theme_location' => 'extra-menu', 'container'=>'', 'echo' => true, ) );?>
	</div>
</div>
<div id="header">
	<div class="center_wrapper">
	
		  <div id="logo">
            <div id="titel">
                 <h1><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?> - <?php bloginfo( 'description' ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
                 <h2><?php bloginfo( 'description' ); ?></h2>
             </div>
    		</div>
	    </div>
    </div>
<div id="wrapper-navigation">
	<div id="navigation">
		<div class="center_wrapper">
        <div class="menu">
			<?php wp_nav_menu( array( 'theme_location' => 'header-menu', 'container'=>'') ); ?> 
            </div>
		</div>
	</div> 
</div>
<!-- Image under menu -->  
<div id="bodyheader"></div>    
<div id="layout_body">
	<div id="main_wrapper_outer">
		<div id="main_wrapper_inner">
			<div class="center_wrapper">