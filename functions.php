<?php

/**
 * Plugin support functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Register the activity actions for Humanities CORE.
 */
function humcore_deposits_register_activity_actions() {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}
	$bp = buddypress();
	bp_activity_set_action(
		$bp->humcore_deposits->id,
		'new_deposit',
		__( 'New Deposits', 'humcore_domain' ),
		'humcore_format_activity_action_new_deposit',
		__( 'New Deposits', 'humcore_domain' ),
		array( 'activity', 'member', 'groups' )
	);
	bp_activity_set_action(
		$bp->groups->id,
		'new_group_deposit',
		__( 'New Group Deposits', 'humcore_domain' ),
		'humcore_format_activity_action_new_group_deposit',
		__( 'New Group Deposits', 'humcore_domain' ),
		array( 'member_groups', 'groups' )
	);
	do_action( 'humcore_deposits_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'humcore_deposits_register_activity_actions' );

/**
 * Format 'new_deposit' activity action.
 *
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function humcore_format_activity_action_new_deposit( $action, $activity ) {

	$deposit_blog_id = bp_activity_get_meta( $activity->id, 'source_blog_id', true  );
	$switched = false;
	if ( $deposit_blog_id !== get_current_blog_id() ) {
		switch_to_blog( $deposit_blog_id );
		$switched = true;
	}

	$item_post = get_post( $activity->secondary_item_id );
	$item_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $activity->primary_link ), $item_post->post_title );
	$post_metadata = json_decode( get_post_meta( $activity->secondary_item_id, '_deposit_metadata', true ), true );
	if ( ! empty( $post_metadata['committee_id'] ) ) {
		$committee = groups_get_group( array( 'group_id' => $post_metadata['committee_id'] ) );
		$initiator_url = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $committee->slug . '/' );
		$initiator_name = $committee->name;
		$initiator_link = sprintf( '<a href="%1$sdeposits/">%2$s</a>', esc_url( $initiator_url ), $initiator_name );
	} else {
		$initiator_url = bp_core_get_userlink( $activity->user_id, false, true );
		$initiator_name = bp_core_get_userlink( $activity->user_id, true, false );
		$initiator_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $initiator_url ), $initiator_name );
	}
	$action = sprintf( __( '%1$s deposited %2$s', 'humcore_domain' ), $initiator_link, $item_link );

	if ( $switched ) {
		restore_current_blog();
	}

	return apply_filters( 'humcore_format_activity_action_new_deposit', $action, $activity );

}

/**
 * Format 'new_group_deposit' activity action.
 *
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function humcore_format_activity_action_new_group_deposit( $action, $activity ) {

	$deposit_blog_id = bp_activity_get_meta( $activity->id, 'source_blog_id', true  );
	$switched = false;
	if ( $deposit_blog_id !== get_current_blog_id() ) {
		switch_to_blog( $deposit_blog_id );
		$switched = true;
	}

	$item_post = get_post( $activity->secondary_item_id );
	$item_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $activity->primary_link ), $item_post->post_title );
	$post_metadata = json_decode( get_post_meta( $activity->secondary_item_id, '_deposit_metadata', true ), true );
	if ( ! empty( $post_metadata['committee_id'] ) && $wpmn_record_identifier[0] == get_current_blog_id() ) {
		$committee = groups_get_group( array( 'group_id' => $post_metadata['committee_id'] ) );
		$initiator_url = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $committee->slug . '/' );
		$initiator_name = $committee->name;
		$initiator_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $initiator_url ), $initiator_name );
	} else {
		$initiator_url = bp_core_get_userlink( $activity->user_id, false, true );
		$initiator_name = bp_core_get_userlink( $activity->user_id, true, false );
		$initiator_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $initiator_url ), $initiator_name );
	}
	$group = groups_get_group( array( 'group_id' => $activity->item_id ) );
	$group_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( bp_get_group_permalink( $group ) ), $group->name );
	$action = sprintf( __( '%1$s deposited %2$s in the group %3$s', 'humcore_domain' ), $initiator_link, $item_link, $group_link );

	if ( $switched ) {
		restore_current_blog();
	}

	return apply_filters( 'humcore_format_activity_action_new_group_deposit', $action, $activity );
}

/**
 * Format 'deposit_review' notification type.
 *
 * @param object $notification Notification data.
 * @return string $return Formatted notification display.
 */
function humcore_format_deposit_review_notification( $action, $item_id, $secondary_item_id, $total_items, $format = 'string',
		 $component_action_name, $component_name, $notification_id ) {

	if ( 'deposit_review' !== $component_action_name ) {
		return $action;
	} else {
		$society_id = bp_notifications_get_meta( $notification_id, 'society_id', true );
		$notification_blog_id = constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );
		$switched = false;
		if ( $notification_blog_id !== get_current_blog_id() ) {
			switch_to_blog( $notification_blog_id );
			$switched = true;
		}

		$deposit_post = get_post( $item_id );
		$post_metadata = json_decode( get_post_meta( $item_id, '_deposit_metadata', true ), true );
		$author_data = get_user_by( 'id', $deposit_post->post_author );
		$pid = $post_metadata['pid'];

		$custom_title = 'Review required: ' . $author_data->display_name . ' deposited a new '  . $post_metadata['genre'];
		$custom_link  = esc_url( sprintf(  HC_SITE_URL . '/deposits/item/%s/', $post_metadata['pid'] ) );
		$custom_text = 'Review required: ' . $author_data->display_name . ' deposited a new ' . $post_metadata['genre'] . ' titled: ' . $deposit_post->post_title;
		// WordPress Toolbar
		if ( 'string' === $format ) {
			$return = apply_filters( 'humcore_format_deposit_review_notification',
				'<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_title ) . '">' . $custom_text . '</a>',
				$custom_text, $custom_link );
		// Deprecated BuddyBar
		} else {

			$return = apply_filters( 'humcore_format_deposit_review_notification', array(
				'text' => $custom_text,
				'link' => $custom_link
			), $custom_link, (int) $total_items, $custom_text, $custom_title );
		}

		if ( $switched ) {
			restore_current_blog();

		}

		return $return;

	}

}
add_filter( 'bp_notifications_get_notifications_for_user', 'humcore_format_deposit_review_notification', 10, 8 );

/**
 * Format 'deposit_published' notification type.
 *
 * @param object $notification Notification data.
 * @return string $return Formatted notification display.
 */
function humcore_format_deposit_published_notification( $action, $item_id, $secondary_item_id, $total_items, $format = 'string',
                 $component_action_name, $component_name, $notification_id ) {

        if ( 'deposit_published' !== $component_action_name ) {
                return $action;
        } else {
                $society_id = bp_notifications_get_meta( $notification_id, 'society_id', true );
                $notification_blog_id = constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );
                $switched = false;
                if ( $notification_blog_id !== get_current_blog_id() ) {
                        switch_to_blog( $notification_blog_id );
                        $switched = true;
                }

                $deposit_post = get_post( $item_id );
                $post_metadata = json_decode( get_post_meta( $item_id, '_deposit_metadata', true ), true );
                $pid = $post_metadata['pid'];
                $pid = $post_metadata['handle'];

                $custom_title = 'Deposit published! Your recently deposited ' . $post_metadata['genre'] . ' has been published.';
                $custom_link  = esc_url( $post_metadata['handle'] );
                $custom_text = 'Deposit published! We published your recently deposited ' . $post_metadata['genre'] . ' (' . $post_metadata['deposit_doi'] .
			')  titled: ' . $deposit_post->post_title;
                // WordPress Toolbar
                if ( 'string' === $format ) {
                        $return = apply_filters( 'humcore_format_deposit_published_notification',
                                '<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_title ) . '">' . $custom_text . '</a>',
                                $custom_text, $custom_link );
                // Deprecated BuddyBar
                } else {
                        $return = apply_filters( 'humcore_format_deposit_published_notification', array(
                                'text' => $custom_text,
                                'link' => $custom_link
                        ), $custom_link, (int) $total_items, $custom_text, $custom_title );
                }

                if ( $switched ) {
                        restore_current_blog();
                }

                return $return;

        }

}
add_filter( 'bp_notifications_get_notifications_for_user', 'humcore_format_deposit_published_notification', 10, 8 );

/**
 * Add a filter option to the filter select box on group activity pages.
 */
function humcore_activity_action_group_deposit_dropdown() {
?>
		<option value="new_group_deposit"><?php _e( 'New Group Deposits', 'humcore_domain' ) ?></option><?php

}
add_action( 'bp_group_activity_filter_options', 'humcore_activity_action_group_deposit_dropdown' );
add_action( 'bp_member_activity_filter_options', 'humcore_activity_action_group_deposit_dropdown' );
add_action( 'bp_activity_filter_options', 'humcore_activity_action_group_deposit_dropdown' );

/**
 * Create a new deposit activity record.
 */
function humcore_new_deposit_activity( $deposit_id, $deposit_content = '', $deposit_link = '', $user_id = '' ) {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$bp = buddypress();
        if ( empty( $user_id ) ) {
                $user_id = bp_loggedin_user_id();
        }
	$userlink = bp_core_get_userlink( $user_id );
	$activity_ID = bp_activity_add(
		array(
			'user_id' => $user_id,
			'secondary_item_id' => $deposit_id,
			'action' => '',
			'component' => $bp->humcore_deposits->id,
			'content' => $deposit_content,
			'primary_link' => $deposit_link,
			'type' => 'new_deposit',
		)
	);

	// Update the last activity date of the members or committee.
	$post_metadata = json_decode( get_post_meta( $deposit_id, '_deposit_metadata', true ), true );
	if ( ! empty( $post_metadata['committee_id'] ) ) {
		groups_update_last_activity( $post_metadata['committee_id'] );
	} else {
		bp_update_user_last_activity( $user_id );
	}
	bp_activity_add_meta( $activity_ID, 'source_blog_id', get_current_blog_id(), true );

	return $activity_ID;
}

/**
 * Create a new group deposity activity record.
 */
function humcore_new_group_deposit_activity( $deposit_id, $group_id, $deposit_content = '', $deposit_link = '', $user_id = '' ) {

	global $current_site;

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

        $wpmn_record_identifier = array();
        $wpmn_record_identifier = explode( '-', $deposit_id );
        // handle legacy MLA value
        if ( $wpmn_record_identifier[0] === $deposit_id ) {
                $wpmn_record_identifier[0] = '1';
                $wpmn_record_identifier[1] = $deposit_id;
        }
/*
	$group_root_blog_id = '';
	$group_society_id = strtoupper( bp_groups_get_group_type( $group_id ) );
	if ( defined( $group_society_id . '_ROOT_BLOG_ID' ) ) {
		$group_root_blog_id = constant( $group_society_id . '_ROOT_BLOG_ID' );
	}

	$current_network = $current_site->id;
	$switched_network = false;
	$switched_blog = false;
	if ( ! empty( $group_root_blog_id ) && $group_root_blog_id != $current_site->blog_id ) {
		$switched_blog = get_blog_details( (int) $group_root_blog_id, false );
		if ( $current_network != $switched_blog->site_id ) {
			switch_to_network( $switched_blog->site_id );
			$switched_network = true;
		}
		switch_to_blog( $group_root_blog_id );
		$switched_blog = true;
	}
*/
	$bp = buddypress();
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}
	$userlink = bp_core_get_userlink( $user_id );

	$group = groups_get_group( $group_id );

	if ( isset( $group->status ) && 'public' != $group->status ) {
		$hide_sitewide = true;
	} else {
		$hide_sitewide = false;
	}

	$activity_ID = bp_activity_add(
		array(
			'user_id' => $user_id,
			'item_id' => $group_id,
			'secondary_item_id' => $wpmn_record_identifier[1],
			'action' => '',
			'component' => $bp->groups->id,
			'content' => $deposit_content,
			'primary_link' => $deposit_link,
			'type' => 'new_group_deposit',
			'hide_sitewide' => $hide_sitewide,
		)
	);

	bp_activity_add_meta( $activity_ID, 'source_blog_id', $wpmn_record_identifier[0], true );

	// Update the group's last activity
	groups_update_last_activity( $group_id );
/*
        if ( $switched_network ) {
                restore_current_network();
        }
        if ( $switched_blog ) {
                restore_current_blog();
        }
*/
	return $activity_ID;

}

/**
 * Get the post id or parent post id for a post slug.
 */
function humcore_get_deposit_post_id( $post_name ) {

	$args = array(
		'name'           => $post_name,
		'post_type'      => 'humcore_deposit',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
	);

	$deposit_post = get_posts( $args );

	if ( 0 == $deposit_post[0]->post_parent ) {
		return $deposit_post[0]->ID;
	} else {
		return $deposit_post[0]->post_parent;
	}

}

/**
 * Format the page head meta fields.
 */
function humcore_deposit_item_search_meta() {

	while ( humcore_deposits() ) :
		humcore_the_deposit();
	endwhile; // Should fetch one record.
	$metadata = (array) humcore_get_current_deposit();

        printf( '<link rel="canonical" href="%1$s/deposits/item/%2$s/">' . "\n\r", HC_SITE_URL, htmlentities( $metadata['pid'] ) );

	printf( '<meta name="description" content="%1$s">' . "\n\r", htmlentities( $metadata['abstract'] ) );
	printf( '<meta name="citation_title" content="%1$s">' . "\n\r", htmlentities( $metadata['title'] ) );
	printf( '<meta name="citation_publication_date" content="%1$s">' ."\n\r", htmlentities( $metadata['date'] ) ); // Format date yyyy/mm/dd.
	if ( ! empty( $metadata['publisher'] ) ) {
		printf( '<meta name="citation_publisher" content="%1$s">' . "\n\r", htmlentities( $metadata['publisher'] ) );
	}
        $contributors = array_filter( $metadata['authors'] );
        $contributor_uni = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
        $contributor_type = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
        $contributors_list = array_map( null, $contributors, $contributor_uni, $contributor_type );
        $authors_list = array();
        foreach( $contributors_list as $contributor ) {
                if ( 'author' === $contributor[2] || empty( $contributor[2] ) ) {
			printf( '<meta name="citation_author" content="%1$s">' . "\n\r", htmlentities( $contributor[0] ) );
                }
        }

	if ( ! empty( $metadata['genre'] ) && in_array( $metadata['genre'], array( 'Dissertation', 'Thesis' ) ) && ! empty( $metadata['institution'][0] ) ) {
		printf( '<meta name="citation_dissertation_institution" content="%1$s">' . "\n\r", htmlentities( $metadata['institution'][0] ) );
	}
	if ( ! empty( $metadata['genre'] ) && 'Technical report' == $metadata['genre'] && ! empty( $metadata['institution'] ) ) {
		printf( '<meta name="citation_technical_report_institution" content="%1$s">' . "\n\r", htmlentities( $metadata['institution'] ) );
	}
	if ( ! empty( $metadata['genre'] ) && ( 'Conference paper' == $metadata['genre'] || 'Conference proceeding' == $metadata['genre'] ) && ! empty( $metadata['conference_title'] ) ) {
		printf( '<meta name="citation_conference_title" content="%1$s">' . "\n\r", htmlentities( $metadata['conference_title'] ) );
	}
	if ( ! empty( $metadata['book_journal_title'] ) ) {
		printf( '<meta name="citation_journal_title" content="%1$s">' . "\n\r", htmlentities( $metadata['book_journal_title'] ) );
	}
	if ( ! empty( $metadata['volume'] ) ) {
		printf( '<meta name="citation_volume" content="%1$s">' . "\n\r", htmlentities( $metadata['volume'] ) );
	}
	if ( ! empty( $metadata['issue'] ) ) {
		printf( '<meta name="citation_issue" content="%1$s">' . "\n\r", htmlentities( $metadata['issue'] ) );
	}
	if ( ! empty( $metadata['start_page'] ) ) {
		printf( '<meta name="citation_firstpage" content="%1$s">' . "\n\r", htmlentities( $metadata['start_page'] ) );
	}
	if ( ! empty( $metadata['end_page'] ) ) {
		printf( '<meta name="citation_lastpage" content="%1$s">' . "\n\r", htmlentities( $metadata['end_page'] ) );
	}
	if ( ! empty( $metadata['doi'] ) ) {
		printf( '<meta name="citation_doi" content="%1$s">' . "\n\r", htmlentities( $metadata['doi'] ) );
	}
	if ( ! empty( $metadata['handle'] ) ) {
		printf( '<meta name="citation_handle_id" content="%1$s">' . "\n\r", htmlentities( $metadata['handle'] ) );
	}
	if ( ! empty( $metadata['issn'] ) ) {
		printf( '<meta name="citation_issn" content="%1$s">' . "\n\r", htmlentities( $metadata['issn'] ) );
	}
	if ( ! empty( $metadata['chapter'] ) ) {
		printf( '<meta name="citation_chapter" content="%1$s">' . "\n\r", htmlentities( $metadata['chapter'] ) );
	}
	if ( ! empty( $metadata['isbn'] ) ) {
		printf( '<meta name="citation_isbn" content="%1$s">' . "\n\r", htmlentities( $metadata['isbn'] ) );
	}

	if ( ! empty( $metadata['subject'] ) ) {
		$full_subject_list = $metadata['subject'];
		foreach ( $full_subject_list as $subject ) {
			printf( '<meta name="citation_keywords" content="%1$s">' . "\n\r", htmlentities( $subject ) );
		}
	}

	$wpmn_record_identifier = array();
	$wpmn_record_identifier = explode( '-', $metadata['record_identifier'] );
        // handle legacy MLA value
        if ( $wpmn_record_identifier[0] === $metadata['record_identifier'] ) {
                $wpmn_record_identifier[0] = '1';
                $wpmn_record_identifier[1] = $metadata['record_identifier'];
        }
	$switched = false;
	if ( $wpmn_record_identifier[0] !== get_current_blog_id() ) {
                switch_to_blog( $wpmn_record_identifier[0] );
                $switched = true;
        }

	$site_url = get_option( 'siteurl' ); 
	printf( '<meta name="citation_abstract_html_url" content="%1$s/deposits/item/%2$s/">' . "\n", $site_url, htmlentities( $metadata['pid'] ) );

	$post_metadata = json_decode( get_post_meta( $wpmn_record_identifier[1], '_deposit_metadata', true ), true );
	if ( 'yes' === $post_metadata['embargoed'] && current_time( 'Y/m/d' ) < date( 'Y/m/d', strtotime( $post_metadata['embargo_end_date'] ) ) ) {
	        if ( $switched ) {
                	restore_current_blog();
        	}
		return;
	} else {
		$file_metadata = json_decode( get_post_meta( $wpmn_record_identifier[1], '_deposit_file_metadata', true ), true );
		printf( '<meta name="citation_pdf_url" content="%1$s/deposits/download/%2$s/%3$s/%4$s/">' . "\n\r",
			$site_url,
			htmlentities( $file_metadata['files'][0]['pid'] ),
			htmlentities( $file_metadata['files'][0]['datastream_id'] ),
			htmlentities( $file_metadata['files'][0]['filename'] )
		);
	        if ( $switched ) {
                	restore_current_blog();
        	}
	}
}

/**
 * Is this the CORE page?
 *
 * @return true If the current request is the CORE page.
 */
function humcore_is_deposit_welcome() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'core' == $wp->query_vars['pagename'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Is this the CORE terms page?
 *
 * @return true If the current request is the CORE Terms page.
 */
function humcore_is_deposit_terms_acceptance() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'core/terms' == $wp->query_vars['pagename'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Is this a search request?
 *
 * @return true If the current request is a search request.
 */
function humcore_is_deposit_search() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits' == $wp->query_vars['pagename'] &&
			( ! empty( $wp->query_vars['s'] ) || ! empty( $wp->query_vars['facets'] ) || ! empty( $wp->query_vars['tag'] ) ||
				! empty( $wp->query_vars['title'] ) || ! empty( $wp->query_vars['subject'] ) || ! empty( $wp->query_vars['author'] ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Is the current page the deposit directory?
 *
 * @return true If the current page is the deposit directory.
 */
function humcore_is_deposit_directory() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits' == $wp->query_vars['pagename'] && ! is_feed() ) {
			return true;
		}
	}
	return false;
}

/**
 * Is the current page the deposit feed?
 *
 * @return true If the current page is the deposit feed.
 */
function humcore_is_deposit_feed() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits' === $wp->query_vars['pagename'] && is_feed() ) {
			return true;
		}
	}
	return false;
}

/**
 * Is the current page the deposit list?
 *
 * @return true If the current page is the deposit list.
 */
function humcore_is_deposit_list() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits/list' === $wp->query_vars['pagename'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Is the current page the deposit item?
 *
 * @return true If the current page is a deposit item.
 */
function humcore_is_deposit_item() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits/item' == $wp->query_vars['pagename'] ) {
			if ( 'new' != $wp->query_vars['deposits_item']  && 'review' !== $wp->query_vars['deposits_command'] ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Is the current page the deposit item review?
 *
 * @return true If the current page is a deposit item review page.
 */
function humcore_is_deposit_item_review() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits/item' === $wp->query_vars['pagename'] ) {
			if ( 'new' !== $wp->query_vars['deposits_item']  && 'review' === $wp->query_vars['deposits_command'] ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Is the current page the new deposit page?
 *
 * @return true If the current page is the new deposit page.
 */
function humcore_is_deposit_new_page() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits/item' == $wp->query_vars['pagename'] ) {
			if ( 'new' == $wp->query_vars['deposits_item'] ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Is this a download request?
 *
 * @return true If the current request is a download request.
 */
function humcore_is_deposit_download() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits/download' == $wp->query_vars['pagename'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Is this a view request?
 *
 * @return true If the current request is a view request.
 */
function humcore_is_deposit_view() {

	global $wp;
	if ( ! empty( $wp->query_vars['pagename'] ) ) {
		if ( 'deposits/view' == $wp->query_vars['pagename'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Is this a bot request?
 *
 * @return true If the current request from a bot.
 */
function humcore_is_bot_user_agent() {

	if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$ua = $_SERVER['HTTP_USER_AGENT'];
	} else {
		return false;
	}
	$bot_agents = array(
		'alexa', 'altavista', 'ask jeeves', 'attentio', 'baiduspider', 'bingbot', 'chtml generic', 'crawler', 'fastmobilecrawl',
		'feedfetcher-google', 'firefly', 'froogle', 'gigabot', 'googlebot', 'googlebot-mobile', 'heritrix', 'ia_archiver', 'irlbot',
		'iescholar', 'infoseek', 'jumpbot', 'lycos', 'mediapartners', 'mediobot', 'motionbot', 'msnbot', 'mshots', 'openbot',
		'pss-webkit-request', 'pythumbnail', 'scooter', 'slurp', 'snapbot', 'spider', 'taptubot', 'technoratisnoop',
		'teoma', 'twiceler', 'yahooseeker', 'yahooysmcm', 'yammybot', 'ahrefsbot', 'pingdom.com_bot', 'kraken', 'yandexbot',
		'twitterbot', 'tweetmemebot', 'openhosebot', 'queryseekerspider', 'linkdexbot', 'grokkit-crawler',
		'livelapbot', 'germcrawler', 'domaintunocrawler', 'grapeshotcrawler', 'cloudflare-alwaysonline',
		'applebot', 'paperlibot', 'duckduckbot', 'seznambot', 'naverbot', 'scoutjet', 'gurujibot', 'exabot', 'solbot',
		'voilabot', 'daumoa', 'architextspider', 'socscibot', 'coccoc', 'browsershots',
	);

	foreach ( $bot_agents as $bot_agent ) {
		if ( false !== stripos( $ua, $bot_agent ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Array of member groups that can author deposits.
 *
 * @return array Group ids.
 */
function humcore_member_groups_with_authorship() {

	return array( 378,444 );
}

/**
 * Get the content for the Offline page.
 */
function humcore_get_offline_content() {

	$args = array(
		'name'             => 'deposits',
		'posts_per_page'   => 1,
		'offset'           => 0,
		'post_type'        => 'page',
		'post_status'      => 'publish',
		'suppress_filters' => true 
	);
	$post_parent = get_posts( $args );
	$parent_id = $posts_parent[0]->ID;

        $args = array(
                'name'             => 'offline',
                'posts_per_page'   => 1,
                'offset'           => 0,
                'post_type'        => 'page',
                'post_status'      => 'publish',
		'post_parent'      => $post_parent,
                'suppress_filters' => true 
        );
        $offline_page = get_posts( $args ); 
	$offline_content = apply_filters( 'the_content', $offline_page[0]->post_content );

	echo '<p />',$offline_content;

}

/**
 * Check the manually entered system status in settings.
 */
function humcore_check_internal_status() {

	global $ezid_api, $fedora_api, $solr_client;

	if ( 'down' === $solr_client->service_status ) {
		return false;
	}

	return true;

}

/**
 * Check the status of the external systems.
 */
function humcore_check_externals() {

	global $ezid_api, $fedora_api, $solr_client;

	$sStatus = $solr_client->get_solr_status();
	if ( is_wp_error( $sStatus ) ) {
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Status Error***** - solr server status : %1$s-%2$s', $sStatus->get_error_code(), $sStatus->get_error_message() ) );
		return false;
	}

	$fStatus = $fedora_api->describe();
	if ( is_wp_error( $fStatus ) ) {
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Status Error***** - fedora server status :  %1$s-%2$s',  $fStatus->get_error_code(), $fStatus->get_error_message() ) );
		return false;
	}

	$eStatus = $ezid_api->server_status();
	if ( is_wp_error( $eStatus ) ) {
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Status Error***** - ezid server status :  %1$s-%2$s',  $eStatus->get_error_code(), $eStatus->get_error_message() ) );
		return false;
	}

	return true;

}

/**
 * Reserve a DOI using EZID API.
 */
function humcore_create_handle( $title, $pid, $creator, $type, $date, $publisher ) {

	global $ezid_api;

	$eStatus = $ezid_api->mint_identifier( array(
		'dc.title' => $title,
		'_target' => sprintf( HC_SITE_URL . '/deposits/item/%s/', $pid ),
		'dc.creator' => $creator,
		'dc.type' => $type,
		'dc.date' => $date,
		'dc.publisher' => $publisher,
	) );

	if ( is_wp_error( $eStatus ) ) {
		echo 'Error - ezid mint doi : ' . esc_html( $eStatus->get_error_code() ) . '-' . esc_html( $eStatus->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - ezid mint doi :  %1$s-%2$s',  $eStatus->get_error_code(), $eStatus->get_error_message() ) );
		return false;
	}

	return $eStatus;

}

/**
 * Publish a DOI using EZID API.
 */
function humcore_publish_handle( $humcore_doi ) {

	global $ezid_api;

	$eStatus = $ezid_api->modify_identifier( array(
		'doi' => $humcore_doi,
		'_status' => 'public',
		'_export' => 'yes',
	) );

	if ( is_wp_error( $eStatus ) ) {
		echo 'Error - ezid publish : ' . esc_html( $eStatus->get_error_code() ) . '-' . esc_html( $eStatus->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - ezid publish :  %1$s-%2$s',  $eStatus->get_error_code(), $eStatus->get_error_message() ) );
		return false;
	}

	return $eStatus;

}

/**
 * Modify DOI metdata using EZID API.
 */
function humcore_modify_handle( $humcore_doi, $title, $creator, $type, $date, $publisher ) {

	global $ezid_api;

	$eStatus = $ezid_api->modify_identifier( array(
		'doi' => $humcore_doi,
		'dc.title' => $title,
		'dc.creator' => $creator,
		'dc.type' => $type,
		'dc.date' => $date,
		'dc.publisher' => $publisher,
	) );

	if ( is_wp_error( $eStatus ) ) {
		echo 'Error - ezid modify : ' . esc_html( $eStatus->get_error_code() ) . '-' . esc_html( $eStatus->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - ezid modify :  %1$s-%2$s',  $eStatus->get_error_code(), $eStatus->get_error_message() ) );
		return false;
	}

	return $eStatus;

}

/**
 * Register the location of the plugin templates.
 */
function humcore_register_template_location() {

	return dirname( __FILE__ ) . '/templates/';
}

/**
 * Add specific CSS class by filter (filter added in humcore_deposits_search_screen).
 */
function humcore_search_page_class_names( $classes ) {

	if ( ( $key = array_search('error404', $classes ) ) !== false ) {
		unset( $classes[$key] );
	}
	$classes[] = 'search-page';
	return $classes;
}

/**
 * Add specific CSS class by filter (filter added in humcore_deposits_screen_index).
 */
function humcore_deposit_directory_page_class_names( $classes ) {

	$classes[] = 'deposits-directory-page';
	return $classes;
}

/**
 * Add specific CSS class by filter (filter added in humcore_deposits_item_screen).
 */
function humcore_deposit_item_page_class_names( $classes ) {

	$classes[] = 'deposits-item-page';
	return $classes;
}

/**
 * Add specific CSS class by filter (filter added in humcore_deposits_new_item_screen).
 */
function humcore_deposit_new_item_page_class_names( $classes ) {

	$classes[] = 'deposits-new-item-page';
	return $classes;
}

/**
 * Add specific CSS class by filter (filter added in humcore_deposits_new_item_screen).
 */
function humcore_core_welcome_page_class_names( $classes ) {

	$classes[] = 'core-welcome-page';
	return $classes;
}

/**
 * Load the CORE screen.
 */
function humcore_deposits_welcome() {

	if ( humcore_is_deposit_welcome() ) {
		if ( is_user_logged_in() ) {
			$user_id = bp_loggedin_user_id();
		}
	add_filter( 'body_class', 'humcore_core_welcome_page_class_names' );
	}
}
add_action( 'bp_screens', 'humcore_deposits_welcome' );

/**
 * Load the Deposits new item screen after accepting the CORE Terms.
 */
function humcore_deposits_terms_acceptance() {

	if ( humcore_is_deposit_terms_acceptance() ) {
		if ( ! is_user_logged_in() ) { auth_redirect(); }
		$wp_nonce = $_POST['accept_core_terms_nonce'];
		if ( ! empty( $_POST ) && wp_verify_nonce( $wp_nonce, 'accept_core_terms' ) ) {
			$core_accept_terms = $_POST['core_accept_terms'];
			if ( ! empty( $core_accept_terms ) ) {
				$user_id = bp_loggedin_user_id();
				update_user_meta( $user_id, 'accepted_core_terms', $core_accept_terms);
				wp_redirect( '/deposits/item/new/' );
				exit();
			}
		}
	}
}
add_action( 'bp_screens', 'humcore_deposits_terms_acceptance' );

/**
 * Load the Search Results template.
 */
function humcore_deposits_search_screen() {
	if ( humcore_is_deposit_search() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		add_filter( 'body_class', 'humcore_search_page_class_names' );
		$extended_query_string = humcore_get_search_request_querystring( 'facets' );
		if ( ! empty( $extended_query_string ) ) {
			setcookie( 'bp-deposits-extras', $extended_query_string, 0, '/' );
		}
		do_action( 'humcore_deposits_search_screen' );
		bp_get_template_part( apply_filters( 'humcore_deposits_search_screen', 'deposits/search' ) );
		exit(); // Suppress extra page display.
	}
}
add_action( 'bp_screens', 'humcore_deposits_search_screen' );

/**
 * Load the Deposits directory.
 *
 * @uses humcore_is_deposit_directory()
 * @uses bp_update_is_directory()
 * @uses do_action() To call the 'humcore_deposits_screen_index' hook.
 * @uses bp_get_template_part()
 * @uses apply_filters() To call the 'humcore_deposits_screen_index' hook.
 */
function humcore_deposits_screen_index() {
	if ( humcore_is_deposit_directory() ) {
		bp_update_is_directory( true, 'humcore_deposits' );
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		add_filter( 'body_class', 'humcore_deposit_directory_page_class_names' );
		setcookie( 'bp-deposits-extras', false, 0, '/' );
		do_action( 'humcore_deposits_screen_index' );
		add_action( 'wp_head', 'humcore_noindex' );
		bp_get_template_part( apply_filters( 'humcore_deposits_screen_index', 'deposits/deposits-index' ) );
	}
}
add_action( 'bp_screens', 'humcore_deposits_screen_index' );

/**
 * Load the Deposits feed.
 */
function humcore_deposits_feed() {
	if ( humcore_is_deposit_feed() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
		do_action( 'humcore_deposits_feed' );
		bp_get_template_part( apply_filters( 'humcore_deposits_feed', 'deposits/deposits-feed' ) );
	}
}
add_action( 'bp_screens', 'humcore_deposits_feed' );

/**
 * Load the Deposits list page.
 */
function humcore_deposits_list_screen() {
	if ( humcore_is_deposit_list() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		do_action( 'humcore_deposits_list_screen' );
		add_action( 'wp_head', 'humcore_noindex' );
		bp_get_template_part( apply_filters( 'humcore_deposits_list_screen', 'deposits/deposits-list' ) );
	}
}
add_action( 'bp_screens', 'humcore_deposits_list_screen' );

/**
 * Load the Deposits item screen.
 */
function humcore_deposits_item_screen() {

	global $wp;
	if ( humcore_is_deposit_item() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		$deposit_id = $wp->query_vars['deposits_item'];
		if ( empty( $deposit_id ) ) {
			bp_do_404();
			//bp_get_template_part( apply_filters( 'humcore_deposits_item_screen', 'deposits/404' ) );
			return;
		}
		$item_found = humcore_has_deposits( 'include=' . $deposit_id );
		if ( $item_found) { 
			add_filter( 'body_class', 'humcore_deposit_item_page_class_names' );
			do_action( 'humcore_deposits_item_screen' );
			remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
			remove_action( 'wp_head', 'rel_canonical' );
			add_action( 'wp_head', 'humcore_deposit_item_search_meta' );
			bp_get_template_part( apply_filters( 'humcore_deposits_item_screen', 'deposits/single/item' ) );
		} else {
			//bp_get_template_part( apply_filters( 'humcore_deposits_item_screen', 'deposits/404' ) );
			bp_do_404();
		}
	}
}
add_action( 'bp_screens', 'humcore_deposits_item_screen' );

/**
 * Load the Deposits item review screen.
 */
function humcore_deposits_item_review_screen() {

	global $wp;
	if ( humcore_is_deposit_item_review() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
		if ( ! is_user_logged_in() ) { auth_redirect(); }
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		$deposit_id = $wp->query_vars['deposits_item'];
		if ( empty( $deposit_id ) ) {
			bp_do_404();
			return;
		}
		$item_found = humcore_has_deposits( 'include=' . $deposit_id );
		if ( $item_found) { 
			do_action( 'humcore_deposits_item_review_screen' );
			remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
			remove_action( 'wp_head', 'rel_canonical' );
			add_action( 'wp_head', 'humcore_deposit_item_search_meta' );
			add_action( 'wp_head', 'humcore_noindex' );
			bp_get_template_part( apply_filters( 'humcore_deposits_item_review_screen', 'deposits/single/review' ) );
		} else {
			bp_do_404();
		}
	}
}
add_action( 'bp_screens', 'humcore_deposits_item_review_screen' );

/**
 * Load the Deposits new item screen.
 */
function humcore_deposits_new_item_screen() {

	if ( humcore_is_deposit_new_page() ) {
		if ( ! is_user_logged_in() ) { auth_redirect(); }
        	if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
		}
		$user_id = bp_loggedin_user_id();
		$core_acceptance = get_the_author_meta( 'accepted_core_terms', $user_id );
		if ( 'Yes' != $core_acceptance ) {
			wp_redirect( '/core/terms/' );
			exit();
		}
		bp_update_is_directory( false, 'humcore_deposits' );
		add_filter( 'body_class', 'humcore_deposit_new_item_page_class_names' );
		do_action( 'humcore_deposits_new_item_screen' );
		add_action( 'bp_template_content', 'humcore_new_deposit_form' );
		ob_start(); // we might redirect in the action so capture any output.
		bp_get_template_part( apply_filters( 'humcore_deposits_new_item_screen', 'deposits/single/new' ) );
	}
}
add_action( 'bp_screens', 'humcore_deposits_new_item_screen' );

/**
 * Redirect the download request.
 */
function humcore_deposits_download() {

	global $wp;
	if ( humcore_is_deposit_download() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		do_action( 'humcore_deposits_download' );
		$deposit_id = $wp->query_vars['deposits_item'];
		$deposit_datastream = $wp->query_vars['deposits_datastream'];
		if ( empty( $deposit_id ) || empty( $deposit_datastream ) ) {
			bp_do_404();
			return;
		}
		$deposit_filename = $wp->query_vars['deposits_filename'];
		$download_param = ( 'xml' == $deposit_filename ) ? '' : '?download=true';
		$downloads_meta_key = sprintf( '_total_downloads_%s_%s', $deposit_datastream, $deposit_id );
		$deposit_post_id = humcore_get_deposit_post_id( $deposit_id );
        	$post_data = get_post( $deposit_post_id );
                $post_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );
                if ( 'yes' === $post_metadata['embargoed'] && current_time( 'Y/m/d' ) < date( 'Y/m/d', strtotime( $post_metadata['embargo_end_date'] ) ) ) {
                        bp_do_404();
                        return;
                }
		$total_downloads = get_post_meta( $deposit_post_id, $downloads_meta_key, true ) + 1; // Downloads counted at file level.
	        if ( $post_data->post_author != bp_loggedin_user_id() && ! humcore_is_bot_user_agent() ) {
			$post_meta_ID = update_post_meta( $deposit_post_id, $downloads_meta_key, $total_downloads );
		}
		$download_url = sprintf( '/deposits/objects/%1$s/datastreams/%2$s/content%3$s', $deposit_id, $deposit_datastream, $download_param );

		wp_redirect( $download_url );
		exit();
	}
}
add_action( 'bp_screens', 'humcore_deposits_download' );

/**
 * Redirect the view request.
 */
function humcore_deposits_view() {

	global $wp;
	if ( humcore_is_deposit_view() ) {
		bp_update_is_directory( false, 'humcore_deposits' );
                if ( ! humcore_check_internal_status() ) {
                        wp_redirect( '/deposits/offline/' );
                        exit();
                }
		do_action( 'humcore_deposits_view' );
		$deposit_id = $wp->query_vars['deposits_item'];
		$deposit_datastream = $wp->query_vars['deposits_datastream'];
                if ( empty( $deposit_id ) || empty( $deposit_datastream ) ) {
                        bp_do_404();
                        return;
                }
		$deposit_filename = $wp->query_vars['deposits_filename'];
		$views_meta_key = sprintf( '_total_views_%s_%s', $deposit_datastream, $deposit_id );
		$deposit_post_id = humcore_get_deposit_post_id( $deposit_id );
        	$post_data = get_post( $deposit_post_id );
		$post_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );
		if ( 'yes' === $post_metadata['embargoed'] && current_time( 'Y/m/d' ) < date( 'Y/m/d', strtotime( $post_metadata['embargo_end_date'] ) ) ) {
                        bp_do_404();
			return;
		}
		$total_views = get_post_meta( $deposit_post_id, $views_meta_key, true ) + 1; // views counted at file level
	        if ( $post_data->post_author != bp_loggedin_user_id() && ! humcore_is_bot_user_agent() ) {
			$post_meta_ID = update_post_meta( $deposit_post_id, $views_meta_key, $total_views );
		}
		$view_url = sprintf( '/deposits/objects/%1$s/datastreams/%2$s/content', $deposit_id, $deposit_datastream );

		wp_redirect( $view_url );
		exit();
	}
}
add_action( 'bp_screens', 'humcore_deposits_view' );

/**
 * Is this group a forum?
 *
 * @return true If the group is a forum.
 */
function humcore_is_group_forum( $group_id = 0 ) {
        // use the current group if we're not passed one.
        if ( 0 == $group_id ) $group_id = bp_get_current_group_id();

        // if mla_oid starts with "M," it's a committee, if "D","G" it's a forum
        return in_array( substr( groups_get_groupmeta( $group_id, 'mla_oid' ), 0, 1 ), array( 'D', 'G' ) );
}

/**
 * Returns group with link.
 * @return string
 */
function humcore_linkify_group( $group, $link_type = 'facet' ) {

	if ( 'facet' != $link_type && function_exists( 'bp_is_active' ) ) {
		if ( bp_is_active( 'groups' ) ) {
			$group_slug = humcore_get_slug_from_name( $group );
			$linked_group = sprintf( '<a href="/groups/%s/deposits">%s</a>', urlencode( $group_slug ), esc_html( $group ) );
			return $linked_group;
		}
	}

	$linked_group = sprintf( '<a href="/deposits/?facets[group_facet][]=%s">%s</a>', urlencode( $group ), esc_html( $group ) );
	return $linked_group;
}

/**
 * Returns subject with link.
 *
 * @return string
 */
function humcore_linkify_subject( $subject ) {

	$linked_subject = sprintf( '<a href="/deposits/?facets[subject_facet][]=%s">%s</a>', urlencode( $subject ), $subject );
	return $linked_subject;
}

/**
 * Returns tag with link.
 *
 * @return string
 */
function humcore_linkify_tag( $tag, $tag_name ) {

	$linked_tag = sprintf( '<a href="/deposits/?tag=%s">%s</a>', urlencode( $tag ), empty( $tag_name ) ? $tag : $tag_name );
	return $linked_tag;
}

/**
 * Returns author with facet link and optional link to profile.
 *
 * @return string
 */
function humcore_linkify_author( $author, $author_meta, $author_type ) {

	$displayed_username = bp_get_displayed_user_username();

	if ( 'creator' === $author_type ) {
		$page_type = 'groups';
		$prompt_text = 'view group';
		$url_suffix = 'deposits/';
	} else {
		$page_type = 'members';
		$prompt_text = 'see profile';
		$url_suffix = '';
	}

	if ( ( ! empty( $author_meta ) && 'null' != $author_meta ) &&
		( ( 'members' === $page_type && $displayed_username != $author_meta ) ||
		( 'groups' === $page_type && ! bp_is_group() ) ) ) {
		$profile = sprintf( ' <a href="/%s/%s/%s">(%s)</a> ', $page_type, $author_meta, $url_suffix, $prompt_text );
	} else {
		$profile = '';
	}
	$linked_author = sprintf( '<a href="/deposits/?facets[author_facet][]=%s">%s</a>%s', urlencode( $author ) , $author, $profile );

	return $linked_author;

}

/**
 * Returns license with link.
 *
 * @return string
 */
function humcore_linkify_license( $license ) {


$license_link_list = array();

        $license_link_list['All Rights Reserved'] = '';
        $license_link_list['Attribution'] = 'https://creativecommons.org/licenses/by/4.0/';
        $license_link_list['Attribution-NonCommercial'] = 'https://creativecommons.org/licenses/by-nc/4.0/';
        $license_link_list['Attribution-ShareAlike'] = 'https://creativecommons.org/licenses/by-sa/4.0/';
        $license_link_list['Attribution-NonCommercial-ShareAlike'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
        $license_link_list['Attribution-NoDerivatives'] = 'https://creativecommons.org/licenses/by-nd/4.0/';
        $license_link_list['Attribution-NonCommercial-NoDerivatives'] = 'https://creativecommons.org/licenses/by-nc-nd/4.0/';
        $license_link_list['All-Rights-Granted'] = 'https://creativecommons.org/publicdomain/zero/1.0/';

	if ( ! empty( $license_link_list[$license] ) ) {
        	return sprintf( '<a onclick="target=' . "'" . '_blank' . "'" . '" href="%s">%s</a>', $license_link_list[$license], $license );
	} else {
        	return $license;
	}

}

/**
 * Returns group slug for a given group name.
 *
 * @return string
 */
function humcore_get_slug_from_name( $group_name ) {

	// Check cache for group slug.
	$group_slug = wp_cache_get( $group_name, 'humcore_get_slug_from_name' );

	if ( false === $group_slug ) {
		$group = BP_Groups_Group::search_groups( $group_name, 1 );
		$group_slug = groups_get_slug( $group['groups'][0]->group_id );
		wp_cache_set( $group_name, $group_slug, 'humcore_get_slug_from_name' );
	}

	return $group_slug;
}

/**
 * Returns group id for a given group name.
 *
 * @return string
 */
function humcore_get_id_from_name( $group_name ) {

	// Check cache for group slug.
	$group_id = wp_cache_get( $group_name, 'humcore_get_id_from_name' );

	if ( false === $group_id ) {
		$group = BP_Groups_Group::search_groups( $group_name, 1 );
		$group_id = $group['groups'][0]->group_id;
		wp_cache_set( $group_name, $group_id, 'humcore_get_id_from_name' );
	}

	return $group_id;
}

/**
 * Return the formatted author_info.
 *
 * @return string
 */
function humcore_deposits_format_author_info( $authors ) {

	$author_info = array();
	foreach ( $authors as $author ) {
		if ( ! empty( $author['given'] ) && ! empty( $author['family'] ) ) {
			$author_name = $author['family'] . ', ' . $author['given'];
		} else {
			$author_name = $author['fullname'];
		}
		if ( 'creator' === $author['role'] && ! empty( $author['uni'] ) ) {
			$author_id = $author['uni'] . ' : group : ' . $author['role'];
		} else if ( ! empty( $author['uni'] ) ) {
			$author_id = $author['uni'] . ' : personal : ' . $author['role'];
		} else {
			$author_id = 'null : personal : ' . $author['role'];
		}
		if ( ! empty( $author['affiliation'] ) ) {
			$author_org = $author['affiliation'];
		} else {
			$author_org = '';
		}
		if ( ! empty( $author['fullname'] ) ) {
			$author_info[] = implode( ' : ', array( $author_name, $author_id, $author_org ) ) . '; ';
		}
	}
	$formatted_author_info = implode( ' ', $author_info );

	return apply_filters( 'humcore_deposits_format_author_info', $formatted_author_info );

}

/**
 * Return the genre list.
 *
 * @return array
 */
function humcore_deposits_genre_list() {

	$genre_list = array();

	$genre_list['Abstract'] = 'Abstract';
	$genre_list['Article'] = 'Article';
	$genre_list['Bibliography'] = 'Bibliography';
	$genre_list['Blog Post'] = 'Blog post';
	$genre_list['Book'] = 'Book';
	$genre_list['Book chapter'] = 'Book chapter';
	$genre_list['Book review'] = 'Book review';
	$genre_list['Book section'] = 'Book section';
	$genre_list['Catalog'] = 'Catalog';
	$genre_list['Chart'] = 'Chart';
	$genre_list['Code or software'] = 'Code or software';
	$genre_list['Conference paper'] = 'Conference paper';
	$genre_list['Conference proceeding'] = 'Conference proceeding';
	$genre_list['Course material or learning objects'] = 'Course material or learning objects';
	$genre_list['Data set'] = 'Data set';
	$genre_list['Dissertation'] = 'Dissertation';
	$genre_list['Documentary'] = 'Documentary';
	$genre_list['Essay'] = 'Essay';
	$genre_list['Fictional work'] = 'Fictional work';
	$genre_list['Finding aid'] = 'Finding aid';
	$genre_list['Image'] = 'Image';
	$genre_list['Interview'] = 'Interview';
	$genre_list['Map'] = 'Map';
	$genre_list['Monograph'] = 'Monograph';
	$genre_list['Music'] = 'Music';
	$genre_list['Performance'] = 'Performance';
	$genre_list['Photograph'] = 'Photograph';
	$genre_list['Poetry'] = 'Poetry';
	$genre_list['Presentation'] = 'Presentation';
	$genre_list['Report'] = 'Report';
	$genre_list['Review'] = 'Review';
	$genre_list['Syllabus'] = 'Syllabus';
	$genre_list['Technical report'] = 'Technical report';
	$genre_list['Thesis'] = 'Thesis';
	$genre_list['Translation'] = 'Translation';
	$genre_list['Video essay'] = 'Video essay';
	$genre_list['Visual art'] = 'Visual art';
	$genre_list['White paper'] = 'White paper';
	$genre_list['Other'] = 'Other';

	return apply_filters( 'bp_humcore_deposits_genre_list', $genre_list );

}

/**
 * Return the group list.
 *
 * @return array
 */
function humcore_deposits_group_list( $user_id ) {

	/**
	 * Groups meta_query with NOT EXISTS does not seem to work correctly. Remove committees from results.
	 */
	$groups_list = array();
        $society_id = get_network_option( '', 'society_id' );
	$society_id = '';

	$args = array(
		'user_id' => $user_id,
		'type' => 'alphabetical',
		'per_page' => '1000',
	);

	$d_groups = groups_get_groups( $args );

	foreach ( $d_groups['groups'] as $group ) {
 		if ( ! mla_is_group_committee( $group->id ) ) {
			$group_type = bp_groups_get_group_type( $group->id );
			$groups_list[ $group->id ] = htmlspecialchars( stripslashes( $group->name . ' (' . strtoupper( $group_type ) . ')' ) );
		}
	}

	natcasesort( $groups_list );

	return apply_filters( 'humcore_deposits_group_list', $groups_list );

}

/**
 * Return the committee list the user is a member of.
 *
 * @param string $user_id User ID.
 * @return array
 */
function humcore_deposits_user_committee_list( $user_id ) {

	$committees_list = array();
        $society_id = get_network_option( '', 'society_id' );

	$args = array(
		'user_id' => $user_id,
		'type' => 'alphabetical',
		'group_type' => $society_id,
		'meta_query' => array(
			array(
				'key' => 'mla_oid',
				'value' => 'M',
				'compare' => 'LIKE',
			),
		),
		'per_page' => '500',
	);

	/* Special case for now - remove committees.
	$m_groups = groups_get_groups( $args );

	foreach ( $m_groups['groups'] as $group ) {
		$committees_list[ $group->id ] = strip_tags( stripslashes( $group->name ) );
	}
	*/

	// Add special exceptions - certain committees where user is admin
        $s_args = array(
                'user_id' => $user_id,
                'type' => 'alphabetical',
		'include' => humcore_member_groups_with_authorship(),
		'show_hidden' => true,
                'per_page' => '500',
        );

        $s_groups = groups_get_groups( $s_args );

        foreach ( $s_groups['groups'] as $s_group ) {
		if ( groups_is_user_admin( $user_id, $s_group->id ) ) {
                	$committees_list[ $s_group->id ] = strip_tags( stripslashes( $s_group->name ) );
        	}
        }

	return apply_filters( 'humcore_deposits_user_committee_list', $committees_list );

}

/**
 * Return the subject list.
 *
 * @return array
 */
function humcore_deposits_subject_list() {

	$subjects_list = array();

	$subject_terms = wpmn_get_terms(
		'humcore_deposit_subject',
		array(
			'orderby' => 'name',
			'fields' => 'names',
			'hide_empty' => 0,
		)
	);
	foreach ( $subject_terms as $term ) {
		$subjects_list[ $term ] = $term;
	}

	natcasesort( $subjects_list );

	return apply_filters( 'bp_humcore_deposits_subject_list', $subjects_list );

}

/**
 * Return the keyword list.
 *
 * @return array
 */
function humcore_deposits_keyword_list() {

	$keywords_list = array();

	$keyword_terms = wpmn_get_terms(
		'humcore_deposit_tag',
		array(
			'orderby' => 'name',
			'fields' => 'names',
			'hide_empty' => 0,
		)
	);
	foreach ( $keyword_terms as $term ) {
		$keywords_list[ $term ] = $term;
	}

	natcasesort( $keywords_list );

	return apply_filters( 'bp_humcore_deposits_keyword_list', $keywords_list );

}

/**
 * Return the language list.
 *
 * @return array
 */
function humcore_deposits_language_list() {

	$keywords_list = array();

	$language_terms = wpmn_get_terms(
		'humcore_deposit_language',
		array(
			'orderby' => 'name',
			'fields' => 'names',
			'hide_empty' => 0,
		)
	);
	foreach ( $language_terms as $term ) {
		$languages_list[ $term ] = $term;
	}

	natcasesort( $languages_list );

	return apply_filters( 'bp_humcore_deposits_language_list', $languages_list );

}

/**
 * Return the license type list.
 *
 * @return array
 */
function humcore_deposits_license_type_list() {

	$license_type_list = array();

	$license_type_list['All Rights Reserved'] = 'All Rights Reserved';
	$license_type_list['Attribution'] = 'Attribution';
	$license_type_list['Attribution-NonCommercial'] = 'Attribution-NonCommercial';
	$license_type_list['Attribution-ShareAlike'] = 'Attribution-ShareAlike';
	$license_type_list['Attribution-NonCommercial-ShareAlike'] = 'Attribution-NonCommercial-ShareAlike';
	$license_type_list['Attribution-NoDerivatives'] = 'Attribution-NoDerivatives';
	$license_type_list['Attribution-NonCommercial-NoDerivatives'] = 'Attribution-NonCommercial-NoDerivatives';
	$license_type_list['All-Rights-Granted'] = 'All Rights Granted';

	return apply_filters( 'bp_humcore_deposits_license_type_list', $license_type_list );

}

/**
 * Return the resource type list
 *
 * @return array
 */
function humcore_deposits_resource_type_list() {

	$resource_type_list = array();

	$resource_type_list['Audio'] = 'Audio';
	$resource_type_list['Image'] = 'Image';
	$resource_type_list['Mixed material'] = 'Mixed material';
	$resource_type_list['Software'] = 'Software';
	$resource_type_list['Text'] = 'Text';
	$resource_type_list['Video'] = 'Video';

	return apply_filters( 'bp_humcore_deposits_resource_type_list', $resource_type_list );

}

/**
 * Return the embargo length options
 *
 * @return array
 */
function humcore_deposits_embargo_length_list() {

	$embargo_length_list = array();

	$embargo_length_list['6 months'] = '6 months';
	$embargo_length_list['12 months'] = '12 months';
	$embargo_length_list['18 months'] = '18 months';
	$embargo_length_list['24 months'] = '24 months';

	return apply_filters( 'bp_humcore_deposits_embargo_length_list', $embargo_length_list );

}

/**
 * Return the search request querystring.
 *
 * @return string
 */
function humcore_get_search_request_querystring( $query_key = '' ) {

	if ( ! empty( $_POST ) ) {
		$current_request = $_POST;
	} else {
		$current_request = $_GET;
	}

	$request_params = array();
	if ( ! empty( $current_request ) ) {
		foreach ( $current_request as $key => $param ) {
			if ( empty( $query_key ) || $query_key == $key ) {
				if ( ! is_array( $param ) ) {
					if ( 'facets' == $key && ! empty( $_POST[ $key ] ) ) {
						// Facets from form post and facets from url query string are formatted differently.
						if ( ! empty( $param ) ) {
							$request_params[] = $param;
						}
					} else {
						if ( ! empty( $param ) ) {
							$request_params[] = sprintf( '%1$s=%2$s', $key, $param );
						}
					}
				} else {
					foreach ( $param as $param_key => $param_values ) {
						foreach ( $param_values as $param_value ) {
							if ( ! empty( $param_value ) ) {
								$request_params[] = sprintf( '%1$s[%2$s][]=%3$s', $key, $param_key, urlencode( $param_value ) );
							}
						}
					}
				}
			}
		}
	}

	return implode( '&', $request_params );

}

/**
 * Return a test DOI for 14 days only.
 *
 * @return object deposit
 */
function humcore_check_test_handle( $deposit_record ) {

	if ( ! is_array( $deposit_record ) && false !== strpos( $deposit_record->handle, '10.5072/FK2' ) &&
			date_create( '14 days ago' ) > date_create( $deposit_record->record_creation_date ) ) {
		$deposit_record->handle = sprintf( '%1$s/deposits/item/%2$s', HC_SITE_URL, $deposit_record->pid );
	}
	return $deposit_record;

}
add_action( 'humcore_get_current_deposit', 'humcore_check_test_handle' );

/**
 * Return the author name and username.
 *
 * @return array author_meta
 */
function humcore_deposit_parse_author_info( $author_info, $element = 1, $filter = '' ) {

	$author_meta = array();
	if ( ! empty( $filter ) && ! is_array( $filter ) ) {
		$filter = array( $filter );
	}
	$author_info_array = explode( ';', $author_info );

	foreach ( $author_info_array as $each_author_info ) {
		$author_fields = explode( ' : ', $each_author_info );
		if ( 5 == count( $author_fields ) ) {
			if ( empty( $filter ) || ( ! empty( $filter ) && in_array( $author_fields[3], $filter ) ) ) {
				$author_meta[] = $author_fields[$element];
			}
		}
	}

	return $author_meta;

}

/**
 * Return noindex robot tag.
 *
 * @return string meta tag
 */
function humcore_noindex() {

    echo "<meta name='robots' content='noindex' />\n";

}

/**
 * Retrieve a deposit by title for an author or group
 *
 * @param string $title Title.
 * @param string $genre Item Type.
 * @param string $group_id Group ID, if group deposit.
 * @return object matching deposit or null.
 */
function humcore_get_deposit_by_title_genre_and_author( $title, $genre, $group_id = '', $user ) {

	if ( ! empty( $group_id ) ) {
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		$author_name = $group->name;
	} else {
		$author_name = $user->display_name;
	}

	humcore_has_deposits( sprintf( 'facets[author_facet][]=%s&facets[genre_facet][]=%s&search_title_exact=%s',
			urlencode( $author_name ),
			urlencode( $genre ),
			urlencode( $title ) ) );

	if ( 0 !== (int) humcore_get_deposit_count() ) {
		humcore_the_deposit();
		return humcore_get_current_deposit();
	} else {
		return;
	}

}
