<?php

/***** Load CSS & JavaScript *****/

if (!function_exists('mh_newsdesk_google_webfonts')) {
	function mh_newsdesk_google_webfonts() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		if ($mh_newsdesk_options['google_webfonts'] == 'enable') {
			$font_body = $mh_newsdesk_options['font_body'];
			$font_heading = $mh_newsdesk_options['font_heading'];
			$font_location = array('armata' => 'Armata', 'arvo' => 'Arvo', 'asap' => 'Asap', 'bree_serif' => 'Bree+Serif', 'droid_sans' => 'Droid+Sans', 'droid_sans_mono' => 'Droid+Sans+Mono', 'droid_serif' => 'Droid+Serif', 'fjalla_one' => 'Fjalla+One', 'lato' => 'Lato', 'lora' => 'Lora', 'merriweather' => 'Merriweather', 'merriweather_sans' => 'Merriweather+Sans', 'monda' => 'Monda', 'nobile' => 'Nobile', 'noto_sans' => 'Noto+Sans', 'noto_serif' => 'Noto+Serif', 'open_sans' => 'Open+Sans', 'oswald' => 'Oswald', 'pt_sans' => 'PT+Sans', 'pt_serif' => 'PT+Serif', 'raleway' => 'Raleway', 'roboto' => 'Roboto', 'roboto_condensed' => 'Roboto+Condensed', 'ubuntu' => 'Ubuntu', 'yanone_kaffeesatz' => 'Yanone+Kaffeesatz');
			$font_styles_body = ':' . $mh_newsdesk_options['font_styles'];
			$font_subset = '';
			if ($mh_newsdesk_options['google_webfonts_subsets'] == 'latin_ext') {
				$font_subset = '&subset=latin,latin-ext';
			} elseif ($mh_newsdesk_options['google_webfonts_subsets'] == 'greek') {
				$font_subset = '&subset=latin,greek';
			} elseif ($mh_newsdesk_options['google_webfonts_subsets'] == 'greek_ext') {
				$font_subset = '&subset=latin,greek,greek-ext';
			} elseif ($mh_newsdesk_options['google_webfonts_subsets'] == 'cyrillic') {
				$font_subset = '&subset=latin,cyrillic';
			} elseif ($mh_newsdesk_options['google_webfonts_subsets'] == 'cyrillic_ext') {
				$font_subset = '&subset=latin,cyrillic,cyrillic-ext';
			}
			if ($font_location[$font_heading] != $font_location[$font_body]) {
				$font_heading = '|' . $font_location[$font_heading];
				$font_styles_heading = ':' . $mh_newsdesk_options['font_styles'];
			} else {
				$font_heading = '';
				$font_styles_heading = '';
			}
			if (empty($mh_newsdesk_options['font_styles'])) {
				$font_styles_body = '';
				$font_styles_heading = '';
			}
			wp_enqueue_style('mh-google-fonts', 'https://fonts.googleapis.com/css?family=' . $font_location[$font_body] . esc_attr($font_styles_body) . $font_heading . esc_attr($font_styles_heading) . $font_subset, array(), null);
		}
	}
}
add_action('wp_enqueue_scripts', 'mh_newsdesk_google_webfonts');

/***** Include Typography Custom CSS *****/

if (!function_exists('mh_newsdesk_fonts_css')) {
	function mh_newsdesk_fonts_css() {
		$mh_newsdesk_options = mh_newsdesk_theme_options();
		if ($mh_newsdesk_options['google_webfonts'] == 'enable') {
			$font_css = array('armata' => '"Armata", sans-serif', 'arvo' => '"Arvo", serif', 'asap' => '"Asap", sans-serif', 'bree_serif' => '"Bree Serif", serif', 'droid_sans' => '"Droid Sans", sans-serif', 'droid_sans_mono' => '"Droid Sans Mono", sans-serif', 'droid_serif' => '"Droid Serif", serif', 'fjalla_one' => '"Fjalla One", sans-serif', 'lato' => '"Lato", sans-serif', 'lora' => '"Lora", serif', 'merriweather' => '"Merriweather", serif', 'merriweather_sans' => '"Merriweather Sans", sans-serif', 'monda' => '"Monda", sans-serif', 'nobile' => '"Nobile", sans-serif', 'noto_sans' => '"Noto Sans", sans-serif', 'noto_serif' => '"Noto Serif", serif', 'open_sans' => '"Open Sans", sans-serif', 'oswald' => '"Oswald", sans-serif', 'pt_sans' => '"PT Sans", sans-serif', 'pt_serif' => '"PT Serif", serif', 'raleway' => '"Raleway", sans-serif', 'roboto' => 'Roboto', 'roboto_condensed' => '"Roboto Condensed", sans-serif', 'ubuntu' => '"Ubuntu", sans-serif', 'yanone_kaffeesatz' => '"Yanone Kaffeesatz", sans-serif');
			if (!empty($mh_newsdesk_options['font_size']) && $mh_newsdesk_options['font_size'] != '17' || $mh_newsdesk_options['font_heading'] != 'oswald' || $mh_newsdesk_options['font_body'] != 'pt_serif') {
				echo '<style type="text/css">' . "\n";
					if (!empty($mh_newsdesk_options['font_size']) && $mh_newsdesk_options['font_size'] != '17') {
						echo '.entry-content p { font-size: ' . $mh_newsdesk_options['font_size'] . 'px; font-size: ' . $mh_newsdesk_options['font_size'] / 16 . 'rem; }' . "\n";
					}
					if ($mh_newsdesk_options['font_heading'] != 'oswald') {
						echo '.header-nav, .header-sub, h1, h2, h3, h4, h5, h6, .breadcrumb, .footer-bottom, .widget-list li, .cat-item, .page_item, .menu-item, .button, .commentlist .vcard, .pagination, .mh-share-buttons { font-family: ' . $font_css[$mh_newsdesk_options['font_heading']] .'; }' . "\n";
					}
					if ($mh_newsdesk_options['font_body'] != 'pt_serif') {
						echo 'body { font-family: ' . $font_css[$mh_newsdesk_options['font_body']] . '; }' . "\n";
					}
				echo '</style>' . "\n";
			}
		}
	}
}
add_action('wp_head', 'mh_newsdesk_fonts_css');

?>