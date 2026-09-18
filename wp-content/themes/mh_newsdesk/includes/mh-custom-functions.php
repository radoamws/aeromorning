<?php

/***** Logo/Sitename *****/

if (!function_exists('mh_newsdesk_logo')) {
	function mh_newsdesk_logo() {
		$header_img = get_header_image();
		$header_title = get_bloginfo('name');
		$header_desc = get_bloginfo('description');
		echo '<a href="' . esc_url(home_url('/')) . '" title="' . esc_attr($header_title) . '" rel="home">' . "\n";
		echo '<div class="logo-wrap" role="banner">' . "\n";
		if ($header_img) {
			echo '<img src="' . esc_url($header_img) . '" height="' . get_custom_header()->height . '" width="' . get_custom_header()->width . '" alt="' . esc_attr($header_title) . '" />' . "\n";
		}
		if (display_header_text()) {
			$text_color = get_header_textcolor();
			if ($text_color != get_theme_support('custom-header', 'default-text-color')) {
				echo '<style type="text/css" id="mh-header-css">';
					echo '.logo-title, .logo-tagline { color: #' . esc_attr($text_color) . '; }';
				echo '</style>' . "\n";
			}
			echo '<div class="logo">' . "\n";
			// Only the front page should have the site name as its single <h1>;
			// every other page already has its own <h1> (article title, page
			// title, archive title via mh_newsdesk_page_title()/entry-title),
			// so the logo drops to a <div>/<p> there to avoid two <h1>s per page.
			$logo_title_tag = is_front_page() ? 'h1' : 'div';
			$logo_desc_tag  = is_front_page() ? 'h2' : 'p';
			if ($header_title) {
				echo '<' . $logo_title_tag . ' class="logo-title">' . esc_attr($header_title) . '</' . $logo_title_tag . '>' . "\n";
			}
			if ($header_desc) {
				echo '<' . $logo_desc_tag . ' class="logo-tagline">' . esc_attr($header_desc) . '</' . $logo_desc_tag . '>' . "\n";
			}
			echo '</div>' . "\n";
		}
		echo '</div>' . "\n";
		echo '</a>' . "\n";
	}
}

/***** Page Title Output *****/

if (!function_exists('mh_newsdesk_page_title')) {
	function mh_newsdesk_page_title() {
		if (!is_front_page()) {
			echo '<h1 class="page-title">';
			if (is_archive()) {
				if (is_category() || is_tax()) {
					single_cat_title();
				} elseif (is_tag()) {
					single_tag_title();
				} elseif (is_author()) {
					global $author;
					$user_info = get_userdata($author);
					printf(_x('Articles by %s', 'post author', 'mh-newsdesk'), esc_attr($user_info->display_name));
				} elseif (is_day()) {
					echo get_the_date();
				} elseif (is_month()) {
					echo get_the_date('F Y');
				} elseif (is_year()) {
					echo get_the_date('Y');
				} elseif (is_post_type_archive()) {
					global $post;
					$post_type = get_post_type_object(get_post_type($post));
					echo $post_type->labels->name;
				} else {
					_e('Archives', 'mh-newsdesk');
				}
			} else {
				if (is_home()) {
					echo get_the_title(get_option('page_for_posts', true));
				} elseif (is_404()) {
					_e('Page not found (404)', 'mh-newsdesk');
				} elseif (is_search()) {
					printf(__('Search Results for %s', 'mh-newsdesk'), esc_attr(get_search_query()));
				} else {
					the_title();
				}
			}
			echo '</h1>' . "\n";
		}
	}
}

/***** Output Post Meta Data *****/

if (!function_exists('mh_newsdesk_post_meta')) {
	function mh_newsdesk_post_meta() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		$post_cat = !$mh_newsdesk_options['post_meta_cat'];
		$post_author = !$mh_newsdesk_options['post_meta_author'];
		$post_date = !$mh_newsdesk_options['post_meta_date'];
		if ($post_cat || $post_author || $post_date) {
			echo '<p class="entry-meta">' . "\n";
				if (has_category() && $post_cat && !is_single()) {
					echo '<span class="entry-meta-cats">' . get_the_category_list(', ', '') . '</span>' . "\n";
				}
				if ($post_author && is_single()) {
					echo '<span class="entry-meta-author vcard author">' . sprintf(_x('Posted By: %s', 'post author', 'mh-newsdesk'), '<a class="fn" href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a>') . '</span>' . "\n";
				}
				if ($post_date) {
					echo '<span class="entry-meta-date updated">' . get_the_date() . '</span>' . "\n";
				}
			echo '</p>' . "\n";
		}
	}
}

/***** Featured Image on Posts *****/

if (!function_exists('mh_newsdesk_featured_image')) {
	function mh_newsdesk_featured_image() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		global $page, $post;
		if (has_post_thumbnail() && $page == '1' && $mh_newsdesk_options['featured_image'] == 'enable') {
			$caption_text = get_post(get_post_thumbnail_id())->post_excerpt;
			echo "\n" . '<div class="entry-thumbnail">' . "\n";
				the_post_thumbnail('content-single');
				if ($caption_text) {
					echo '<span class="wp-caption-text">' . wp_kses_post($caption_text) . '</span>' . "\n";
				}
			echo '</div>' . "\n";
		}
	}
}

/***** Custom Excerpts *****/

if (!function_exists('mh_newsdesk_trim_excerpt')) {
	function mh_newsdesk_trim_excerpt($text = '') {
		$raw_excerpt = $text;
		if ('' == $text) {
			$mh_newsdesk_options = mh_newsdesk_theme_options();
			$text = get_the_content('');
			$text = strip_shortcodes($text);
			$text = apply_filters('the_content', $text);
			$text = str_replace(']]>', ']]&gt;', $text);
			$excerpt_length = apply_filters('excerpt_length', $mh_newsdesk_options['excerpt_length']);
			$excerpt_more = apply_filters('excerpt_more', '...');
			$text = wp_trim_words($text, $excerpt_length, $excerpt_more);
		}
		return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
	}
}
remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'mh_newsdesk_trim_excerpt');

/***** Pagination *****/

if (!function_exists('mh_newsdesk_pagination')) {
	function mh_newsdesk_pagination() {
		global $wp_query;
	    $big = 9999;
	    $paginate_links = paginate_links(array(
	    	'base' 		=> str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
	    	'format' 	=> '?paged=%#%',
	    	'current' 	=> max(1, get_query_var('paged')),
	    	'prev_next' => true,
	    	'prev_text' => __('&laquo;', 'mh-newsdesk'),
	    	'next_text' => __('&raquo;', 'mh-newsdesk'),
	    	'total' 	=> $wp_query->max_num_pages
	    ));
	    if ($paginate_links) {
	    	echo '<div class="pagination clearfix">';
				echo $paginate_links;
			echo '</div>';
		}
	}
}

/***** Pagination for paginated Posts *****/

if (!function_exists('mh_newsdesk_posts_pagination')) {
	function mh_newsdesk_posts_pagination($content) {
		if (is_singular() && is_main_query()) {
			$content .= wp_link_pages(array('before' => '<div class="pagination clear">', 'after' => '</div>', 'link_before' => '<span class="pagelink">', 'link_after' => '</span>', 'nextpagelink' => __('&raquo;', 'mh-newsdesk'), 'previouspagelink' => __('&laquo;', 'mh-newsdesk'), 'pagelink' => '%', 'echo' => 0));
		}
		return $content;
	}
}
add_filter('the_content', 'mh_newsdesk_posts_pagination', 1);

/***** Post / Image Navigation *****/

if (!function_exists('mh_newsdesk_postnav')) {
	function mh_newsdesk_postnav() {
		global $post;
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		if ($mh_newsdesk_options['post_nav'] == 'enable') {
			$parent_post = get_post($post->post_parent);
			$attachment = is_attachment();
			$previous = ($attachment) ? $parent_post : get_adjacent_post(false, '', true);
			$next = get_adjacent_post(false, '', false);

			if (!$next && !$previous)
			return;

			if ($attachment) {
				$attachments = get_children(array('post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => $parent_post->ID));
				$count = count($attachments);
			}
			echo '<nav class="post-nav-wrap" role="navigation">' . "\n";
				echo '<ul class="post-nav clearfix">' . "\n";
					if ($previous || $attachment) {
						echo '<li class="post-nav-prev">' . "\n";
							if ($attachment) {
								if ($count == 1) {
									$permalink = get_permalink($parent_post);
									echo '<a href="' . $permalink . '"><i class="fa fa-chevron-left"></i>' . __('Back to post', 'mh-newsdesk') . '</a>';
								} else {
									previous_image_link('%link', '<i class="fa fa-chevron-left"></i>' . __('Previous image', 'mh-newsdesk'));
								}
							} else {
								previous_post_link('%link', '<i class="fa fa-chevron-left"></i>' . __('Previous post', 'mh-newsdesk'));
							}
						echo '</li>' . "\n";
					}
					if ($next || $attachment) {
						echo '<li class="post-nav-next">' . "\n";
							if ($attachment) {
								next_image_link('%link', __('Next image', 'mh-newsdesk') . '<i class="fa fa-chevron-right"></i>');
							} else {
								next_post_link('%link', __('Next post', 'mh-newsdesk') . '<i class="fa fa-chevron-right"></i>');
							}
						echo '</li>' . "\n";
					}
				echo '</ul>' . "\n";
			echo '</nav>' . "\n";
		}
	}
}

/***** Custom Commentlist *****/

if (!function_exists('mh_newsdesk_comments')) {
	function mh_newsdesk_comments($comment, $args, $depth) {
		$GLOBALS['comment'] = $comment; ?>
		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
			<div id="comment-<?php comment_ID(); ?>">
				<div class="vcard meta">
					<?php echo get_avatar($comment->comment_author_email, 70); ?>
					<span class="fn"><?php echo get_comment_author_link() ?></span> |
					<a href="<?php echo esc_url(get_comment_link($comment->comment_ID)) ?>"><?php printf(__('%1$s at %2$s', 'mh-newsdesk'), get_comment_date(),  get_comment_time()) ?></a> |
					<?php if (comments_open() && $args['max_depth']!=$depth) { ?>
						<?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
					<?php } ?>
					<?php edit_comment_link(__('(Edit)', 'mh-newsdesk'),'  ','') ?>
				</div>
				<?php if ($comment->comment_approved == '0') : ?>
					<div class="comment-info"><?php _e('Your comment is awaiting moderation.', 'mh-newsdesk') ?></div>
				<?php endif; ?>
				<div class="comment-text">
					<?php comment_text() ?>
				</div>
			</div><?php
	}
}

/***** Custom Comment Fields *****/

if (!function_exists('mh_newsdesk_comment_fields')) {
	function mh_newsdesk_comment_fields($fields) {
		$commenter = wp_get_current_commenter();
		$req = get_option('require_name_email');
		$aria_req = ($req ? " aria-required='true'" : '');
		$fields =  array(
			'author'	=>	'<p class="comment-form-author"><label for="author">' . __('Name ', 'mh-newsdesk') . '</label>' . ($req ? '<span class="required">*</span>' : '') . '<br/><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' /></p>',
			'email' 	=>	'<p class="comment-form-email"><label for="email">' . __('Email ', 'mh-newsdesk') . '</label>' . ($req ? '<span class="required">*</span>' : '' ) . '<br/><input id="email" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' /></p>',
			'url' 		=>	'<p class="comment-form-url"><label for="url">' . __('Website', 'mh-newsdesk') . '</label><br/><input id="url" name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" /></p>'
		);
		return $fields;
	}
}
add_filter('comment_form_default_fields', 'mh_newsdesk_comment_fields');

/***** Read More Button *****/

if (!function_exists('mh_newsdesk_more')) {
	function mh_newsdesk_more() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		if ($mh_newsdesk_options['excerpt_more'] != '') { ?>
			<a class="button" href="<?php the_permalink(); ?>">
				<span><?php echo esc_attr($mh_newsdesk_options['excerpt_more']); ?></span>
			</a><?php
		}
	}
}

/***** Social Share Buttons *****/

if (!function_exists('mh_newsdesk_socialise')) {
	function mh_newsdesk_socialise() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		if ($mh_newsdesk_options['social_sharing'] == 'enable') {
			global $post; ?>
            <div class="mh-share-buttons mh-group">
            	<div class="mh-col mh-1-4 mh-facebook">
            		<a href="#" onclick="window.open('http://www.facebook.com/sharer.php?u=<?php the_permalink();?>&t=<?php the_title(); ?>', 'facebookShare', 'width=626,height=436'); return false;" title="<?php _e('Share on Facebook', 'mh-newsdesk'); ?>"><span class="mh-share-button"><i class="fa fa-facebook fa-2x"></i><?php _e('SHARE', 'mh-newsdesk'); ?></span></a>
            	</div>
            	<div class="mh-col mh-1-4 mh-twitter">
            		<a href="#" onclick="window.open('http://twitter.com/share?text=<?php the_title(); ?> -&url=<?php the_permalink() ?>', 'twitterShare', 'width=626,height=436'); return false;" title="<?php _e('Tweet This Post', 'mh-newsdesk'); ?>"><span class="mh-share-button"><i class="fa fa-twitter fa-2x"></i><?php _e('TWEET', 'mh-newsdesk'); ?></span></a>
            	</div>
            	<div class="mh-col mh-1-4 mh-pinterest">
            		<a href="#" onclick="window.open('http://pinterest.com/pin/create/button/?url=<?php the_permalink();?>&media=<?php $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'post-thumb'); echo $thumb['0']; ?>&description=<?php the_title(); ?>', 'pinterestShare', 'width=750,height=350'); return false;" title="<?php _e('Pin This Post', 'mh-newsdesk'); ?>"><span class="mh-share-button"><i class="fa fa-pinterest fa-2x"></i><?php _e('PIN', 'mh-newsdesk'); ?></span></a>
            	</div>
            	<div class="mh-col mh-1-4 mh-googleplus">
            		<a href="#" onclick="window.open('https://plusone.google.com/_/+1/confirm?hl=en-US&url=<?php the_permalink() ?>', 'googleShare', 'width=626,height=436'); return false;" title="<?php _e('Share on Google+', 'mh-newsdesk'); ?>" target="_blank"><span class="mh-share-button"><i class="fa fa-google-plus fa-2x"></i><?php _e('SHARE', 'mh-newsdesk'); ?></span></a>
            	</div>
            </div><?php
		}
	}
}

/***** Load Facebook Script (SDK) *****/

if (!function_exists('mh_newsdesk_facebook_sdk')) {
	function mh_newsdesk_facebook_sdk() {
		if (is_active_widget('', '', 'mh_newsdesk_facebook_page')) {
			global $locale; ?>
			<div id="fb-root"></div>
			<script>
				(function(d, s, id){
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) return;
					js = d.createElement(s); js.id = id;
					js.src = "//connect.facebook.net/<?php echo esc_attr($locale); ?>/sdk.js#xfbml=1&version=v2.3";
					fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));
			</script> <?php
		}
	}
}
add_action('wp_footer', 'mh_newsdesk_facebook_sdk');

/***** Add CSS classes to body tag *****/

if (!function_exists('mh_newsdesk_body_class')) {
	function mh_newsdesk_body_class($classes) {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		$classes[] = 'mh-' . $mh_newsdesk_options['sidebar'] . '-sb';
		return $classes;
	}
}
add_filter('body_class', 'mh_newsdesk_body_class');

/***** Add Favicon *****/

if (!function_exists('mh_newsdesk_favicon')) {
	function mh_newsdesk_favicon() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		if (isset($mh_newsdesk_options['favicon']) && $mh_newsdesk_options['favicon']) {
			echo '<link rel="shortcut icon" href="' . esc_url($mh_newsdesk_options['favicon']) . '">' . "\n";
		}
	}
}
add_action('wp_head', 'mh_newsdesk_favicon');

/***** Add CSS3 Media Queries Support for older versions of IE *****/

function mh_newsdesk_media_queries() {
	echo '<!--[if lt IE 9]>' . "\n";
	echo '<script src="' . get_template_directory_uri() . '/js/css3-mediaqueries.js"></script>' . "\n";
	echo '<![endif]-->' . "\n";
}
add_action('wp_head', 'mh_newsdesk_media_queries');

?>