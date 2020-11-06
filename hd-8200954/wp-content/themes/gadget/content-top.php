
<div id="featured">
  <h1><a href="<?php echo get_category_link(get_theme_mod('featuredcat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('featuredcat')); ?></a> </h1>
  <div id="featuredcontent">
    <div class="jflow-content-slider">
      <div id="slides">
        <?php $recent = new WP_Query("cat=".get_theme_mod('featuredcat')."&showposts=".get_theme_mod('list')); while($recent->have_posts()) : $recent->the_post();?>
        <div class="slide-wrapper">
          <div class="slide-thumbnail">
            <?php tj_show_thumb('featuredthumbw', 'featuredthumbh'); ?>
          </div>
          <!--end: slide-thumbnail-->
          <div class="slide-details">
            <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
              <?php the_title();?>
              </a></h2>
            <div class="description">
              <?php the_content_limit('400'); ?>
            </div>
            <!--end: description-->
          </div>
          <!--end: slide-details-->
          <div class="clear"></div>
        </div>
        <!--end: slide-wrapper-->
        <?php endwhile; ?>
      </div>
      <!--end: slides-->
      <div id="myController"> <span class="jFlowPrev">&lt;&lt;</span>
        <?php $i = 1; $list = get_theme_mod('list'); while($i <= $list) : echo '<span class="jFlowControl">'; echo $i; echo '</span>'; $i++;?>
        <?php endwhile; ?>
        <span class="jFlowNext">&gt;&gt;</span> </div>
      <!--end: myController-->
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
  </div>
  <div class="clear"></div>
</div>
<!--end: featured-->
<div class="clear"></div>
