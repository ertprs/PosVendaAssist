<?php get_header(); ?>

<div id="content">
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
  <h2 class="h2title">Página não encontrada.</h2>
  <?php endif; ?>
</div>
<!--end: content-->
<?php include('page-sidebar.php'); ?>
<?php get_footer(); ?>
