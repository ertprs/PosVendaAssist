<?php
/*
Template Name: Full Width
*/
?>
<?php get_header(); ?>

<div id="fullcontent">
  <?php if (have_posts()) : ?>
  <?php while (have_posts()) : the_post(); ?>
  <h2 class="h2title">
    <?php the_title(); ?>
  </h2>
  <div class="entry">
    <?php the_content('Mais &raquo;'); ?>
  </div>
  <!--end: entry-->
  <div class="clear"></div>
  <?php edit_post_link('[ '.__('Edit', 'themejunkie').' ]', '', ''); ?>
  <?php endwhile; ?>
  <?php else : ?>
  <h2 class="h2title">Pagina nao encontrada</h2>
  <div class="entry"> Desculpe, mas você está procurando algo que não existe aqui...</div>
  <?php endif; ?>
</div>
<!--end: fullcontent-->
<?php get_footer(); ?>
