<article <?php post_class('content-lead'); ?>>
<?php mh_newsdesk_post_meta(); ?>
	<h3 class="content-lead-title"><a href="<?php echo get_permalink(); ?>" title="<?php the_title_attribute(); ?>" rel="bookmark"><?php the_title(); ?></a></h3>
	<div class="content-thumb content-lead-thumb"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php if (has_post_thumbnail()) { the_post_thumbnail('content-single'); } else { echo '<img src="' . get_template_directory_uri() . '/images/placeholder-content-single.jpg' . '" alt="No Picture" />'; } ?></a></div>
	<div class="content-lead-excerpt">
		<?php the_excerpt(); ?>
		<?php mh_newsdesk_more(); ?>
	</div>
</article>