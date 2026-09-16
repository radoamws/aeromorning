<?php
namespace ILJ\Backend;

use ILJ\Core\Options;

if (!defined('ILJ_PATH')) die('No direct access allowed');

if (!class_exists('Updraft_Notices_1_3')) require_once(ILJ_PATH.'/vendor/team-updraft/common-libs/src/updraft-notices/updraft-notices.php');

/**
 * Internal Link Juicer Notices
 *
 * This class is responsible for the creation of ILJ notices.
 *
 * @since 2.24.6
 */
class Notices extends \Updraft_Notices_1_3 {

	const ILJ_DISMISS_ADMIN_WARNING_LITESPEED = "ilj_dismiss_admin_warning_litespeed";

	private $initialized = false;

	protected $notices_content = array();

	/**
	 * show_admin_warning_litespeed
	 *
	 * @return void
	 */
	public static function show_admin_warning_litespeed() {
		?>
		<div class="iljmessage updated admin-warning-litespeed notice is-dismissible">
			<p><strong><?php esc_html_e('Warning', 'internal-links'); ?></strong>
			<?php esc_html_e('Your website is hosted using the LiteSpeed web server.', 'internal-links'); ?>
				<a href="https://www.internallinkjuicer.com/faqs/" target="_blank">
					<?php esc_html_e('Please consult this FAQ if you have problems building links.', 'internal-links'); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * This function will add ilj_dismiss_admin_warning_litespeed option to hide litespeed admin warning after dismissed
	 *
	 * @return void
	 */
	public static function dismiss_admin_warning_litespeed() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ilj-general-nonce')) {
			die;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		Options::setOption(self::ILJ_DISMISS_ADMIN_WARNING_LITESPEED, true);
		wp_die();
	}

	/**
	 * Returns array_merge of notices from parent and notices in $child_notice_content.
	 *
	 * @return Array
	 */
	protected function populate_notices_content() {
		$parent_notice_content = parent::populate_notices_content();
		$child_notice_content = array(
			'rate_plugin' => array(
				// translators: %1s represents the plugin support link value.
				'text' => sprintf(htmlspecialchars(__('Hey - you have been using the Internal Link Juicer for a while now - that\'s great.', 'internal-links') . ' ' . __('If you like us, please consider leaving a positive review to spread the word.', 'internal-links'). ' ' . __('Or if you have any issues or questions please leave us a support message %s.', 'internal-links')), '<a href="https://wordpress.org/support/plugin/internal-links/" target="_blank">'.__('here', 'internal-links').'</a>').'<br>'.__('Thank you so much!', 'internal-links').'<br><br>- <b>'.htmlspecialchars(__('Team Internal Link Juicer', 'internal-links')).'</b>',
				'image' => 'plugin-logos/internal-link-juicer-logo-sm.png',
				'button_link' => 'https://wordpress.org/support/plugin/internal-links/reviews/?filter=5#new-post',
				'button_meta' => 'review',
				'dismiss_time' => 'ilj_dismiss_review_notice',
				'supported_positions' => $this->dashboard_top,
				'validity_function' => 'show_rate_notice'
			),
		);

		return array_merge($parent_notice_content, $child_notice_content);
	}

	/**
	 * Call this method to setup the notices
	 */
	public function notices_init() {
		if ($this->initialized) return;
		$this->initialized = true;
		$this->notices_content = $this->populate_notices_content();

		$enqueue_version = (defined('WP_DEBUG') && WP_DEBUG) ? ILJ_VERSION.'.'.time() : ILJ_VERSION;
		wp_enqueue_style('ilj-admin-notices-css', ILJ_URL . 'admin/css/ilj-notices.css', array(), $enqueue_version);
	}

	/**
	 * Get timestamp that is considered as current timestamp for notice.
	 *
	 * @return integer timestamp that should be consider as a current time.
	 */
	public function get_time_now() {
		$time_now = defined('ILJ_NOTICES_FORCE_TIME') ? ILJ_NOTICES_FORCE_TIME : time();
		return $time_now;
	}

	/**
	 * Checks whether a notice is dismissed(returns true) or not(returns false).
	 *
	 * @param String $dismiss_time - dismiss time id for the notice
	 *
	 * @return boolean
	 */
	protected function check_notice_dismissed($dismiss_time) {
		$time_now = $this->get_time_now();
		$dismiss  = ($time_now < get_option($dismiss_time, 0));
		return $dismiss;
	}

	/**
	 * Get ILJ Plugin installation timestamp.
	 *
	 * @return integer ILJ Plugin installation timestamp.
	 */
	public function get_ilj_plugin_installed_timestamp() {
		$installed_at = @filemtime(ILJ_PATH.'/index.php'); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- ignore warning as we handle it below
		$installed_at = apply_filters('ilj_plugin_installed_timestamp', $installed_at);
		return $installed_at;
	}

	/**
	 * This function will check if we should display the rate notice or not
	 *
	 * @return boolean - to indicate if we should show the notice or not
	 */
	protected function show_rate_notice() {
		$installed_at  = $this->get_ilj_plugin_installed_timestamp();
		$time_now      = $this->get_time_now();
		$installed_for = $time_now - $installed_at;

		if ($installed_at && $installed_for > 28*86400) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether to prepare a seasonal notice(returns true) or not(returns false).
	 *
	 * @param Array $notice_data - all data for the notice
	 *
	 * @return Boolean
	 */
	protected function skip_seasonal_notices($notice_data) {
		$time_now   = $this->get_time_now();
		$valid_from = strtotime($notice_data['valid_from']);
		$valid_to   = strtotime($notice_data['valid_to']);
		$dismiss    = $this->check_notice_dismissed($notice_data['dismiss_time']);

		if (($time_now >= $valid_from && $time_now <= $valid_to) && !$dismiss) {
			// return true so that we return this notice to be displayed
			return true;
		}
		
		return false;
	}

	/**
	 * Renders or returns a notice.
	 *
	 * @param Boolean|String $advert_information     - all data for the notice
	 * @param Boolean        $return_instead_of_echo - whether to return the notice(true) or render it to the page(false)
	 * @param String         $position               - notice position
	 *
	 * @return Void|String
	 */
	protected function render_specified_notice($advert_information, $return_instead_of_echo = false, $position = 'top') {

		if ('bottom' == $position) {
			$template_file = 'bottom-notice.php';
		} elseif ('report' == $position) {
			$template_file = 'report.php';
		} else {
			$template_file = 'horizontal-notice.php';
		}

		return $this->include_template('notices/'.$template_file, $return_instead_of_echo, $advert_information);
	}

	/**
	 * Render or returns a dashboard notice.
	 */
	public function render_dashboard_thanks_for_using_notice() {
		return $this->include_template('notices/thanks-for-using-main-dash.php');
	}

	/**
	 * Output, or return, the results of running a template (from the 'templates' directory, unless a filter over-rides it).
	 *
	 * @param String  $path                   - path to the template
	 * @param Boolean $return_instead_of_echo - by default, the template is echo-ed; set this to instead return it
	 * @param Array   $extract_these          - variables to inject into the template's run context
	 *
	 * @return Void|String
	 */
	protected function include_template($path, $return_instead_of_echo = false, $extract_these = array()) {
		
		if ($return_instead_of_echo) ob_start();

		if (!isset($template_file)) $template_file = ILJ_PATH.'templates/'.$path;

		if (!file_exists($template_file)) {
			echo esc_html__('Error:', 'internal-links') .' '. esc_html__('template not found', 'internal-links') . esc_html(" ($template_file)");
		} else {
			extract($extract_these);

			include $template_file;
		}

		if ($return_instead_of_echo) return ob_get_clean();
	}

	/**
	 * This function will enqueue the WordPress dashboard widget styles or scripts.
	 *
	 * @return void
	 */
	protected function widget_enqueue() {
	}
}
