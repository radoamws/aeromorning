<?php /* Template for related posts based on tags. */
$tags = wp_get_post_tags($post->ID);
if ($tags) {
	$tag_ids = array();
	foreach ($tags as $tag) $tag_ids[] = $tag->term_id;
	$args = array('tag__in' => $tag_ids, 'post__not_in' => array($post->ID), 'posts_per_page' => 4, 'ignore_sticky_posts' => 1, 'orderby' => 'rand');
	$related = new wp_query($args);
	if ($related->have_posts()) { ?>
		<h4 class="widget-title related-content-title"><span><?php _e('Related Articles', 'mh-newsdesk'); ?></span></h4>
		<div class="related-content clearfix"><?php
			while ($related->have_posts()) : $related->the_post();
				get_template_part('content', 'grid');
			endwhile; ?>
		</div><?php
	}
	wp_reset_postdata();
}