<?php
/**
 * BuddyPress Experiments Toolbar
 *
 * Handles the experiments functions related to the WordPress Toolbar.
 *
 * @package BuddyPress
 * @subpackage Experiments
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add the Experiment Admin top-level menu when viewing experiment pages.
 *
 * @since BuddyPress (1.5.0)
 *
 * @todo Add dynamic menu items for experiment extensions.
 */
function bp_experiments_experiment_admin_menu() {
	global $wp_admin_bar, $bp;

	// Only show if viewing a experiment
	if ( !bp_is_experiment() )
		return false;

	// Only show this menu to experiment admins and super admins
	if ( !bp_current_user_can( 'bp_moderate' ) && !bp_experiment_is_admin() )
		return false;

	// Unique ID for the 'Edit Experiment' menu
	$bp->experiment_admin_menu_id = 'experiment-admin';

	// Add the top-level Experiment Admin button
	$wp_admin_bar->add_menu( array(
		'id'    => $bp->experiment_admin_menu_id,
		'title' => __( 'Edit Experiment', 'buddypress' ),
		'href'  => bp_get_experiment_permalink( $bp->experiments->current_experiment )
	) );

	// Experiment Admin > Edit details
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->experiment_admin_menu_id,
		'id'     => 'edit-details',
		'title'  => __( 'Edit Details', 'buddypress' ),
		'href'   =>  bp_get_experiments_action_link( 'admin/edit-details' )
	) );

	// Experiment Admin > Experiment settings
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->experiment_admin_menu_id,
		'id'     => 'experiment-settings',
		'title'  => __( 'Edit Settings', 'buddypress' ),
		'href'   =>  bp_get_experiments_action_link( 'admin/experiment-settings' )
	) );

	// Experiment Admin > Experiment avatar
	if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->experiment_admin_menu_id,
			'id'     => 'experiment-avatar',
			'title'  => __( 'Edit Avatar', 'buddypress' ),
			'href'   =>  bp_get_experiments_action_link( 'admin/experiment-avatar' )
		) );
	}

	// Experiment Admin > Manage invitations
	if ( bp_is_active( 'friends' ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->experiment_admin_menu_id,
			'id'     => 'manage-invitations',
			'title'  => __( 'Manage Invitations', 'buddypress' ),
			'href'   =>  bp_get_experiments_action_link( 'send-invites' )
		) );
	}

	// Experiment Admin > Manage members
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->experiment_admin_menu_id,
		'id'     => 'manage-members',
		'title'  => __( 'Manage Members', 'buddypress' ),
		'href'   =>  bp_get_experiments_action_link( 'admin/manage-members' )
	) );

	// Experiment Admin > Membership Requests
	if ( bp_get_experiment_status( $bp->experiments->current_experiment ) == 'private' ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->experiment_admin_menu_id,
			'id'     => 'membership-requests',
			'title'  => __( 'Membership Requests', 'buddypress' ),
			'href'   =>  bp_get_experiments_action_link( 'admin/membership-requests' )
		) );
	}

	// Delete Experiment
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->experiment_admin_menu_id,
		'id'     => 'delete-experiment',
		'title'  => __( 'Delete Experiment', 'buddypress' ),
		'href'   =>  bp_get_experiments_action_link( 'admin/delete-experiment' )
	) );
}
add_action( 'admin_bar_menu', 'bp_experiments_experiment_admin_menu', 99 );

/**
 * Remove rogue WP core Edit menu when viewing a single experiment.
 *
 * @since BuddyPress (1.6.0)
 */
function bp_experiments_remove_edit_page_menu() {
	if ( bp_is_experiment() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
	}
}
add_action( 'add_admin_bar_menus', 'bp_experiments_remove_edit_page_menu' );
