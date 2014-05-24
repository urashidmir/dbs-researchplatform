<?php
/**
 * BuddyPress Experiments Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage Experiments
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Slurp up metadata for a set of experiments.
 *
 * This function is called in two places in the BP_Experiments_Experiment class:
 *   - in the populate() method, when single experiment objects are populated
 *   - in the get() method, when multiple experiments are queried
 *
 * It grabs all experimentmeta associated with all of the experiments passed in
 * $experiment_ids and adds it to WP cache. This improves efficiency when using
 * experimentmeta within a loop context.
 *
 * @param int|str|array $experiment_ids Accepts a single experiment_id, or a
 *        comma-separated list or array of experiment ids.
 */
function bp_experiments_update_meta_cache( $experiment_ids = false ) {
	global $bp;

	$cache_args = array(
		'object_ids' 	   => $experiment_ids,
		'object_type' 	   => $bp->experiments->id,
		'cache_experiment'      => 'experiment_meta',
		'object_column'    => 'experiment_id',
		'meta_table' 	   => $bp->experiments->table_name_experimentmeta,
		'cache_key_prefix' => 'bp_experiments_experimentmeta'
	);

	bp_update_meta_cache( $cache_args );
}

/**
 * Clear the cached experiment count.
 *
 * @param $experiment_id Not used.
 */
function experiments_clear_experiment_object_cache( $experiment_id ) {
	wp_cache_delete( 'bp_total_experiment_count', 'bp' );
}
add_action( 'experiments_experiment_deleted',              'experiments_clear_experiment_object_cache' );
add_action( 'experiments_settings_updated',           'experiments_clear_experiment_object_cache' );
add_action( 'experiments_details_updated',            'experiments_clear_experiment_object_cache' );
add_action( 'experiments_experiment_avatar_updated',       'experiments_clear_experiment_object_cache' );
add_action( 'experiments_create_experiment_step_complete', 'experiments_clear_experiment_object_cache' );

/**
 * Bust experiment caches when editing or deleting.
 *
 * @since BuddyPress (1.7.0)
 *
 * @param int $experiment_id The experiment being edited.
 */
function bp_experiments_delete_experiment_cache( $experiment_id = 0 ) {
	wp_cache_delete( $experiment_id, 'bp_experiments' );
}
add_action( 'experiments_delete_experiment',     'bp_experiments_delete_experiment_cache' );
add_action( 'experiments_update_experiment',     'bp_experiments_delete_experiment_cache' );
add_action( 'experiments_details_updated',  'bp_experiments_delete_experiment_cache' );
add_action( 'experiments_settings_updated', 'bp_experiments_delete_experiment_cache' );

/**
 * Bust experiment cache when modifying metadata.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_experiments_delete_experiment_cache_on_metadata_change( $meta_id, $experiment_id ) {
	wp_cache_delete( $experiment_id, 'bp_experiments' );
}
add_action( 'updated_experiment_meta', 'bp_experiments_delete_experiment_cache_on_metadata_change', 10, 2 );
add_action( 'added_experiment_meta', 'bp_experiments_delete_experiment_cache_on_metadata_change', 10, 2 );

/**
 * Clear caches for the experiment creator when a experiment is created.
 *
 * @since BuddyPress (1.6.0)
 *
 * @param int $experiment_id ID of the experiment.
 * @param BP_Experiments_Experiment $experiment_obj Experiment object.
 */
function bp_experiments_clear_experiment_creator_cache( $experiment_id, $experiment_obj ) {
	// Clears the 'total experiments' for this user
	experiments_clear_experiment_user_object_cache( $experiment_obj->id, $experiment_obj->creator_id );
}
add_action( 'experiments_created_experiment', 'bp_experiments_clear_experiment_creator_cache', 10, 2 );

/**
 * Clears caches for all members in a experiment when a experiment is deleted
 *
 * @since BuddyPress (1.6.0)
 *
 * @param BP_Experiments_Experiment $experiment_obj Experiment object.
 * @param array User IDs who were in this experiment.
 */
function bp_experiments_clear_experiment_members_caches( $experiment_obj, $user_ids ) {
	// Clears the 'total experiments' cache for each member in a experiment
	foreach ( (array) $user_ids as $user_id )
		experiments_clear_experiment_user_object_cache( $experiment_obj->id, $user_id );
}
add_action( 'bp_experiments_delete_experiment', 'bp_experiments_clear_experiment_members_caches', 10, 2 );

/**
 * Clear a user's cached total experiment invite count.
 *
 * Count is cleared when an invite is accepted, rejected or deleted.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $user_id The user ID.
 */
function bp_experiments_clear_invite_count_for_user( $user_id ) {
	wp_cache_delete( $user_id, 'bp_experiment_invite_count' );
}
add_action( 'experiments_accept_invite', 'bp_experiments_clear_invite_count_for_user' );
add_action( 'experiments_reject_invite', 'bp_experiments_clear_invite_count_for_user' );
add_action( 'experiments_delete_invite', 'bp_experiments_clear_invite_count_for_user' );

/**
 * Clear a user's cached total experiment invite count when a user is uninvited.
 *
 * Groan. Our API functions are not consistent.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $experiment_id The experiment ID. Not used in this function.
 * @param int $user_id The user ID.
 */
function bp_experiments_clear_invite_count_on_uninvite( $experiment_id, $user_id ) {
	bp_experiments_clear_invite_count_for_user( $user_id );
}
add_action( 'experiments_uninvite_user', 'bp_experiments_clear_invite_count_on_uninvite', 10, 2 );

/**
 * Clear a user's cached total experiment invite count when a new invite is sent.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $experiment_id The experiment ID. Not used in this function.
 * @param array $invited_users Array of invited user IDs.
 */
function bp_experiments_clear_invite_count_on_send( $experiment_id, $invited_users ) {
	foreach ( $invited_users as $user_id ) {
		bp_experiments_clear_invite_count_for_user( $user_id );
	}
}
add_action( 'experiments_send_invites', 'bp_experiments_clear_invite_count_on_send', 10, 2 );

/**
 * Clear a user's cached experiment count.
 *
 * @param int $experiment_id The experiment ID. Not used in this function.
 * @param int $user_id The user ID.
 */
function experiments_clear_experiment_user_object_cache( $experiment_id, $user_id ) {
	wp_cache_delete( 'bp_total_experiments_for_user_' . $user_id, 'bp' );
}
add_action( 'experiments_join_experiment',    'experiments_clear_experiment_user_object_cache', 10, 2 );
add_action( 'experiments_leave_experiment',   'experiments_clear_experiment_user_object_cache', 10, 2 );
add_action( 'experiments_ban_member',    'experiments_clear_experiment_user_object_cache', 10, 2 );
add_action( 'experiments_unban_member',  'experiments_clear_experiment_user_object_cache', 10, 2 );
add_action( 'experiments_uninvite_user', 'experiments_clear_experiment_user_object_cache', 10, 2 );
add_action( 'experiments_remove_member', 'experiments_clear_experiment_user_object_cache', 10, 2 );

/* List actions to clear super cached pages on, if super cache is installed */
add_action( 'experiments_join_experiment',                 'bp_core_clear_cache' );
add_action( 'experiments_leave_experiment',                'bp_core_clear_cache' );
add_action( 'experiments_accept_invite',              'bp_core_clear_cache' );
add_action( 'experiments_reject_invite',              'bp_core_clear_cache' );
add_action( 'experiments_invite_user',                'bp_core_clear_cache' );
add_action( 'experiments_uninvite_user',              'bp_core_clear_cache' );
add_action( 'experiments_details_updated',            'bp_core_clear_cache' );
add_action( 'experiments_settings_updated',           'bp_core_clear_cache' );
add_action( 'experiments_unban_member',               'bp_core_clear_cache' );
add_action( 'experiments_ban_member',                 'bp_core_clear_cache' );
add_action( 'experiments_demote_member',              'bp_core_clear_cache' );
add_action( 'experiments_promote_member',             'bp_core_clear_cache' );
add_action( 'experiments_membership_rejected',        'bp_core_clear_cache' );
add_action( 'experiments_membership_accepted',        'bp_core_clear_cache' );
add_action( 'experiments_membership_requested',       'bp_core_clear_cache' );
add_action( 'experiments_create_experiment_step_complete', 'bp_core_clear_cache' );
add_action( 'experiments_created_experiment',              'bp_core_clear_cache' );
add_action( 'experiments_experiment_avatar_updated',       'bp_core_clear_cache' );
