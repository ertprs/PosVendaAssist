<?php get_header(); ?>

<div id="container">

	<div id="content" class="narrow">
	
<?php
	/* Queue the first post, that way we know who
	 * the author is when we try to get their name,
	 * URL, description, avatar, etc.
	 *
	 * We reset this later so we can run the loop
	 * properly with a call to rewind_posts().
	 */
	if ( have_posts() )
		the_post();
?>	

<h1 class="page-title author"><?php printf( __( 'Author Archives: %s', 'shaan' ), "<span class='vcard capitalize'>" . get_the_author() . "</span>" ); ?></h1>

<?php
// If a user has filled out their description, show a bio on their entries.
if ( get_the_author_meta( 'description' ) ) : ?>
		<div id="entry-author-info" class="clearfix">
			<div id="author-avatar">
				<?php echo get_avatar( get_the_author_meta( 'user_email')); ?>
			</div><!-- #author-avatar -->
			<div id="author-description">
				<h2><?php printf( __('About %s', 'shaan'), "<span class='capitalize'>".get_the_author() ."</span>" ); ?></h2>
				<?php the_author_meta( 'description' ); ?>
			</div><!-- #author-description	-->
		</div><!-- #entry-author-info -->
<?php endif; ?>
			
<?php
	/* Since we called the_post() above, we need to
	 * rewind the loop back to the beginning that way
	 * we can run the loop properly, in full.
	 */
	rewind_posts();
?>

		<?php if ( have_posts() ) :?>
		<?php while (have_posts()) : the_post();?>
		
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			
			<div class="post-thumb">
				<?php 
				if( has_post_thumbnail($post->ID) &&
				( /* $src, $width, $height */ $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'bigThumb' ) ) &&
				$image[1] >= 600 &&
				$image[2] >= 250 ) { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>">
				<?php // Use the image above post title
					echo get_the_post_thumbnail( $post->ID, 'bigThumb' ); ?>
				</a>
				<?php } ?>
			</div><!--  #post-thumb -->	

			
			<h2 class="post-title"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
			<p class="post-meta"><?php the_author_posts_link(); ?>  &diams;  <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_time('F j, Y') ?></a>  &diams;  <?php comments_popup_link( __('Leave Your Comment', 'shaan'), __( '1 Comment', 'shaan'), __('% Comments', 'shaan')); ?><?php if(is_sticky()) {?>  &diams;  <?php _e('Sticky Post','shaan'); ?><?php } ?></p>
			
			<?php 
				if( has_post_thumbnail($post->ID) &&
				( /* $src, $width, $height */ $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'post-thumbnail' ) ) &&
				$image[1] < 600 ) {
				// Use as small thumbnail beow headline ?>
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute( ); ?>" rel="bookmark">
				<?php echo get_the_post_thumbnail( $post->ID, 'thumbnail', 'class=home-thumb-small alignleft'); ?>
				</a>
			<?php } ?>
			
			<?php the_excerpt(); ?>
			
		</div><!--#posts-->

		<?php endwhile; ?>

		<?php if (function_exists('wp_pagenavi')) { wp_pagenavi(); } else { include('navigation.php'); } ?>

		
	<?php else : ?>
		
		<h2 class="page-title"><?php _e('Not Found', 'shaan'); ?></h2>
		<p><?php _e('Sorry, but you are looking for something that is not here.', 'shaan'); ?></p>
		<?php get_search_form(); ?>
		
	<?php endif; ?>

	</div><!-- #content -->
	
		
	<?php get_sidebar(); ?>
	<?php get_footer(); ?>
