<?php
/**
 * BuddyPress Experiments Loader
 *
 * A experiments component, for users to experiment themselves together. Includes a
 * robust sub-component API that allows Experiments to be extended.
 * Comes preconfigured with an activity stream, discussion forums, and settings.
 *
 * @package BuddyPress
 * @subpackage ExperimentsLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Experiments_Component extends BP_Component {

	/**
	 * Auto join experiment when non experiment member performs experiment activity
	 *
	 * @since BuddyPress (1.5)
	 * @var bool
	 */
	public $auto_join;

	/**
	 * The experiment being currently accessed
	 *
	 * @since BuddyPress (1.5)
	 * @var BP_Experiments_Experiment
	 */
	public $current_experiment;

	/**
	 * Default experiment extension
	 *
	 * @since BuddyPress (1.6)
	 * @todo Is this used anywhere? Is this a duplicate of $default_extension?
	 */
	var $default_component;

	/**
	 * Default experiment extension
	 *
	 * @since BuddyPress (1.6)
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal experiment names/slugs
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * Experiment creation/edit steps (e.g. Details, Settings, Avatar, Invites)
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $experiment_creation_steps;

	/**
	 * Types of experiment statuses (Public, Private, Hidden)
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $valid_status;

	/**
	 * Start the experiments component creation process
	 *
	 * @since BuddyPress (1.5)
	 */
	public function __construct() {
		parent::start(
			'experiments',
			__( 'User Experiments', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 70
			)
		);
	}

	/**
	 * Include files
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cache',
			'forums',
			'actions',
			'filters',
			'screens',
			'classes',
			'widgets',
			'activity',
			'template',
			'buddybar',
			'adminbar',
			'functions',
			'notifications'
		);

		if ( is_admin() )
			$includes[] = 'admin';

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_EXPERIMENTS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary
		if ( !defined( 'BP_EXPERIMENTS_SLUG' ) )
			define( 'BP_EXPERIMENTS_SLUG', $this->id );

		// Global tables for messaging component
		$global_tables = array(
			'table_name'           => $bp->table_prefix . 'bp_experiments',
			'table_name_members'   => $bp->table_prefix . 'bp_experiments_members',
			'table_name_experimentmeta' => $bp->table_prefix . 'bp_experiments_experimentmeta'
		);

		// Metadata tables for experiments component
		$meta_tables = array(
			'experiment' => $bp->table_prefix . 'bp_experiments_experimentmeta',
		);

		// All globals for experiments component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BP_EXPERIMENTS_SLUG,
			'root_slug'             => isset( $bp->pages->experiments->slug ) ? $bp->pages->experiments->slug : BP_ExperimentS_SLUG,
			'has_directory'         => true,
			'directory_title'       => _x( 'Experiments', 'component directory title', 'buddypress' ),
			'notification_callback' => 'experiments_format_notifications',
			'search_string'         => __( 'Search Experiments...', 'buddypress' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $args );

		/** Single Experiment Globals **********************************************/

		// Are we viewing a single experiment?
		if ( bp_is_experiments_component() && $experiment_id = BP_Experiments_Experiment::experiment_exists( bp_current_action() ) ) {

			$bp->is_single_item  = true;
			$current_experiment_class = apply_filters( 'bp_experiments_current_experiment_class', 'BP_Experiments_Experiment' );

			if ( $current_experiment_class == 'BP_Experiments_Experiment' ) {
				$this->current_experiment = experiments_get_experiment( array(
					'experiment_id'        => $experiment_id,
					'populate_extras' => true,
				) );

			} else {
				$this->current_experiment = apply_filters( 'bp_experiments_current_experiment_object', new $current_experiment_class( $experiment_id ) );
			}

			// When in a single experiment, the first action is bumped down one because of the
			// experiment name, so we need to adjust this and set the experiment name to current_item.
			$bp->current_item   = bp_current_action();
			$bp->current_action = bp_action_variable( 0 );
			array_shift( $bp->action_variables );

			// Using "item" not "experiment" for generic support in other components.
			if ( bp_current_user_can( 'bp_moderate' ) )
				bp_update_is_item_admin( true, 'experiments' );
			else
				bp_update_is_item_admin( experiments_is_user_admin( bp_loggedin_user_id(), $this->current_experiment->id ), 'experiments' );

			// If the user is not an admin, check if they are a moderator
			if ( !bp_is_item_admin() )
				bp_update_is_item_mod  ( experiments_is_user_mod  ( bp_loggedin_user_id(), $this->current_experiment->id ), 'experiments' );

			// Is the logged in user a member of the experiment?
			if ( ( is_user_logged_in() && experiments_is_user_member( bp_loggedin_user_id(), $this->current_experiment->id ) ) )
				$this->current_experiment->is_user_member = true;
			else
				$this->current_experiment->is_user_member = false;

			// Should this experiment be visible to the logged in user?
			if ( 'public' == $this->current_experiment->status || $this->current_experiment->is_user_member )
				$this->current_experiment->is_visible = true;
			else
				$this->current_experiment->is_visible = false;

			// If this is a private or hidden experiment, does the user have access?
			if ( 'private' == $this->current_experiment->status || 'hidden' == $this->current_experiment->status ) {
				if ( $this->current_experiment->is_user_member && is_user_logged_in() || bp_current_user_can( 'bp_moderate' ) )
					$this->current_experiment->user_has_access = true;
				else
					$this->current_experiment->user_has_access = false;
			} else {
				$this->current_experiment->user_has_access = true;
			}

		// Set current_experiment to 0 to prevent debug errors
		} else {
			$this->current_experiment = 0;
		}

		// Illegal experiment names/slugs
		$this->forbidden_names = apply_filters( 'experiments_forbidden_names', array(
			'my-experiments',
			'create',
			'invites',
			'send-invites',
			'forum',
			'delete',
			'add',
			'admin',
			'request-membership',
			'members',
			'settings',
			'avatar',
			$this->slug,
			$this->root_slug,
		) );

		// If the user was attempting to access a experiment, but no experiment by that name was found, 404
		if ( bp_is_experiments_component() && empty( $this->current_experiment ) && bp_current_action() && !in_array( bp_current_action(), $this->forbidden_names ) ) {
			bp_do_404();
			return;
		}

		if ( bp_is_experiments_component() && !empty( $this->current_experiment ) ) {

			$this->default_extension = apply_filters( 'bp_experiments_default_extension', defined( 'BP_ExperimentS_DEFAULT_EXTENSION' ) ? BP_ExperimentS_DEFAULT_EXTENSION : 'home' );

			if ( !bp_current_action() ) {
				$bp->current_action = $this->default_extension;
			}

			// Prepare for a redirect to the canonical URL
			$bp->canonical_stack['base_url'] = bp_get_experiment_permalink( $this->current_experiment );

			if ( bp_current_action() ) {
				$bp->canonical_stack['action'] = bp_current_action();
			}

			if ( !empty( $bp->action_variables ) ) {
				$bp->canonical_stack['action_variables'] = bp_action_variables();
			}

			// When viewing the default extension, the canonical URL should not have
			// that extension's slug, unless more has been tacked onto the URL via
			// action variables
			if ( bp_is_current_action( $this->default_extension ) && empty( $bp->action_variables ) )  {
				unset( $bp->canonical_stack['action'] );
			}

		}

		// Experiment access control
		if ( bp_is_experiments_component() && !empty( $this->current_experiment ) ) {
			if ( !$this->current_experiment->user_has_access ) {

				// Hidden experiments should return a 404 for non-members.
				// Unset the current experiment so that you're not redirected
				// to the default experiment tab
				if ( 'hidden' == $this->current_experiment->status ) {
					$this->current_experiment = 0;
					$bp->is_single_item  = false;
					bp_do_404();
					return;

				// Skip the no_access check on home and membership request pages
				} elseif ( !bp_is_current_action( 'home' ) && !bp_is_current_action( 'request-membership' ) ) {

					// Off-limits to this user. Throw an error and redirect to the experiment's home page
					if ( is_user_logged_in() ) {
						bp_core_no_access( array(
							'message'  => __( 'You do not have access to this experiment.', 'buddypress' ),
							'root'     => bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'home/',
							'redirect' => false
						) );

					// User does not have access, and does not get a message
					} else {
						bp_core_no_access();
					}
				}
			}

			// Protect the admin tab from non-admins
			if ( bp_is_current_action( 'admin' ) && !bp_is_item_admin() ) {
				bp_core_no_access( array(
					'message'  => __( 'You are not an admin of this experiment.', 'buddypress' ),
					'root'     => bp_get_experiment_permalink( $bp->experiments->current_experiment ),
					'redirect' => false
				) );
			}
		}

		// Preconfigured experiment creation steps
		$this->experiment_creation_steps = apply_filters( 'experiments_create_experiment_steps', array(
			'experiment-details'  => array(
				'name'       => __( 'Details',  'buddypress' ),
				'position'   => 0
                                           
/*
			),
                                                                                                       
                                                                                    
            'experiment-variables'  => array(
                'name'       => __( 'Measurements',  'buddypress' ),
                'position'   => 10
            ),
                                                                                                       
                                                                                                       

            'experiment-settings' => array(
				'name'       => __( 'Settings', 'buddypress' ),
				'position'   => 20

     */
			)

		) );


/*
		// If avatar uploads are not disabled, add avatar option
		if ( ! (int) buddypress()->site_options['bp-disable-avatar-uploads'] ) {
			$this->experiment_creation_steps['experiment-avatar'] = array(
				'name'     => __( 'Avatar',   'buddypress' ),
				'position' => 30
			);
		}
        */
 
  /*
		// If friends component is active, add invitations
		if ( bp_is_active( 'friends' ) ) {
			$this->experiment_creation_steps['experiment-invites'] = array(
				'name'     => __( 'Invites', 'buddypress' ),
				'position' => 40
			);
		}
       
*/

		// Experiments statuses
		$this->valid_status = apply_filters( 'experiments_valid_status', array(
			'public',
			'private',
			'hidden'
		) );


 
		// Auto join experiment when non experiment member performs experiment activity
		$this->auto_join = defined( 'BP_DISABLE_AUTO_Experiment_JOIN' ) && BP_DISABLE_AUTO_Experiment_JOIN ? false : true;
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Only grab count if we're on a user page
		if ( bp_is_user() ) {
			$count    = bp_get_total_experiment_count_for_user();
			$class    = ( 0 === $count ) ? 'no-count' : 'count';
			$nav_name = sprintf( __( 'Experiments <span class="%s">%s</span>', 'buddypress' ), esc_attr( $class ), number_format_i18n( $count ) );
		} else {
			$nav_name = __( 'Experiments', 'buddypress' );
		}

		// Add 'Experiments' to the main navigation
		$main_nav = array(
			'name'                => $nav_name,
			'slug'                => $this->slug,
			'position'            => 70,
			'screen_function'     => 'experiments_screen_my_experiments',
			'default_subnav_slug' => 'my-experiments',
			'item_css_id'         => $this->id
		);

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			$user_domain = false;
		}

		if ( !empty( $user_domain ) ) {
			$experiments_link = trailingslashit( $user_domain . $this->slug );

			// Add the My Experiments nav item
			$sub_nav[] = array(
				'name'            => __( 'Memberships', 'buddypress' ),
				'slug'            => 'my-experiments',
				'parent_url'      => $experiments_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'experiments_screen_my_experiments',
				'position'        => 10,
				'item_css_id'     => 'experiments-my-experiments'
			);

			// Add the Experiment Invites nav item
			$sub_nav[] = array(
				'name'            => __( 'Invitations', 'buddypress' ),
				'slug'            => 'invites',
				'parent_url'      => $experiments_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'experiments_screen_experiment_invites',
				'user_has_access' => bp_core_can_edit_settings(),
				'position'        => 30
			);

			parent::setup_nav( $main_nav, $sub_nav );
		}

		if ( bp_is_experiments_component() && bp_is_single_item() ) {

			// Reset sub nav
			$sub_nav = array();

			// Add 'Experiments' to the main navigation
			$main_nav = array(
				'name'                => __( 'Memberships', 'buddypress' ),
				'slug'                => $this->current_experiment->slug,
				'position'            => -1, // Do not show in BuddyBar
				'screen_function'     => 'experiments_screen_experiment_home',
				'default_subnav_slug' => $this->default_extension,
				'item_css_id'         => $this->id
			);

			$experiment_link = bp_get_experiment_permalink( $this->current_experiment );

			// Add the "Home" subnav item, as this will always be present
			$sub_nav[] = array(
				'name'            =>  _x( 'Home', 'Experiment home navigation title', 'buddypress' ),
				'slug'            => 'home',
				'parent_url'      => $experiment_link,
				'parent_slug'     => $this->current_experiment->slug,
				'screen_function' => 'experiments_screen_experiment_home',
				'position'        => 10,
				'item_css_id'     => 'home'
			);

			// If this is a private experiment, and the user is not a
			// member and does not have an outstanding invitation,
			// show a "Request Membership" nav item.
			if ( is_user_logged_in() &&
				 ! $this->current_experiment->is_user_member &&
				 ! experiments_check_for_membership_request( bp_loggedin_user_id(), $this->current_experiment->id ) &&
				 $this->current_experiment->status == 'private' &&
				 ! experiments_check_user_has_invite( bp_loggedin_user_id(), $this->current_experiment->id )
				) {

				$sub_nav[] = array(
					'name'               => __( 'Request Membership', 'buddypress' ),
					'slug'               => 'request-membership',
					'parent_url'         => $experiment_link,
					'parent_slug'        => $this->current_experiment->slug,
					'screen_function'    => 'experiments_screen_experiment_request_membership',
					'position'           => 30
				);
			}

			// Forums are enabled and turned on
			if ( $this->current_experiment->enable_forum && bp_is_active( 'forums' ) ) {
				$sub_nav[] = array(
					'name'            => __( 'Forum', 'buddypress' ),
					'slug'            => 'forum',
					'parent_url'      => $experiment_link,
					'parent_slug'     => $this->current_experiment->slug,
					'screen_function' => 'experiments_screen_experiment_forum',
					'position'        => 40,
					'user_has_access' => $this->current_experiment->user_has_access,
					'item_css_id'     => 'forums'
				);
			}

			$sub_nav[] = array(
				'name'            => sprintf( __( 'Members <span>%s</span>', 'buddypress' ), number_format( $this->current_experiment->total_member_count ) ),
				'slug'            => 'members',
				'parent_url'      => $experiment_link,
				'parent_slug'     => $this->current_experiment->slug,
				'screen_function' => 'experiments_screen_experiment_members',
				'position'        => 60,
				'user_has_access' => $this->current_experiment->user_has_access,
				'item_css_id'     => 'members'
			);

			if ( bp_is_active( 'friends' ) && bp_experiments_user_can_send_invites() ) {
				$sub_nav[] = array(
					'name'            => __( 'Send Invites', 'buddypress' ),
					'slug'            => 'send-invites',
					'parent_url'      => $experiment_link,
					'parent_slug'     => $this->current_experiment->slug,
					'screen_function' => 'experiments_screen_experiment_invite',
					'item_css_id'     => 'invite',
					'position'        => 70,
					'user_has_access' => $this->current_experiment->user_has_access
				);
			}

			// If the user is a experiment admin, then show the experiment admin nav item
			if ( bp_is_item_admin() ) {
				$sub_nav[] = array(
					'name'            => __( 'Admin', 'buddypress' ),
					'slug'            => 'admin',
					'parent_url'      => $experiment_link,
					'parent_slug'     => $this->current_experiment->slug,
					'screen_function' => 'experiments_screen_experiment_admin',
					'position'        => 1000,
					'user_has_access' => true,
					'item_css_id'     => 'admin'
				);
			}

			parent::setup_nav( $main_nav, $sub_nav );
		}

		if ( isset( $this->current_experiment->user_has_access ) ) {
			do_action( 'experiments_setup_nav', $this->current_experiment->user_has_access );
		} else {
			do_action( 'experiments_setup_nav');
		}
	}

	/**
	 * Set up the Toolbar
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		$bp = buddypress();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain = bp_loggedin_user_domain();
			$experiments_link = trailingslashit( $user_domain . $this->slug );

			// Pending experiment invites
			$count   = experiments_get_invite_count_for_user();
			$title   = __( 'Experiments',             'buddypress' );
			$pending = __( 'No Pending Invites', 'buddypress' );

			if ( !empty( $count['total'] ) ) {
				$title   = sprintf( __( 'Experiments <span class="count">%s</span>',          'buddypress' ), $count );
				$pending = sprintf( __( 'Pending Invites <span class="count">%s</span>', 'buddypress' ), $count );
			}

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => trailingslashit( $experiments_link )
			);

			// My Experiments
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-memberships',
				'title'  => __( 'Memberships', 'buddypress' ),
				'href'   => trailingslashit( $experiments_link )
			);

			// Invitations
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-invites',
				'title'  => $pending,
				'href'   => trailingslashit( $experiments_link . 'invites' )
			);

			// Create a Experiment
			if ( bp_user_can_create_experiments() ) {
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-create',
					'title'  => __( 'Create a Experiment', 'buddypress' ),
					'href'   => trailingslashit( bp_get_experiments_directory_permalink() . 'create' )
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 */
	public function setup_title() {
		$bp = buddypress();

		if ( bp_is_experiments_component() ) {

			if ( bp_is_my_profile() && !bp_is_single_item() ) {
				$bp->bp_options_title = __( 'Memberships', 'buddypress' );

			} else if ( !bp_is_my_profile() && !bp_is_single_item() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();

			// We are viewing a single experiment, so set up the
			// experiment navigation menu using the $this->current_experiment global.
			} else if ( bp_is_single_item() ) {
				$bp->bp_options_title  = $this->current_experiment->name;
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id'    => $this->current_experiment->id,
					'object'     => 'experiment',
					'type'       => 'thumb',
					'avatar_dir' => 'experiment-avatars',
					'alt'        => __( 'Experiment Avatar', 'buddypress' )
				) );

				if ( empty( $bp->bp_options_avatar ) ) {
					$bp->bp_options_avatar = '<img src="' . esc_url( bp_core_avatar_default_thumb() ) . '" alt="' . esc_attr__( 'No Experiment Avatar', 'buddypress' ) . '" class="avatar" />';
				}
			}
		}

		parent::setup_title();
	}
}


function bp_setup_experiments() {
	buddypress()->experiments = new BP_Experiments_Component();
}
add_action( 'bp_setup_components', 'bp_setup_experiments', 6 );
