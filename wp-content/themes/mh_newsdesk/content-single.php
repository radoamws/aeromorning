<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header clearfix">
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<?php the_tags('<div class="entry-tags clearfix"><span>' . __('TOPICS:', 'mh-newsdesk') . '</span>','','</div>'); ?>
	</header><?php
	mh_newsdesk_featured_image();
	if (is_active_sidebar('post-ad-1')) { ?>
		<div class="advertisement">
			<?php dynamic_sidebar('post-ad-1'); ?>
		</div><?php
	}
	mh_newsdesk_post_meta(); ?>
	<div class="entry-content clearfix">
		<?php the_content(); ?>
	</div><?php
	if (is_active_sidebar('post-ad-2')) { ?>
		<div class="advertisement">
			<?php dynamic_sidebar('post-ad-2'); ?>
		</div><?php
	} ?>
</article>