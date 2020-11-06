<?php get_header(); ?>
<!--Content placement-->
<div class="left" id="main">
    <div id="main_content">
<?php is_tag(); ?>
		<?php if (have_posts()) : ?>
<h2 class="pagetitle">
<?php if (is_category()) : ?>
	<?php _e( 'Category: ', 'Grayandgold' ); ?>&#8216<?php printf( single_cat_title());?>&#8217;
<?php elseif ( is_tag() ) : ?>
	<?php _e( 'Posts Tagged: ', 'Grayandgold' ); ?>&#8216;<?php printf( single_tag_title());?>&#8217;
<?php elseif ( is_day() ) : ?>
	<?php _e( 'Archive for: ', 'Grayandgold' ); ?>&#8216<?php printf( get_the_date() ); ?>&#8217;
<?php elseif ( is_month() ) : ?>
	<?php _e( 'Archive for: ', 'Grayandgold' ); ?>&#8216<?php printf(  get_the_date('F Y') ); ?>&#8217;
<?php elseif ( is_year() ) : ?>
	<?php _e( 'Archive for: ', 'Grayandgold' ); ?>&#8216<?php printf( get_the_date('Y') ); ?>&#8217;
<?php endif; ?>

</h2>
		<?php while (have_posts()) : the_post(); ?>
<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">

<!--Post title-->
<h2 class="title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

<!--Date, Time and Author-->
<p class="meta"><span class="clock"></span><span class="date"><?php the_time(get_option('date_format')) ?></span><span class="posted"> <?php _e( 'Posted by ', 'Grayandgold' ); ?> <?php the_author() ?>  <?php edit_post_link(__('Edit', 'Grayandgold' )); ?></span></p>
<!--Post content-->
<div class="entry">
	<?php the_content(); ?>
    <div class="clearer"></div>
</div>

<!--Post footer-->
<div class="postfooter"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to: ', 'Grayandgold' ); ?> <?php the_title_attribute(); ?>" class="more"><?php _e( 'Read full article', 'Grayandgold' ); ?></a>
<span class="full_post"></span> |<span class="commentbouble"></span><?php comments_popup_link( __( 'Leave a comment', 'Grayandgold' ), __( '1 Comment', 'Grayandgold' ), __( '% Comments', 'Grayandgold' ) ); ?><br/><?php $tag = get_the_tags(); if (! has_tag()){ echo __( 'Tags: No tags', 'Grayandgold' ); } else { the_tags(__('Tags: ', ', ', 'Grayandgold')); } ?>

<br/><?php _e( 'Categories: ', 'Grayandgold' ); ?><?php the_category(', '); ?>
</div></div>
<?php endwhile; ?>

<!-- Older / newer posts -->
<div class="navigation">
			<div class="alignleft"><?php next_posts_link (__( '&laquo; Older Entries', 'Grayandgold')) ?></div>
			<div class="alignright"><?php previous_posts_link(__( 'Newer Entries &raquo;', 'Grayandgold')) ?></div>
            
</div>

<?php else : ?>
<h2 class="center"><?php _e( 'Not Found', 'Grayandgold' ); ?></h2>
<p class="center"><?php _e( 'Sorry, but you are looking for something that is not here.', 'Grayandgold' ); ?></p>
<?php endif; ?>

    </div>
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
