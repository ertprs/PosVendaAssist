<?php get_header(); ?>
<!--Content-->
<div class="left" id="main">
    <div id="main_content">
    
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">

<!--Post title-->
<h2 class="title"><a href="<?php echo get_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h2>

<!--Date, Time and Author-->
<p class="meta"><span class="clock"></span><span class="date"><?php the_time(get_option('date_format')) ?></span><span class="posted"> <?php _e( 'Posted by ', 'Grayandgold' ); ?> <?php the_author() ?>  <?php edit_post_link(__('Edit', 'Grayandgold' )); ?></span></p>
<!--Post content-->			
<div class="entry">
    <?php if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
  	the_post_thumbnail();}?>
	<?php the_content(); ?><div class="clearer">
</div>
	<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
    
<!--Post Footer-->
<div class="clearer">
</div>
<?php if ( get_the_author_meta( 'description' ) ) : // If a user has filled out their description, show a bio on their entries  ?>
<div id="entry-author-info">
    <h2 title="<?php printf( __( 'About %s', 'Grayandgold' ), get_the_author() ); ?>"><?php printf( esc_attr__( 'About %s', 'Grayandgold' ), get_the_author() ); ?></h2>
    <div id="author-avatar">
        <?php echo get_avatar( get_the_author_meta( 'user_email' ), 60  ); ?>
    </div><!-- #author-avatar -->
    <div id="author-description">
        
        <?php the_author_meta( 'description' ); ?>
        <div id="author-link">
       <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" title="<?php printf( __( 'View all posts by %s', 'grayandgold' ), get_the_author() ); ?>">
                <?php printf( __( 'View all posts by %s', 'Grayandgold' ), get_the_author() ); ?>
            </a>
        </div><!-- #author-link	-->
    </div><!-- #author-description -->
</div><!-- #entry-author-info -->
<?php endif; ?>
<!--Post Footer-->
<div class="longmeta">
	<div class="postfooter"><?php $tag = get_the_tags(); if (! has_tag()){ echo __( 'Tags: No tags', 'Grayandgold' ); } else { the_tags(__('Tags: ', ', ', 'Grayandgold')); } ?>

<br/><?php _e( 'Categories: ', 'Grayandgold' ); ?><?php the_category(', '); ?><br/>
						
						

						<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Both Comments and Pings are open ?>
							<?php _e( 'You can <a href="#respond">leave a response</a>, or <a href="<?php trackback_url(true); ?>" rel="trackback">trackback</a> from your own site.', 'Grayandgold') ?>

						<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Only Pings are Open ?>
							<?php _e( 'Responses are currently closed, but you can <a href="<?php trackback_url(true); ?> " rel="trackback">trackback</a> from your own site.', 'Grayandgold') ?>

						<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Comments are open, Pings are not ?>
							<?php _e( 'You can skip to the end and leave a response. Pinging is currently not allowed.', 'Grayandgold') ?>

						<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Neither Comments, nor Pings are open ?>
							<?php _e( 'Both comments and pings are currently closed.', 'Grayandgold') ?>

						<?php }  ?>

	</div>
    
<!--Post comments-->
<?php comments_template(); ?>
				</div>
			</div>
		</div>
<?php endwhile; else: ?>
	<p><?php _( 'Sorry, no posts matched your criteria.', 'Grayandgold')?></p>
<?php endif; ?>

			</div>
		</div>
<!--Content end-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
