<?php $mh_newsdesk_options = mh_newsdesk_theme_options(); ?>
<?php if ($mh_newsdesk_options['comments_pages'] == 'enable') { ?>
<section>
	<?php comments_template(); ?>
</section>
<?php } ?>