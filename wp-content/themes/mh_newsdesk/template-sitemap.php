<?php /* Template Name: Sitemap */ ?>
<?php get_header(); ?>
<?php mh_newsdesk_before_page_content(); ?>
<?php mh_newsdesk_page_title(); ?>
<div class="sitemap mh-section mh-group">
	<div class="mh-col mh-1-3">
		<h4 class="widget-title"><span><?php _e('Recent Articles', 'mh-newsdesk'); ?></span></h4>
		<ul class="sitemap-list widget-list"><?php
			$args = array('posts_per_page' => 10);
			$recent = new WP_query($args);
			while ($recent->have_posts()) : $recent->the_post(); ?>
				<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li><?php
			endwhile; wp_reset_postdata(); ?>
		</ul>
		<h4 class="widget-title"><span><?php _e('Pages', 'mh-newsdesk'); ?></span></h4>
		<ul class="sitemap-list widget-list"><?php
			$args = array('title_li' => '', 'post_status' => 'publish');
			wp_list_pages($args); ?>
		</ul>
	</div>
	<div class="mh-col mh-1-3">
		<h4 class="widget-title"><span><?php _e('Archives', 'mh-newsdesk'); ?></span></h4>
		<ul class="sitemap-list widget-list">
			<?php wp_get_archives('type=monthly&show_post_count=1'); ?>
		</ul>
	</div>
	<div class="mh-col mh-1-3">
		<h4 class="widget-title"><span><?php _e('Categories', 'mh-newsdesk'); ?></span></h4>
		<ul class="sitemap-list widget-list"><?php
			$args = array('title_li' => '', 'feed' => 'RSS', 'show_option_none' => __('No categories', 'mh-newsdesk'));
			wp_list_categories($args); ?>
		</ul>
	</div>
</div>
<?php get_footer(); ?>