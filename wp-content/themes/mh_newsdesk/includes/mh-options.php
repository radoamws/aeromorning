<?php

function mh_newsdesk_customize_register($wp_customize) {

	/***** Register Custom Controls *****/

	class MH_Customize_Header_Control extends WP_Customize_Control {
        public function render_content() { ?>
			<span class="customize-control-title"><?php echo esc_html($this->label); ?></span> <?php
        }
    }

	class MH_Customize_Image_Control extends WP_Customize_Image_Control {
    	public $extensions = array('jpg', 'jpeg', 'gif', 'png', 'ico');
	}

	/***** Add Panels *****/

	$wp_customize->add_panel('mh_theme_options', array('title' => __('Theme Options', 'mh-newsdesk'), 'description' => '', 'capability' => 'edit_theme_options', 'theme_supports' => '', 'priority' => 1,));

	/***** Add Sections *****/

	$wp_customize->add_section('mh_general', array('title' => __('General', 'mh-newsdesk'), 'priority' => 1, 'panel' => 'mh_theme_options'));
	$wp_customize->add_section('mh_layout', array('title' => __('Layout', 'mh-newsdesk'), 'priority' => 2, 'panel' => 'mh_theme_options'));
	$wp_customize->add_section('mh_typo', array('title' => __('Typography', 'mh-newsdesk'), 'priority' => 3, 'panel' => 'mh_theme_options'));
	$wp_customize->add_section('mh_ticker', array('title' => __('News Ticker', 'mh-newsdesk'), 'priority' => 4, 'panel' => 'mh_theme_options'));

    /***** Add Settings *****/

	$wp_customize->add_setting('mh_newsdesk_options[favicon]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'esc_url_raw'));
	$wp_customize->add_setting('mh_newsdesk_options[excerpt_length]', array('default' => '25', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_integer'));
    $wp_customize->add_setting('mh_newsdesk_options[excerpt_more]', array('default' => __('Read More', 'mh-newsdesk'), 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_text'));
	$wp_customize->add_setting('mh_newsdesk_options[copyright]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_text'));

    $wp_customize->add_setting('mh_newsdesk_options[breadcrumbs]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
    $wp_customize->add_setting('mh_newsdesk_options[featured_image]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
	$wp_customize->add_setting('mh_newsdesk_options[social_sharing]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
    $wp_customize->add_setting('mh_newsdesk_options[post_nav]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
    $wp_customize->add_setting('mh_newsdesk_options[author_box]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
    $wp_customize->add_setting('mh_newsdesk_options[related_content]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
    $wp_customize->add_setting('mh_newsdesk_options[comments_pages]', array('default' => 'disable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
	$wp_customize->add_setting('mh_newsdesk_options[sidebar]', array('default' => 'right', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
	$wp_customize->add_setting('mh_newsdesk_options[post_meta_header]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'esc_attr'));
    $wp_customize->add_setting('mh_newsdesk_options[post_meta_date]', array('default' => 0, 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_checkbox'));
    $wp_customize->add_setting('mh_newsdesk_options[post_meta_author]', array('default' => 0, 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_checkbox'));
    $wp_customize->add_setting('mh_newsdesk_options[post_meta_cat]', array('default' => 0, 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_checkbox'));

	$wp_customize->add_setting('mh_newsdesk_options[font_size]', array('default' => '17', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_integer'));
	$wp_customize->add_setting('mh_newsdesk_options[google_webfonts]', array('default' => 'enable', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
	$wp_customize->add_setting('mh_newsdesk_options[google_webfonts_subsets]', array('default' => 'latin', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_select'));
	$wp_customize->add_setting('mh_newsdesk_options[font_heading]', array('default' => 'oswald', 'type' => 'option', 'sanitize_callback' => 'esc_attr'));
	$wp_customize->add_setting('mh_newsdesk_options[font_body]', array('default' => 'pt_serif', 'type' => 'option', 'sanitize_callback' => 'esc_attr'));
	$wp_customize->add_setting('mh_newsdesk_options[font_styles]', array('default' => '300,400,400italic,600,700', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_text'));

    $wp_customize->add_setting('mh_newsdesk_options[show_ticker]', array('default' => 1, 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_checkbox'));
    $wp_customize->add_setting('mh_newsdesk_options[ticker_title]', array('default' => __('Hot Topics', 'mh-newsdesk'), 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_text'));
    $wp_customize->add_setting('mh_newsdesk_options[ticker_posts]', array('default' => '5', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_integer'));
    $wp_customize->add_setting('mh_newsdesk_options[ticker_cats]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_text'));
    $wp_customize->add_setting('mh_newsdesk_options[ticker_tags]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_text'));
    $wp_customize->add_setting('mh_newsdesk_options[ticker_offset]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_integer'));
    $wp_customize->add_setting('mh_newsdesk_options[ticker_sticky]', array('default' => '', 'type' => 'option', 'sanitize_callback' => 'mh_newsdesk_sanitize_checkbox'));

	$wp_customize->add_setting('mh_newsdesk_options[color_1]', array('default' => '#005a8c', 'type' => 'option', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_setting('mh_newsdesk_options[color_2]', array('default' => '#ccdee8', 'type' => 'option', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_setting('mh_newsdesk_options[color_3]', array('default' => '#1f1e1e', 'type' => 'option', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_setting('mh_newsdesk_options[color_4]', array('default' => '#333333', 'type' => 'option', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_setting('mh_newsdesk_options[color_5]', array('default' => '#464646', 'type' => 'option', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_setting('mh_newsdesk_options[color_6]', array('default' => '#ffffff', 'type' => 'option', 'sanitize_callback' => 'sanitize_hex_color'));

    /***** Add Controls *****/

    $wp_customize->add_control(new MH_Customize_Image_Control($wp_customize, 'favicon', array('label' => esc_html__('Favicon Upload', 'mh-newsdesk'), 'section' => 'mh_general', 'settings' => 'mh_newsdesk_options[favicon]', 'priority' => 1)));
    $wp_customize->add_control('excerpt_length', array('label' => esc_html__('Custom Excerpt Length in Words', 'mh-newsdesk'), 'section' => 'mh_general', 'settings' => 'mh_newsdesk_options[excerpt_length]', 'priority' => 2, 'type' => 'text'));
    $wp_customize->add_control('excerpt_more', array('label' => esc_html__('Custom Excerpt More-Text', 'mh-newsdesk'), 'section' => 'mh_general', 'settings' => 'mh_newsdesk_options[excerpt_more]', 'priority' => 3, 'type' => 'text'));
    $wp_customize->add_control('copyright', array('label' => esc_html__('Copyright Text', 'mh-newsdesk'), 'section' => 'mh_general', 'settings' => 'mh_newsdesk_options[copyright]', 'priority' => 4, 'type' => 'text'));

    $wp_customize->add_control('breadcrumbs', array('label' => esc_html__('Breadcrumb Navigation', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[breadcrumbs]', 'priority' => 1, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
    $wp_customize->add_control('featured_image', array('label' => esc_html__('Featured Image on Posts', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[featured_image]', 'priority' => 2, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
	$wp_customize->add_control('social_sharing', array('label' => esc_html__('Sharing Buttons', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[social_sharing]', 'priority' => 3, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
    $wp_customize->add_control('post_nav', array('label' => esc_html__('Post/Attachment Navigation', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[post_nav]', 'priority' => 4, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
    $wp_customize->add_control('author_box', array('label' => esc_html__('Author Box', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[author_box]', 'priority' => 5, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
    $wp_customize->add_control('related_content', array('label' => esc_html__('Related Articles', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[related_content]', 'priority' => 6, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
    $wp_customize->add_control('comments_pages', array('label' => esc_html__('Comments on Pages', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[comments_pages]', 'priority' => 7, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
	$wp_customize->add_control('sidebar', array('label' => esc_html__('Sidebar', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[sidebar]', 'priority' => 8, 'type' => 'select', 'choices' => array('right' => __('Right Sidebar', 'mh-newsdesk'), 'left' => __('Left Sidebar', 'mh-newsdesk'))));
	$wp_customize->add_control(new MH_Customize_Header_Control($wp_customize, 'post_meta_header', array('label' => esc_html__('Hide Post Meta Data', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[post_meta_header]', 'priority' => 9)));
    $wp_customize->add_control('post_meta_date', array('label' => esc_html__('Hide Date', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[post_meta_date]', 'priority' => 10, 'type' => 'checkbox'));
    $wp_customize->add_control('post_meta_author', array('label' => esc_html__('Hide Author', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[post_meta_author]', 'priority' => 11, 'type' => 'checkbox'));
	$wp_customize->add_control('post_meta_cat', array('label' => esc_html__('Hide Categories', 'mh-newsdesk'), 'section' => 'mh_layout', 'settings' => 'mh_newsdesk_options[post_meta_cat]', 'priority' => 12, 'type' => 'checkbox'));

	$wp_customize->add_control('font_size', array('label' => esc_html__('Change default Font Size (px)', 'mh-newsdesk'), 'section' => 'mh_typo', 'settings' => 'mh_newsdesk_options[font_size]', 'priority' => 1, 'type' => 'text'));
	$google_fonts = array('armata' => 'Armata', 'arvo' => 'Arvo', 'asap' => 'Asap', 'bree_serif' => 'Bree Serif', 'droid_sans' => 'Droid Sans', 'droid_sans_mono' => 'Droid Sans Mono', 'droid_serif' => 'Droid Serif', 'fjalla_one' => 'Fjalla One', 'lato' => 'Lato', 'lora' => 'Lora', 'merriweather' => 'Merriweather', 'merriweather_sans' => 'Merriweather Sans', 'monda' => 'Monda', 'nobile' => 'Nobile', 'noto_sans' => 'Noto Sans', 'noto_serif' => 'Noto Serif', 'open_sans' => 'Open Sans', 'oswald' => 'Oswald', 'pt_sans' => 'PT Sans', 'pt_serif' => 'PT Serif', 'raleway' => 'Raleway', 'roboto' => 'Roboto', 'roboto_condensed' => 'Roboto Condensed', 'ubuntu' => 'Ubuntu', 'yanone_kaffeesatz' => 'Yanone Kaffeesatz');
    $wp_customize->add_control('google_webfonts', array('label' => esc_html__('Google Webfonts', 'mh-newsdesk'), 'section' => 'mh_typo', 'settings' => 'mh_newsdesk_options[google_webfonts]', 'priority' => 2, 'type' => 'select', 'choices' => array('enable' => __('Enable', 'mh-newsdesk'), 'disable' => __('Disable', 'mh-newsdesk'))));
	$wp_customize->add_control('google_webfonts_subsets', array('label' => esc_html__('Google Webfonts Characters', 'mh-newsdesk'), 'section' => 'mh_typo', 'settings' => 'mh_newsdesk_options[google_webfonts_subsets]', 'priority' => 3, 'type' => 'select', 'choices' => array('latin' => __('Latin', 'mh-newsdesk'), 'latin_ext' => __('Latin Extended', 'mh-newsdesk'), 'greek' => __('Greek', 'mh-newsdesk'), 'greek_ext' => __('Greek Extended', 'mh-newsdesk'), 'cyrillic' => __('Cyrillic', 'mh-newsdesk'), 'cyrillic_ext' => __('Cyrillic Extended', 'mh-newsdesk'))));
	$wp_customize->add_control('font_heading', array('label' => esc_html__('Google Webfont for Headings', 'mh-newsdesk'), 'section' => 'mh_typo', 'settings' => 'mh_newsdesk_options[font_heading]', 'priority' => 4, 'type' => 'select', 'choices' => $google_fonts));
	$wp_customize->add_control('font_body', array('label' => esc_html__('Google Webfont for Body Text', 'mh-newsdesk'), 'section' => 'mh_typo', 'settings' => 'mh_newsdesk_options[font_body]', 'priority' => 5, 'type' => 'select', 'choices' => $google_fonts));
	$wp_customize->add_control('font_styles', array('label' => esc_html__('Imported Google Font Styles', 'mh-newsdesk'), 'section' => 'mh_typo', 'settings' => 'mh_newsdesk_options[font_styles]', 'priority' => 6, 'type' => 'text'));

	$wp_customize->add_control('show_ticker', array('label' => esc_html__('Enable Ticker', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[show_ticker]', 'priority' => 1, 'type' => 'checkbox'));
    $wp_customize->add_control('ticker_title', array('label' => esc_html__('Ticker Title', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[ticker_title]', 'priority' => 2, 'type' => 'text'));
    $wp_customize->add_control('ticker_posts', array('label' => esc_html__('Limit Post Number', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[ticker_posts]', 'priority' => 3, 'type' => 'text'));
    $wp_customize->add_control('ticker_cats', array('label'=> esc_html__('Custom Categories (use ID - e.g. 3,5,9):', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[ticker_cats]', 'priority' => 4, 'type' => 'text'));
    $wp_customize->add_control('ticker_tags', array('label' => esc_html__('Custom Tags (use slug - e.g. lifestyle):', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[ticker_tags]', 'priority' => 5, 'type' => 'text'));
    $wp_customize->add_control('ticker_offset', array('label' => esc_html__('Skip Posts (Offset):', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[ticker_offset]', 'priority' => 6, 'type' => 'text'));
	$wp_customize->add_control('ticker_sticky', array('label' => esc_html__('Ignore Sticky Posts', 'mh-newsdesk'), 'section' => 'mh_ticker', 'settings' => 'mh_newsdesk_options[ticker_sticky]', 'priority' => 7, 'type' => 'checkbox'));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'color_1', array('label' => sprintf(esc_html_x('Theme Color %d', 'options panel', 'mh-newsdesk'), 1), 'section' => 'colors', 'settings' => 'mh_newsdesk_options[color_1]', 'priority' => 52)));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'color_2', array('label' => sprintf(esc_html_x('Theme Color %d', 'options panel', 'mh-newsdesk'), 2), 'section' => 'colors', 'settings' => 'mh_newsdesk_options[color_2]', 'priority' => 53)));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'color_3', array('label' => sprintf(esc_html_x('Theme Color %d', 'options panel', 'mh-newsdesk'), 3), 'section' => 'colors', 'settings' => 'mh_newsdesk_options[color_3]', 'priority' => 54)));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'color_4', array('label' => sprintf(esc_html_x('Theme Color %d', 'options panel', 'mh-newsdesk'), 4), 'section' => 'colors', 'settings' => 'mh_newsdesk_options[color_4]', 'priority' => 55)));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'color_5', array('label' => sprintf(esc_html_x('Theme Color %d', 'options panel', 'mh-newsdesk'), 5), 'section' => 'colors', 'settings' => 'mh_newsdesk_options[color_5]', 'priority' => 56)));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'color_6', array('label' => esc_html__('Text Color (Navigation/Footer)', 'mh-newsdesk'), 'section' => 'colors', 'settings' => 'mh_newsdesk_options[color_6]', 'priority' => 57)));
}
add_action('customize_register', 'mh_newsdesk_customize_register');

/***** Data Sanitization *****/

function mh_newsdesk_sanitize_text($input) {
    return wp_kses_post(force_balance_tags($input));
}
function mh_newsdesk_sanitize_integer($input) {
    return strip_tags(intval($input));
}
function mh_newsdesk_sanitize_checkbox($input) {
    if ($input == 1) {
        return 1;
    } else {
        return '';
    }
}
function mh_newsdesk_sanitize_select($input) {
    $valid = array(
        'enable' => __('Enable', 'mh-newsdesk'),
        'disable' => __('Disable', 'mh-newsdesk'),
        'right' => __('Right Sidebar', 'mh-newsdesk'),
        'left' => __('Left Sidebar', 'mh-newsdesk'),
        'latin' => __('Latin', 'mh-newsdesk'),
        'latin_ext' => __('Latin Extended', 'mh-newsdesk'),
        'greek' => __('Greek', 'mh-newsdesk'),
        'greek_ext' => __('Greek Extended', 'mh-newsdesk'),
        'cyrillic' => __('Cyrillic', 'mh-newsdesk'),
        'cyrillic_ext' => __('Cyrillic Extended', 'mh-newsdesk'),
    );
    if (array_key_exists($input, $valid)) {
        return $input;
    } else {
        return '';
    }
}

/***** Return Theme Options / Set Default Options *****/

if (!function_exists('mh_newsdesk_theme_options')) {
	function mh_newsdesk_theme_options() {
		$theme_options = wp_parse_args(
			get_option('mh_newsdesk_options', array()),
			mh_newsdesk_default_options()
		);
		return $theme_options;
	}
}

if (!function_exists('mh_newsdesk_default_options')) {
	function mh_newsdesk_default_options() {
		$default_options = array(
			'favicon' => '',
			'excerpt_length' => '25',
			'excerpt_more' => __('Read More', 'mh-newsdesk'),
			'copyright' => '',
			'breadcrumbs' => 'enable',
			'featured_image' => 'enable',
			'social_sharing' => 'enable',
			'post_nav' => 'enable',
			'author_box' => 'enable',
			'related_content' => 'enable',
			'comments_pages' => 'disable',
			'sidebar' => 'right',
			'post_meta_date' => 0,
			'post_meta_author' => 0,
			'post_meta_cat' => 0,
			'font_size' => '17',
			'google_webfonts' => 'enable',
			'google_webfonts_subsets' => 'latin',
			'font_heading' => 'oswald',
			'font_body' => 'pt_serif',
			'font_styles' => '300,400,400italic,600,700',
			'show_ticker' => 1,
			'ticker_title' => __('Hot Topics', 'mh-newsdesk'),
			'ticker_posts' => '5',
			'ticker_cats' => '',
			'ticker_tags' => '',
			'ticker_offset' => '',
			'ticker_sticky' => 0,
			'color_1' => '#005a8c',
			'color_2' => '#ccdee8',
			'color_3' => '#1f1e1e',
			'color_4' => '#333333',
			'color_5' => '#464646',
			'color_6' => '#ffffff'
		);
		return $default_options;
	}
}

/***** Enqueue Customizer CSS *****/

function mh_newsdesk_customizer_css() {
	wp_enqueue_style('mh-customizer-css', get_template_directory_uri() . '/admin/customizer.css', array());
}
add_action('customize_controls_print_styles', 'mh_newsdesk_customizer_css');

/***** CSS Output *****/

function mh_newsdesk_custom_css() {
	$mh_newsdesk_options = mh_newsdesk_theme_options();
	if ($mh_newsdesk_options['color_1'] != '#005a8c' || $mh_newsdesk_options['color_2'] != '#ccdee8' || $mh_newsdesk_options['color_3'] != '#1f1e1e' || $mh_newsdesk_options['color_4'] != '#333333' || $mh_newsdesk_options['color_5'] != '#464646' || $mh_newsdesk_options['color_6'] != '#ffffff') : ?>
	<style type="text/css">
    	<?php if ($mh_newsdesk_options['color_1'] != '#005a8c') { ?>
    		.social-nav a:hover, .logo-title, .entry-content a, a:hover, .entry-meta .entry-meta-author, .entry-meta a, .comment-info, blockquote, .pagination a:hover .pagelink { color: <?php echo $mh_newsdesk_options['color_1']; ?>; }
			.main-nav li:hover, .slicknav_menu, .ticker-title, .breadcrumb a, .breadcrumb .bc-text, .button span, .widget-title span, input[type=submit], table th, .comment-section-title .comment-count, #cancel-comment-reply-link:hover, .pagination .current, .pagination .pagelink { background: <?php echo $mh_newsdesk_options['color_1']; ?>; }
			blockquote, input[type=text]:hover, input[type=email]:hover, input[type=tel]:hover, input[type=url]:hover, textarea:hover { border-color: <?php echo $mh_newsdesk_options['color_1']; ?>; }
    	<?php } ?>
    	<?php if ($mh_newsdesk_options['color_2'] != '#ccdee8') { ?>
			.widget-title, .pagination a.page-numbers:hover, .dots:hover, .pagination a:hover .pagelink, .comment-section-title { background: <?php echo $mh_newsdesk_options['color_2']; ?>; }
			.commentlist .depth-1, .commentlist .bypostauthor .avatar { border-color: <?php echo $mh_newsdesk_options['color_2']; ?>; }
    	<?php } ?>
    	<?php if ($mh_newsdesk_options['color_3'] != '#1f1e1e') { ?>
			.main-nav, .main-nav ul li:hover > ul, .mh-footer { background: <?php echo $mh_newsdesk_options['color_3']; ?>; }
    	<?php } ?>
    	<?php if ($mh_newsdesk_options['color_4'] != '#333333') { ?>
			.header-top, .header-nav ul li:hover > ul, .footer-ad-wrap, .footer-1, .footer-2, .footer-3, .footer-bottom { background: <?php echo $mh_newsdesk_options['color_4']; ?>; }
			.social-nav a { color: <?php echo $mh_newsdesk_options['color_4']; ?>; }
    	<?php } ?>
    	<?php if ($mh_newsdesk_options['color_5'] != '#464646') { ?>
			.footer-widgets .widget-title { background: <?php echo $mh_newsdesk_options['color_5']; ?>; }
    	<?php } ?>
    	<?php if ($mh_newsdesk_options['color_6'] != '#ffffff') { ?>
			.header-nav li a, .main-nav li a, .footer-nav li a, .social-nav .fa-mh-social, .mh-footer, .mh-footer a, .footer-widgets .widget-title { color: <?php echo $mh_newsdesk_options['color_6']; ?>; }
    	<?php } ?>
	</style>
    <?php
	endif;
}
add_action('wp_head', 'mh_newsdesk_custom_css');

?>