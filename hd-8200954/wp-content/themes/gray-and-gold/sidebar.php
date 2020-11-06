<div class="right" id="sidebar">
    <div id="sidebar_content">

<ul>
	<?php if ( !dynamic_sidebar() ) : ?>
<li>
    <h2>Archives</h2>
    <ul>
        <?php wp_get_archives('type=monthly'); ?>
    </ul>
</li>
<?php wp_list_categories('show_count=1&title_li=<h2>Categories</h2>'); ?>
<?php wp_list_bookmarks(); ?>
<li>
    <h2>Meta</h2>
    <ul>
        <?php wp_register(); ?>
        <li><?php wp_loginout(); ?></li>

        <?php wp_meta(); ?>
    </ul>
</li>
<?php endif; ?>
	</ul>
    </div>
</div>
<div class="clearer"></div>
			</div>
		</div>
	</div>
</div>