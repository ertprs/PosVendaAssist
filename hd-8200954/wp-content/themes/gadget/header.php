<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title>
<?php bloginfo('name'); ?>
<?php wp_title(); ?>
</title>
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_directory'); ?>/style.css" media="screen"/>
<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/slider.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> Atom Feed" href="<?php bloginfo('atom_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<link rel="shortcut icon" href="<?php bloginfo('template_directory'); ?>/images/favicon.ico" />
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/javascripts/dropdown.js"></script>
<?php if (function_exists('wp_enqueue_script') && function_exists('is_singular')) : ?>
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php endif; ?>
<?php wp_head(); ?>
<script language="javascript" type="text/javascript" src="<?php bloginfo('template_url'); ?>/javascripts/jquery-1.3.2.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php bloginfo('template_url'); ?>/javascripts/jquery.flow.1.2.auto.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$("#myController").jFlow({
		slides: "#slides",
		controller: ".jFlowControl", // must be class, use . sign
		slideWrapper : "#jFlowSlide", // must be id, use # sign
		selectedWrapper: "jFlowSelected",  // just pure text, no sign
		auto: true,		//auto change slide, default true
		width: "100%",
		height: "201px",
		duration: 400,
		prev: ".jFlowPrev", // must be class, use . sign
		next: ".jFlowNext" // must be class, use . sign
	});
});
</script>
<!--[if lt IE 7]>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/javascripts/unitpngfix.js"></script>
<![endif]-->
</head>
<body>
<div id="top">
  <div id="topwrapper">
    <div id='topnav'>
      <div class="left">
        <ul>
          <?php if ( is_home() ) { ?>
          <li class="current_page_item"><a href="<?php echo get_option('home'); ?>/">
            <?php _e("Home", 'themejunkie'); ?>
            </a></li>
          <?php } else { ?>
          <li><a href="<?php echo get_option('home'); ?>/">
            <?php _e("Home", 'themejunkie'); ?>
            </a></li>
          <?php } ?>
          <?php wp_list_pages('depth=1&sort_column=menu_order&title_li='); ?>
        </ul>
      </div>
      <!--end: left-->
      <div class="right">
        <form method="get" id="searchform" action="<?php bloginfo('home'); ?>/">
          <div id="search">
            <input class="searchinput" type="text" value="pesquisar..." onclick="this.value='';" name="s" id="s" />
            <input type="submit" class="searchsubmit" value="?"/>
          </div>
        </form>
      </div>
      <!--end: right-->
    </div>
  </div>
  <!--end: topwrapper-->
</div>
<!--end: top-->
<div id="header">
  <div id="headerwrapper"><a class="logo" href="<?php bloginfo('siteurl'); ?>"><img src="/site-wp/wp-content/themes/gadget/images/logo-2011-texto.png"></a>
    <!--end: logo-->
    <?php include('ads/header468x60.php'); ?>
  </div>
  <!--end: headerwrapper-->
</div>
<!--end: header-->
<div id="menu">
  <div id="menuwrapper">
    <div class="left">
      <ul>
        <?php wp_list_categories('title_li=&orderby=id'); ?>
      </ul>
    </div>
    <div class="right">
      <?php if (get_theme_mod('feed') == 'feedburner') { ?>
      <a class="rssfeed" href="http://feeds.feedburner.com/<?php echo get_theme_mod('feedburner_id'); ?>" title="Assine o feed RSS"> </a>
      <?php } else { ?>
      <a class="rssfeed" href="<?php bloginfo('rss2_url'); ?>" title="Subscribe to RSS feed"> </a>
      <?php } ?>
    </div>
    <!--end: right-->
  </div>
  <!--end: menuwrapper-->
</div>
<!--end: menu-->
<div id="wrapper">

<?php #include ("/var/www/telecontrol/www/aviso_ruptura_cabo.html"); ?>
