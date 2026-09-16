<?php

/***** Authors Widget *****/

class mh_newsdesk_authors extends WP_Widget {
    function __construct() {
		parent::__construct(
			'mh_newsdesk_authors', esc_html_x('MH Authors Widget', 'widget name', 'mh-newsdesk'),
			array('classname' => 'mh_newsdesk_authors', 'description' => esc_html__('MH Authors widget to display a list of authors including the number of published posts.', 'mh-newsdesk'))
		);
	}
    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $authorcount = empty($instance['authorcount']) ? '5' : $instance['authorcount'];
        $offset = empty($instance['offset']) ? '' : $instance['offset'];
        $role = isset($instance['role']) ? $instance['role'] : '';
        $orderby = isset($instance['orderby']) ? $instance['orderby'] : 'post_count';
        $order = isset($instance['order']) ? $instance['order'] : 'DESC';

        echo $before_widget;
        if (!empty($title)) { echo $before_title . $title . $after_title; } ?>
        <ul class="user-widget widget-list row clearfix"><?php
		$args = array('number' => $authorcount, 'offset' => $offset, 'role' => $role, 'orderby' => $orderby, 'order' => $order);
        $wp_user_query = new WP_User_Query($args);
        $authors = $wp_user_query->get_results();
		if (!empty($authors)) {
			foreach ($authors as $author) {
			$author_ID = $author->ID; ?>
			<li class="uw-wrap clearfix">
				<div class="uw-avatar"><a href="<?php echo get_author_posts_url($author_ID); ?>" title="<?php printf(__('Articles by %s', 'mh-newsdesk'), $author->display_name); ?>"><?php echo get_avatar($author_ID, 64); ?></a></div>
				<div class="uw-text">
					<a href="<?php echo get_author_posts_url($author_ID); ?>" title="<?php printf(__('Articles by %s', 'mh-newsdesk'), $author->display_name); ?>" class="author-name"><?php echo $author->display_name; ?></a>
					<p class="uw-data"><?php printf(_x('published %d articles', 'author post count', 'mh-newsdesk'), count_user_posts($author_ID)); ?></p>
				</div>
			</li><?php
			}
		} else {
			_e('No authors found', 'mh-newsdesk');
		} ?>
		</ul><?php
        echo $after_widget;
    }
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['authorcount'] = absint($new_instance['authorcount']);
        $instance['offset'] = absint($new_instance['offset']);
        $instance['role'] = strip_tags($new_instance['role']);
        $instance['orderby'] = strip_tags($new_instance['orderby']);
        $instance['order'] = strip_tags($new_instance['order']);
        return $instance;
    }
    function form($instance) {
        $defaults = array('title' => __('Authors', 'mh-newsdesk'), 'authorcount' => '5', 'offset' => '0', 'role' => '', 'orderby' => 'post_count', 'order' => 'DESC');
        $instance = wp_parse_args((array) $instance, $defaults); ?>

        <p>
        	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['title']); ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" />
        </p>
        <p>
        	<label for="<?php echo $this->get_field_id('authorcount'); ?>"><?php _e('Limit Author Number:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['authorcount']); ?>" name="<?php echo $this->get_field_name('authorcount'); ?>" id="<?php echo $this->get_field_id('authorcount'); ?>" />
	    </p>
	    <p>
        	<label for="<?php echo $this->get_field_id('offset'); ?>"><?php _e('Skip Authors (Offset):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['offset']); ?>" name="<?php echo $this->get_field_name('offset'); ?>" id="<?php echo $this->get_field_id('offset'); ?>" />
	    </p>
	    <p>
	    	<label for="<?php echo $this->get_field_id('role'); ?>"><?php _e('User Role:', 'mh-newsdesk'); ?></label>
			<select id="<?php echo $this->get_field_id('role'); ?>" class="widefat" name="<?php echo $this->get_field_name('role'); ?>">
				<option value="" <?php if ($instance['role'] == "") { echo "selected='selected'"; } ?>><?php _e('All Users', 'mh-newsdesk') ?></option>
				<option value="administrator" <?php if ($instance['role'] == "administrator") { echo "selected='selected'"; } ?>><?php _e('Administrator', 'mh-newsdesk') ?></option>
				<option value="editor" <?php if ($instance['role'] == "editor") { echo "selected='selected'"; } ?>><?php _e('Editor', 'mh-newsdesk') ?></option>
				<option value="author" <?php if ($instance['role'] == "author") { echo "selected='selected'"; } ?>><?php _e('Author', 'mh-newsdesk') ?></option>
				<option value="contributor" <?php if ($instance['role'] == "contributor") { echo "selected='selected'"; } ?>><?php _e('Contributor', 'mh-newsdesk') ?></option>
				<option value="subscriber" <?php if ($instance['role'] == "subscriber") { echo "selected='selected'"; } ?>><?php _e('Subscriber', 'mh-newsdesk') ?></option>
			</select>
        </p>
        <p>
	    	<label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('Order Authors by:', 'mh-newsdesk'); ?></label>
			<select id="<?php echo $this->get_field_id('orderby'); ?>" class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
				<option value="post_count" <?php if ($instance['orderby'] == "post_count") { echo "selected='selected'"; } ?>><?php _e('Number of Posts', 'mh-newsdesk') ?></option>
				<option value="display_name" <?php if ($instance['orderby'] == "display_name") { echo "selected='selected'"; } ?>><?php _e('User Name', 'mh-newsdesk') ?></option>
			</select>
        </p>
        <p>
	    	<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Order:', 'mh-newsdesk'); ?></label>
			<select id="<?php echo $this->get_field_id('order'); ?>" class="widefat" name="<?php echo $this->get_field_name('order'); ?>">
				<option value="ASC" <?php if ($instance['order'] == "ASC") { echo "selected='selected'"; } ?>><?php _e('Ascending', 'mh-newsdesk') ?></option>
				<option value="DESC" <?php if ($instance['order'] == "DESC") { echo "selected='selected'"; } ?>><?php _e('Descending', 'mh-newsdesk') ?></option>
			</select>
        </p><?php
    }
}

?>