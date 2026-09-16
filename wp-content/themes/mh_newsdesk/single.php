<?php $mh_newsdesk_options = mh_newsdesk_theme_options(); ?>
<?php get_header(); ?>
<!--<div class="mh-section mh-group">-->
	<div id="main-content" class="mh-content"><?php
		mh_newsdesk_before_post_content();
		if (have_posts()) :
			while (have_posts()) : the_post();
				get_template_part('content', 'single');
				mh_newsdesk_socialise();
				mh_newsdesk_postnav();
				if ($mh_newsdesk_options['author_box'] == 'enable') {
					get_template_part('template', 'authorbox');
				}
				if ($mh_newsdesk_options['related_content'] == 'enable') {
					get_template_part('content', 'related');
				}
			endwhile;
			comments_template();
		endif; ?>
	</div>
	<?php get_sidebar(); ?>
<!--</div>-->
<?php get_footer(); ?>