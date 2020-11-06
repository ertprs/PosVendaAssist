<?php //Retrieve Theme Options Data
global $options;
$options = get_option('p2h_theme_options'); 
?>

<?php get_header(); ?>

<div id="container">

	<div id="content" class="narrow">
	
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post();?>
		
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			
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

			
			<h1 class="post-title"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
			<p class="post-meta"><?php the_author_posts_link(); ?>  &diams;  <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_time('F j, Y') ?></a>  &diams;  <?php comments_popup_link( __('Leave Your Comment', 'shaan'), __( '1 Comment', 'shaan'), __('% Comments', 'shaan')); ?><?php if(is_sticky()) {?>  &diams;  <?php _e('Sticky Post','shaan');?><?php } ?></p>
			
			<!--Show Ads Below Post Title -->
			<?php if ( isset($options['posttop_adcode']) && ($options['posttop_adcode']!="") ){ ?>
			<div id="topad"><?php echo(stripslashes ($options['posttop_adcode']));?></div>
			<?php } ?>
			
			<?php the_content( __('<p><a class="read-more" href="'. get_permalink() . '">' . __( 'Read more &raquo;', 'shaan' ) . '</a></p>', 'shaan') ); ?>
			<?php wp_link_pages( __('before=<div class="page-link">Pages:&after=</div>', 'shaan')) ; ?>
			
			<!--Show Ads Below Post -->
			<?php if ( isset($options['postend_adcode']) && ($options['postend_adcode']!="") ){ ?>
			<div id="bottomad"><?php echo(stripslashes ($options['postend_adcode']));?></div>
			<?php } ?>
			
			<div id="post-info">
			<ul>
			<li>Posted in: <?php the_category(' &diams; ');?> <?php the_tags( __(' &diams; ', 'shaan'), ' &diams; ', ''); ?></li>
			<?php edit_post_link(__('Edit this post','shaan'), '<li>', '</li>'); ?>
			</ul>
			</div>

		<!--RELATED POSTS-->
		<?php
			$original_post = $post;
			$tags = wp_get_post_tags($post->ID);
			if ($tags) {
			  $first_tag = $tags[0]->term_id;
			  $args=array(
			    'tag__in' => array($first_tag),
			    'post__not_in' => array($post->ID),
			    'showposts'=>3,
			    'ignore_sticky_posts'=>1
			   );
			  $my_query = new WP_Query($args);
			  if( $my_query->have_posts() ) {
			      echo "<div id=\"relatedposts\">";
			      _e('<h3 id="related-title">Related Posts</h3>','shaan');
			      echo "<ul>";
			    while ($my_query->have_posts()) : $my_query->the_post(); ?>
		  
		   <li class="relatedthumb">
		   <!-- IF HAS THUMBNAIl DEFINED-->
			<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
			<!-- Post Thumbnail TimThumb-->
			<?php	if ( has_post_thumbnail() ) { ?>
			<?php // Show the thumbnail
			echo get_the_post_thumbnail( $post->ID, 'thumbnail');
			?>
			<?php } else { ?><!-- If post has no image, show default icon -->
			<img src="<?php echo get_template_directory_uri(); ?>/images/default.jpg" alt="<?php the_title(); ?>" />	
			<?php }	?> <!-- has thumbnail else close -->
			<!-- /Post Tumbnail -->
			</a>
			
			<span><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
			<?php the_title(); ?>
			</a></span>
			</li>
   		<?php endwhile;
        echo "</ul>";
		echo "</div>";
		}
		}
	$post = $original_post;
	wp_reset_query();
	?>

	<!--RELATED POSTS ENDS-->
	
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
