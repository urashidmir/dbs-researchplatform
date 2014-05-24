<?php
/**
 * BuddyPress Experiments Activity Functions
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage ExperimentsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Register activity actions for the Experiments component.
 */
function experiments_register_activity_actions() {
	global $bp;

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	bp_activity_set_action(
		$bp->experiments->id,
		'created_experiment',
		__( 'Created a experiment', 'buddypress' ),
		'bp_experiments_format_activity_action_created_experiment'
	);

	bp_activity_set_action(
		$bp->experiments->id,
		'joined_experiment',
		__( 'Joined a experiment', 'buddypress' ),
		'bp_experiments_format_activity_action_joined_experiment'
	);

	// These actions are for the legacy forums
	// Since the bbPress plugin also shares the same 'forums' identifier, we also
	// check for the legacy forums loader class to be extra cautious
	if ( bp_is_active( 'forums' ) && class_exists( 'BP_Forums_Component' ) ) {
		bp_activity_set_action( $bp->experiments->id, 'new_forum_topic', __( 'New experiment forum topic', 'buddypress' ) );
		bp_activity_set_action( $bp->experiments->id, 'new_forum_post',  __( 'New experiment forum post',  'buddypress' ) );
	}

	do_action( 'experiments_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'experiments_register_activity_actions' );

/**
 * Format 'created_experiment' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_experiments_format_activity_action_created_experiment( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	$experiment = experiments_get_experiment( array(
		'experiment_id'        => $activity->item_id,
		'populate_extras' => false,
	) );
	$experiment_link = '<a href="' . esc_url( bp_get_experiment_permalink( $experiment ) ) . '">' . esc_html( $experiment->name ) . '</a>';

	$action = sprintf( __( '%1$s created the experiment %2$s', 'buddypress'), $user_link, $experiment_link );

	return apply_filters( 'experiments_activity_created_experiment_action', $action, $activity );
}

/**
 * Format 'joined_experiment' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_experiments_format_activity_action_joined_experiment( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	$experiment = experiments_get_experiment( array(
		'experiment_id'        => $activity->item_id,
		'populate_extras' => false,
	) );
	$experiment_link = '<a href="' . esc_url( bp_get_experiment_permalink( $experiment ) ) . '">' . esc_html( $experiment->name ) . '</a>';

	$action = sprintf( __( '%1$s joined the experiment %2$s', 'buddypress' ), $user_link, $experiment_link );

	// Legacy filters (do not follow parameter patterns of other activity
	// action filters, and requires apply_filters_ref_array())
	if ( has_filter( 'experiments_activity_membership_accepted_action' ) ) {
		$action = apply_filters_ref_array( 'experiments_activity_membership_accepted_action', array( $action, $user_link, &$experiment ) );
	}

	// Another legacy filter
	if ( has_filter( 'experiments_activity_accepted_invite_action' ) ) {
		$action = apply_filters_ref_array( 'experiments_activity_accepted_invite_action', array( $action, $activity->user_id, &$experiment ) );
	}

	return apply_filters( 'bp_experiments_format_activity_action_joined_experiment', $action, $activity );
}

/**
 * Fetch data related to experiments at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $activities Array of activity items.
 * @return array
 */
function bp_experiments_prefetch_activity_object_data( $activities ) {
	$experiment_ids = array();

	if ( empty( $activities ) ) {
		return $activities;
	}

	foreach ( $activities as $activity ) {
		if ( buddypress()->experiments->id !== $activity->component ) {
			continue;
		}

		$experiment_ids[] = $activity->item_id;
	}

	if ( ! empty( $experiment_ids ) ) {

		// TEMPORARY - Once the 'populate_extras' issue is solved
		// in the experiments component, we can do this with experiments_get_experiments()
		// rather than manually
		$uncached_ids = array();
		foreach ( $experiment_ids as $experiment_id ) {
			if ( false === wp_cache_get( $experiment_id, 'bp_experiments' ) ) {
				$uncached_ids[] = $experiment_id;
			}
		}

		if ( ! empty( $uncached_ids ) ) {
			global $wpdb, $bp;
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );
			$experiments = $wpdb->get_results( "SELECT * FROM {$bp->experiments->table_name} WHERE id IN ({$uncached_ids_sql})" );
			foreach ( $experiments as $experiment ) {
				wp_cache_set( $experiment->id, $experiment, 'bp_experiments' );
			}
		}
	}

	return $activities;
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_experiments_prefetch_activity_object_data' );

/**
 * Record an activity item related to the Experiments component.
 *
 * A wrapper for {@link bp_activity_add()} that provides some Experiments-specific
 * defaults.
 *
 * @see bp_activity_add() for more detailed description of parameters and
 *      return values.
 *
 * @param array $args {
 *     An array of arguments for the new activity item. Accepts all parameters
 *     of {@link bp_activity_add()}. However, this wrapper provides some
 *     additional defaults, as described below:
 *     @type string $component Default: the id of your Experiments component
 *           (usually 'experiments').
 *     @type bool $hide_sitewide Default: True if the current experiment is not
 *           public, otherwise false.
 * }
 * @return bool See {@link bp_activity_add()}.
 */
function experiments_record_activity( $args = '' ) {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Set the default for hide_sitewide by checking the status of the experiment
	$hide_sitewide = false;
	if ( !empty( $args['item_id'] ) ) {
		if ( bp_get_current_experiment_id() == $args['item_id'] ) {
			$experiment = experiments_get_current_experiment();
		} else {
			$experiment = experiments_get_experiment( array( 'experiment_id' => $args['item_id'] ) );
		}

		if ( isset( $experiment->status ) && 'public' != $experiment->status ) {
			$hide_sitewide = true;
		}
	}

	$r = wp_parse_args( $args, array(
		'id'                => false,
		'user_id'           => bp_loggedin_user_id(),
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => buddypress()->experiments->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => $hide_sitewide
	) );

	return bp_activity_add( $r );
}

/**
 * Update the last_activity meta value for a given experiment.
 *
 * @param int $experiment_id Optional. The ID of the experiment whose last_activity is
 *        being updated. Default: the current experiment's ID.
 */
function experiments_update_last_activity( $experiment_id = 0 ) {

	if ( empty( $experiment_id ) ) {
		$experiment_id = buddypress()->experiments->current_experiment->id;
	}

	if ( empty( $experiment_id ) ) {
		return false;
	}

	experiments_update_experimentmeta( $experiment_id, 'last_activity', bp_core_current_time() );
}
add_action( 'experiments_leave_experiment',          'experiments_update_last_activity' );
add_action( 'experiments_created_experiment',        'experiments_update_last_activity' );
add_action( 'experiments_new_forum_topic',      'experiments_update_last_activity' );
add_action( 'experiments_new_forum_topic_post', 'experiments_update_last_activity' );

/**
 * Add an activity stream item when a member joins a experiment
 *
 * @since BuddyPress (1.9.0)
 * @param int $user_id
 * @param int $experiment_id
 */
function bp_experiments_membership_accepted_add_activity( $user_id, $experiment_id ) {

	// Bail if Activity is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	// Get the experiment so we can get it's name
	$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );

	// Record in activity streams
	experiments_record_activity( array(
		'action'  => apply_filters_ref_array( 'experiments_activity_membership_accepted_action', array( sprintf( __( '%1$s joined the experiment %2$s', 'buddypress' ), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_experiment_permalink( $experiment ) . '">' . esc_attr( $experiment->name ) . '</a>' ), $user_id, &$experiment ) ),
		'type'    => 'joined_experiment',
		'item_id' => $experiment_id,
		'user_id' => $user_id
	) );
}
add_action( 'experiments_membership_accepted', 'bp_experiments_membership_accepted_add_activity', 10, 2 );

/**
 * Delete all experiment activity from activity streams
 *
 * @since BuddyPress (1.9.0)
 */
function bp_experiments_delete_experiment_delete_all_activity( $experiment_id ) {
	if ( bp_is_active( 'activity' ) ) {
		bp_activity_delete_by_item_id( array(
			'item_id'   => $experiment_id,
			'component' => buddypress()->experiments->id
		) );
	}
}
add_action( 'experiments_delete_experiment', 'bp_experiments_delete_experiment_delete_all_activity', 10 );

/**
 * Delete experiment member activity if they leave or are removed within 5 minutes of
 * membership modification.
 *
 * If the user joined this experiment less than five minutes ago, remove the
 * joined_experiment activity so users cannot flood the activity stream by
 * joining/leaving the experiment in quick succession.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_experiments_leave_experiment_delete_recent_activity( $experiment_id, $user_id ) {

	// Bail if Activity component is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Get the member's experiment membership information
	$membership = new BP_Experiments_Member( $user_id, $experiment_id );

	// Check the time period, and maybe delete their recent experiment activity
	if ( time() <= strtotime( '+5 minutes', (int) strtotime( $membership->date_modified ) ) ) {
		bp_activity_delete( array(
			'component' => buddypress()->experiments->id,
			'type'      => 'joined_experiment',
			'user_id'   => $user_id,
			'item_id'   => $experiment_id
		) );
	}
}
add_action( 'experiments_leave_experiment',   'bp_experiments_leave_experiment_delete_recent_activity', 10, 2 );
add_action( 'experiments_remove_member', 'bp_experiments_leave_experiment_delete_recent_activity', 10, 2 );
add_action( 'experiments_ban_member',    'bp_experiments_leave_experiment_delete_recent_activity', 10, 2 );
