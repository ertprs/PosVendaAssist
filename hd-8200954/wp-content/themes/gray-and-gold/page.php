<?php get_header(); ?>
<!--Content placement-->
<div class="left" id="main">
    <div id="main_content">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<!--Page title-->
<h2 class="title"><?php the_title_attribute(); ?></h2>
<!--Page content-->
    <div class="entry">
   		<?php if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
        the_post_thumbnail();
        }?>
        <?php the_content(); ?>
        <div class="clearer"></div>
        <?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
     </div>
</div>
			
		<?php endwhile; endif; ?>
		<?php if ( comments_open() ) comments_template(); ?>
		<div class="navigation">
			<?php edit_post_link(__('Edit', 'Grayandgold' )); ?>
		</div>

    </div>
</div>
<!-- end content -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
