<?php

add_shortcode( 'mbs_next', 'mbds_shortcode_next' );
add_shortcode( 'mbs_prev', 'mbds_shortcode_prev' );
add_shortcode( 'mbs_summary', 'mbds_shortcode_summary' );
add_shortcode( 'mbs_cover', 'mbds_shortcode_cover' );
add_shortcode( 'mbs_toc', 'mbds_shortcode_toc' );
add_shortcode( 'mbs_toc_link', 'mbds_shortcode_toc_link' );
//jmihalik customization
add_shortcode( 'mbs_storyname_link', 'mbds_shortcode_storyname_link' );
add_shortcode( 'mbs_copyright_notice', 'mbds_shortcode_copyright_notice' );
add_shortcode( 'mbs_cover_link', 'mbds_shortcode_cover_link' );
add_shortcode( 'mbs_stories_list', 'mbds_shortcode_stories_list' );
add_shortcode( 'mbs_story_top_link', 'mbds_shortcode_story_top_link' );
add_shortcode( 'mbs_story_footer', 'mbds_shortcode_story_footer' );


function mbds_get_storyID( $story ) {
	global $post;
	if ( $story == '' ) {
		return get_post_meta( $post->ID, '_mbds_story', true );
	} else {
		return mbds_get_storyID_by_slug( $story );
	}

}

function mbds_output_summary( $display, $postID ) {
	if ( $display == 'yes' ) {
		$summary = get_post_meta( $postID, '_mbds_summary', true );
		if ( $summary != '' ) {
			return ': ' . preg_replace( '/\\n/', '<br>', $summary );
		} else {
			return '';
		}
	}
}

function mbds_next_prev( $nextprev, $text, $story, $summary ) {
	global $post;
	$html_output = '';
	$storyID     = mbds_get_storyID( $story );
	$mbds_story  = mbds_get_story( $storyID );
	if ( $storyID == '' ) {
		return $html_output;
	}
	$posts = mbds_get_posts_list( $storyID );
	$found = null;
	foreach ( $posts as $one_post ) {
		if ( $one_post['ID'] == $post->ID ) {
			$found = $one_post['order'];
		}
	}

	if ( $found !== null ) {

		if ( $nextprev == 'next' ) {
			// make sure not last item if next
			$found ++;
			if ( $found >= count( $posts ) ) {
				$found = null;
			}
		}
		if ( $nextprev == 'prev' ) {
			// make sure no the first tiem
			if ( $found == 0 ) {
				$found = null;
			} else {
				$found = $found - 1;
			}
		}
		if ( $found !== null ) {
			$html_output .= '<div class="mbs_' . $nextprev . '">' . $text . ': <a href="' . $posts[ $found ]['link'] . '">';
			if ( isset( $mbds_story['_mbds_include_posts_name'] ) ) {
				$html_output .= '<span class="mbs_' . $nextprev . '_posts_name">' . mbds_display_posts_name( $mbds_story, $posts[ $found ]['ID'] ) . ': </span>';
			}
			//jmihalik customization - remove story name from next/prev link
			$html_output .= str_replace( $mbds_story['title'] . ':', '', $posts[ $found ]['title'] ) . '</a>';
			$html_output .= mbds_output_summary( $summary, $posts[ $found ]['ID'] );
			$html_output .= '</div>';
		}
	}
	//jmihalik customization - curly quotes
	return wptexturize( $html_output );
		
}


function mbds_shortcode_next( $attr, $content ) {
	$attr = shortcode_atts( array(
		'summary' => 'no',
		'story'   => '',
	), $attr );

	return apply_filters( 'mbds_next_shortcode', mbds_next_prev( 'next', __( 'Next', 'mooberry-story' ), $attr['story'], $attr['summary'] ) );
}

function mbds_shortcode_prev( $attr, $content ) {
	$attr = shortcode_atts( array(
		'summary' => 'no',
		'story'   => '',
	), $attr );

	return apply_filters( 'mbds_prev_shortcode', mbds_next_prev( 'prev', __( 'Previous', 'mooberry-story' ), $attr['story'], $attr['summary'] ) );
}

function mbds_shortcode_summary( $attr, $content ) {
	$attr       = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID    = mbds_get_storyID( $attr['story'] );
	$mbds_story = mbds_get_story( $storyID );

	$html_output = '<div class="mbs_story_summary">';
	if ( isset( $mbds_story['_mbds_summary'] ) ) {
		//jmihalik customization - Use wpautop rather than search/replace
		$html_output .= wpautop( $mbds_story['_mbds_summary'] );
	}
	$html_output .= '</div>';
		
	//jmihalik customization - curly quotes
	return apply_filters( 'mbds_summary_shortcode', wptexturize( $html_output ) );
}


function mbds_shortcode_cover( $attr, $content ) {
	$attr        = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID     = mbds_get_storyID( $attr['story'] );
	$mbds_story  = mbds_get_story( $storyID );
	$html_output = '';
	if ( isset( $mbds_story['_mbds_cover'] ) ) {
		$html_output = '<img class="mbs_cover_image" src="' . $mbds_story['_mbds_cover'] . '">';
	}

	return apply_filters( 'mbds_cover_shortcode', $html_output );
}

//jmihalik customization - add shortcode to get link, used for open graph tags
function mbds_shortcode_cover_link( $attr, $content) {
	$attr = shortcode_atts(array('story' => '', 'story_id' => ''), $attr);
	if ($attr['story_id'] != '') {
		$storyID = $attr['story_id'];
	}else {
		$storyID = mbds_get_storyID($attr['story']);
	}
	$mbds_story = mbds_get_story($storyID);
	$link = '';
	if (isset($mbds_story['_mbds_cover'])) {
		$link = $mbds_story['_mbds_cover'];
	}
	return apply_filters('mbds_cover_shortcode_link', $link);
}

//jmihalik customization - change to fieldset, add options to show/hide metadata and word count
function mbds_shortcode_toc( $attr, $content ) {
	$attr             = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID          = mbds_get_storyID( $attr['story'] );
	$mbds_story       = mbds_get_story( $storyID );
	$story_post_meta  = get_post_meta( $storyID );
	$include_metadata = isset( $story_post_meta['_mbds_include_metadata'] );
	$show_metadata_wc = isset( $story_post_meta['_mbds_show_metadata_wc'][0] ) ? $story_post_meta['_mbds_show_metadata_wc'][0] == 'yes' : true;
	$show_wordcount   = isset( $story_post_meta['_mbds_show_wordcount'][0] ) ? $story_post_meta['_mbds_show_wordcount'][0] == 'yes' : true;
	$html_output      = '';

	//jmihalik customization - option for metadata to be hidden
	if ( $include_metadata ) {
		$html_output .= '<div class="mbs_meta">';
		//$series = get_the_terms( $storyID, 'mbds_series');
		if ( is_array( $mbds_story['series'] ) && count( $mbds_story['series'] ) > 0 ) {
			$html_output .= '<div class="mbs_meta_series"><span class="mbs_meta_label mbs_meta_series_label">' . __( 'Series:', 'mooberry-story' ) . '</span> <span class="mbs_meta_value mbs_meta_series">' . get_the_term_list( $storyID, 'mbds_series', '', ', ' ) . '</span></div>';
		}
		//$genres = get_the_terms( $storyID, 'mbds_genre');
		if ( is_array( $mbds_story['genres'] ) && count( $mbds_story['genres'] ) > 0 ) {
			$html_output .= '<div class="mbs_meta_genre"><span class="mbs_meta_label mbs_meta_genre_label">' . _n( 'Genre:', 'Genres:', count( $mbds_story['genres'] ), 'mooberry-story' ) . '</span> <span class="mbs_meta_value mbs_meta_genre">' . get_the_term_list( $storyID, 'mbds_genre', '', ', ' ) . '</div>';
		}
		$complete    = isset( $mbds_story['_mbds_complete'] ) ? 'Yes' : 'No';
		$html_output .= '<div class="mbs_meta_complete"><span class="mbs_meta_label mbs_meta_complete_label">' . __( 'Completed:', 'mooberry-story' ) . '</span> <span class="mbs_meta_value mbs_meta_complete">' . $complete . '</span></div>';

		if ( $show_metadata_wc ) {
			$total_word_count = mbds_get_story_word_count( $storyID );
			$html_output      .= '<div class="mbs_meta_word_count"><span class="mbs_meta_label mbs_meta_word_count_label">' . __( 'Word Count:', 'mooberry-story' ) . '</span> <span class="mbs_meta_value mbs_meta_word_count">' . $total_word_count . '</span></div>';
		}

		$html_output .= '</div>'; //mbs_meta_series
	}
	$html_output .= '<fieldset class="mbs_toc" id="story_toc"><legend class="mbs_toc_title">' .  __('Table of Contents', 'mooberry-story') . '</legend>';
	$html_output .= '<ul class="mbs_toc_list">';
	$posts = mbds_get_posts_list( $storyID );
	foreach ( $posts as $each_post ) {
		$alt_title = get_post_meta( $each_post['ID'], '_mbds_alt_chapter_title', true );
		if ( $alt_title != '' ) {
			$post['title'] = $alt_title;
		}
		
		$html_output .= '<li><a href="' . $each_post['link'] . '">';
		if ( isset( $mbds_story['_mbds_include_posts_name'] ) ) {
			$html_output .= '<span class="mbs_toc_item_posts_name">' . mbds_display_posts_name( $mbds_story, $each_post['ID'] ) . ': </span>';
		}
		//jmihalik customization - remove story title from links
		$html_output .= '<span class="mbs_toc_item_title">'; 
		$html_output .= str_replace( $mbds_story['title'] . ':', '', $each_post['title'] );
		$html_output .= '</span></a>';
		
		//jmihalik customization - option for word count to be hidden
		if ( $show_wordcount ) {
			$html_output .= ' <span class="mbs_toc_item_word_count">(' . mbds_get_word_count( get_post_field( 'post_content', $each_post['ID'] ) ) . ' words)</span>';
		}

		$html_output .= '</li>';
	}
	$html_output .= '</ul>';
	$html_output .= '</fieldset>';
	//jmihalik customization - curly quotes
	return apply_filters( 'mbds_toc_shortcode', wptexturize( $html_output ) );
}

function mbds_shortcode_toc_link( $attr, $content ) {
	$attr        = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID     = mbds_get_storyID( $attr['story'] );
	$html_output = '<a class="mbs_toc_link" href="' . get_permalink( $storyID ) . '">' . __( 'Table of Contents', 'mooberry-story' ) . '</a>';

	return apply_filters( 'mbds_toc_link_shortcode', $html_output );
}

//jmihalik customization
function mbds_shortcode_storyname_link( $attr, $content ) {
	$attr        = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID     = mbds_get_storyID( $attr['story'] );
	$mbds_story  = mbds_get_story( $storyID );
	$html_output = '<a class="mbs_storyname_link" href="' . get_permalink( $storyID ) . '">' . __( $mbds_story['title'], 'mooberry-story' ) . '</a>';
	//jmihalik customization - curly quotes
	return apply_filters( 'mbds_storyname_link_shortcode', wptexturize( $html_output ) );
}

function mbds_shortcode_copyright_notice( $attr, $content ) {
	$attr        = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID     = mbds_get_storyID( $attr['story'] );
	$mbds_story  = mbds_get_story( $storyID );
	$html_output = '';
	if ( isset( $mbds_story['_mbds_include_copyright'] ) ) {
		$html_output .= '<div class="mbs_copyright_notice">Copyright &copy; ' . date("Y") . ' by ';
		if ( $author = $mbds_story['_mbds_copyright_author'] ) {
			$html_output .= $author;
		}else {
			$html_output .= get_the_author();
		}
		$html_output .= '. All rights reserved. No part of this material may be reproduced without permission.';
		$html_output .= '</div>';
			
	}

	return apply_filters( 'mbds_copyright_notice_shortcode', wptexturize( $html_output ) );
}

function mbds_shortcode_stories_list( $attr, $content ) {
	$stories = mbds_get_stories( 'all', null, null, null );
	$html_output = '';
	if ( $stories != null ) {
		$html_output .= '<ul class="mbs_stories_list">';
		foreach ( $stories as $story ) {
			$html_output .= '<li><a href="' . get_the_permalink( $story->ID ) . '">' . $story->post_title . '</a></li>';
		}
		$html_output .= '</ul>';
	} else {
		$html_output .= '<span class="mbs_stories_list_none">';
		$html_output .= __( 'No stories found', 'mooberry-story' );
		$html_output .= '</span>';
	}

	return apply_filters( 'mbds_stories_list_shortcode', wptexturize( $html_output ) );
}

function mbds_shortcode_story_top_link( $attr, $content ) {
	$attr        = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID     = mbds_get_storyID( $attr['story'] );
	$mbds_story  = mbds_get_story( $storyID );
	$html_output = '';
	if ( isset( $mbds_story['_mbds_include_story_top_link'] ) ) {
		$html_output .= '<div class="mbs_post_link_top">Part of the serial story ';
		$html_output .= '<a class="mbs_storyname_link" href="' . get_permalink( $storyID ) . '">' . __( $mbds_story['title'], 'mooberry-story' ) . '</a>';
		$html_output .= '</div>';
	}

	return apply_filters( 'mbds_story_top_link_shortcode', wptexturize( $html_output ) );
}

function mbds_shortcode_story_footer( $attr, $content ) {
	$attr        = shortcode_atts( array( 'story' => '' ), $attr );
	$storyID     = mbds_get_storyID( $attr['story'] );
	$mbds_story  = mbds_get_story( $storyID );
	$html_output = '';
	if ( isset( $mbds_story['_mbds_include_story_footer'] ) ) {
		$html_output .= '<div class="mbs_story_footer"><div class="mbs_story_footer_top_border"></div>';
		if ( $footer_content = $mbds_story['_mbds_story_footer'] ) {
			$html_output .= wpautop( $footer_content );
		}
		$html_output .= '</div>';
	}

	return apply_filters( 'mbds_story_footer_shortcode', wptexturize( $html_output ) );
}
//end customization
