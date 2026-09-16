<?php /* Comments Template */
if (post_password_required()) {
	return;
}
$comments_by_type = separate_comments($comments);
$comment_count = count($comments_by_type['comment']);
if (have_comments()) {
	if (!empty($comments_by_type['comment'])) { ?>
		<div class="comments-wrap">
			<h4 class="comment-section-title"><?php printf(_n('<span class="comment-count">1 Comment</span> <span class="comment-count-more">on "%2$s"</span>', '<span class="comment-count">%1$s Comments</span> <span class="comment-count-more">on "%2$s"</span>', $comment_count, 'mh-newsdesk'), number_format_i18n($comment_count), get_the_title()); ?></h4>
			<ol class="commentlist">
				<?php echo wp_list_comments('callback=mh_newsdesk_comments&type=comment'); ?>
			</ol>
		</div><?php
	}
	if (get_comments_number() > get_option('comments_per_page')) { ?>
		<div class="pagination comments-pagination">
			<?php paginate_comments_links(array('prev_text' => __('&laquo;', 'mh-newsdesk'), 'next_text' => __('&raquo;', 'mh-newsdesk'))); ?>
		</div><?php
	}
	if (!empty($comments_by_type['pings'])) {
		$pings = $comments_by_type['pings'];
		$ping_count = count($comments_by_type['pings']); ?>
		<div class="pingback-wrap">
			<h4 class="comment-section-title"><?php printf(__('<span class="comment-count">%s</span> <span class="comment-count-more">Trackbacks & Pingbacks</span>', 'mh-newsdesk'), $ping_count); ?></h4>
			<ol class="pinglist">
        		<?php foreach ($pings as $ping) { ?>
					<li class="pings"><i class="fa fa-link"></i><?php echo get_comment_author_link($ping); ?></li>
        		<?php } ?>
        	</ol>
		</div><?php
	}
	if (!comments_open()) { ?>
		<p class="no-comments"><?php _e('Comments are closed.', 'mh-newsdesk'); ?></p><?php
	}
} else {
	if (comments_open()) {
		echo '<div class="comments-wrap">' . "\n";
			echo '<h4 class="comment-section-title">' . sprintf(__('<span class="comment-count">Be the first to comment</span> <span class="comment-count-more">on "%s"</span>', 'mh-newsdesk'), get_the_title()) . '</h4>' . "\n";
		echo '</div>' . "\n";
	}
}
if (comments_open()) {
	$custom_args = array(
    	'title_reply' => __('Leave a comment', 'mh-newsdesk'),
		'comment_notes_before' => '<p class="comment-notes">' . __('Your email address will not be published.', 'mh-newsdesk') . '</p>',
		'comment_notes_after'  => '',
		'comment_field' => '<p class="comment-form-comment"><label for="comment">' . __('Comment', 'mh-newsdesk') . '</label><br/><textarea id="comment" name="comment" cols="45" rows="5" aria-required="true"></textarea></p>');
	comment_form($custom_args);
}