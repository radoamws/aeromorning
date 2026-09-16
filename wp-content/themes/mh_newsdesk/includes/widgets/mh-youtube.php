<?php

/***** MH YouTube Video Widget *****/

class mh_newsdesk_youtube extends WP_Widget {
    function __construct() {
		parent::__construct(
			'mh_newsdesk_youtube', esc_html_x('MH YouTube Video', 'widget name', 'mh-newsdesk'),
			array('classname' => 'mh_newsdesk_youtube', 'description' => esc_html__('Widget to display a YouTube Video.', 'mh-newsdesk'))
		);
	}
    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$video_id = empty($instance['video_id']) ? '' : $instance['video_id'];

        echo $before_widget;
        if (!empty( $title)) { echo $before_title . esc_attr($title) . $after_title; }
		echo '<div class="mh-video-widget">';
			echo '<div class="mh-video-container">';
				echo '<iframe seamless width="1200" height="675" src="//www.youtube.com/embed/' . esc_attr($instance['video_id']) . '?rel=0&amp;controls=2&amp;hd=1&amp;autoplay=0"></iframe>';
			echo '</div>';
		echo '</div>';
        echo $after_widget;
    }
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['video_id'] = strip_tags($new_instance['video_id']);
        return $instance;
    }
    function form($instance) {
        $defaults = array('title' => '', 'video_id' => '');
        $instance = wp_parse_args((array) $instance, $defaults); ?>

        <p>
        	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['title']); ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" />
        </p>
	    <p>
        	<label for="<?php echo $this->get_field_id('video_id'); ?>"><?php _e('Video ID of your Featured Video:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['video_id']); ?>" name="<?php echo $this->get_field_name('video_id'); ?>" id="<?php echo $this->get_field_id('video_id'); ?>" />
			<small><?php _e('Also known as the Watch Code (Eg: fQydqPWdjoc)', 'mh-newsdesk'); ?></small>
        </p><?php
    }
}
?>