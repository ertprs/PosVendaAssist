<?php get_header(); ?>

<div id="container">

	<div id="content" class="fullpage">
	
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post();?>
		
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			
		<h1 class="post-title"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
		<p class="post-meta"><?php the_author_posts_link(); ?>  &diams;  <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_time('F j, Y') ?></a>  &diams;  <?php comments_popup_link( __('Leave Your Comment', 'shaan'), __( '1 Comment', 'shaan'), __('% Comments', 'shaan')); ?>
		
		<?php	if ( wp_attachment_is_image() ) {
			echo ' &diams ';
			$metadata = wp_get_attachment_metadata();
			printf( __( '%s pixels', 'shaan'),
				sprintf( '<a href="%1$s" title="%2$s">%3$s &times; %4$s</a>',
				wp_get_attachment_url(),
				esc_attr( __('Link to full-size image', 'shaan') ),
				$metadata['width'],
				$metadata['height']
				)
				);
			}
		?>
		
		<?php if ( ! empty( $post->post_parent ) ) : ?>
			&diams; <a href="<?php echo get_permalink( $post->post_parent ); ?>" title="<?php esc_attr( printf( __( 'Return to %s', 'shaan' ), get_the_title( $post->post_parent ) ) ); ?>" rel="gallery"><?php _e('Back to gallery','shaan');?></a>
		<?php endif; ?>
						
		<?php if(is_sticky()) {?>  &diams;  <?php _e('Sticky Post','shaan');?><?php } ?> <?php edit_post_link( __( 'Edit', 'shaan' ), ' &diams; ', '' ); ?></p>
		
		<div class="entry-attachment">
<?php if ( wp_attachment_is_image() ) :
	$attachments = array_values( get_children( array( 'post_parent' => $post->post_parent, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID' ) ) );
	foreach ( $attachments as $k => $attachment ) {
		if ( $attachment->ID == $post->ID )
			break;
	}
	$k++;
	// If there is more than 1 image attachment in a gallery
	if ( count( $attachments ) > 1 ) {
		if ( isset( $attachments[ $k ] ) )
			// get the URL of the next image attachment
			$next_attachment_url = get_attachment_link( $attachments[ $k ]->ID );
		else
			// or get the URL of the first image attachment
			$next_attachment_url = get_attachment_link( $attachments[ 0 ]->ID );
	} else {
		// or, if there's only 1 image attachment, get the URL of the image
		$next_attachment_url = wp_get_attachment_url();
	}
?>
						<div class="attachment"><a href="<?php echo $next_attachment_url; ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="attachment"><?php
							$attachment_size = apply_filters( 'shaan_attachment_size', 860 );
							echo wp_get_attachment_image( $post->ID, array( $attachment_size, 9999 ) ); // filterable image width with, essentially, no limit for image height.
						?></a></div>

						<div class="entry-caption"><?php if ( !empty( $post->post_excerpt ) ) the_excerpt(); ?></div>

						<?php the_content( __('<p> Read more &raquo;</p>', 'shaan') ); ?>
						<?php wp_link_pages( __('before=<div class="post-page-links">Pages:&after=</div>', 'shaan')) ; ?>

						<div class="navigation">
						<div class="nav-previous"><?php previous_image_link( false, __('&laquo; Previous Image', 'shaan') ); ?></div>
						<div class="nav-next"><?php next_image_link( false, __('Next Image &raquo;', 'shaan') ); ?></div>
						</div><!-- .navigation -->
						
						<?php if ( ! empty( $post->post_parent ) ) : ?>
						<div class="return-attachment"><?php _e('Return to ','shaan'); ?><a href="<?php echo get_permalink( $post->post_parent ); ?>" title="<?php esc_attr( printf( __( 'Return to %s', 'shaan' ), get_the_title( $post->post_parent ) ) ); ?>" rel="gallery"><?php
								printf( __( '%s', 'shaan' ), get_the_title( $post->post_parent ) );
							?></a></div>
						
						<?php the_tags( __(' &diams ', 'shaan'), '<div id="post-info"><ul><li>', '</li></div>'); ?>
						<?php endif; ?>

<?php else : ?>
						<a href="<?php echo wp_get_attachment_url(); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="attachment"><?php echo basename( get_permalink() ); ?></a>
<?php endif; ?>


		</div><!-- .entry-attachment -->
		</div><!--#posts-->

<?php comments_template(); ?>

		<?php endwhile; ?>

		
	<?php else : ?>
		
		<h2 class="page-title"><?php _e('Not Found', 'shaan'); ?></h2>
		<p><?php _e('Sorry, but you are looking for something that is not here.', 'shaan'); ?></p>
		<?php get_search_form(); ?>
		
	<?php endif; ?>

	</div><!-- #content -->
	
	<?php get_footer(); ?>
