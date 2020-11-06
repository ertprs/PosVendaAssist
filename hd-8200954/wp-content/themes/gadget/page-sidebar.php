<div id="sidebar">
  <?php include('ads/sidebar300x250.php'); ?>
  <div class="fullwidget">
    <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Page Full Width') ) : ?>
    <h3>
      <?php _e("Page Full Width", 'themejunkie'); ?>
    </h3>
    <div class="box">
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <?php endif; ?>
  </div>
  <!--end: fullwidget-->
  <div class="leftwidget">
    <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Page Left') ) : ?>
    <h3>
      <?php _e("Page Left", 'themejunkie'); ?>
    </h3>
    <div class="box">
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <?php endif; ?>
  </div>
  <!--end: leftwidget-->
  <div class="rightwidget">
    <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Page Right') ) : ?>
    <h3>
      <?php _e("Page Right", 'themejunkie'); ?>
    </h3>
    <div class="box">
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <?php endif; ?>
  </div>
  <!--end: rightwidget-->
</div>
<!--end: sidebar-->
