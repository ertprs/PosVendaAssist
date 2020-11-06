<?php


get_header(); ?>

				<div class="left" id="main">
					<div id="main_content">

			<h2 class="center"><?php _e( 'Error 404 - Not Found', 'Grayandgold') ?></h2>
            <p><?php _e( 'Apologies, but we were unable to find what you were looking for. Perhaps searching will help.', 'Grayandgold') ?></p>
            
            <?php get_search_form(); ?>
		</div>
	</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>