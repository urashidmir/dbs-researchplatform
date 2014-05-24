<?php
/**
 * BuddyPress Experiments BuddyBar
 *
 * @package BuddyPress
 * @subpackage ExperimentsBuddyBar
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add menu items to the BuddyBar.
 *
 * @since BuddyPress (1.0.0)
 *
 * @global BuddyPress $bp
 */
function bp_experiments_adminbar_admin_menu() {
	global $bp;

	if ( empty( $bp->experiments->current_experiment ) )
		return false;

	// Only experiment admins and site admins can see this menu
	if ( !current_user_can( 'edit_users' ) && !bp_current_user_can( 'bp_moderate' ) && !bp_is_item_admin() )
		return false; ?>

	<li id="bp-adminbar-adminoptions-menu">
		<a href="<?php bp_experiments_action_link( 'admin' ); ?>"><?php _e( 'Admin Options', 'buddypress' ); ?></a>

		<ul>
			<li><a href="<?php bp_experiments_action_link( 'admin/edit-details' ); ?>"><?php _e( 'Edit Details', 'buddypress' ); ?></a></li>

			<li><a href="<?php bp_experiments_action_link( 'admin/experiment-settings' );  ?>"><?php _e( 'Experiment Settings', 'buddypress' ); ?></a></li>

			<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?>

				<li><a href="<?php bp_experiments_action_link( 'admin/experiment-avatar' ); ?>"><?php _e( 'Experiment Avatar', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<?php if ( bp_is_active( 'friends' ) ) : ?>

				<li><a href="<?php bp_experiments_action_link( 'send-invites' ); ?>"><?php _e( 'Manage Invitations', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<li><a href="<?php bp_experiments_action_link( 'admin/manage-members' ); ?>"><?php _e( 'Manage Members', 'buddypress' ); ?></a></li>

			<?php if ( $bp->experiments->current_experiment->status == 'private' ) : ?>

				<li><a href="<?php bp_experiments_action_link( 'admin/membership-requests' ); ?>"><?php _e( 'Membership Requests', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<li><a class="confirm" href="<?php echo wp_nonce_url( bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'admin/delete-experiment/', 'experiments_delete_experiment' ); ?>&amp;delete-experiment-button=1&amp;delete-experiment-understand=1"><?php _e( "Delete Experiment", 'buddypress' ) ?></a></li>

			<?php do_action( 'bp_experiments_adminbar_admin_menu' ) ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'bp_experiments_adminbar_admin_menu', 20 );
