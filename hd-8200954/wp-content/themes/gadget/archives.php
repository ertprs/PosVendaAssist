<?php
/*
Template Name: Archives
*/
?>
<?php get_header(); ?>

<div id="content">
  <h2 class="h2title">Archives</h2>
  <div class="entry">
    <h4>Monthly:</h4>
    <ul>
      <?php wp_get_archives('type=monthly'); ?>
    </ul>
    <h4>Subjects:</h4>
    <ul>
      <?php wp_list_categories(); ?>
    </ul>
    <h4>Posts:</h4>
    <ul>
      <?php wp_get_archives('type=postbypost&limit=50&format=custom&before=<li>&after=</li>'); ?>
    </ul>
  </div>
  <!--end: entry-->
</div>
<!--end: content-->
<?php include('page-sidebar.php'); ?>
<?php get_footer(); ?>
