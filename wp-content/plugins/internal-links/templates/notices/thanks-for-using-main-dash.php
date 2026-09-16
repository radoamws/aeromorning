<?php
if (!defined('ILJ_PATH')) die('No direct access allowed');
?>

<div id="ilj-dashnotice" class="updated">
	<div style="float: right;"><a href="#" onclick="jQuery('#ilj-dashnotice').slideUp(); jQuery.post(ajaxurl, {action: 'ilj_notice_dismiss', subaction: 'ilj_dismiss_dash_notice_until', nonce: '<?php echo esc_js(wp_create_nonce('ilj-notice-ajax-nonce')); ?>'});"><?php /* translators: %1s represents the notice dismiss 'month' value. */ printf(esc_html__('Dismiss (for %s months)', 'internal-links'), 12); ?></a></div>
	<h3><?php esc_html_e('Thank you for installing Internal Link Juicer.', 'internal-links'); ?></h3>	

	<a href="https://www.internallinkjuicer.com/" target="_blank"><img id="ilj-notice-logo" alt="<?php esc_html_e('Internal Link Juicer', 'internal-links'); ?>" title="<?php esc_html_e('Internal Link Juicer', 'internal-links'); ?>" src="<?php echo esc_url(ILJ_URL . 'admin/img/plugin-logos/internal-link-juicer-logo-sm.png'); ?>"></a>
	<div id="aiowps-dashnotice_wrapper" style="max-width: 800px;">
		<p>
			<?php
				esc_html_e('Automate the building of internal links on your WordPress website.', 'internal-links') . ' ' . esc_html_e('Save time and boost SEO.', 'internal-links') . ' ' . esc_html_e('You don’t need to be an SEO expert to use this plugin.', 'internal-links');
				echo '&nbsp;';
				esc_html_e('Get more rated plugins below:', 'internal-links');
			?>
		</p>
		<ul>
			<li>
				<a href="https://aiosplugin.com/" target="_blank"><strong><?php esc_html_e('All-In-One Security (AIOS)', 'internal-links'); ?>:</strong></a>
				<?php
					esc_html_e('Still on the fence?', 'internal-links');
					echo '&nbsp;';
					esc_html_e('Secure your WordPress website with AIOS.', 'internal-links');
					echo '&nbsp;';
					esc_html_e('Comprehensive, cost-effective, 5* rated and easy to use.', 'internal-links');
				?>
			</li>
			<li>
				<a href="https://getwpo.com/buy/" target="_blank"><strong><?php esc_html_e('WP-Optimize', 'internal-links'); ?>:</strong></a>
				<?php
					esc_html_e('Speed up and optimize your WordPress website.', 'internal-links');
					echo '&nbsp;';
					esc_html_e('Cache your site, clean the database and compress images.', 'internal-links');
				?>
			</li>
			<li>
				<a href="https://teamupdraft.com/updraftplus/" target="_blank"><strong><?php esc_html_e('UpdraftPlus', 'internal-links'); ?>:</strong></a>
				<?php
					esc_html_e('Back up your website with the world’s leading backup and migration plugin.', 'internal-links');
					echo '&nbsp;';
					esc_html_e('Actively installed on more than 3 million WordPress websites.', 'internal-links');
				?>
			</li>
			<li>
				<a href="https://wpgetapi.com" target="_blank"><strong><?php esc_html_e('WPGetAPI', 'internal-links'); ?>:</strong></a>
				<?php
					esc_html_e('Connect WordPress to external APIs, without code.', 'internal-links');
				?>
			</li>
			<li>
				<a href="https://wpovernight.com/" target="_blank"><strong><?php esc_html_e('WP Overnight', 'internal-links'); ?>:</strong></a>
				<?php
					esc_html_e('Quality add-ons for WooCommerce.', 'internal-links');
					echo '&nbsp;';
					esc_html_e('Designed to optimize your store, enhance user experience and increase revenue.', 'internal-links');
				?>
			</li>
		</ul>
	</div>
	<p>
		<strong><?php esc_html_e('More quality plugins', 'internal-links'); ?>: </strong><a href="https://www.simbahosting.co.uk/s3/shop/" target="_blank" ><?php esc_html_e('Premium WooCommerce plugins', 'internal-links'); ?></a>;
	</p>
	<div style="float: right;"><a href="#" onclick="jQuery('#ilj-dashnotice').slideUp(); jQuery.post(ajaxurl, {action: 'ilj_notice_dismiss', subaction: 'ilj_dismiss_dash_notice_until', nonce: '<?php echo esc_js(wp_create_nonce('ilj-notice-ajax-nonce')); ?>'});"><?php /* translators: %1s represents the notice dismiss 'month' value. */ printf(esc_html__('Dismiss (for %s months)', 'internal-links'), 12); ?></a></div>
	<p>&nbsp;</p>
</div>
