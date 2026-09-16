<?php /* Template for displaying posts page and category archives */
$counter = 1;
$max_posts = $wp_query->post_count;
while (have_posts()) : the_post();
	if ($counter == 1) :
		get_template_part('content', 'lead'); ?>
		<hr class="mh-separator"><?php
	endif;
	if ($counter == 1 && $max_posts > 1) : ?>
		<div class="archive-grid mh-section mh-group"><?php
	endif;
	if ($counter > 1 && $counter <= 9) :
		get_template_part('content', 'grid');
	endif;
	if ($counter == 5 && $max_posts > 5) : ?>
		</div>
		<hr class="mh-separator hidden-sm">
		<div class="archive-grid mh-section mh-group"><?php
	endif;
	if ($counter == 10) : ?>
		</div>
		<hr class="mh-separator hidden-sm">
		<div class="archive-list mh-section mh-group"><?php
	endif;
	if ($counter >= 10) :
		get_template_part('content');
	endif;
$counter++;
endwhile;
if ($max_posts > 1 && $max_posts < 10) : ?>
	</div>
	<hr class="mh-separator hidden-sm"><?php
endif;
if ($max_posts >= 10) : ?>
	</div><?php
endif; ?>