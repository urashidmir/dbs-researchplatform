<?php
/**
 * BuddyPress Experiments Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 *
 * @package BuddyPress
 * @subpackage ExperimentsScreens
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function experiments_directory_experiments_setup() {
	if ( bp_is_experiments_directory() ) {
		bp_update_is_directory( true, 'experiments' );

		do_action( 'experiments_directory_experiments_setup' );

		bp_core_load_template( apply_filters( 'experiments_template_directory_experiments', 'experiments/index' ) );
	}
}
add_action( 'bp_screens', 'experiments_directory_experiments_setup', 2 );

function experiments_screen_my_experiments() {

	do_action( 'experiments_screen_my_experiments' );

	bp_core_load_template( apply_filters( 'experiments_template_my_experiments', 'members/single/home' ) );
}

function experiments_screen_experiment_invites() {
	$experiment_id = (int)bp_action_variable( 1 );

	if ( bp_is_action_variable( 'accept' ) && is_numeric( $experiment_id ) ) {
		// Check the nonce
		if ( !check_admin_referer( 'experiments_accept_invite' ) )
			return false;

		if ( !experiments_accept_invite( bp_loggedin_user_id(), $experiment_id ) ) {
			bp_core_add_message( __('Experiment invite could not be accepted', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Experiment invite accepted', 'buddypress') );

			// Record this in activity streams
			$experiment = experiments_get_experiment( array( 'experiment_id' => $experiment_id ) );

			experiments_record_activity( array(
				'type'    => 'joined_experiment',
				'item_id' => $experiment->id
			) );
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$redirect_to = trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() . '/' . bp_current_action() );
		}

		bp_core_redirect( $redirect_to );

	} else if ( bp_is_action_variable( 'reject' ) && is_numeric( $experiment_id ) ) {
		// Check the nonce
		if ( !check_admin_referer( 'experiments_reject_invite' ) )
			return false;

		if ( !experiments_reject_invite( bp_loggedin_user_id(), $experiment_id ) ) {
			bp_core_add_message( __( 'Experiment invite could not be rejected', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Experiment invite rejected', 'buddypress' ) );
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$redirect_to = trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() . '/' . bp_current_action() );
		}

		bp_core_redirect( $redirect_to );
	}

	do_action( 'experiments_screen_experiment_invites', $experiment_id );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_invites', 'members/single/home' ) );
}

function experiments_screen_experiment_home() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	do_action( 'experiments_screen_experiment_home' );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_home', 'experiments/single/home' ) );
}

/**
 * This screen function handles actions related to experiment forums
 *
 * @package BuddyPress
 */
function experiments_screen_experiment_forum() {

	if ( !bp_is_active( 'forums' ) || !bp_forums_is_installed_correctly() )
		return false;

	if ( bp_action_variable( 0 ) && !bp_is_action_variable( 'topic', 0 ) ) {
		bp_do_404();
		return;
	}

	$bp = buddypress();

	if ( !$bp->experiments->current_experiment->user_has_access ) {
		bp_core_no_access();
		return;
	}

	if ( ! bp_is_single_item() )
		return false;

	// Fetch the details we need
	$topic_slug	= (string)bp_action_variable( 1 );
	$topic_id       = bp_forums_get_topic_id_from_slug( $topic_slug );
	$forum_id       = experiments_get_experimentmeta( $bp->experiments->current_experiment->id, 'forum_id' );
	$user_is_banned = false;

	if ( !bp_current_user_can( 'bp_moderate' ) && experiments_is_user_banned( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) )
		$user_is_banned = true;

	if ( !empty( $topic_slug ) && !empty( $topic_id ) ) {

		// Posting a reply
		if ( !$user_is_banned && !bp_action_variable( 2 ) && isset( $_POST['submit_reply'] ) ) {
			// Check the nonce
			check_admin_referer( 'bp_forums_new_reply' );

			// Auto join this user if they are not yet a member of this experiment
			if ( bp_experiments_auto_join() && !bp_current_user_can( 'bp_moderate' ) && 'public' == $bp->experiments->current_experiment->status && !experiments_is_user_member( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {
				experiments_join_experiment( $bp->experiments->current_experiment->id, bp_loggedin_user_id() );
			}

			$topic_page = isset( $_GET['topic_page'] ) ? $_GET['topic_page'] : false;

			// Don't allow reply flooding
			if ( bp_forums_reply_exists( $_POST['reply_text'], $topic_id, bp_loggedin_user_id() ) ) {
				bp_core_add_message( __( 'It looks like you\'ve already said that!', 'buddypress' ), 'error' );
			} else {
				if ( !$post_id = experiments_new_experiment_forum_post( $_POST['reply_text'], $topic_id, $topic_page ) ) {
					bp_core_add_message( __( 'There was an error when replying to that topic', 'buddypress'), 'error' );
				} else {
					bp_core_add_message( __( 'Your reply was posted successfully', 'buddypress') );
				}
			}

			$query_vars = isset( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '';

			$redirect = bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'forum/topic/' . $topic_slug . '/' . $query_vars;

			if ( !empty( $post_id ) ) {
				$redirect .= '#post-' . $post_id;
			}

			bp_core_redirect( $redirect );
		}

		// Sticky a topic
		else if ( bp_is_action_variable( 'stick', 2 ) && ( bp_is_item_admin() || bp_is_item_mod() ) ) {
			// Check the nonce
			check_admin_referer( 'bp_forums_stick_topic' );

			if ( !bp_forums_sticky_topic( array( 'topic_id' => $topic_id ) ) ) {
				bp_core_add_message( __( 'There was an error when making that topic a sticky', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The topic was made sticky successfully', 'buddypress' ) );
			}

			do_action( 'experiments_stick_forum_topic', $topic_id );
			bp_core_redirect( wp_get_referer() );
		}

		// Un-Sticky a topic
		else if ( bp_is_action_variable( 'unstick', 2 ) && ( bp_is_item_admin() || bp_is_item_mod() ) ) {
			// Check the nonce
			check_admin_referer( 'bp_forums_unstick_topic' );

			if ( !bp_forums_sticky_topic( array( 'topic_id' => $topic_id, 'mode' => 'unstick' ) ) ) {
				bp_core_add_message( __( 'There was an error when unsticking that topic', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __( 'The topic was unstuck successfully', 'buddypress') );
			}

			do_action( 'experiments_unstick_forum_topic', $topic_id );
			bp_core_redirect( wp_get_referer() );
		}

		// Close a topic
		else if ( bp_is_action_variable( 'close', 2 ) && ( bp_is_item_admin() || bp_is_item_mod() ) ) {
			// Check the nonce
			check_admin_referer( 'bp_forums_close_topic' );

			if ( !bp_forums_openclose_topic( array( 'topic_id' => $topic_id ) ) ) {
				bp_core_add_message( __( 'There was an error when closing that topic', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __( 'The topic was closed successfully', 'buddypress') );
			}

			do_action( 'experiments_close_forum_topic', $topic_id );
			bp_core_redirect( wp_get_referer() );
		}

		// Open a topic
		else if ( bp_is_action_variable( 'open', 2 ) && ( bp_is_item_admin() || bp_is_item_mod() ) ) {
			// Check the nonce
			check_admin_referer( 'bp_forums_open_topic' );

			if ( !bp_forums_openclose_topic( array( 'topic_id' => $topic_id, 'mode' => 'open' ) ) ) {
				bp_core_add_message( __( 'There was an error when opening that topic', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __( 'The topic was opened successfully', 'buddypress') );
			}

			do_action( 'experiments_open_forum_topic', $topic_id );
			bp_core_redirect( wp_get_referer() );
		}

		// Delete a topic
		else if ( empty( $user_is_banned ) && bp_is_action_variable( 'delete', 2 ) && !bp_action_variable( 3 ) ) {
			// Fetch the topic
			$topic = bp_forums_get_topic_details( $topic_id );

			/* Check the logged in user can delete this topic */
			if ( ! bp_is_item_admin() && ! bp_is_item_mod() && ( (int) bp_loggedin_user_id() != (int) $topic->topic_poster ) ) {
				bp_core_redirect( wp_get_referer() );
			}

			// Check the nonce
			check_admin_referer( 'bp_forums_delete_topic' );

			do_action( 'experiments_before_delete_forum_topic', $topic_id );

			if ( !experiments_delete_experiment_forum_topic( $topic_id ) ) {
				bp_core_add_message( __( 'There was an error deleting the topic', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The topic was deleted successfully', 'buddypress' ) );
			}

			do_action( 'experiments_delete_forum_topic', $topic_id );
			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'forum/' );
		}

		// Editing a topic
		else if ( empty( $user_is_banned ) && bp_is_action_variable( 'edit', 2 ) && !bp_action_variable( 3 ) ) {
			// Fetch the topic
			$topic = bp_forums_get_topic_details( $topic_id );

			// Check the logged in user can edit this topic
			if ( ! bp_is_item_admin() && ! bp_is_item_mod() && ( (int) bp_loggedin_user_id() != (int) $topic->topic_poster ) ) {
				bp_core_redirect( wp_get_referer() );
			}

			if ( isset( $_POST['save_changes'] ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_edit_topic' );

				$topic_tags = !empty( $_POST['topic_tags'] ) ? $_POST['topic_tags'] : false;

				if ( !experiments_update_experiment_forum_topic( $topic_id, $_POST['topic_title'], $_POST['topic_text'], $topic_tags ) ) {
					bp_core_add_message( __( 'There was an error when editing that topic', 'buddypress'), 'error' );
				} else {
					bp_core_add_message( __( 'The topic was edited successfully', 'buddypress') );
				}

				do_action( 'experiments_edit_forum_topic', $topic_id );
				bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'forum/topic/' . $topic_slug . '/' );
			}

			bp_core_load_template( apply_filters( 'experiments_template_experiment_forum_topic_edit', 'experiments/single/home' ) );

		// Delete a post
		} else if ( empty( $user_is_banned ) && bp_is_action_variable( 'delete', 2 ) && $post_id = bp_action_variable( 4 ) ) {
			// Fetch the post
			$post = bp_forums_get_post( $post_id );

			// Check the logged in user can edit this topic
			if ( ! bp_is_item_admin() && ! bp_is_item_mod() && ( (int) bp_loggedin_user_id() != (int) $post->poster_id ) ) {
				bp_core_redirect( wp_get_referer() );
			}

			// Check the nonce
			check_admin_referer( 'bp_forums_delete_post' );

			do_action( 'experiments_before_delete_forum_post', $post_id );

			if ( !experiments_delete_experiment_forum_post( $post_id ) ) {
				bp_core_add_message( __( 'There was an error deleting that post', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __( 'The post was deleted successfully', 'buddypress') );
			}

			do_action( 'experiments_delete_forum_post', $post_id );
			bp_core_redirect( wp_get_referer() );

		// Editing a post
		} else if ( empty( $user_is_banned ) && bp_is_action_variable( 'edit', 2 ) && $post_id = bp_action_variable( 4 ) ) {

			// Fetch the post
			$post = bp_forums_get_post( $post_id );

			// Check the logged in user can edit this topic
			if ( ! bp_is_item_admin() && ! bp_is_item_mod() && ( (int) bp_loggedin_user_id() != (int) $post->poster_id ) ) {
				bp_core_redirect( wp_get_referer() );
			}

			if ( isset( $_POST['save_changes'] ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_edit_post' );

				$topic_page = isset( $_GET['topic_page'] ) ? $_GET['topic_page'] : false;

				if ( !$post_id = experiments_update_experiment_forum_post( $post_id, $_POST['post_text'], $topic_id, $topic_page ) ) {
					bp_core_add_message( __( 'There was an error when editing that post', 'buddypress'), 'error' );
				} else {
					bp_core_add_message( __( 'The post was edited successfully', 'buddypress') );
				}

				if ( $_SERVER['QUERY_STRING'] ) {
					$query_vars = '?' . $_SERVER['QUERY_STRING'];
				}

				do_action( 'experiments_edit_forum_post', $post_id );
				bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'forum/topic/' . $topic_slug . '/' . $query_vars . '#post-' . $post_id );
			}

			bp_core_load_template( apply_filters( 'experiments_template_experiment_forum_topic_edit', 'experiments/single/home' ) );

		// Standard topic display
		} else {
			if ( !empty( $user_is_banned ) ) {
				bp_core_add_message( __( "You have been banned from this experiment.", 'buddypress' ) );
			}

			bp_core_load_template( apply_filters( 'experiments_template_experiment_forum_topic', 'experiments/single/home' ) );
		}

	// Forum topic does not exist
	} elseif ( !empty( $topic_slug ) && empty( $topic_id ) ) {
		bp_do_404();
		return;

	} else {
		// Posting a topic
		if ( isset( $_POST['submit_topic'] ) && bp_is_active( 'forums' ) ) {

			// Check the nonce
			check_admin_referer( 'bp_forums_new_topic' );

			if ( $user_is_banned ) {
				$error_message = __( "You have been banned from this experiment.", 'buddypress' );

			} elseif ( bp_experiments_auto_join() && !bp_current_user_can( 'bp_moderate' ) && 'public' == $bp->experiments->current_experiment->status && !experiments_is_user_member( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {
				// Auto join this user if they are not yet a member of this experiment
				experiments_join_experiment( $bp->experiments->current_experiment->id, bp_loggedin_user_id() );
			}

			if ( empty( $_POST['topic_title'] ) ) {
				$error_message = __( 'Please provide a title for your forum topic.', 'buddypress' );
			} else if ( empty( $_POST['topic_text'] ) ) {
				$error_message = __( 'Forum posts cannot be empty. Please enter some text.', 'buddypress' );
			}

			if ( empty( $forum_id ) ) {
				$error_message = __( 'This experiment does not have a forum setup yet.', 'buddypress' );
			}

			if ( isset( $error_message ) ) {
				bp_core_add_message( $error_message, 'error' );
				$redirect = bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'forum';
			} else {
				if ( !$topic = experiments_new_experiment_forum_topic( $_POST['topic_title'], $_POST['topic_text'], $_POST['topic_tags'], $forum_id ) ) {
					bp_core_add_message( __( 'There was an error when creating the topic', 'buddypress'), 'error' );
					$redirect = bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'forum';
				} else {
					bp_core_add_message( __( 'The topic was created successfully', 'buddypress') );
					$redirect = bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'forum/topic/' . $topic->topic_slug . '/';
				}
			}

			bp_core_redirect( $redirect );
		}

		do_action( 'experiments_screen_experiment_forum', $topic_id, $forum_id );

		bp_core_load_template( apply_filters( 'experiments_template_experiment_forum', 'experiments/single/home' ) );
	}
}

function experiments_screen_experiment_members() {

	if ( !bp_is_single_item() )
		return false;

	$bp = buddypress();

	// Refresh the experiment member count meta
	experiments_update_experimentmeta( $bp->experiments->current_experiment->id, 'total_member_count', experiments_get_total_member_count( $bp->experiments->current_experiment->id ) );

	do_action( 'experiments_screen_experiment_members', $bp->experiments->current_experiment->id );
	bp_core_load_template( apply_filters( 'experiments_template_experiment_members', 'experiments/single/home' ) );
}

function experiments_screen_experiment_invite() {

	if ( !bp_is_single_item() )
		return false;

	$bp = buddypress();

	if ( bp_is_action_variable( 'send', 0 ) ) {

		if ( !check_admin_referer( 'experiments_send_invites', '_wpnonce_send_invites' ) )
			return false;

		if ( !empty( $_POST['friends'] ) ) {
			foreach( (array) $_POST['friends'] as $friend ) {
				experiments_invite_user( array( 'user_id' => $friend, 'experiment_id' => $bp->experiments->current_experiment->id ) );
			}
		}

		// Send the invites.
		experiments_send_invites( bp_loggedin_user_id(), $bp->experiments->current_experiment->id );
		bp_core_add_message( __('Experiment invites sent.', 'buddypress') );
		do_action( 'experiments_screen_experiment_invite', $bp->experiments->current_experiment->id );
		bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );

	} elseif ( !bp_action_variable( 0 ) ) {
		// Show send invite page
		bp_core_load_template( apply_filters( 'experiments_template_experiment_invite', 'experiments/single/home' ) );

	} else {
		bp_do_404();
	}
}

/**
 * Process experiment invitation removal requests.
 *
 * Note that this function is only used when JS is disabled. Normally, clicking
 * Remove Invite removes the invitation via AJAX.
 *
 * @since BuddyPress (2.0.0)
 */
function experiments_remove_experiment_invite() {
	if ( ! bp_is_experiment_invites() ) {
		return;
	}

	if ( ! bp_is_action_variable( 'remove', 0 ) || ! is_numeric( bp_action_variable( 1 ) ) ) {
		return;
	}

	if ( ! check_admin_referer( 'experiments_invite_uninvite_user' ) ) {
		return false;
	}

	$friend_id = intval( bp_action_variable( 1 ) );
	$experiment_id  = bp_get_current_experiment_id();
	$message   = __( 'Invite successfully removed', 'buddypress' );
	$redirect  = wp_get_referer();
	$error     = false;

	if ( ! bp_experiments_user_can_send_invites( $experiment_id ) ) {
		$message = __( 'You are not allowed to send or remove invites', 'buddypress' );
		$error = 'error';
	} else if ( BP_Experiments_Member::check_for_membership_request( $friend_id, $experiment_id ) ) {
		$message = __( 'The member requested to join the experiment', 'buddypress' );
		$error = 'error';
	} else if ( ! experiments_uninvite_user( $friend_id, $experiment_id ) ) {
		$message = __( 'There was an error removing the invite', 'buddypress' );
		$error = 'error';
	}

	bp_core_add_message( $message, $error );
	bp_core_redirect( $redirect );
}
add_action( 'bp_screens', 'experiments_remove_experiment_invite' );

function experiments_screen_experiment_request_membership() {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	$bp = buddypress();

	if ( 'private' != $bp->experiments->current_experiment->status )
		return false;

	// If the user is already invited, accept invitation
	if ( experiments_check_user_has_invite( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {
		if ( experiments_accept_invite( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) )
			bp_core_add_message( __( 'Experiment invite accepted', 'buddypress' ) );
		else
			bp_core_add_message( __( 'There was an error accepting the experiment invitation, please try again.', 'buddypress' ), 'error' );
		bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );
	}

	// If the user has submitted a request, send it.
	if ( isset( $_POST['experiment-request-send']) ) {

		// Check the nonce
		if ( !check_admin_referer( 'experiments_request_membership' ) )
			return false;

		if ( !experiments_send_membership_request( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {
			bp_core_add_message( __( 'There was an error sending your experiment membership request, please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Your membership request was sent to the experiment administrator successfully. You will be notified when the experiment administrator responds to your request.', 'buddypress' ) );
		}
		bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );
	}

	do_action( 'experiments_screen_experiment_request_membership', $bp->experiments->current_experiment->id );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_request_membership', 'experiments/single/home' ) );
}

function experiments_screen_experiment_activity_permalink() {

	if ( !bp_is_experiments_component() || !bp_is_active( 'activity' ) || ( bp_is_active( 'activity' ) && !bp_is_current_action( bp_get_activity_slug() ) ) || !bp_action_variable( 0 ) )
		return false;

	buddypress()->is_single_item = true;

	bp_core_load_template( apply_filters( 'experiments_template_experiment_home', 'experiments/single/home' ) );
}
add_action( 'bp_screens', 'experiments_screen_experiment_activity_permalink' );

function experiments_screen_experiment_admin() {
	if ( !bp_is_experiments_component() || !bp_is_current_action( 'admin' ) )
		return false;

	if ( bp_action_variables() )
		return false;

	bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/edit-details/' );
}

function experiments_screen_experiment_admin_edit_details() {

	if ( 'edit-details' != bp_get_experiment_current_admin_tab() )
		return false;

	if ( bp_is_item_admin() ) {

		$bp = buddypress();

		// If the edit form has been submitted, save the edited details
		if ( isset( $_POST['save'] ) ) {
			// Check the nonce
			if ( !check_admin_referer( 'experiments_edit_experiment_details' ) )
				return false;

			$experiment_notify_members = isset( $_POST['experiment-notify-members'] ) ? (int) $_POST['experiment-notify-members'] : 0;

			if ( !experiments_edit_base_experiment_details( $_POST['experiment-id'], $_POST['experiment-name'], $_POST['experiment-desc'], $experiment_notify_members ) ) {
				bp_core_add_message( __( 'There was an error updating experiment details, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Experiment details were successfully updated.', 'buddypress' ) );
			}

			do_action( 'experiments_experiment_details_edited', $bp->experiments->current_experiment->id );

			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/edit-details/' );
		}

		do_action( 'experiments_screen_experiment_admin_edit_details', $bp->experiments->current_experiment->id );

		bp_core_load_template( apply_filters( 'experiments_template_experiment_admin', 'experiments/single/home' ) );
	}
}
add_action( 'bp_screens', 'experiments_screen_experiment_admin_edit_details' );

function experiments_screen_experiment_admin_settings() {

	if ( 'experiment-settings' != bp_get_experiment_current_admin_tab() )
		return false;

	if ( ! bp_is_item_admin() )
		return false;

	$bp = buddypress();

	// If the edit form has been submitted, save the edited details
	if ( isset( $_POST['save'] ) ) {
		$enable_forum   = ( isset($_POST['experiment-show-forum'] ) ) ? 1 : 0;

		// Checked against a whitelist for security
		$allowed_status = apply_filters( 'experiments_allowed_status', array( 'public', 'private', 'hidden' ) );
		$status         = ( in_array( $_POST['experiment-status'], (array) $allowed_status ) ) ? $_POST['experiment-status'] : 'public';

		// Checked against a whitelist for security
		$allowed_invite_status = apply_filters( 'experiments_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
		$invite_status	       = isset( $_POST['experiment-invite-status'] ) && in_array( $_POST['experiment-invite-status'], (array) $allowed_invite_status ) ? $_POST['experiment-invite-status'] : 'members';

		// Check the nonce
		if ( !check_admin_referer( 'experiments_edit_experiment_settings' ) )
			return false;

		if ( !experiments_edit_experiment_settings( $_POST['experiment-id'], $enable_forum, $status, $invite_status ) ) {
			bp_core_add_message( __( 'There was an error updating experiment settings, please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Experiment settings were successfully updated.', 'buddypress' ) );
		}

		do_action( 'experiments_experiment_settings_edited', $bp->experiments->current_experiment->id );

		bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/experiment-settings/' );
	}

	do_action( 'experiments_screen_experiment_admin_settings', $bp->experiments->current_experiment->id );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_admin_settings', 'experiments/single/home' ) );
}
add_action( 'bp_screens', 'experiments_screen_experiment_admin_settings' );

function experiments_screen_experiment_admin_avatar() {

	if ( 'experiment-avatar' != bp_get_experiment_current_admin_tab() )
		return false;

	// If the logged-in user doesn't have permission or if avatar uploads are disabled, then stop here
	if ( ! bp_is_item_admin() || (int) bp_get_option( 'bp-disable-avatar-uploads' ) )
		return false;

	$bp = buddypress();

	// If the experiment admin has deleted the admin avatar
	if ( bp_is_action_variable( 'delete', 1 ) ) {

		// Check the nonce
		check_admin_referer( 'bp_experiment_avatar_delete' );

		if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->experiments->current_experiment->id, 'object' => 'experiment' ) ) ) {
			bp_core_add_message( __( 'Your avatar was deleted successfully!', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem deleting that avatar, please try again.', 'buddypress' ), 'error' );
		}
	}

	if ( ! isset( $bp->avatar_admin ) ) {
		$bp->avatar_admin = new stdClass();
	}

	$bp->avatar_admin->step = 'upload-image';

	if ( !empty( $_FILES ) ) {

		// Check the nonce
		check_admin_referer( 'bp_avatar_upload' );

		// Pass the file to the avatar upload handler
		if ( bp_core_avatar_handle_upload( $_FILES, 'experiments_avatar_upload_dir' ) ) {
			$bp->avatar_admin->step = 'crop-image';

			// Make sure we include the jQuery jCrop file for image cropping
			add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
		}

	}

	// If the image cropping is done, crop the image and save a full/thumb version
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		// Check the nonce
		check_admin_referer( 'bp_avatar_cropstore' );

		$args = array(
			'object'        => 'experiment',
			'avatar_dir'    => 'experiment-avatars',
			'item_id'       => $bp->experiments->current_experiment->id,
			'original_file' => $_POST['image_src'],
			'crop_x'        => $_POST['x'],
			'crop_y'        => $_POST['y'],
			'crop_w'        => $_POST['w'],
			'crop_h'        => $_POST['h']
		);

		if ( !bp_core_avatar_handle_crop( $args ) ) {
			bp_core_add_message( __( 'There was a problem cropping the avatar.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'The new experiment avatar was uploaded successfully.', 'buddypress' ) );
		}
	}

	do_action( 'experiments_screen_experiment_admin_avatar', $bp->experiments->current_experiment->id );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_admin_avatar', 'experiments/single/home' ) );
}
add_action( 'bp_screens', 'experiments_screen_experiment_admin_avatar' );

/**
 * This function handles actions related to member management on the experiment admin.
 *
 * @package BuddyPress
 */
function experiments_screen_experiment_admin_manage_members() {

	if ( 'manage-members' != bp_get_experiment_current_admin_tab() )
		return false;

	if ( ! bp_is_item_admin() )
		return false;

	$bp = buddypress();

	if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) && bp_action_variable( 3 ) ) {
		if ( bp_is_action_variable( 'promote', 1 ) && ( bp_is_action_variable( 'mod', 2 ) || bp_is_action_variable( 'admin', 2 ) ) && is_numeric( bp_action_variable( 3 ) ) ) {
			$user_id = bp_action_variable( 3 );
			$status  = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'experiments_promote_member' ) )
				return false;

			// Promote a user.
			if ( !experiments_promote_member( $user_id, $bp->experiments->current_experiment->id, $status ) )
				bp_core_add_message( __( 'There was an error when promoting that user, please try again', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User promoted successfully', 'buddypress' ) );

			do_action( 'experiments_promoted_member', $user_id, $bp->experiments->current_experiment->id );

			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/manage-members/' );
		}
	}

	if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) ) {
		if ( bp_is_action_variable( 'demote', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'experiments_demote_member' ) )
				return false;

			// Stop sole admins from abandoning their experiment
			$experiment_admins = experiments_get_experiment_admins( $bp->experiments->current_experiment->id );
			if ( 1 == count( $experiment_admins ) && $experiment_admins[0]->user_id == $user_id )
				bp_core_add_message( __( 'This experiment must have at least one admin', 'buddypress' ), 'error' );

			// Demote a user.
			elseif ( !experiments_demote_member( $user_id, $bp->experiments->current_experiment->id ) )
				bp_core_add_message( __( 'There was an error when demoting that user, please try again', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User demoted successfully', 'buddypress' ) );

			do_action( 'experiments_demoted_member', $user_id, $bp->experiments->current_experiment->id );

			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'ban', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'experiments_ban_member' ) )
				return false;

			// Ban a user.
			if ( !experiments_ban_member( $user_id, $bp->experiments->current_experiment->id ) )
				bp_core_add_message( __( 'There was an error when banning that user, please try again', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User banned successfully', 'buddypress' ) );

			do_action( 'experiments_banned_member', $user_id, $bp->experiments->current_experiment->id );

			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'unban', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'experiments_unban_member' ) )
				return false;

			// Remove a ban for user.
			if ( !experiments_unban_member( $user_id, $bp->experiments->current_experiment->id ) )
				bp_core_add_message( __( 'There was an error when unbanning that user, please try again', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User ban removed successfully', 'buddypress' ) );

			do_action( 'experiments_unbanned_member', $user_id, $bp->experiments->current_experiment->id );

			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'remove', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'experiments_remove_member' ) )
				return false;

			// Remove a user.
			if ( !experiments_remove_member( $user_id, $bp->experiments->current_experiment->id ) )
				bp_core_add_message( __( 'There was an error removing that user from the experiment, please try again', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User removed successfully', 'buddypress' ) );

			do_action( 'experiments_removed_member', $user_id, $bp->experiments->current_experiment->id );

			bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/manage-members/' );
		}
	}

	do_action( 'experiments_screen_experiment_admin_manage_members', $bp->experiments->current_experiment->id );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_admin_manage_members', 'experiments/single/home' ) );
}
add_action( 'bp_screens', 'experiments_screen_experiment_admin_manage_members' );

function experiments_screen_experiment_admin_requests() {
	$bp = buddypress();

	if ( 'membership-requests' != bp_get_experiment_current_admin_tab() ) {
		return false;
	}

	if ( ! bp_is_item_admin() || ( 'public' == $bp->experiments->current_experiment->status ) ) {
		return false;
	}

	$request_action = (string) bp_action_variable( 1 );
	$membership_id  = (int) bp_action_variable( 2 );

	if ( !empty( $request_action ) && !empty( $membership_id ) ) {
		if ( 'accept' == $request_action && is_numeric( $membership_id ) ) {

			// Check the nonce first.
			if ( !check_admin_referer( 'experiments_accept_membership_request' ) )
				return false;

			// Accept the membership request
			if ( !experiments_accept_membership_request( $membership_id ) )
				bp_core_add_message( __( 'There was an error accepting the membership request, please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'Experiment membership request accepted', 'buddypress' ) );

		} elseif ( 'reject' == $request_action && is_numeric( $membership_id ) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'experiments_reject_membership_request' ) )
				return false;

			// Reject the membership request
			if ( !experiments_reject_membership_request( $membership_id ) )
				bp_core_add_message( __( 'There was an error rejecting the membership request, please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'Experiment membership request rejected', 'buddypress' ) );
		}

		do_action( 'experiments_experiment_request_managed', $bp->experiments->current_experiment->id, $request_action, $membership_id );
		bp_core_redirect( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/membership-requests/' );
	}

	do_action( 'experiments_screen_experiment_admin_requests', $bp->experiments->current_experiment->id );
	bp_core_load_template( apply_filters( 'experiments_template_experiment_admin_requests', 'experiments/single/home' ) );
}
add_action( 'bp_screens', 'experiments_screen_experiment_admin_requests' );

function experiments_screen_experiment_admin_delete_experiment() {
	global $bp;

	if ( 'delete-experiment' != bp_get_experiment_current_admin_tab() )
		return false;

	if ( ! bp_is_item_admin() && !bp_current_user_can( 'bp_moderate' ) )
		return false;

	if ( isset( $_REQUEST['delete-experiment-button'] ) && isset( $_REQUEST['delete-experiment-understand'] ) ) {

		// Check the nonce first.
		if ( !check_admin_referer( 'experiments_delete_experiment' ) ) {
			return false;
		}

		do_action( 'experiments_before_experiment_deleted', $bp->experiments->current_experiment->id );

		// Experiment admin has deleted the experiment, now do it.
		if ( !experiments_delete_experiment( $bp->experiments->current_experiment->id ) ) {
			bp_core_add_message( __( 'There was an error deleting the experiment, please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'The experiment was deleted successfully', 'buddypress' ) );

			do_action( 'experiments_experiment_deleted', $bp->experiments->current_experiment->id );

			bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() ) );
		}

		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() ) );
	}

	do_action( 'experiments_screen_experiment_admin_delete_experiment', $bp->experiments->current_experiment->id );

	bp_core_load_template( apply_filters( 'experiments_template_experiment_admin_delete_experiment', 'experiments/single/home' ) );
}
add_action( 'bp_screens', 'experiments_screen_experiment_admin_delete_experiment' );

/**
 * Renders the experiment settings fields on the Notification Settings page
 *
 * @package BuddyPress
 */
function experiments_screen_notification_settings() {

	if ( !$experiment_invite = bp_get_user_meta( bp_displayed_user_id(), 'notification_experiments_invite', true ) )
		$experiment_invite  = 'yes';

	if ( !$experiment_update = bp_get_user_meta( bp_displayed_user_id(), 'notification_experiments_experiment_updated', true ) )
		$experiment_update  = 'yes';

	if ( !$experiment_promo = bp_get_user_meta( bp_displayed_user_id(), 'notification_experiments_admin_promotion', true ) )
		$experiment_promo   = 'yes';

	if ( !$experiment_request = bp_get_user_meta( bp_displayed_user_id(), 'notification_experiments_membership_request', true ) )
		$experiment_request = 'yes'; ?>

	<table class="notification-settings" id="experiments-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Experiments', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="experiments-notification-settings-invitation">
				<td></td>
				<td><?php _e( 'A member invites you to join a experiment', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_experiments_invite]" value="yes" <?php checked( $experiment_invite, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_experiments_invite]" value="no" <?php checked( $experiment_invite, 'no', true ) ?>/></td>
			</tr>
			<tr id="experiments-notification-settings-info-updated">
				<td></td>
				<td><?php _e( 'Experiment information is updated', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_experiments_experiment_updated]" value="yes" <?php checked( $experiment_update, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_experiments_experiment_updated]" value="no" <?php checked( $experiment_update, 'no', true ) ?>/></td>
			</tr>
			<tr id="experiments-notification-settings-promoted">
				<td></td>
				<td><?php _e( 'You are promoted to a experiment administrator or moderator', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_experiments_admin_promotion]" value="yes" <?php checked( $experiment_promo, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_experiments_admin_promotion]" value="no" <?php checked( $experiment_promo, 'no', true ) ?>/></td>
			</tr>
			<tr id="experiments-notification-settings-request">
				<td></td>
				<td><?php _e( 'A member requests to join a private experiment for which you are an admin', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_experiments_membership_request]" value="yes" <?php checked( $experiment_request, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_experiments_membership_request]" value="no" <?php checked( $experiment_request, 'no', true ) ?>/></td>
			</tr>

			<?php do_action( 'experiments_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'experiments_screen_notification_settings' );

/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyPress Experiments
 *
 * This class sets up the necessary theme compatability actions to safely output
 * experiment template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyPress (1.7)
 */
class BP_Experiments_Theme_Compat {

	/**
	 * Setup the experiments component theme compatibility
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_experiment' ) );
	}

	/**
	 * Are we looking at something that needs experiment theme compatability?
	 *
	 * @since BuddyPress (1.7)
	 */
	public function is_experiment() {

		// Bail if not looking at a experiment
		if ( ! bp_is_experiments_component() )
			return;

		// Experiment Directory
		if ( ! bp_current_action() && ! bp_current_item() ) {
			bp_update_is_directory( true, 'experiments' );

			do_action( 'experiments_directory_experiments_setup' );

			add_filter( 'bp_get_buddypress_template',                array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		// Creating a experiment
		} elseif ( bp_is_experiments_component() && bp_is_current_action( 'create' ) ) {
			add_filter( 'bp_get_buddypress_template',                array( $this, 'create_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'create_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'create_content'    ) );

		// Experiment page
		} elseif ( bp_is_single_item() ) {
			add_filter( 'bp_get_buddypress_template',                array( $this, 'single_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'single_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'single_content'    ) );

		}
	}

	/** Directory *************************************************************/

	/**
	 * Add template hierarchy to theme compat for the experiment directory page.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates()
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates ) {
		// Setup our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_experiments_directory', array(
			'experiments/index-directory.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with directory data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_dummy_post() {

		$title = apply_filters( 'bp_experiments_directory_header', bp_get_directory_title( 'experiments' ) );

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_experiment',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the experiments index template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_content() {
		return bp_buffer_template_part( 'experiments/index', null, false );
	}

	/** Create ****************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for the experiment create page.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates()
	 * @return array $templates Array of custom templates to look for.
	 */
	public function create_template_hierarchy( $templates ) {
		// Setup our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_experiments_create', array(
			'experiments/index-create.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with create screen data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function create_dummy_post() {

		$title = __( 'Experiments', 'buddypress' );

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_experiment',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the create screen template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function create_content() {
		return bp_buffer_template_part( 'experiments/create', null, false );
	}

	/** Single ****************************************************************/

	/**
	 * Add custom template hierarchy to theme compat for experiment pages.
	 *
	 * This is to mirror how WordPress has {@link https://codex.wordpress.org/Template_Hierarchy template hierarchy}.
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates()
	 * @return array $templates Array of custom templates to look for.
	 */
	public function single_template_hierarchy( $templates ) {
		// Setup some variables we're going to reference in our custom templates
		$experiment = experiments_get_current_experiment();

		// Setup our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_experiments_single_item', array(
			'experiments/single/index-id-'     . sanitize_file_name( bp_get_current_experiment_id() )   . '.php',
			'experiments/single/index-slug-'   . sanitize_file_name( bp_get_current_experiment_slug() ) . '.php',
			'experiments/single/index-action-' . sanitize_file_name( bp_current_action() )         . '.php',
			'experiments/single/index-status-' . sanitize_file_name( $experiment->status )              . '.php',
			'experiments/single/index.php'
		) );

		// Merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		return $templates;
	}

	/**
	 * Update the global $post with single experiment data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => '<a href="' . bp_get_experiment_permalink( experiments_get_current_experiment() ) . '">' . bp_get_current_experiment_name() . '</a>',
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_experiment',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the single experiment template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_content() {
		return bp_buffer_template_part( 'experiments/single/home', null, false );
	}
}
new BP_Experiments_Theme_Compat();
