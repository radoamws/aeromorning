<?php $mh_newsdesk_options = mh_newsdesk_theme_options(); ?>
</div>
<footer class="mh-footer">
	<?php dynamic_sidebar('footer-ad'); ?>
	<div class="wrapper-inner clearfix">
		<?php if (is_active_sidebar('footer-1') || is_active_sidebar('footer-2') || is_active_sidebar('footer-3')) { ?>
			<div class="mh-section mh-group footer-widgets">
				<?php if (is_active_sidebar('footer-1')) { ?>
					<div class="mh-col mh-1-3 footer-1">
						<?php dynamic_sidebar('footer-1'); ?>
					</div>
				<?php } ?>
				<?php if (is_active_sidebar('footer-2')) { ?>
					<div class="mh-col mh-1-3 footer-2">
						<?php dynamic_sidebar('footer-2'); ?>
					</div>
				<?php } ?>
				<?php if (is_active_sidebar('footer-3')) { ?>
					<div class="mh-col mh-1-3 footer-3">
						<?php dynamic_sidebar('footer-3'); ?>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
	<div class="footer-bottom">
		<div class="wrapper-inner clearfix">
			<?php if (has_nav_menu('footer_nav')) { ?>
				<nav class="footer-nav clearfix">
					<?php wp_nav_menu(array('theme_location' => 'footer_nav', 'fallback_cb' => '')); ?>
				</nav>
			<?php } ?>
			<div class="copyright-wrap">
				<p class="copyright"><?php echo empty($mh_newsdesk_options['copyright']) ? sprintf(__('Copyright %1$s | MH Newsdesk by %2$s', 'mh-newsdesk'), date("Y"), '<a href="' . esc_url('http://www.mhthemes.com/') . '" title="Premium Magazine WordPress Themes" rel="nofollow">MH Themes</a>') : $mh_newsdesk_options['copyright']; ?> |
<a href="http://www.aeromorning.com/mentions-legales/"> Mentions légales</a></p>
			</div>
		</div>

	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>