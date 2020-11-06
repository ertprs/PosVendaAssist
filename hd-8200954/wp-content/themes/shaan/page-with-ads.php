<?php
/**
 * Template Name: Page With Ads
 *
 * A custom page template with ads.
 */
?>
<?php get_header(); ?>

<div id="container">

	<div id="content" class="narrow">
	
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post();?>
		
		<div id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
			
			<div class="post-thumb">
				<?php 
				if( has_post_thumbnail($post->ID) &&
				( /* $src, $width, $height */ $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'bigThumb' ) ) &&
				$image[1] >= 600 &&
				$image[2] >= 250 ) { ?>
				<?php // Use the image above post title
					echo get_the_post_thumbnail( $post->ID, 'bigThumb' ); ?>
				<?php } ?>
			</div><!--  #post-thumb -->	

			
			<h1 class="post-title"><?php the_title(); ?></h1>
			<p class="post-meta"><?php the_author_posts_link(); ?>  &diams;  <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_time('F j, Y') ?></a>  &diams;  <?php comments_popup_link( __('Leave Your Comment', 'shaan'), __( '1 Comment', 'shaan'), __('% Comments', 'shaan')); ?><?php if(is_sticky()) {?>  &diams;  <?php _e('Sticky Post','shaan');?><?php } ?></p>

			<!--Show Ads Below Post Title -->
			<?php if ( isset($options['posttop_adcode']) && ($options['posttop_adcode']!="") ){ ?>
			<div id="topad"><?php echo(stripslashes ($options['posttop_adcode']));?></div>
			</div>
			<?php } ?>
			
			<?php the_content( __('<p><a class="read-more" href="'. get_permalink() . '">' . __( 'Read more &raquo;', 'shaan' ) . '</a></p>', 'shaan') ); ?>
			<?php wp_link_pages( __('before=<div class="page-link">Pages:&after=</div>', 'shaan')) ; ?>
			
			<!--Show Ads Below Post -->
			<?php if ( isset($options['postend_adcode']) && ($options['postend_adcode']!="") ){ ?>
			<div id="bottomad"><?php echo(stripslashes ($options['postend_adcode']));?></div>
			</div>
			<?php } ?>
			
	</div><!--#posts-->

		<?php endwhile; ?>
		
		<?php else : ?>
		
			<h2 class="page-title"><?php _e('Not Found', 'shaan'); ?></h2>
			<p><?php _e('Sorry, but you are looking for something that is not here.', 'shaan'); ?></p>
			<?php get_search_form(); ?>
				
			<script type="text/javascript">
				// focus on search field after it has loaded
				document.getElementById('s') && document.getElementById('s').focus();
			</script>			
	
		<?php endif; ?>
		
		<?php comments_template(); ?>

	</div><!-- #content -->
	
		
	<?php get_sidebar(); ?>
	<?php get_footer(); ?>
