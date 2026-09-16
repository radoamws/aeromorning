<?php

namespace ILJ\Helper;

use ILJ\Core\IndexBuilder;
use ILJ\Core\Options;
use ILJ\Core\Options\OptionInterface;
use ILJ\Database\Keywords;

/**
 * Post toolset
 *
 * Methods for handling POST requests
 *
 * @package ILJ\Helper
 *
 * @since 1.1.3
 */
class Post {
	public static function option_actions() {
		if (!check_admin_referer(Options::KEY) || !current_user_can('manage_options')) {
			return false;
		}

		$url = wp_get_referer();

		if (!$url) {
			$url = admin_url();
		}

		if (filter_has_var(INPUT_POST, 'ilj-reset-options') && filter_has_var(INPUT_POST, 'section') && filter_has_var(INPUT_POST, 'action') && isset($_POST['action']) && Options::KEY === $_POST['action']) {
			self::resetOptionsAction();
		}

		if (filter_has_var(INPUT_POST, 'ilj-reset-keywords')) {
			self::reset_all_keywords();
		}

		wp_safe_redirect(esc_url_raw($url));
		exit;
	}
	/**
	 * Handles the process of resetting options from a given section to default.
	 *
	 * @since  1.1.3
	 * @return bool
	 */
	public static function resetOptionsAction() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is handled in the caller method.
		$section = isset($_POST['section']) ? Options::getSection(sanitize_text_field(wp_unslash($_POST['section']))) : array();

		if ($section) {
			foreach ($section['options'] as $option) {
				if (!$option instanceof OptionInterface) {
					continue;
				}

				delete_option($option::getKey());
			}
		}

		Options::setOptionsDefault();

		do_action(IndexBuilder::ILJ_INITIATE_BATCH_REBUILD);
	}

	/**
	 * Handles the process of deleting all keywords set in ILJ.
	 *
	 * @since  2.1.2
	 * @return bool
	 */
	public static function reset_all_keywords() {
		Keywords::reset_all_keywords();
		Cleanup::initiate_cleanup();
	}
}
