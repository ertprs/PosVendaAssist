<div id="leftcol">
  <?php if (get_theme_mod('box1') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box1cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box1cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box1cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxleftthumbw', 'boxleftthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box1cat')."&offset=1&showposts=".get_theme_mod('list1')); while($recent->have_posts()) : $recent->the_post();?>
        <li><a href="<?php the_permalink(); ?>" rel="bookmark">
          <?php the_title(); ?>
          </a></li>
        <?php endwhile; ?>
      </ul>
      <div class="clear"></div>
    </div>
    <!--postboxcontent-->
  </div>
  <!--end: postbox-->
  <?php } else { ?>
  <?php } ?>
  <?php if (get_theme_mod('box2') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box2cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box2cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box2cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxleftthumbw', 'boxleftthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box2cat')."&offset=1&showposts=".get_theme_mod('list1')); while($recent->have_posts()) : $recent->the_post();?>
        <li><a href="<?php the_permalink(); ?>" rel="bookmark">
          <?php the_title(); ?>
          </a></li>
        <?php endwhile; ?>
      </ul>
      <div class="clear"></div>
    </div>
    <!--postboxcontent-->
  </div>
  <!--end: postbox-->
  <?php } else { ?>
  <?php } ?>
  <?php if (get_theme_mod('box3') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box3cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box3cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box3cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxleftthumbw', 'boxleftthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box3cat')."&offset=1&showposts=".get_theme_mod('list1')); while($recent->have_posts()) : $recent->the_post();?>
        <li><a href="<?php the_permalink(); ?>" rel="bookmark">
          <?php the_title(); ?>
          </a></li>
        <?php endwhile; ?>
      </ul>
      <div class="clear"></div>
    </div>
    <!--postboxcontent-->
  </div>
  <!--end: postbox-->
  <?php } else { ?>
  <?php } ?>
  <?php if (get_theme_mod('box4') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box4cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box4cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box4cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxleftthumbw', 'boxleftthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box4cat')."&offset=1&showposts=".get_theme_mod('list1')); while($recent->have_posts()) : $recent->the_post();?>
        <li><a href="<?php the_permalink(); ?>" rel="bookmark">
          <?php the_title(); ?>
          </a></li>
        <?php endwhile; ?>
      </ul>
      <div class="clear"></div>
    </div>
    <!--postboxcontent-->
  </div>
  <!--end: postbox-->
  <?php } else { ?>
  <?php } ?>
  <div class="clear"></div>
</div>
<!--end: leftcol-->
