<?php

/***** Register Widgets *****/

function mh_newsdesk_register_widgets() {
	register_widget('mh_newsdesk_custom_posts');
	register_widget('mh_newsdesk_custom_pages');
	register_widget('mh_newsdesk_posts_large');
	register_widget('mh_newsdesk_posts_grid');
	register_widget('mh_newsdesk_posts_list');
	register_widget('mh_newsdesk_recent_posts');
	register_widget('mh_newsdesk_youtube');
	register_widget('mh_newsdesk_facebook_page');
	register_widget('mh_newsdesk_authors');
	register_widget('mh_newsdesk_comments');
}
add_action('widgets_init', 'mh_newsdesk_register_widgets');

/***** Include Widgets *****/

require_once('widgets/mh-custom-posts.php');
require_once('widgets/mh-custom-pages.php');
require_once('widgets/mh-posts-large.php');
require_once('widgets/mh-posts-grid.php');
require_once('widgets/mh-posts-list.php');
require_once('widgets/mh-recent-posts.php');
require_once('widgets/mh-youtube.php');
require_once('widgets/mh-facebook-page.php');
require_once('widgets/mh-authors.php');
require_once('widgets/mh-comments.php');

?>