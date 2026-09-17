<?php $mh_newsdesk_options = mh_newsdesk_theme_options(); ?>
<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php wp_head(); ?>

<link rel="alternate" hreflang="fr" href="<?php echo esc_url( get_home_url( 1, '/' ) ); ?>" />
<link rel="alternate" hreflang="en" href="<?php echo esc_url( get_home_url( 2, '/' ) ); ?>" />
<link rel="alternate" hreflang="x-default" href="<?php echo esc_url( get_home_url( 2, '/' ) ); ?>" />
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-S5ED39PCB0"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-S5ED39PCB0');
</script>


</head>
<body <?php body_class(); ?>>

<?php if (has_nav_menu('header_nav') || has_nav_menu('social_nav')) { ?>
	<div class="header-top">
		<div class="wrapper-inner clearfix">
			<?php if (has_nav_menu('header_nav')) { ?>
				<nav class="header-nav clearfix">
					<?php wp_nav_menu(array('theme_location' => 'header_nav', 'fallback_cb' => '')); ?>
				</nav>
			<?php } ?>
			<?php if (has_nav_menu('social_nav')) { ?>
				<nav class="social-nav clearfix">
					<?php wp_nav_menu(array('theme_location' => 'social_nav', 'link_before' => '<span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-mh-social fa-stack-1x"></i></span><span class="screen-reader-text">', 'link_after' => '</span>')); ?>
				</nav>
			<?php } ?>
		</div>
	</div>
<?php } ?>
<div id="mh-wrapper">
<header class="mh-header">
	<div class="header-wrap clearfix">
		<?php is_active_sidebar('header-ad') ? $logo_class = ' header-logo' : $logo_class = ' header-logo'; ?>
		<div class="mh-col mh-1-3<?php echo $logo_class; ?>">
			<?php mh_newsdesk_logo(); ?>
			<div class="langue">
<a href="/"><img class="alignleft wp-image-1352" title="Langue Francaise" src="<?php echo esc_url( get_home_url( 1, '/wp-content/uploads/2015/11/francais.jpg' ) ); ?>" alt="Langue Francaise" width="20" height="15" /></a>

<a href="/en"><img class="alignleft wp-image-1353" title="English language" src="<?php echo esc_url( get_home_url( 1, '/wp-content/uploads/2015/11/anglais.jpg' ) ); ?>" alt="English language" width="20" height="15" /></a>
</div>
		</div>
		
		<?php //dynamic_sidebar('header-ad'); ?>
		<div class="mh-col mh-2-3 widget_smartslider3">
		 <?php
		echo do_shortcode('[smartslider3 slider=4]');
		?>
		</div>
	</div>
	<div class="header-menu clearfix">
		<nav class="main-nav clearfix">
			<?php wp_nav_menu(array('theme_location' => 'main_nav')); ?>
		</nav>
		<div class="header-sub clearfix">
			<?php if ($mh_newsdesk_options['show_ticker']) { ?>
				<?php get_template_part('template', 'news-ticker'); ?>
			<?php } ?>
			<aside class="mh-col mh-1-3 header-search">
			
				<?php get_search_form(); ?>
				
			</aside>
		
		</div>
		<div class="headslide">
 <?php
echo do_shortcode('[smartslider3 slider=5]');
echo do_shortcode('[smartslider3 slider=7]');
?>
		</div>
	</div>
</header>