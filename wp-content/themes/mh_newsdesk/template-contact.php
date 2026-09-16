<?php /* Template Name: Contact */ ?>
<?php get_header(); ?>
<div class="mh-section mh-group">
	<div id="main-content" class="mh-content contact-page"><?php
		mh_newsdesk_before_page_content();
		if (have_posts()) :
			while (have_posts()) : the_post();
				get_template_part('content', 'page');
			endwhile;
		endif; ?>
	</div>
	<aside class="mh-sidebar">
		<?php dynamic_sidebar('contact'); ?>
	</aside>
</div>
<?php get_footer(); ?>