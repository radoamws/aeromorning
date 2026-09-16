<?php /* Template for displaying a "No posts found" message. */ ?>
<div class="entry-content clearfix"><?php
	if (is_search()) { ?>
		<p><?php _e('Sorry, but nothing matched your search terms. Please try again with different keywords.', 'mh-newsdesk'); ?></p><?php
	} else { ?>
		<p><?php _e('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'mh-newsdesk'); ?></p><?php
	}
	get_search_form(); ?>
</div>
<div class="not-found-widgets mh-section mh-group">
	<div class="mh-col mh-1-2 home-2"><?php
		$instance = array('title' => __('Popular Articles', 'mh-newsdesk'), 'postcount' => '5', 'order' => 'comment_count', 'excerpt' => 'first', 'sticky' => 1);
		$args = array('before_widget' => '<div class="sb-widget">', 'after_widget' => '</div>', 'before_title' => '<h4 class="widget-title"><span>', 'after_title' => '</span></h4>');
		the_widget('mh_newsdesk_custom_posts', $instance , $args); ?>
	</div>
	<div class="mh-col mh-1-2 home-3"><?php
		$instance = array('title' => __('Random Articles', 'mh-newsdesk'), 'postcount' => '5', 'order' => 'rand', 'excerpt' => 'first', 'sticky' => 1);
		$args = array('before_widget' => '<div class="sb-widget">', 'after_widget' => '</div>', 'before_title' => '<h4 class="widget-title"><span>', 'after_title' => '</span></h4>');
		the_widget('mh_newsdesk_custom_posts', $instance , $args); ?>
	</div>
</div>