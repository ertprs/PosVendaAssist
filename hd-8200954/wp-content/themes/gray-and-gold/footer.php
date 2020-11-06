<div id="footer">
	<div class="center_wrapper">
    
		<div class="left">
        <!--Blog title in footer-->
		<a href="<?php echo home_url( '/' ) ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
		<?php bloginfo( 'name' ); ?></a> <?php bloginfo( 'description' ); ?>
     </div>
        <!--Credit link and powered by wordpress-->
<p style="float:right;margin-right:12px; font-size:10px;">Theme by <a href="http://w3blog.dk" title="w3blog" target="_blank">W3blog</a></p>
	<div class="clearer"></div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
