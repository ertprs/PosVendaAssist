<?php
/**
 * The template for displaying Comments.
 */
?>

<div id="comments">
<?php if ( post_password_required() ) : ?>
				<p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'shaan' ); ?></p>
			</div><!-- #comments -->
<?php
		/* Stop the rest of comments.php from being processed,
		 * but don't kill the script entirely -- we still have
		 * to fully load the template.
		 */
		return;
	endif;
?>

<?php
	// You can start editing here -- including this comment!
?>

<?php if ( have_comments() ) : ?>
	<h3 id="comments-title"><?php comments_number( __('No Comments', 'shaan'), __( '1 Comment', 'shaan'), __('% Comments', 'shaan') );?></h3>

			<ol class="commentlist">
				<?php wp_list_comments( array( 'callback' => 'p2h_comment', 'type' => 'comment' ) ); ?>
			</ol>

	<?php $comments_by_type = &separate_comments($comments); ?>
	<?php if ( !empty($comments_by_type['pings']) ) : ?>
		<h4><?php _e('Trackbacks','shaan');?></h4>
				<ol class="pingslist">
					<?php wp_list_comments( array( 'callback' => 'p2h_comment', 'type' => 'pings' ) ); ?>
				</ol>
	<?php endif; ?>
			
	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
				<div class="navigation">
					<div class="nav-previous"><?php previous_comments_link( __( '&laquo; Older Comments', 'shaan' ) ); ?></div>
					<div class="nav-next"><?php next_comments_link( __( 'Newer Comments &raquo;', 'shaan' ) ); ?></div>
				</div><!-- .navigation -->
	<?php endif; // check for comment navigation ?>

			
<?php else : // or, if we don't have comments:

	/* If there are no comments and comments are closed,
	 * let's leave a little note, shall we?
	 */
	if ( ! comments_open() ) :
?>
	<p class="nocomments"><?php _e( 'Comments are closed.', 'shaan' ); ?></p>
<?php endif; // end ! comments_open() ?>

<?php endif; // end have_comments() ?>

<?php 

$fields =  array(
	'author' => '<div class="comment-form-info"><p class="comment-form-author">' . '<label for="author">' . __( 'Name' ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
	            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" /></p>',
	'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email' ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
	            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"/></p>',
	'url'    => '<p class="comment-form-url"><label for="url">' . __( 'Website' ) . '</label>' .
	            '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p></div>',
);

comment_form(
array(
	'fields'               => apply_filters( 'comment_form_default_fields', $fields ),
	'comment_field'        => '<div class="comment-form-msg"><p class="comment-form-comment"><label for="comment">' . __( 'Comment', 'shaan' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p></div>',
	'comment_notes_before' => '<p class="comment-notes">' . __( 'Your email will not be published or shared.','shaan' ) . ( $req ? __( ' Required fields are marked <span class="required">*</span>', 'shaan' ) : '' ) . '</p>',
	'comment_notes_after'  => '<p class="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s', 'shaan' ), ' <code>' . allowed_tags() . '</code>' ) . '</p>',
	'id_submit'            => 'submit',
	'title_reply'          => __( 'Leave Your Comment', 'shaan' ),
	'cancel_reply_link'    => __( '(Cancel Reply)', 'shaan' ),
	'label_submit'         => __( 'Submit', 'shaan'),
)
); 

?>

</div><!-- #comments -->
