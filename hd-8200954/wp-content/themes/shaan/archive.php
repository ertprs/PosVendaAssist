<?php get_header(); ?>

<div id="container">

	<div id="content" class="narrow">
	
	<?php if (have_posts()) : ?>
	
		<?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
		
	<h1 class="page-title">
		<?php if (is_category()) : ?>
			<?php _e('Category Archives: ', 'shaan'); ?><span class="capitalize"><?php single_cat_title(); ?></span>
	 	<?php elseif( is_tag() ) : ?>
			<?php _e('Tag Archives: ', 'shaan'); ?><span class="capitalize"><?php single_tag_title(); ?></span>
		<?php elseif (is_day()) : ?>
			<?php _e('Daily Archives: ', 'shaan'); ?><?php the_time('F jS, Y'); ?>
		<?php elseif (is_month()) : ?>
			<?php _e('Monthly Archives:  ', 'shaan'); ?><?php the_time('F, Y'); ?>
		<?php elseif (is_year()) : ?>
			<?php _e('Yearly Archives: ', 'shaan'); ?><?php the_time('Y'); ?>
		<?php elseif (is_author()) : ?>
			<?php _e('Author Archive', 'shaan'); ?>
		<?php else : ?>
			<?php _e( 'Blog Archives', 'shaan' ); ?>
		<?php endif; ?>
	</h1>
			
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
