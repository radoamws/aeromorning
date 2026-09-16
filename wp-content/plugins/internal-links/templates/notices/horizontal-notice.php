<?php
if (!defined('ILJ_PATH')) die('No direct access allowed');

// Ensure all variables are set to avoid warnings
$button_meta   = isset($button_meta) ? $button_meta : '';
$image         = isset($image) ? $image : '';
$text          = isset($text) ? $text : '';
$button_link   = isset($button_link) ? $button_link : '';
$dismiss_time  = isset($dismiss_time) ? $dismiss_time : '';
$prefix        = isset($prefix) ? $prefix : '';
$title         = isset($title) ? $title : '';
$discount_code = isset($discount_code) ? $discount_code : '';
?>


<?php if (!empty($button_meta) && 'review' == $button_meta) : ?>

	<div class="ilj_add_ad_container updated">
	<div class="ilj_add_notice_container ilj_add_review_notice_container">
		<div class="ilj_add_advert_content_left_extra">
			<img src="<?php echo esc_url(ILJ_URL.'admin/img/'.$image); ?>" width="100" alt="<?php esc_attr_e('notice image', 'internal-links'); ?>" />
		</div>
		<div class="ilj_add_advert_content_right">
			<p>
				<?php echo wp_kses_post($text); ?>
			</p>
					
			<?php if (!empty($button_link)) {  ?>
				<div class="ilj_add_advert_button_container">
					<a class="button button-primary" href="<?php echo esc_url($button_link); ?>" target="_blank" onclick="jQuery(this).closest('.ilj_add_ad_container').slideUp(); jQuery.post(ajaxurl, {action: 'ilj_notice_dismiss', subaction: 'ilj_dismiss_review_notice', nonce: '<?php echo esc_js(wp_create_nonce('ilj-notice-ajax-nonce')); ?>', data: { notice: '<?php echo esc_js($dismiss_time); ?>', dismiss_forever: '1'}});">
						<?php esc_html_e('Ok, you deserve it', 'internal-links'); ?>
					</a>
					<div class="dashicons dashicons-calendar"></div>
					<a class="ilj_add_notice_link" href="#" onclick="jQuery(this).closest('.ilj_add_ad_container').slideUp(); jQuery.post(ajaxurl, {action: 'ilj_notice_dismiss', subaction: 'ilj_dismiss_review_notice', nonce: '<?php echo esc_js(wp_create_nonce('ilj-notice-ajax-nonce')); ?>', data: { notice: '<?php echo esc_js($dismiss_time); ?>', dismiss_forever: '0'}});">
						<?php esc_html_e('Maybe later', 'internal-links'); ?>
					</a>
					<div class="dashicons dashicons-no-alt"></div>
					<a class="ilj_add_notice_link" href="#" onclick="jQuery(this).closest('.ilj_add_ad_container').slideUp(); jQuery.post(ajaxurl, {action: 'ilj_notice_dismiss', subaction: 'ilj_dismiss_review_notice', nonce: '<?php echo esc_js(wp_create_nonce('ilj-notice-ajax-nonce')); ?>', data: { notice: '<?php echo esc_js($dismiss_time); ?>', dismiss_forever: '1'}});">
						<?php esc_html_e('Never', 'internal-links'); ?>
					</a>
				</div>
			<?php } ?>
		</div>
	</div>
	<div class="clear"></div>
</div>

<?php else : ?>

<div class="ilj_add_ad_container updated">
	<div class="ilj_add_notice_container">
		<div class="ilj_add_advert_content_left">
			<img src="<?php echo esc_url(ILJ_URL.'admin/img/'.$image); ?>" width="60" height="60" alt="<?php esc_attr_e('notice image', 'internal-links');?>" />
		</div>
		<div class="ilj_add_advert_content_right">
			<h3 class="ilj_add_advert_heading">
				<?php
					if (!empty($prefix)) echo esc_html($prefix).' ';
					echo esc_html($title);
				?>
				<div class="ilj_add_advert_dismiss">
				<?php if (!empty($dismiss_time)) {  ?>
					<a href="#" onclick="jQuery(this).closest('.ilj_add_ad_container').slideUp(); jQuery.post(ajaxurl, {action: 'ilj_notice_dismiss', subaction: '<?php echo esc_js($dismiss_time); ?>', nonce: '<?php echo esc_js(wp_create_nonce('ilj-notice-ajax-nonce')); ?>'});"><?php esc_html_e('Dismiss', 'internal-links'); ?></a>
				<?php } else { ?>
					<a href="#" onclick="jQuery(this).closest('.ilj_add_ad_container').slideUp();"><?php esc_html_e('Dismiss', 'internal-links'); ?></a>
				<?php } ?>
				</div>
			</h3>
			<p>
				<?php
					echo wp_kses_post($text);

					if (isset($discount_code)) echo ' <b>' . esc_html($discount_code) . '</b>';
					
					if (!empty($button_link) && !empty($button_meta)) {
				?>
				<a class="ilj_add_notice_link" href="<?php echo esc_url($button_link); ?>"><?php
						if ('updraftcentral' == $button_meta) {
							esc_html_e('Get UpdraftCentral', 'internal-links');
						} elseif ('updraftplus' == $button_meta) {
							esc_html_e('Get UpdraftPlus', 'internal-links');
						} elseif ('wp-optimize' == $button_meta) {
							esc_html_e('Get WP-Optimize', 'internal-links');
						} elseif ('internal-links' == $button_meta) {
							esc_html_e('Get Premium.', 'internal-links');
						} elseif ('signup' == $button_meta) {
							esc_html_e('Sign up', 'internal-links');
						} elseif ('go_there' == $button_meta) {
							esc_html_e('Go there', 'internal-links');
						} elseif ('learn_more' == $button_meta) {
							esc_html_e('Learn more', 'internal-links');
						} else {
							esc_html_e('Read more', 'internal-links');
						}
					?></a>
				<?php
					}
				?>
			</p>
		</div>
	</div>
	<div class="clear"></div>
</div>

<?php

endif;
