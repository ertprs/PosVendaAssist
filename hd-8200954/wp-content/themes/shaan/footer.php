<?php //Retrieve Theme Options Data
global $options;
$options = get_option('p2h_theme_options'); 
?>
</div><!--#container-->

<div id="footer">

	<div id="footer-menu">

	<?php if ( isset($options['hide_footer_nav']) && ($options['hide_footer_nav']!="") ){ ?>
	<?php //Do Nothing ?>
	<?php } else { ?>
		<?php wp_nav_menu( array( 'menu' => 'Footer Navigation', 'container' => 'div','container_id' => 'footer-navi', 'depth' => '1', 'theme_location' => 'footer-menu') ); ?>
	<?php } ?>
	
	<?php if ( isset($options['footer_text']) && ($options['footer_text']!="") ){ ?>
	<p><?php echo(stripslashes ($options['footer_text'])); ?></p>
	<?php } ?>
	
	</div>
	
	<div id="footer-credit">
	<?php if ( isset($options['hide_footer_credit']) && ($options['hide_footer_credit']!="") ){ ?>
	<?php } else { ?>
		<p><a href="<?php echo home_url( '/' ); ?>" title="<?php bloginfo ('name');?>"><?php bloginfo ('name');?></a> <?php _e('powered by','shaan'); ?> <a href="http://www.wordpress.org"><?php _e('WordPress','shaan'); ?></a> and <a href="http://www.speckygeek.com/shaan-free-wordpress-theme/" title="WordPress Themes by Specky Geek"><?php _e('Shaan','shaan'); ?></a></p>
	<?php } ?>
	</div>
	<a class="top-link" href="#wrapper"><?php _e('Top','shaan');?> &uArr;</a>
</div><!--#footer-->

<div class="clear"></div>
</div><!--#wrapper -->

<?php if ( isset($options['analytics_code']) && ($options['analytics_code']!="") ){ ?>
<?php echo(stripslashes ($options['analytics_code']));?>
<?php } ?>
		
<!-- Do not remove this, it's required for certain plugins which generally use this hook to reference JavaScript files. -->
<?php wp_footer(); ?>	
</body>
</html>
