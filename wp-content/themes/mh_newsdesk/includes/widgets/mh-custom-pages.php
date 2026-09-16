<?php

/***** Custom Pages Widget *****/

class mh_newsdesk_custom_pages extends WP_Widget {
    function __construct() {
		parent::__construct(
			'mh_newsdesk_custom_pages', esc_html_x('MH Custom Pages', 'widget name', 'mh-newsdesk'),
			array('classname' => 'mh_newsdesk_custom_pages', 'description' => esc_html__('Custom Pages Widget to display pages based on page IDs.', 'mh-newsdesk'))
		);
	}
    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$link = empty($instance['link']) ? '' : $instance['link'];
        $pages = empty($instance['pages']) ? '' : $instance['pages'];
        $excerpt = isset($instance['excerpt']) ? $instance['excerpt'] : 'first';

        if ($link) {
	        $before_title = $before_title . '<a href="' . esc_url($link) . '" class="widget-title-link">';
	        $after_title = '</a>' . $after_title;
        }

        echo $before_widget;
        if (!empty( $title)) { echo $before_title . $title . $after_title; }
        $include_ids = explode(',', $pages);
		$args = array('post_type' => 'page', 'post__in' => $include_ids, 'orderby' => 'post__in');
		$counter = 1;
		$widget_loop = new WP_Query($args); ?>
        <div class="mh-cp-widget clearfix"> <?php
			while ($widget_loop->have_posts()) : $widget_loop->the_post();
				if ($counter == 1 && $excerpt == 'first' || $excerpt == 'all') : ?>
					<article class="cp-wrap cp-large clearfix">
						<div class="cp-thumb-xl"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php if (has_post_thumbnail()) { the_post_thumbnail('cp-thumb-xl'); } else { echo '<img src="' . get_template_directory_uri() . '/images/placeholder-thumb-xl.jpg' . '" alt="No Picture" />'; } ?></a></div>
						<h3 class="cp-title-xl"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" rel="bookmark"><?php the_title(); ?></a></h3>
						<div class="cp-excerpt-xl">
							<?php the_excerpt(); ?>
							<?php mh_newsdesk_more(); ?>
						</div>
					</article>
					<hr class="mh-separator"><?php
				else : ?>
					<article class="cp-wrap cp-small clearfix">
						<div class="cp-thumb-small"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php if (has_post_thumbnail()) { the_post_thumbnail('cp-thumb-small'); } else { echo '<img src="' . get_template_directory_uri() . '/images/placeholder-thumb-small.jpg' . '" alt="No Picture" />'; } ?></a></div>
						<h3 class="cp-title-small"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" rel="bookmark"><?php the_title(); ?></a></h3>
					</article>
					<hr class="mh-separator"><?php
				endif;
				$counter++;
			endwhile;
			wp_reset_postdata(); ?>
        </div><?php
        echo $after_widget;
    }
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['link'] = esc_url_raw($new_instance['link']);
        $instance['pages'] = strip_tags($new_instance['pages']);
        $instance['excerpt'] = strip_tags($new_instance['excerpt']);
        return $instance;
    }
    function form($instance) {
        $defaults = array('title' => '', 'link' => '', 'pages' => '', 'excerpt' => 'first');
        $instance = wp_parse_args((array) $instance, $defaults); ?>

        <p>
        	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title (optional):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['title']); ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" />
        </p>
        <p>
        	<label for="<?php echo $this->get_field_id('link'); ?>"><?php _e('Link Title to URL (optional):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_url($instance['link']); ?>" name="<?php echo $this->get_field_name('link'); ?>" id="<?php echo $this->get_field_id('link'); ?>" />
        </p>
		<p>
        	<label for="<?php echo $this->get_field_id('pages'); ?>"><?php _e('Filter Pages by ID (comma separated):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['pages']); ?>" name="<?php echo $this->get_field_name('pages'); ?>" id="<?php echo $this->get_field_id('pages'); ?>" />
	    </p>
        <p>
	    	<label for="<?php echo $this->get_field_id('excerpt'); ?>"><?php _e('Display Excerpts:', 'mh-newsdesk'); ?></label>
			<select id="<?php echo $this->get_field_id('excerpt'); ?>" class="widefat" name="<?php echo $this->get_field_name('excerpt'); ?>">
				<option value="first" <?php if ($instance['excerpt'] == "first") { echo "selected='selected'"; } ?>><?php _e('Excerpt for first Page', 'mh-newsdesk') ?></option>
				<option value="all" <?php if ($instance['excerpt'] == "all") { echo "selected='selected'"; } ?>><?php _e('Excerpt for all Pages', 'mh-newsdesk') ?></option>
				<option value="none" <?php if ($instance['excerpt'] == "none") { echo "selected='selected'"; } ?>><?php _e('Display no Excerpts', 'mh-newsdesk') ?></option>
			</select>
        </p><?php
    }
}

?>