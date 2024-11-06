<?php

class mbds_Story_Widget extends WP_Widget {
	function __construct() {
		 parent::__construct(
			 
			// base ID of the widget
			'mbds_story_widget',
			 
			// name of the widget
			__('Mooberry Story - Stories List', 'mooberry-story' ),
			 
			// widget options
			array (
				'description' => __( 'Displays a list of stories.', 'mooberry-story' ),
				'classname' => 'mbds_Story_Widget',
			)
			 
		);
	}
	 
	function form( $instance ) {
	
	 	if ($instance) {
			$mbds_sw_stories = $instance['mbds_sw_stories'];
			$mbds_sw_title = $instance['mbds_sw_title'];
			$mbds_sw_genre = $instance['mbds_sw_genre'];
			$mbds_sw_series = $instance['mbds_sw_series'];
		} else {
			$mbds_sw_stories = 'all';
			$mbds_sw_title = '';
			$mbds_sw_genre = '';
			$mbds_sw_series = '';
		}
		
		include dirname( __FILE__ ) . '/views/widget-stories.php';
	}
	 
	function update( $new_instance, $old_instance ) {       
		$instance = $old_instance;
		$instance['mbds_sw_stories'] = strip_tags( $new_instance['mbds_sw_stories']);
		$instance['mbds_sw_title'] = strip_tags($new_instance['mbds_sw_title']);
		$instance['mbds_sw_genre'] = strip_tags($new_instance['mbds_sw_genre']);
		$instance['mbds_sw_series'] = strip_tags($new_instance['mbds_sw_series']);
		return $instance;
	}
	 
	//jmihalik customization = change echos to $html_output so the whole thing can be texturized for curly quotes
	function widget( $args, $instance ) {
		global $wp_query, $post;

		extract( $args );
		$html_output = '';
		$html_output .= $before_widget;        
		$html_output .= $before_title . strip_tags($instance['mbds_sw_title']) . $after_title; 
	
		// get list of stories
		// jmihalik customization - add current and archived story options
		switch ($instance['mbds_sw_stories']) {
			case 'all':
				$stories = mbds_get_stories('all', null, null, null);
				break;
			case 'current':
				$stories = mbds_get_stories('current', null, null, null);
				break;
			case 'archived':
				$stories = mbds_get_stories('archived', null, null, null);
				break;
			case 'complete':
				$stories = mbds_get_stories('all', 'complete', null, null);
				break;
			case 'incomplete':
				$stories = mbds_get_stories('all', 'incomplete', null, null);
				break;
			case 'recent':
				$stories = mbds_get_stories('recent', null, null, null);
				break;
			case 'series':
				$stories = mbds_get_stories('all', null, $instance['mbds_sw_series'], null);
				break;
			case 'genre':
				$stories = mbds_get_stories('all', null, null, $instance['mbds_sw_genre']);
				break;
		}
		if ($stories != null) {
			$html_output .= '<ul class="mbs_story_widget_list">';
			foreach ($stories as $story) {
				$html_output .= '<li><a href="' . get_the_permalink($story->ID) . '">' . $story->post_title . '</a></li>';
			}
			$html_output .= '</ul>';
		} else {
			$html_output .= '<span class="mbs_story_widget_none">';
			$html_output .= __('No stories found', 'mooberry-story');
			$html_output .= '</span>';
		}


		if ( ( $wp_query->is_single ) && $series = get_post_meta($post->ID, '_mbds_story', true) ) {
			$posts = mbds_get_posts_list($series);
			if ($posts != null) {
				$selected_story = mbds_get_story($series);
				$html_output .= '<h2 class="widget-title" style="padding-top: 3rem;">' . $selected_story['title'];
				$html_output .= ' ' . mbds_get_story_post_name($selected_story['ID'], 'plural') . '</h2>';
				$html_output .= '<ul class="mbs_posts_widget_list">';
			
				foreach ($posts as $post) {
					//jmihalik customization - remove story name from link title
					$html_output .= '<li><a href="' . $post['link'] . '">';
					$html_output .= str_replace($selected_story['title'] . ':', '', $post['title']);
					$html_output .= '</a></li>';
				}
				$html_output .= '</ul>';
			} 
		}

		$html_output .= $after_widget;
		echo wptexturize($html_output);
		 
	} 
}