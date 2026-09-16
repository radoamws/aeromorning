<?php

/***** Theme Info Page *****/

if (!function_exists('mh_newsdesk_add_theme_info_page')) {
	function mh_newsdesk_add_theme_info_page() {
		add_theme_page(__('Welcome to MH Newsdesk', 'mh-newsdesk'), __('Theme Info', 'mh-newsdesk'), 'edit_theme_options', 'newsdesk', 'mh_newsdesk_display_theme_info_page');
	}
}
add_action('admin_menu', 'mh_newsdesk_add_theme_info_page');

if (!function_exists('mh_newsdesk_display_theme_info_page')) {
	function mh_newsdesk_display_theme_info_page() {
		$theme_data = wp_get_theme(); ?>
		<div class="theme-info-wrap">
			<h1><?php printf(__('Welcome to %1s %2s', 'mh-newsdesk'), $theme_data->Name, $theme_data->Version); ?></h1>
			<div class="theme-description"><?php echo $theme_data->Description; ?></div>
			<hr>
			<div class="theme-links clearfix">
				<p><strong><?php _e('Important Links:', 'mh-newsdesk'); ?></strong>
					<a href="<?php echo esc_url('http://www.mhthemes.com/themes/mh/newsdesk/'); ?>" target="_blank"><?php _e('Theme Info Page', 'mh-newsdesk'); ?></a>
					<a href="<?php echo esc_url('http://www.mhthemes.com/support/'); ?>" target="_blank"><?php _e('Support Center', 'mh-newsdesk'); ?></a>
					<a href="<?php echo esc_url('https://www.facebook.com/MHthemes'); ?>" target="_blank"><?php _e('Facebook', 'mh-newsdesk'); ?></a>
					<a href="<?php echo esc_url('https://twitter.com/MHthemes'); ?>" target="_blank"><?php _e('Twitter', 'mh-newsdesk'); ?></a>
					<a href="<?php echo esc_url('https://www.youtube.com/user/MHthemesEN'); ?>" target="_blank"><?php _e('YouTube', 'mh-newsdesk'); ?></a>
				</p>
			</div>
			<hr>
			<div id="getting-started">
				<h3><?php printf(__('Getting Started with %s', 'mh-newsdesk'), $theme_data->Name); ?></h3>
				<div class="row clearfix">
					<div class="col-1-2">
						<div class="section">
							<h4><?php _e('Theme Documentation', 'mh-newsdesk'); ?></h4>
							<p class="about"><?php printf(__('Need support to setup and configure %s? Please have a look at the instructions in the theme documentation or register at our support forums when you click on "Support" in your MH Themes account.', 'mh-newsdesk'), $theme_data->Name); ?></p>
							<p>
								<a href="<?php echo esc_url('http://www.mhthemes.com/support/documentation-mh-newsdesk/'); ?>" target="_blank" class="button button-secondary"><?php _e('Visit Documentation', 'mh-newsdesk'); ?></a>
							</p>
						</div>
						<div class="section">
							<h4><?php _e('Theme Options', 'mh-newsdesk'); ?></h4>
							<p class="about"><?php printf(__('%s WordPress theme supports the Theme Customizer for all theme settings. Click "Customize Theme" to open the Customizer now.',  'mh-newsdesk'), $theme_data->Name); ?></p>
							<p>
								<a href="<?php echo admin_url('customize.php'); ?>" class="button button-primary"><?php _e('Customize Theme', 'mh-newsdesk'); ?></a>
							</p>
						</div>
						<div class="section">
							<h4><?php _e('Upgrade to MH Themes Bundle', 'mh-newsdesk'); ?></h4>
							<p class="about"><?php _e('Do you want to have access to all Premium WordPress Themes by MH Themes including support and lifetime updates? You can upgrade to the MH Themes Bundle right away when you click on "Shop" in your MH Themes account.', 'mh-newsdesk'); ?></p>
							<p>
								<a href="<?php echo esc_url('http://www.mhthemes.com/members/login/'); ?>" target="_blank" class="button button-secondary"><?php _e('Login to MH Themes account', 'mh-newsdesk'); ?></a>
							</p>
						</div>
					</div>
					<div class="col-1-2">
						<img src="<?php echo get_template_directory_uri(); ?>/screenshot.png" alt="<?php _e('Theme Screenshot', 'mh-newsdesk'); ?>" />
					</div>
				</div>
			</div>
			<hr>
			<div id="theme-author">
				<p><?php printf(__('%1s WordPress theme is proudly brought to you by %2s.', 'mh-newsdesk'), $theme_data->Name, '<a target="_blank" href="http://www.mhthemes.com/" title="MH Themes">MH Themes</a>'); ?></p>
			</div>
		</div> <?php
	}
}

/***** Add Admin Notice *****/

if (!function_exists('mh_newsdesk_admin_notice')) {
	function mh_newsdesk_admin_notice() {
		global $pagenow;
		if (current_user_can('edit_theme_options') && $pagenow == 'index.php') {
    		$theme_data = wp_get_theme(); ?>
			<div class="updated">
        		<p><?php printf(__('Thanks for using %1s by MH Themes. To learn more about this theme, please %1s.', 'mh-newsdesk'), '<strong>' . $theme_data->Name . ' WordPress Theme</strong>', '<a href="' . admin_url('themes.php?page=newsdesk') . '"><strong>' . __('click here', 'mh-newsdesk') . '</strong></a>'); ?></p>
			</div><?php
		}
	}
}
add_action('admin_notices', 'mh_newsdesk_admin_notice');

?>