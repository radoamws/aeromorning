<?php

/***** Breadcrumbs *****/

if (!function_exists('mh_newsdesk_breadcrumbs')) {
	function mh_newsdesk_breadcrumbs() {
		if (!is_home() && !is_front_page()) {
			global $post;
			$mh_newsdesk_options = mh_newsdesk_theme_options();
			if ($mh_newsdesk_options['breadcrumbs'] == 'enable') {
				$delimiter = ' ';
				$before_title = '<span itemprop="title">';
				$before_text = '<span class="bc-text">';
				$before_link = isset($before_link) ? $before_link : "";
				$close_span = '</span>';
				echo '<nav class="breadcrumb"><a href="' . esc_url(home_url()) . '" itemprop="url" class="bc-home">' . $before_title . __('Home', 'mh-newsdesk') . $close_span . '</a>' . $close_span . $delimiter;
					if (is_single() && get_post_type() == 'post' && !is_attachment()) {
						$category = get_the_category();
						$category_id = $category[0]->cat_ID;
						$parent_id = $category[0]->category_parent;
						$parents = get_category_parents($parent_id, true, $delimiter);
						if ($parent_id != 0) {
							echo $parents;
						}
						echo $before_link . '<a href="' . esc_url(get_category_link($category_id)) . '" itemprop="url">' . $before_title . esc_attr($category[0]->name) . $close_span . '</a>' . $close_span;
					} elseif (is_attachment()) {
						echo $before_text . __('Media', 'mh-newsdesk') . $close_span;
					} elseif (is_page() && !$post->post_parent) {
						echo $before_text . get_the_title() . $close_span;
					} elseif (is_page() && $post->post_parent) {
						$parent_id  = $post->post_parent;
						$breadcrumbs = array();
						while ($parent_id) {
							$page = get_page($parent_id);
							$breadcrumbs[] = $before_link . '<a href="' . get_permalink($page->ID) . '" itemprop="url">' . $before_title . get_the_title($page->ID) . $close_span . '</a>' . $close_span;
							$parent_id  = $page->post_parent;
						}
						$breadcrumbs = array_reverse($breadcrumbs);
						foreach ($breadcrumbs as $bc);
						echo $bc;
					} elseif (is_category() || is_tax()) {
						$term = get_queried_object();
						$term_id = $term->term_id;
						if (is_category()) {
							$term_id = get_category($term_id);
							$parents = get_category($term_id->parent);
							if ($term_id->parent != 0) {
								echo (get_category_parents($parents, true, $delimiter));
							}
						} elseif (is_tax()) {
							$taxonomy = get_taxonomy($term->taxonomy);
							echo $taxonomy->labels->name . $delimiter;
						}
						echo $before_text . single_cat_title('', false) . $close_span;
					} elseif (is_tag()) {
						echo $before_text . single_term_title('', false) . $close_span;
					} elseif (is_author()) {
						global $author;
						$user_info = get_userdata($author);
						echo $before_text . __('Authors', 'mh-newsdesk') . $close_span;
					} elseif (is_404()) {
						echo $before_text . __('Page not found (404)', 'mh-newsdesk') . $close_span;
					} elseif (is_search()) {
						echo $before_text . __('Search', 'mh-newsdesk') . $close_span;
					} elseif (is_date()) {
						$arc_year = get_the_time('Y');
						$arc_month = get_the_time('F');
						$arc_month_num = get_the_time('m');
						$arc_day = get_the_time('d');
						$arc_day_full = get_the_time('l');
						$url_year = get_year_link($arc_year);
						$url_month = get_month_link($arc_year, $arc_month_num);
						if (is_day()) {
							echo $before_link . '<a href="' . $url_year . '" title="' . __('Yearly Archives', 'mh-newsdesk') . '" itemprop="url">' . $before_title . $arc_year . $close_span . '</a>' . $close_span . $delimiter;
							echo $before_link . '<a href="' . $url_month . '" title="' . __('Monthly Archives', 'mh-newsdesk') . '" itemprop="url">' . $before_title . $arc_month . $close_span . '</a>' . $close_span . $delimiter . $before_text . $arc_day . $close_span;
						} elseif (is_month()) {
							echo $before_link . '<a href="' . $url_year . '" title="' . __('Yearly Archives', 'mh-newsdesk') . '" itemprop="url">' . $before_title . $arc_year . $close_span . '</a>' . $close_span . $delimiter . $before_text . $arc_month . $close_span;
						} elseif (is_year()) {
							echo $before_text . $arc_year . $close_span;
						}
					} elseif (is_single() && get_post_type() != 'post' || is_post_type_archive(get_post_type())) {
						$post_type_data = get_post_type_object(get_post_type());
						$post_type_name = $post_type_data->labels->name;
						if (is_single() && get_post_type() != 'post') {
							$post_type_slug = $post_type_data->rewrite['slug'];
							$permalinks = get_option('permalink_structure');
							if ($permalinks == '') {
								echo $before_link . '<a href="' . esc_url(home_url()) . '?post_type=' . $post_type_slug . get_post_type() .'" itemprop="url">' . $before_title . $post_type_name . $close_span . '</a>' . $close_span;
							} else {
								echo $before_link . '<a href="' . esc_url(home_url()) . '/' . $post_type_slug . '/" itemprop="url">' . $before_title . $post_type_name . $close_span . '</a>' . $close_span;
							}
						} elseif (is_post_type_archive(get_post_type())) {
							echo $before_text . $post_type_name . $close_span;
						}
					}
				echo '</nav>' . "\n";
			}
		}
	}
}
add_action('mh_newsdesk_before_post_content', 'mh_newsdesk_breadcrumbs');
add_action('mh_newsdesk_before_page_content', 'mh_newsdesk_breadcrumbs');

?>