<div id="rightcol">
  <?php if (get_theme_mod('box5') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box5cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box5cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box5cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxrightthumbw', 'boxrightthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box5cat')."&offset=1&showposts=".get_theme_mod('list2')); while($recent->have_posts()) : $recent->the_post();?>
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
  <?php if (get_theme_mod('box6') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box6cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box6cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box6cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxrightthumbw', 'boxrightthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box6cat')."&offset=1&showposts=".get_theme_mod('list2')); while($recent->have_posts()) : $recent->the_post();?>
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
  <?php if (get_theme_mod('box7') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box7cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box7cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box7cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxrightthumbw', 'boxrightthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box7cat')."&offset=1&showposts=".get_theme_mod('list2')); while($recent->have_posts()) : $recent->the_post();?>
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
  <?php if (get_theme_mod('box8') == 'Enable') { ?>
  <div class="postbox">
    <?php $recent = new WP_Query("cat=".get_theme_mod('box8cat')."&showposts=1"); while($recent->have_posts()) : $recent->the_post();?>
    <h1><a href="<?php echo get_category_link(get_theme_mod('box8cat')); ?>" rel="bookmark"><?php echo cat_id_to_name(get_theme_mod('box8cat')); ?></a></h1>
    <div class="postboxcontent">
      <h2><a href="<?php the_permalink(); ?>" rel="bookmark">
        <?php the_title(); ?>
        </a></h2>
      <div class="midthumb">
        <?php tj_show_thumb('boxrightthumbw', 'boxrightthumbh'); ?>
      </div>
      <?php the_content_limit('110'); ?>
      <div class="clear"></div>
      <?php endwhile; ?>
      <div class="more">
        <?php _e('Mais', 'themejunkie'); ?>
        &raquo;</div>
      <ul>
        <?php $recent = new WP_Query("cat=".get_theme_mod('box8cat')."&offset=1&showposts=".get_theme_mod('list2')); while($recent->have_posts()) : $recent->the_post();?>
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
<!--end: rightcol-->
<div class="clear"></div>
