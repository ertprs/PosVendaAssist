<?php

include("includes/theme-options.php");

# Sidebar
if (function_exists('register_sidebar'))
{
    register_sidebar(array(
		'name'			=> 'Home Full Width',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<h3>',
        'after_title'	=> '</h3><div class="box">',
    ));		
	
    register_sidebar(array(
		'name'			=> 'Home Left',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<h3>',
        'after_title'	=> '</h3><div class="box">',
    ));	
	
    register_sidebar(array(
		'name'			=> 'Home Right',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<h3>',
        'after_title'	=> '</h3><div class="box">',
    ));		

    register_sidebar(array(
		'name'			=> 'Page Full Width',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<h3>',
        'after_title'	=> '</h3><div class="box">',
    ));			
	
    register_sidebar(array(
		'name'			=> 'Page Left',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<h3>',
        'after_title'	=> '</h3><div class="box">',
    ));	
	
    register_sidebar(array(
		'name'			=> 'Page Right',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<h3>',
        'after_title'	=> '</h3><div class="box">',
    ));	

    register_sidebar(array(
		'name'			=> 'Footer',
        'before_widget'	=> '',
        'after_widget'	=> '</div>',
        'before_title'	=> '<div class="footerwidget left"><h3>',
        'after_title'	=> '</h3><div class="box"></div>',
    ));	
	
}

# Limit Post
function the_content_limit($max_char, $more_link_text = '', $stripteaser = 0, $more_file = '') {
    $content = get_the_content($more_link_text, $stripteaser, $more_file);
    $content = apply_filters('the_content', $content);
    $content = str_replace(']]>', ']]&gt;', $content);
    $content = strip_tags($content);

   if (strlen($_GET['p']) > 0) {
      echo "";
      echo $content;
      echo "&nbsp;<a href='";
      the_permalink();
      echo "'>"."Saiba mais &rarr;</a>";
      echo "";
   }
   else if ((strlen($content)>$max_char) && ($espacio = strpos($content, " ", $max_char ))) {
        $content = substr($content, 0, $espacio);
        $content = $content;
        echo "";
        echo $content;
        echo "...";
        echo "&nbsp;<a href='";
        the_permalink();
        echo "'>"."</a>";
        echo "";
   }
   else {
      echo "";
      echo $content;
      echo "&nbsp;<a href='";
      the_permalink();
      echo "'>"."Saiba mais &rarr;</a>";
      echo "";
   }
}

# Turn a category ID to a Name
function cat_id_to_name($id) {
	foreach((array)(get_categories()) as $category) {
    	if ($id == $category->cat_ID) { return $category->cat_name; break; }
	}
}

// Get Image Attachments
function tj_get_image($postid=0, $size='full') {
	if ($postid<1) 
	$postid = get_the_ID();
	$thumb = get_post_meta($postid, "thumb", TRUE); // Declare the custom field for the image
	if ($thumb != null or $thumb != '') {
		echo get_image_path($thumb); 
	}
	elseif ($images = get_children(array(
		'post_parent' => $postid,
		'post_type' => 'attachment',
		'numberposts' => '1',
		'post_mime_type' => 'image', )))
		foreach($images as $image) {
			$thumbnail=wp_get_attachment_image_src($image->ID, $size);
			?>
	<?php echo get_image_path($thumbnail[0]); ?>
	<?php }
		else {
		$theme_name = strtolower(get_current_theme());
#        echo get_image_path('wp-content/themes/'.$theme_name.'/images/image-pending.gif');
        echo get_image_path('/site-wp/wp-content/uploads/2011/06/icone-transparente.png');
	}
}

function get_image_path($thumbnail='') {
	global $blog_id;
	if (isset($blog_id) && $blog_id > 0) {
		$imagePath = explode('/files/', $thumbnail);
		if (isset($imagePath[1])) {
			$thumbnail = '/blogs.dir/' . $blog_id . '/files/' . $imagePath[1];
		}
	}
	
	return $thumbnail;
}

// Show Post Thumbnails
function tj_show_thumb($width = 100, $height = 100) {
?>
<a href="<?php the_permalink() ?>" rel="bookmark"><img class="thumb" src="<?php tj_get_image(); ?>" width="100"><imgXX class="thumb" src="<?php bloginfo('template_directory'); ?>/includes/timthumb.php?src=<?php tj_get_image(); ?>&amp;h=<?php echo get_theme_mod($height); ?>&amp;w=<?php echo get_theme_mod($width); ?>&amp;zc=1" alt="<?php the_title(); ?>" /></a>
<?php
}
# Breadcrumb
function the_breadcrumb() {
	if (!is_home()) {
		echo '<a href="';
		echo get_option('home');
		echo '">';
		echo "Home";
		echo "</a> &raquo; ";
		if (is_category() || is_single()) {
			single_cat_title();
			if (is_single()) {
			the_category(', ');
				echo " &raquo; ";
				the_title();
			}
		} elseif (is_page()) {
			echo the_title();
		}
		  elseif (is_tag()) {
			echo 'Posts tagged with "'; 
			single_tag_title();
			echo '"'; }
		elseif (is_day()) {echo "Archive for "; the_time(' F jS, Y');}
		elseif (is_month()) {echo "Archive for "; the_time(' F, Y');}
		elseif (is_year()) {echo "Archive for "; the_time(' Y');}
		elseif (is_author()) {echo "Author Archive";}
		elseif (isset($_GET['paged']) && !empty($_GET['paged'])) {echo "Blog Archives";}
		elseif (is_search()) {echo "Search Results";}
	}
}
?>