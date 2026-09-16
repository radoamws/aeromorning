<?php

/***** MH Posts List (Homepage) Widget *****/

class mh_newsdesk_posts_list extends WP_Widget {
    function __construct() {
		parent::__construct(
			'mh_newsdesk_posts_list', esc_html_x('MH Posts List (Homepage)', 'widget name', 'mh-newsdesk'),
			array('classname' => 'mh_newsdesk_posts_list', 'description' => esc_html__('Display a list of posts on front page for use in "Home 1" or "Home 4" widget areas.', 'mh-newsdesk'))
		);
	}
    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $category = isset($instance['category']) ? $instance['category'] : '';
        $cats = empty($instance['cats']) ? '' : $instance['cats'];
        $tags = empty($instance['tags']) ? '' : $instance['tags'];
        $postcount = empty($instance['postcount']) ? '5' : $instance['postcount'];
        $offset = empty($instance['offset']) ? '' : $instance['offset'];
        $order = isset($instance['order']) ? $instance['order'] : 'date';
        $link = empty($instance['link']) ? '' : $instance['link'];
        $sticky = isset($instance['sticky']) ? $instance['sticky'] : 1;

        if ($link) {
	        $before_title = $before_title . '<a href="' . esc_url($link) . '" class="widget-title-link">';
	        $after_title = '</a>' . $after_title;
        } elseif ($category) {
        	$cat_url = get_category_link($category);
	        $before_title = $before_title . '<a href="' . esc_url($cat_url) . '" class="widget-title-link">';
	        $after_title = '</a>' . $after_title;
        }

        if ($cats) {
	    	$category = $category . ', ' . $cats;
        }

        echo $before_widget;
        if (!empty($title)) { echo $before_title . esc_attr($title) . $after_title; }
		$args = array('posts_per_page' => $postcount, 'offset' => $offset, 'cat' => $category, 'tag' => $tags, 'orderby' => $order, 'ignore_sticky_posts' => $sticky);
		$widget_loop = new WP_Query($args); ?>
		<div class="mh-fp-list-widget clearfix"><?php
			while ($widget_loop->have_posts()) : $widget_loop->the_post();
				get_template_part('content');
			endwhile;
			wp_reset_postdata(); ?>
		</div><?php
		echo $after_widget;
    }
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['category'] = absint($new_instance['category']);
        $instance['cats'] = strip_tags($new_instance['cats']);
        $instance['tags'] = strip_tags($new_instance['tags']);
        $instance['postcount'] = absint($new_instance['postcount']);
        $instance['offset'] = absint($new_instance['offset']);
        $instance['order'] = strip_tags($new_instance['order']);
        $instance['link'] = esc_url_raw($new_instance['link']);
        $instance['sticky'] = isset($new_instance['sticky']) ? strip_tags($new_instance['sticky']) : '';
        return $instance;
    }
    function form($instance) {
        $defaults = array('title' => '', 'category' => '', 'cats' => '', 'tags' => '', 'postcount' => '5', 'offset' => '0', 'order' => 'date', 'link' => '', 'sticky' => 1);
        $instance = wp_parse_args((array) $instance, $defaults); ?>

        <p>
        	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['title']); ?>" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" />
        </p>
        <p>
			<label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Select a Category:', 'mh-newsdesk'); ?></label>
			<select id="<?php echo $this->get_field_id('category'); ?>" class="widefat" name="<?php echo $this->get_field_name('category'); ?>">
				<option value="0" <?php if (!$instance['category']) echo 'selected="selected"'; ?>><?php _e('All', 'mh-newsdesk'); ?></option>
				<?php
				$categories = get_categories(array('type' => 'post'));
				foreach($categories as $cat) {
					echo '<option value="' . $cat->cat_ID . '"';
					if ($cat->cat_ID == $instance['category']) { echo ' selected="selected"'; }
					echo '>' . $cat->cat_name . ' (' . $cat->category_count . ')';
					echo '</option>';
				}
				?>
			</select>
		</p>
		<p>
        	<label for="<?php echo $this->get_field_id('cats'); ?>"><?php _e('Multiple Categories Filter by ID:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['cats']); ?>" name="<?php echo $this->get_field_name('cats'); ?>" id="<?php echo $this->get_field_id('cats'); ?>" />
	    </p>
		<p>
        	<label for="<?php echo $this->get_field_id('tags'); ?>"><?php _e('Filter Posts by Tags (e.g. lifestyle):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['tags']); ?>" name="<?php echo $this->get_field_name('tags'); ?>" id="<?php echo $this->get_field_id('tags'); ?>" />
	    </p>
	    <p>
        	<label for="<?php echo $this->get_field_id('postcount'); ?>"><?php _e('Limit Post Number:', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['postcount']); ?>" name="<?php echo $this->get_field_name('postcount'); ?>" id="<?php echo $this->get_field_id('postcount'); ?>" />
	    </p>
	    <p>
        	<label for="<?php echo $this->get_field_id('offset'); ?>"><?php _e('Skip Posts (Offset):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_attr($instance['offset']); ?>" name="<?php echo $this->get_field_name('offset'); ?>" id="<?php echo $this->get_field_id('offset'); ?>" />
	    </p>
	    <p>
	    	<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Post Order:', 'mh-newsdesk'); ?></label>
			<select id="<?php echo $this->get_field_id('order'); ?>" class="widefat" name="<?php echo $this->get_field_name('order'); ?>">
				<option value="date" <?php if ($instance['order'] == "date") { echo "selected='selected'"; } ?>><?php _e('Latest Posts', 'mh-newsdesk') ?></option>
				<option value="rand" <?php if ($instance['order'] == "rand") { echo "selected='selected'"; } ?>><?php _e('Random Posts', 'mh-newsdesk') ?></option>
				<option value="comment_count" <?php if ($instance['order'] == "comment_count") { echo "selected='selected'"; } ?>><?php _e('Popular Posts', 'mh-newsdesk') ?></option>
			</select>
        </p>
	    <p>
        	<label for="<?php echo $this->get_field_id('link'); ?>"><?php _e('Link Title to custom URL (optional):', 'mh-newsdesk'); ?></label>
			<input class="widefat" type="text" value="<?php echo esc_url($instance['link']); ?>" name="<?php echo $this->get_field_name('link'); ?>" id="<?php echo $this->get_field_id('link'); ?>" />
        </p>
        <p>
      		<input id="<?php echo $this->get_field_id('sticky'); ?>" name="<?php echo $this->get_field_name('sticky'); ?>" type="checkbox" value="1" <?php checked('1', $instance['sticky']); ?>/>
	  		<label for="<?php echo $this->get_field_id('sticky'); ?>"><?php _e('Ignore Sticky Posts', 'mh-newsdesk'); ?></label>
    	</p><?php
    }
}
?>