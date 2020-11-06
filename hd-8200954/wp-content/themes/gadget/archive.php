<?php get_header(); ?>

<div id="content">
  <?php if (have_posts()) : ?>
  <p class="browse">
    <?php the_breadcrumb(); ?>
  </p>
  <?php while (have_posts()) : the_post(); ?>
  <div class="archive">
    <h2 class="h2title"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
      <?php the_title(); ?>
      </a> </h2>
    <div class="left"> <a href="<?php the_permalink() ?>" rel="bookmark"><img class="thumb" src="<?php tj_get_image(); ?>" width="100"<imgXX src="<?php bloginfo('template_directory'); ?>/includes/timthumb.php?src=<?php tj_get_image($post->ID, 'full'); ?>&amp;h=100&amp;w=100&amp;zc=1" alt="<?php the_title(); ?>" /> </a> </div>
    <div class="archiveright">
      <?php the_content_limit(400,''); ?>
    </div>
    <div class="clear"></div>
    <div class="tags">
      <?php the_tags('Tags: ', ', ', ' '); ?>
      <?php edit_post_link('Edit', '[ ', ' ]'); ?>
    </div>
    <div class="clear"></div>
  </div>
  <?php endwhile; ?>
  <div class="clear"></div>
  <div class="navigation">
    <div class="left">
      <?php previous_posts_link('&laquo; P&aacute;gina Anterior', 0); ?>
    </div>
    <div class="right">
      <?php next_posts_link('Pr&oacute;xima P&aacute;gina &raquo;', 0); ?>
    </div>
  </div>
  <?php else : ?>
  <?php endif; ?>
</div>
<!--end: content-->
<?php include('page-sidebar.php'); ?>
<?php get_footer(); ?>
