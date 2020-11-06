<div id="comments">
        <!-- Password protected Comments -->
<?php if ( post_password_required() ) : ?>
<p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'Grayandgold' ); ?></p>
		</div>
<?php return; endif; ?>
<?php if ( have_comments() ) : ?>
<h3 id="comments-title">
<?php printf( __( 'One Response to %2$s', '%1$s Responses to %2$s', get_comments_number(), 'Grayandgold' ), number_format_i18n( get_comments_number() ), '<em>' . get_the_title() . '</em>' ); ?></h3>
<!-- Count comments-->
<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>


<?php endif; ?>
<ol class="commentlist">
<?php

	wp_list_comments( array( 'callback' => 'Grayandgold_comment' ) );
?>
</ol>
<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
			<div class="navigation">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'Grayandgold' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'Grayandgold' ) ); ?></div>
			</div> 
<?php endif; ?>
<?php else : if ( ! comments_open() ) : ?>
<p class="nocomments"><?php _e( 'Comments are closed.', 'Grayandgold' ); ?></p>
<?php endif; ?>
<?php endif; ?>
<?php comment_form(); ?></div><!-- #comments -->
