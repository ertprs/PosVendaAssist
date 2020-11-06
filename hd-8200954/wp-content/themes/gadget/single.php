<?php get_header(); ?>

<div id="content">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <p class="browse">
    <?php the_breadcrumb(); ?>
  </p>
  <?php the_title('<h2 class="h2title" style="padding-bottom:0; border:none;">', '</h2>'); ?>
  <p class="postmeta">
    <?php the_author_posts_link(); ?>
    /
    <?php the_time('F j, Y'); ?>
    /
    <?php comments_popup_link('Sem coment&aacute;rios', '1 Coment&aacute;rio', 
'% Comments', 'comments-link', 'Comments Off'); ?>
  </p>
  <div class="entry">
    <?php the_content('Saiba mais ...'); ?>
    <div class="clear"></div>
    <div class="tags">
      <?php the_tags('Tags: ', ', ', ' '); ?>
      <?php edit_post_link('Edit', '[ ', ' ]'); ?>
    </div>
  </div>
  <!--end: entry-->
  <div class="postnav">
    <div class="left">
      <?php previous_post_link('%link', '<div class="previouspost">&laquo; Anterior</div>%title', TRUE);  ?>
    </div>
    <div class="right">
      <?php next_post_link('%link', '<div class="nextpost">Pr&oacute;ximo &raquo;</div>%title', TRUE); ?>
    </div>
    <div class="clear"></div>
  </div>
  <!--end: postnav-->
  <?php comments_template(); ?>
  <?php endwhile; else: ?>
  <?php endif; ?>
</div>
<!--end: content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
