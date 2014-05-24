<?php
/**
 * BuddyPress Experiments Functions
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyPress
 * @subpackage ExperimentsFunctions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Checks $bp pages global and looks for directory page
 *
 * @since BuddyPress (1.5)
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @return bool True if set, False if empty
 */
function bp_experiments_has_directory() {
	global $bp;

	return (bool) !empty( $bp->pages->experiments->id );
}

/**
 * Pulls up the database object corresponding to a experiment
 *
 * When calling up a experiment object, you should always use this function instead
 * of instantiating BP_Experiments_Experiment directly, so that you will inherit cache
 * support and pass through the experiments_get_experiment filter.
 *
 * @param string $args The load_users parameter is deprecated and does nothing.
 * @return BP_Experiments_Experiment $experiment The experiment object
 */
function experiments_get_experiment( $args = '' ) {
	$r = wp_parse_args( $args, array(
		'experiment_id'          => false,
		'load_users'        => false,
		'populate_extras'   => false,
	) );

	$experiment_args = array(
		'populate_extras' => $r['populate_extras'],
	);

	$experiment = new BP_Experiments_Experiment( $r['experiment_id'], $experiment_args );

	return apply_filters( 'experiments_get_experiment', $experiment );
}

/*** Experiment Creation, Editing & Deletion *****************************************/

/**
 * Create a experiment.
 *
 * @since BuddyPress (1.0.0)
 *
 * @param array $args {
 *     An array of arguments.
 *     @type int|bool $experiment_id Pass a experiment ID to update an existing item, or
 *           0 / false to create a new experiment. Default: 0.
 *     @type int $creator_id The user ID that creates the experiment.
 *     @type string $name The experiment name.
 *     @type string $description Optional. The experiment's description.
 *     @type string $slug The experiment slug.
 *     @type string $status The experiment's status. Accepts 'public', 'private' or
             'hidden'. Defaults to 'public'.
 *     @type int $enable_forum Optional. Whether the experiment has a forum enabled.
 *           If the legacy forums are enabled for this experiment or if a bbPress
 *           forum is enabled for the experiment, set this to 1. Default: 0.
 *     @type string $date_created The GMT time, in Y-m-d h:i:s format,
 *           when the experiment was created. Defaults to the current time.
 * }
 * @return int|bool The ID of the experiment on success. False on error.
 */
function experiments_create_experiment( $args = '' ) {

	$defaults = array(
		'experiment_id'     => 0,
		'creator_id'   => 0,
		'name'         => '',
		'description'  => '',
		'slug'         => '',
		'status'       => 'public',
		'enable_forum' => 0,
		'date_created' => bp_core_current_time()
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	// Pass an existing experiment ID
	if ( ! empty( $experiment_id ) ) {
		$experiment = experiments_get_experiment( array( 'experiment_id' => (int) $experiment_id ) );
		$name  = ! empty( $name ) ? $name : $experiment->name;
		$slug  = ! empty( $slug ) ? $slug : $experiment->slug;
		$description = ! empty( $description ) ? $description : $experiment->description;

		// Experiments need at least a name
		if ( empty( $name ) ) {
			return false;
		}

	// Create a new experiment
	} else {
		// Instantiate new experiment object
		$experiment = new BP_Experiments_Experiment;
	}

	// Set creator ID
	if ( ! empty( $creator_id ) ) {
		$experiment->creator_id = (int) $creator_id;
	} else {
		$experiment->creator_id = bp_loggedin_user_id();
	}

	// Validate status
	if ( ! experiments_is_valid_status( $status ) ) {
		return false;
	}

	// Set experiment name
	$experiment->name         = $name;
	$experiment->description  = $description;
	$experiment->slug         = $slug;
	$experiment->status       = $status;
	$experiment->enable_forum = (int) $enable_forum;
	$experiment->date_created = $date_created;

	// Save experiment
	if ( ! $experiment->save() ) {
		return false;
	}

	// If this is a new experiment, set up the creator as the first member and admin
	if ( empty( $experiment_id ) ) {
		$member                = new BP_Experiments_Member;
		$member->experiment_id      = $experiment->id;
		$member->user_id       = $experiment->creator_id;
		$member->is_admin      = 1;
		$member->user_title    = __( 'Experiment Admin', 'buddypress' );
		$member->is_confirmed  = 1;
		$member->date_modified = bp_core_current_time();
		$member->save();

		experiments_update_experimentmeta( $experiment->id, 'last_activity', bp_core_current_time() );

		do_action( 'experiments_create_experiment', $experiment->id, $member, $experiment );

	} else {
		do_action( 'experiments_update_experiment', $experiment->id, $experiment );
	}

	do_action( 'experiments_created_experiment', $experiment->id, $experiment );

	return $experiment->id;
}
    
    
    
    
    
    function experiments_create_variables( $args = '' ) {
        
        extract( $args );
        
        /**
         * Possible parameters (pass as assoc array):
         *	'experiment_id'
         *	'creator_id'
         *	'name'
         *	'description'
         *	'slug'
         *	'status'
         *	'enable_forum'
         *	'date_created'
         */
        
        if (empty( $experiment_id ) )
            return false;
        
        //$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );
        
        
        for($x = 0; $x < count($name); $x++ )
        {
            
            $variable = new BP_Experiments_Variable;
            $variable->experiment_id = $experiment_id;
            $variable->name = $name[$x];
            $variable->type = $type[$x];
            
            if (!$variable->save() )
                return false;
        }
        
        /*
         foreach( $name as $key => $variable_name ) {
         
         $variable[] = new BP_Experiments_Variable;
         $variable[]->experiment_id = $experiment_id;
         $variable[]->name = $variable_name;
         }
         
         foreach( $type as $key => $type ) {
         
         $variable[]->type = $type;
         }
         
         foreach( $variable as $key => $variable ) {
         
         if ( !$variable->save() )
         return false;
         }
         
         */
        
        /*
         $variable1 = new BP_Experiments_Variable;
         $variable1->experiment_id = $experiment_id;
         $variable1->name = $variable1_name;
         $variable1->type= $variable1_type;
         
         if ( !$variable1->save() )
         return false;
         else{
         
         
         $variable2 = new BP_Experiments_Variable;
         $variable2->experiment_id = $experiment_id;
         $variable2->name = $variable2_name;
         $variable2->type= $variable2_type;
         
         if ( !$variable2->save() )
         return false;
         }
         
         
         do_action( 'experiments_created_variables', $variable1->id, $variable1 );
         do_action( 'experiments_created_variables', $variable2->id, $variable2 );
         */
        
        return true;
    }
    
    

function experiments_edit_base_experiment_details( $experiment_id, $experiment_name, $experiment_desc, $notify_members ) {

	if ( empty( $experiment_name ) || empty( $experiment_desc ) )
		return false;

	$experiment              = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );
	$experiment->name        = $experiment_name;
	$experiment->description = $experiment_desc;

	if ( !$experiment->save() )
		return false;

	if ( $notify_members ) {
		experiments_notification_experiment_updated( $experiment->id );
	}

	do_action( 'experiments_details_updated', $experiment->id );

	return true;
}

function experiments_edit_experiment_settings( $experiment_id, $enable_forum, $status, $invite_status = false ) {

	$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );
	$experiment->enable_forum = $enable_forum;

	/***
	 * Before we potentially switch the experiment status, if it has been changed to public
	 * from private and there are outstanding membership requests, auto-accept those requests.
	 */
	if ( 'private' == $experiment->status && 'public' == $status )
		experiments_accept_all_pending_membership_requests( $experiment->id );

	// Now update the status
	$experiment->status = $status;

	if ( !$experiment->save() )
		return false;

	// If forums have been enabled, and a forum does not yet exist, we need to create one.
	if ( $experiment->enable_forum ) {
		if ( bp_is_active( 'forums' ) && !experiments_get_experimentmeta( $experiment->id, 'forum_id' ) ) {
			experiments_new_experiment_forum( $experiment->id, $experiment->name, $experiment->description );
		}
	}

	// Set the invite status
	if ( $invite_status )
		experiments_update_experimentmeta( $experiment->id, 'invite_status', $invite_status );

	experiments_update_experimentmeta( $experiment->id, 'last_activity', bp_core_current_time() );
	do_action( 'experiments_settings_updated', $experiment->id );

	return true;
}

/**
 * Delete a experiment and all of its associated meta
 *
 * @global object $bp BuddyPress global settings
 * @param int $experiment_id
 * @since BuddyPress (1.0)
 */
function experiments_delete_experiment( $experiment_id ) {

	do_action( 'experiments_before_delete_experiment', $experiment_id );

	// Get the experiment object
	$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );

	// Bail if experiment cannot be deleted
	if ( ! $experiment->delete() ) {
		return false;
	}

	// Remove all outstanding invites for this experiment
	experiments_delete_all_experiment_invites( $experiment_id );

	do_action( 'experiments_delete_experiment', $experiment_id );

	return true;
}

function experiments_is_valid_status( $status ) {
	global $bp;

	return in_array( $status, (array) $bp->experiments->valid_status );
}

function experiments_check_slug( $slug ) {
	global $bp;

	if ( 'wp' == substr( $slug, 0, 2 ) )
		$slug = substr( $slug, 2, strlen( $slug ) - 2 );

	if ( in_array( $slug, (array) $bp->experiments->forbidden_names ) )
		$slug = $slug . '-' . rand();

	if ( BP_Experiments_Experiment::check_slug( $slug ) ) {
		do {
			$slug = $slug . '-' . rand();
		}
		while ( BP_Experiments_Experiment::check_slug( $slug ) );
	}

	return $slug;
}

/**
 * Get a experiment slug by its ID
 *
 * @param int $experiment_id The numeric ID of the experiment
 * @return string The experiment's slug
 */
function experiments_get_slug( $experiment_id ) {
	$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );
	return !empty( $experiment->slug ) ? $experiment->slug : '';
}

/**
 * Get a experiment ID by its slug
 *
 * @since BuddyPress (1.6)
 *
 * @param string $experiment_slug The experiment's slug
 * @return int The ID
 */
function experiments_get_id( $experiment_slug ) {
	return (int)BP_Experiments_Experiment::experiment_exists( $experiment_slug );
}

/*** User Actions ***************************************************************/

function experiments_leave_experiment( $experiment_id, $user_id = 0 ) {
	global $bp;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Don't let single admins leave the experiment.
	if ( count( experiments_get_experiment_admins( $experiment_id ) ) < 2 ) {
		if ( experiments_is_user_admin( $user_id, $experiment_id ) ) {
			bp_core_add_message( __( 'As the only Admin, you cannot leave the experiment.', 'buddypress' ), 'error' );
			return false;
		}
	}

	// This is exactly the same as deleting an invite, just is_confirmed = 1 NOT 0.
	if ( !experiments_uninvite_user( $user_id, $experiment_id ) ) {
		return false;
	}

	bp_core_add_message( __( 'You successfully left the experiment.', 'buddypress' ) );

	do_action( 'experiments_leave_experiment', $experiment_id, $user_id );

	return true;
}

function experiments_join_experiment( $experiment_id, $user_id = 0 ) {
	global $bp;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Check if the user has an outstanding invite. If so, delete it.
	if ( experiments_check_user_has_invite( $user_id, $experiment_id ) )
		experiments_delete_invite( $user_id, $experiment_id );

	// Check if the user has an outstanding request. If so, delete it.
	if ( experiments_check_for_membership_request( $user_id, $experiment_id ) )
		experiments_delete_membership_request( $user_id, $experiment_id );

	// User is already a member, just return true
	if ( experiments_is_user_member( $user_id, $experiment_id ) )
		return true;

	$new_member                = new BP_Experiments_Member;
	$new_member->experiment_id      = $experiment_id;
	$new_member->user_id       = $user_id;
	$new_member->inviter_id    = 0;
	$new_member->is_admin      = 0;
	$new_member->user_title    = '';
	$new_member->date_modified = bp_core_current_time();
	$new_member->is_confirmed  = 1;

	if ( !$new_member->save() )
		return false;

	if ( !isset( $bp->experiments->current_experiment ) || !$bp->experiments->current_experiment || $experiment_id != $bp->experiments->current_experiment->id )
		$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );
	else
		$experiment = $bp->experiments->current_experiment;

	// Record this in activity streams
	experiments_record_activity( array(
		'type'    => 'joined_experiment',
		'item_id' => $experiment_id,
		'user_id' => $user_id,
	) );

	// Modify experiment meta
	experiments_update_experimentmeta( $experiment_id, 'last_activity', bp_core_current_time() );

	do_action( 'experiments_join_experiment', $experiment_id, $user_id );

	return true;
}

/*** General Experiment Functions ****************************************************/

function experiments_get_experiment_admins( $experiment_id ) {
	return BP_Experiments_Member::get_experiment_administrator_ids( $experiment_id );
}

function experiments_get_experiment_mods( $experiment_id ) {
	return BP_Experiments_Member::get_experiment_moderator_ids( $experiment_id );
}

/**
 * Fetch the members of a experiment.
 *
 * Since BuddyPress 1.8, a procedural wrapper for BP_Experiment_Member_Query.
 * Previously called BP_Experiments_Member::get_all_for_experiment().
 *
 * To use the legacy query, filter 'bp_use_legacy_experiment_member_query',
 * returning true.
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type int $experiment_id ID of the experiment whose members are being queried.
 *           Default: current experiment ID.
 *     @type int $page Page of results to be queried. Default: 1.
 *     @type int $per_page Number of items to return per page of results.
 *           Default: 20.
 *     @type int $max Optional. Max number of items to return.
 *     @type array $exclude Optional. Array of user IDs to exclude.
 *     @type bool|int True (or 1) to exclude admins and mods from results.
 *           Default: 1.
 *     @type bool|int True (or 1) to exclude banned users from results.
 *           Default: 1.
 *     @type array $experiment_role Optional. Array of experiment roles to include.
 *     @type string $search_terms Optional. Filter results by a search string.
 *     @type string $type Optional. Sort the order of results. 'last_joined',
 *           'first_joined', or any of the $type params available in
 *           {@link BP_User_Query}. Default: 'last_joined'.
 * }
 * @return array Multi-d array of 'members' list and 'count'.
 */
function experiments_get_experiment_members( $args = array() ) {

	// Backward compatibility with old method of passing arguments
	if ( ! is_array( $args ) || func_num_args() > 1 ) {
		_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

		$old_args_keys = array(
			0 => 'experiment_id',
			1 => 'per_page',
			2 => 'page',
			3 => 'exclude_admins_mods',
			4 => 'exclude_banned',
			5 => 'exclude',
			6 => 'experiment_role',
		);

		$func_args = func_get_args();
		$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
	}

	$r = wp_parse_args( $args, array(
		'experiment_id'            => bp_get_current_experiment_id(),
		'per_page'            => false,
		'page'                => false,
		'exclude_admins_mods' => true,
		'exclude_banned'      => true,
		'exclude'             => false,
		'experiment_role'          => array(),
		'search_terms'        => false,
		'type'                => 'last_joined',
	) );

	// For legacy users. Use of BP_Experiments_Member::get_all_for_experiment()
	// is deprecated. func_get_args() can't be passed to a function in PHP
	// 5.2.x, so we create a variable
	$func_args = func_get_args();
	if ( apply_filters( 'bp_use_legacy_experiment_member_query', false, __FUNCTION__, $func_args ) ) {
		$retval = BP_Experiments_Member::get_all_for_experiment( $r['experiment_id'], $r['per_page'], $r['page'], $r['exclude_admins_mods'], $r['exclude_banned'], $r['exclude'] );
	} else {

		// exclude_admins_mods and exclude_banned are legacy arguments.
		// Convert to experiment_role
		if ( empty( $r['experiment_role'] ) ) {
			$r['experiment_role'] = array( 'member' );

			if ( ! $r['exclude_admins_mods'] ) {
				$r['experiment_role'][] = 'mod';
				$r['experiment_role'][] = 'admin';
			}

			if ( ! $r['exclude_banned'] ) {
				$r['experiment_role'][] = 'banned';
			}
		}

		// Perform the experiment member query (extends BP_User_Query)
		$members = new BP_Experiment_Member_Query( array(
			'experiment_id'       => $r['experiment_id'],
			'per_page'       => $r['per_page'],
			'page'           => $r['page'],
			'experiment_role'     => $r['experiment_role'],
			'exclude'        => $r['exclude'],
			'search_terms'   => $r['search_terms'],
			'type'           => $r['type'],
		) );

		// Structure the return value as expected by the template functions
		$retval = array(
			'members' => array_values( $members->results ),
			'count'   => $members->total_users,
		);
	}

	return $retval;
}

function experiments_get_total_member_count( $experiment_id ) {
	return BP_Experiments_Experiment::get_total_member_count( $experiment_id );
}

/*** Experiment Fetching, Filtering & Searching  *************************************/

/**
 * Get a collection of experiments, based on the parameters passed
 *
 * @uses apply_filters_ref_array() Filter 'experiments_get_experiments' to modify return value
 * @uses BP_Experiments_Experiment::get()
 * @param array $args See inline documentation for details
 * @return array
 */
function experiments_get_experiments( $args = '' ) {

	$defaults = array(
		'type'              => false,    // active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts
		'order'             => 'DESC',   // 'ASC' or 'DESC'
		'orderby'           => 'date_created', // date_created, last_activity, total_member_count, name, random
		'user_id'           => false,    // Pass a user_id to limit to only experiments that this user is a member of
		'include'           => false,    // Only include these specific experiments (experiment_ids)
		'exclude'           => false,    // Do not include these specific experiments (experiment_ids)
		'search_terms'      => false,    // Limit to experiments that match these search terms
		'meta_query'        => false,    // Filter by experimentmeta. See WP_Meta_Query for syntax
		'show_hidden'       => false,    // Show hidden experiments to non-admins
		'per_page'          => 20,       // The number of results to return per page
		'page'              => 1,        // The page to return if limiting per page
		'populate_extras'   => true,     // Fetch meta such as is_banned and is_member
		'update_meta_cache' => true,   // Pre-fetch experimentmeta for queried experiments
	);

	$r = wp_parse_args( $args, $defaults );

	$experiments = BP_Experiments_Experiment::get( array(
		'type'              => $r['type'],
		'user_id'           => $r['user_id'],
		'include'           => $r['include'],
		'exclude'           => $r['exclude'],
		'search_terms'      => $r['search_terms'],
		'meta_query'        => $r['meta_query'],
		'show_hidden'       => $r['show_hidden'],
		'per_page'          => $r['per_page'],
		'page'              => $r['page'],
		'populate_extras'   => $r['populate_extras'],
		'update_meta_cache' => $r['update_meta_cache'],
		'order'             => $r['order'],
		'orderby'           => $r['orderby'],
	) );

	return apply_filters_ref_array( 'experiments_get_experiments', array( &$experiments, &$r ) );
}

function experiments_get_total_experiment_count() {
	if ( !$count = wp_cache_get( 'bp_total_experiment_count', 'bp' ) ) {
		$count = BP_Experiments_Experiment::get_total_experiment_count();
		wp_cache_set( 'bp_total_experiment_count', $count, 'bp' );
	}

	return $count;
}

function experiments_get_user_experiments( $user_id = 0, $pag_num = 0, $pag_page = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	return BP_Experiments_Member::get_experiment_ids( $user_id, $pag_num, $pag_page );
}

function experiments_total_experiments_for_user( $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	if ( !$count = wp_cache_get( 'bp_total_experiments_for_user_' . $user_id, 'bp' ) ) {
		$count = BP_Experiments_Member::total_experiment_count( $user_id );
		wp_cache_set( 'bp_total_experiments_for_user_' . $user_id, $count, 'bp' );
	}

	return $count;
}

/**
 * Returns the experiment object for the experiment currently being viewed
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return BP_Experiments_Experiment The current experiment object
 */
function experiments_get_current_experiment() {
	global $bp;

	$current_experiment = isset( $bp->experiments->current_experiment ) ? $bp->experiments->current_experiment : false;

	return apply_filters( 'experiments_get_current_experiment', $current_experiment );
}

/*** Experiment Avatars *************************************************************/

function experiments_avatar_upload_dir( $experiment_id = 0 ) {
	global $bp;

	if ( !$experiment_id )
		$experiment_id = $bp->experiments->current_experiment->id;

	$path    = bp_core_avatar_upload_path() . '/experiment-avatars/' . $experiment_id;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl    = bp_core_avatar_url() . '/experiment-avatars/' . $experiment_id;
	$newburl   = $newurl;
	$newsubdir = '/experiment-avatars/' . $experiment_id;

	return apply_filters( 'experiments_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

/*** Experiment Member Status Checks ************************************************/

function experiments_is_user_admin( $user_id, $experiment_id ) {
	return BP_Experiments_Member::check_is_admin( $user_id, $experiment_id );
}

function experiments_is_user_mod( $user_id, $experiment_id ) {
	return BP_Experiments_Member::check_is_mod( $user_id, $experiment_id );
}

function experiments_is_user_member( $user_id, $experiment_id ) {
	return BP_Experiments_Member::check_is_member( $user_id, $experiment_id );
}

function experiments_is_user_banned( $user_id, $experiment_id ) {
	return BP_Experiments_Member::check_is_banned( $user_id, $experiment_id );
}

/**
 * Is the specified user the creator of the experiment?
 *
 * @param int $user_id
 * @param int $experiment_id
 * @since BuddyPress (1.2.6)
 * @uses BP_Experiments_Member
 */
function experiments_is_user_creator( $user_id, $experiment_id ) {
	return BP_Experiments_Member::check_is_creator( $user_id, $experiment_id );
}

/*** Experiment Activity Posting **************************************************/

function experiments_post_update( $args = '' ) {
	global $bp;

	$defaults = array(
		'content'  => false,
		'user_id'  => bp_loggedin_user_id(),
		'experiment_id' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( empty( $experiment_id ) && !empty( $bp->experiments->current_experiment->id ) )
		$experiment_id = $bp->experiments->current_experiment->id;

	if ( empty( $content ) || !strlen( trim( $content ) ) || empty( $user_id ) || empty( $experiment_id ) )
		return false;

	$bp->experiments->current_experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );

	// Be sure the user is a member of the experiment before posting.
	if ( !bp_current_user_can( 'bp_moderate' ) && !experiments_is_user_member( $user_id, $experiment_id ) )
		return false;

	// Record this in activity streams
	$activity_action  = sprintf( __( '%1$s posted an update in the experiment %2$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_experiment_permalink( $bp->experiments->current_experiment ) . '">' . esc_attr( $bp->experiments->current_experiment->name ) . '</a>' );
	$activity_content = $content;

	$activity_id = experiments_record_activity( array(
		'user_id' => $user_id,
		'action'  => apply_filters( 'experiments_activity_new_update_action',  $activity_action  ),
		'content' => apply_filters( 'experiments_activity_new_update_content', $activity_content ),
		'type'    => 'activity_update',
		'item_id' => $experiment_id
	) );

	experiments_update_experimentmeta( $experiment_id, 'last_activity', bp_core_current_time() );
	do_action( 'bp_experiments_posted_update', $content, $user_id, $experiment_id, $activity_id );

	return $activity_id;
}

/*** Experiment Invitations *********************************************************/

function experiments_get_invites_for_user( $user_id = 0, $limit = false, $page = false, $exclude = false ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	return BP_Experiments_Member::get_invites( $user_id, $limit, $page, $exclude );
}

/**
 * Gets the total experiment invite count for a user.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $user_id The user ID
 * @return int
 */
function experiments_get_invite_count_for_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return BP_Experiments_Member::get_invite_count_for_user( $user_id );
}

function experiments_invite_user( $args = '' ) {

	$defaults = array(
		'user_id'       => false,
		'experiment_id'      => false,
		'inviter_id'    => bp_loggedin_user_id(),
		'date_modified' => bp_core_current_time(),
		'is_confirmed'  => 0
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	if ( empty( $user_id ) || empty( $experiment_id ) )
		return false;

	// if the user has already requested membership, accept the request
	if ( $membership_id = experiments_check_for_membership_request( $user_id, $experiment_id ) ) {
		experiments_accept_membership_request( $membership_id, $user_id, $experiment_id );

	// Otherwise, create a new invitation
	} else if ( ! experiments_is_user_member( $user_id, $experiment_id ) && ! experiments_check_user_has_invite( $user_id, $experiment_id, 'all' ) ) {
		$invite                = new BP_Experiments_Member;
		$invite->experiment_id      = $experiment_id;
		$invite->user_id       = $user_id;
		$invite->date_modified = $date_modified;
		$invite->inviter_id    = $inviter_id;
		$invite->is_confirmed  = $is_confirmed;

		if ( !$invite->save() )
			return false;

		do_action( 'experiments_invite_user', $args );
	}

	return true;
}

function experiments_uninvite_user( $user_id, $experiment_id ) {

	if ( !BP_Experiments_Member::delete( $user_id, $experiment_id ) )
		return false;

	do_action( 'experiments_uninvite_user', $experiment_id, $user_id );

	return true;
}

/**
 * Process the acceptance of a experiment invitation.
 *
 * Returns true if a user is already a member of the experiment.
 *
 * @param int $user_id
 * @param int $experiment_id
 * @return bool True when the user is a member of the experiment, otherwise false
 */
function experiments_accept_invite( $user_id, $experiment_id ) {

	// If the user is already a member (because BP at one point allowed two invitations to
	// slip through), delete all existing invitations/requests and return true
	if ( experiments_is_user_member( $user_id, $experiment_id ) ) {
		if ( experiments_check_user_has_invite( $user_id, $experiment_id ) ) {
			experiments_delete_invite( $user_id, $experiment_id );
		}

		if ( experiments_check_for_membership_request( $user_id, $experiment_id ) ) {
			experiments_delete_membership_request( $user_id, $experiment_id );
		}

		return true;
	}

	$member = new BP_Experiments_Member( $user_id, $experiment_id );
	$member->accept_invite();

	if ( !$member->save() ) {
		return false;
	}

	// Remove request to join
	if ( $member->check_for_membership_request( $user_id, $experiment_id ) ) {
		$member->delete_request( $user_id, $experiment_id );
	}

	// Modify experiment meta
	experiments_update_experimentmeta( $experiment_id, 'last_activity', bp_core_current_time() );

	do_action( 'experiments_accept_invite', $user_id, $experiment_id );

	return true;
}

function experiments_reject_invite( $user_id, $experiment_id ) {
	if ( ! BP_Experiments_Member::delete( $user_id, $experiment_id ) )
		return false;

	do_action( 'experiments_reject_invite', $user_id, $experiment_id );

	return true;
}

function experiments_delete_invite( $user_id, $experiment_id ) {
	if ( ! BP_Experiments_Member::delete_invite( $user_id, $experiment_id ) )
		return false;

	do_action( 'experiments_delete_invite', $user_id, $experiment_id );

	return true;
}

function experiments_send_invites( $user_id, $experiment_id ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Send friend invites.
	$invited_users = experiments_get_invites_for_experiment( $user_id, $experiment_id );
	$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );

	for ( $i = 0, $count = count( $invited_users ); $i < $count; ++$i ) {
		$member = new BP_Experiments_Member( $invited_users[$i], $experiment_id );

		// Send the actual invite
		experiments_notification_experiment_invites( $experiment, $member, $user_id );

		$member->invite_sent = 1;
		$member->save();
	}

	do_action( 'experiments_send_invites', $experiment_id, $invited_users );
}

function experiments_get_invites_for_experiment( $user_id, $experiment_id ) {
	return BP_Experiments_Experiment::get_invites( $user_id, $experiment_id );
}

/**
 * Check to see whether a user has already been invited to a experiment
 *
 * By default, the function checks for invitations that have been sent. Entering 'all' as the $type
 * parameter will return unsent invitations as well (useful to make sure AJAX requests are not
 * duplicated)
 *
 * @package BuddyPress Experiments
 *
 * @param int $user_id Potential experiment member
 * @param int $experiment_id Potential experiment
 * @param string $type Optional. Use 'sent' to check for sent invites, 'all' to check for all
 * @return bool Returns true if an invitation is found
 */
function experiments_check_user_has_invite( $user_id, $experiment_id, $type = 'sent' ) {
	return BP_Experiments_Member::check_has_invite( $user_id, $experiment_id, $type );
}

function experiments_delete_all_experiment_invites( $experiment_id ) {
	return BP_Experiments_Experiment::delete_all_invites( $experiment_id );
}

/*** Experiment Promotion & Banning *************************************************/

function experiments_promote_member( $user_id, $experiment_id, $status ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Experiments_Member( $user_id, $experiment_id );

	// Don't use this action. It's deprecated as of BuddyPress 1.6.
	do_action( 'experiments_premote_member', $experiment_id, $user_id, $status );

	// Use this action instead.
	do_action( 'experiments_promote_member', $experiment_id, $user_id, $status );

	return $member->promote( $status );
}

function experiments_demote_member( $user_id, $experiment_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Experiments_Member( $user_id, $experiment_id );

	do_action( 'experiments_demote_member', $experiment_id, $user_id );

	return $member->demote();
}

function experiments_ban_member( $user_id, $experiment_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Experiments_Member( $user_id, $experiment_id );

	do_action( 'experiments_ban_member', $experiment_id, $user_id );

	return $member->ban();
}

function experiments_unban_member( $user_id, $experiment_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Experiments_Member( $user_id, $experiment_id );

	do_action( 'experiments_unban_member', $experiment_id, $user_id );

	return $member->unban();
}

/*** Experiment Removal *******************************************************/

function experiments_remove_member( $user_id, $experiment_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Experiments_Member( $user_id, $experiment_id );

	do_action( 'experiments_remove_member', $experiment_id, $user_id );

	return $member->remove();
}

/*** Experiment Membership ****************************************************/

function experiments_send_membership_request( $requesting_user_id, $experiment_id ) {

	// Prevent duplicate requests
	if ( experiments_check_for_membership_request( $requesting_user_id, $experiment_id ) )
		return false;

	// Check if the user is already a member or is banned
	if ( experiments_is_user_member( $requesting_user_id, $experiment_id ) || experiments_is_user_banned( $requesting_user_id, $experiment_id ) )
		return false;

	// Check if the user is already invited - if so, simply accept invite
	if ( experiments_check_user_has_invite( $requesting_user_id, $experiment_id ) ) {
		experiments_accept_invite( $requesting_user_id, $experiment_id );
		return true;
	}

	$requesting_user                = new BP_Experiments_Member;
	$requesting_user->experiment_id      = $experiment_id;
	$requesting_user->user_id       = $requesting_user_id;
	$requesting_user->inviter_id    = 0;
	$requesting_user->is_admin      = 0;
	$requesting_user->user_title    = '';
	$requesting_user->date_modified = bp_core_current_time();
	$requesting_user->is_confirmed  = 0;
	$requesting_user->comments      = isset( $_POST['experiment-request-membership-comments'] ) ? $_POST['experiment-request-membership-comments'] : '';

	if ( $requesting_user->save() ) {
		$admins = experiments_get_experiment_admins( $experiment_id );

		// Saved okay, now send the email notification
		for ( $i = 0, $count = count( $admins ); $i < $count; ++$i )
			experiments_notification_new_membership_request( $requesting_user_id, $admins[$i]->user_id, $experiment_id, $requesting_user->id );

		do_action( 'experiments_membership_requested', $requesting_user_id, $admins, $experiment_id, $requesting_user->id );

		return true;
	}

	return false;
}

function experiments_accept_membership_request( $membership_id, $user_id = 0, $experiment_id = 0 ) {

	if ( !empty( $user_id ) && !empty( $experiment_id ) ) {
		$membership = new BP_Experiments_Member( $user_id, $experiment_id );
	} else {
		$membership = new BP_Experiments_Member( false, false, $membership_id );
	}

	$membership->accept_request();

	if ( !$membership->save() ) {
		return false;
	}

	// Check if the user has an outstanding invite, if so delete it.
	if ( experiments_check_user_has_invite( $membership->user_id, $membership->experiment_id ) ) {
		experiments_delete_invite( $membership->user_id, $membership->experiment_id );
	}

	do_action( 'experiments_membership_accepted', $membership->user_id, $membership->experiment_id, true );

	return true;
}

function experiments_reject_membership_request( $membership_id, $user_id = 0, $experiment_id = 0 ) {
	if ( !$membership = experiments_delete_membership_request( $membership_id, $user_id, $experiment_id ) ) {
		return false;
	}

	do_action( 'experiments_membership_rejected', $membership->user_id, $membership->experiment_id, false );

	return true;
}

function experiments_delete_membership_request( $membership_id, $user_id = 0, $experiment_id = 0 ) {
	if ( !empty( $user_id ) && !empty( $experiment_id ) )
		$membership = new BP_Experiments_Member( $user_id, $experiment_id );
	else
		$membership = new BP_Experiments_Member( false, false, $membership_id );

	if ( !BP_Experiments_Member::delete( $membership->user_id, $membership->experiment_id ) )
		return false;

	return $membership;
}

function experiments_check_for_membership_request( $user_id, $experiment_id ) {
	return BP_Experiments_Member::check_for_membership_request( $user_id, $experiment_id );
}

function experiments_accept_all_pending_membership_requests( $experiment_id ) {
	$user_ids = BP_Experiments_Member::get_all_membership_request_user_ids( $experiment_id );

	if ( !$user_ids )
		return false;

	foreach ( (array) $user_ids as $user_id )
		experiments_accept_membership_request( false, $user_id, $experiment_id );

	do_action( 'experiments_accept_all_pending_membership_requests', $experiment_id );

	return true;
}

/*** Experiment Meta ****************************************************/

/**
 * Delete metadata for a experiment.
 *
 * @param int $experiment_id ID of the experiment.
 * @param string $meta_key The key of the row to delete.
 * @param string $meta_value Optional. Metadata value. If specified, only delete
 *        metadata entries with this value.
 * @param bool $delete_all Optional. If true, delete matching metadata entries
 *        for all experiments. Default: false.
 * @param bool $delete_all Optional. If true, delete matching metadata entries
 * 	  for all objects, ignoring the specified experiment_id. Otherwise, only
 * 	  delete matching metadata entries for the specified experiment.
 * 	  Default: false.
 * @return bool True on success, false on failure.
 */
function experiments_delete_experimentmeta( $experiment_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Legacy - if no meta_key is passed, delete all for the item
	if ( empty( $meta_key ) ) {
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->experimentmeta} WHERE experiment_id = %d", $experiment_id ) );

		// With no meta_key, ignore $delete_all
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'experiment', $experiment_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of experiment metadata.
 *
 * @param int $experiment_id ID of the experiment.
 * @param string $meta_key Metadata key.
 * @param bool $single Optional. If true, return only the first value of the
 *        specified meta_key. This parameter has no effect if meta_key is
 *        empty.
 * @return mixed Metadata value.
 */
function experiments_get_experimentmeta( $experiment_id, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'experiment', $experiment_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update a piece of experiment metadata.
 *
 * @param int $experiment_id ID of the experiment.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Value to store.
 * @param mixed $prev_value Optional. If specified, only update existing
 *        metadata entries with the specified value. Otherwise, update all
 *        entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *         metadata, returns true. On successful creation of new metadata,
 *         returns the integer ID of the new metadata row.
 */
function experiments_update_experimentmeta( $experiment_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'experiment', $experiment_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of experiment metadata.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int $experiment_id ID of the experiment.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique. Optional. Whether to enforce a single metadata value
 *        for the given key. If true, and the object already has a value for
 *        the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function experiments_add_experimentmeta( $experiment_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'experiment', $experiment_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/*** Experiment Cleanup Functions ****************************************************/

function experiments_remove_data_for_user( $user_id ) {
	BP_Experiments_Member::delete_all_for_user( $user_id );

	do_action( 'experiments_remove_data_for_user', $user_id );
}
add_action( 'wpmu_delete_user',  'experiments_remove_data_for_user' );
add_action( 'delete_user',       'experiments_remove_data_for_user' );
add_action( 'bp_make_spam_user', 'experiments_remove_data_for_user' );
