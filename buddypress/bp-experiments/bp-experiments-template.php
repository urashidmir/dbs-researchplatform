<?php
/**
 * BuddyPress Experiments Template Functions
 *
 * @package BuddyPress
 * @subpackage ExperimentsTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the experiments component slug
 *
 * @package BuddyPress
 * @subpackage Experiments Template
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_experiments_slug()
 */
function bp_experiments_slug() {
	echo bp_get_experiments_slug();
}
	/**
	 * Return the experiments component slug
	 *
	 * @package BuddyPress
	 * @subpackage Experiments Template
	 * @since BuddyPress (1.5)
	 */
	
function bp_get_experiments_slug() {
    return apply_filters( 'bp_get_experiments_slug', buddypress()->experiments->slug );
}

/**
 * Output the experiments component root slug
 *
 * @package BuddyPress
 * @subpackage Experiments Template
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_experiments_root_slug()
 */
function bp_experiments_root_slug() {
	echo bp_get_experiments_root_slug();
}
	/**
	 * Return the experiments component root slug
	 *
	 * @package BuddyPress
	 * @subpackage Experiments Template
	 * @since BuddyPress (1.5)
	 */
	function bp_get_experiments_root_slug() {
		return apply_filters( 'bp_get_experiments_root_slug', buddypress()->experiments->root_slug );
	}

/**
 * Output experiment directory permalink
 *
 * @package BuddyPress
 * @subpackage Experiments Template
 * @since BuddyPress (1.5)
 * @uses bp_get_experiments_directory_permalink()
 */
function bp_experiments_directory_permalink() {
	echo bp_get_experiments_directory_permalink();
}
	/**
	 * Return experiment directory permalink
	 *
	 * @package BuddyPress
	 * @subpackage Experiments Template
	 * @since BuddyPress (1.5)
	 * @uses apply_filters()
	 * @uses traisingslashit()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_experiments_root_slug()
	 * @return string
	 */
	function bp_get_experiments_directory_permalink() {
		return apply_filters( 'bp_get_experiments_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() ) );
	}

/*****************************************************************************
 * Experiments Template Class/Tags
 **/

class BP_Experiments_Template {
	var $current_experiment = -1;
	var $experiment_count;
	var $experiments;
	var $experiment;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_experiment_count;

	var $single_experiment = false;

	var $sort_by;
	var $order;

	function __construct( $args = array() ){

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.7', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'user_id',
				1  => 'type',
				2  => 'page',
				3  => 'per_page',
				4  => 'max',
				5  => 'slug',
				6  => 'search_terms',
				7  => 'populate_extras',
				8  => 'include',
				9  => 'exclude',
				10 => 'show_hidden',
				11 => 'page_arg',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'type'            => 'active',
			'page'            => 1,
			'per_page'        => 20,
			'max'             => false,
			'show_hidden'     => false,
			'page_arg'        => 'grpage',
			'user_id'         => 0,
			'slug'            => false,
			'include'         => false,
			'exclude'         => false,
			'search_terms'    => '',
			'meta_query'      => false,
			'populate_extras' => true,
			'update_meta_cache' => true,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_page = isset( $_REQUEST[$page_arg] ) ? intval( $_REQUEST[$page_arg] ) : $page;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		if ( bp_current_user_can( 'bp_moderate' ) || ( is_user_logged_in() && $user_id == bp_loggedin_user_id() ) )
			$show_hidden = true;

		if ( 'invites' == $type ) {
			$this->experiments = experiments_get_invites_for_user( $user_id, $this->pag_num, $this->pag_page, $exclude );
		} else if ( 'single-experiment' == $type ) {
			$this->single_experiment = true;

			if ( experiments_get_current_experiment() ) {
				$experiment = experiments_get_current_experiment();

			} else {
				$experiment = experiments_get_experiment( array(
					'experiment_id'        => BP_Experiments_Experiment::get_id_from_slug( $r['slug'] ),
					'populate_extras' => $r['populate_extras'],
				) );
			}

			// backwards compatibility - the 'experiment_id' variable is not part of the
			// BP_Experiments_Experiment object, but we add it here for devs doing checks against it
			//
			// @see https://buddypress.trac.wordpress.org/changeset/3540
			//
			// this is subject to removal in a future release; devs should check against
			// $experiment->id instead
			$experiment->experiment_id = $experiment->id;

			$this->experiments = array( $experiment );

		} else {
			$this->experiments = experiments_get_experiments( array(
				'type'              => $type,
				'order'             => $order,
				'orderby'           => $orderby,
				'per_page'          => $this->pag_num,
				'page'              => $this->pag_page,
				'user_id'           => $user_id,
				'search_terms'      => $search_terms,
				'meta_query'        => $meta_query,
				'include'           => $include,
				'exclude'           => $exclude,
				'populate_extras'   => $populate_extras,
				'update_meta_cache' => $update_meta_cache,
				'show_hidden'       => $show_hidden
			) );
		}

		if ( 'invites' == $type ) {
			$this->total_experiment_count = (int) $this->experiments['total'];
			$this->experiment_count       = (int) $this->experiments['total'];
			$this->experiments            = $this->experiments['experiments'];
		} else if ( 'single-experiment' == $type ) {
			if ( empty( $experiment->id ) ) {
				$this->total_experiment_count = 0;
				$this->experiment_count       = 0;
			} else {
				$this->total_experiment_count = 1;
				$this->experiment_count       = 1;
			}
		} else {
			if ( empty( $max ) || $max >= (int) $this->experiments['total'] ) {
				$this->total_experiment_count = (int) $this->experiments['total'];
			} else {
				$this->total_experiment_count = (int) $max;
			}

			$this->experiments = $this->experiments['experiments'];

			if ( !empty( $max ) ) {
				if ( $max >= count( $this->experiments ) ) {
					$this->experiment_count = count( $this->experiments );
				} else {
					$this->experiment_count = (int) $max;
				}
			} else {
				$this->experiment_count = count( $this->experiments );
			}
		}

		// Build pagination links
		if ( (int) $this->total_experiment_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( array( $page_arg => '%#%', 'num' => $this->pag_num, 's' => $search_terms, 'sortby' => $this->sort_by, 'order' => $this->order ) ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_experiment_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Experiment pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Experiment pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	function has_experiments() {
		if ( $this->experiment_count )
			return true;

		return false;
	}

	function next_experiment() {
		$this->current_experiment++;
		$this->experiment = $this->experiments[$this->current_experiment];

		return $this->experiment;
	}

	function rewind_experiments() {
		$this->current_experiment = -1;
		if ( $this->experiment_count > 0 ) {
			$this->experiment = $this->experiments[0];
		}
	}

	function experiments() {
		if ( $this->current_experiment + 1 < $this->experiment_count ) {
			return true;
		} elseif ( $this->current_experiment + 1 == $this->experiment_count ) {
			do_action('experiment_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_experiments();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_experiment() {
		$this->in_the_loop = true;
		$this->experiment       = $this->next_experiment();

		if ( 0 == $this->current_experiment ) // loop has just started
			do_action('experiment_loop_start');
	}
}

/**
 * Start the Experiments Template Loop
 *
 * See the $defaults definition below for a description of parameters.
 *
 * Note that the 'type' parameter overrides 'order' and 'orderby'. See
 * BP_Experiments_Experiment::get() for more details.
 *
 * @param array $args
 * @return bool True if there are experiments to display that match the params
 */
function bp_has_experiments( $args = '' ) {
	global $experiments_template, $bp;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$slug    = false;
	$type    = '';
	$user_id = 0;
	$order   = '';

	// User filtering
	if ( bp_displayed_user_id() )
		$user_id = bp_displayed_user_id();

	// Type
	// @todo What is $order? At some point it was removed incompletely?
	if ( bp_is_current_action( 'my-experiments' ) ) {
		if ( 'most-popular' == $order ) {
			$type = 'popular';
		} elseif ( 'alphabetically' == $order ) {
			$type = 'alphabetical';
		}
	} elseif ( bp_is_current_action( 'invites' ) ) {
		$type = 'invites';
	} elseif ( isset( $bp->experiments->current_experiment->slug ) && $bp->experiments->current_experiment->slug ) {
		$type = 'single-experiment';
		$slug = $bp->experiments->current_experiment->slug;
	}

	// Default search string
	if ( ! empty( $_REQUEST['experiment-filter-box'] ) ) {
		$search_terms = $_REQUEST['experiment-filter-box'];
	} elseif ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) ) {
		$search_terms = $_REQUEST['s'];
	} else {
		$search_terms = false;
	}

	$defaults = array(
		'type'              => $type, // 'type' is an override for 'order' and 'orderby'. See docblock.
		'order'             => 'DESC',
		'orderby'           => 'last_activity',
		'page'              => 1,
		'per_page'          => 20,
		'max'               => false,
		'show_hidden'       => false,

		'page_arg'          => 'grpage', // See https://buddypress.trac.wordpress.org/ticket/3679

		'user_id'           => $user_id, // Pass a user ID to limit to experiments this user has joined
		'slug'              => $slug,    // Pass a experiment slug to only return that experiment
		'search_terms'      => $search_terms, // Pass search terms to return only matching experiments
		'meta_query'        => false,    // Filter by experimentmeta. See WP_Meta_Query for format
		'include'           => false,    // Pass comma separated list or array of experiment ID's to return only these experiments
		'exclude'           => false,    // Pass comma separated list or array of experiment ID's to exclude these experiments

		'populate_extras'   => true,     // Get extra meta - is_member, is_banned
		'update_meta_cache' => true,
	);

	$r = bp_parse_args( $args, $defaults, 'has_experiments' );

	$experiments_template = new BP_Experiments_Template( array(
		'type'              => $r['type'],
		'order'             => $r['order'],
		'orderby'           => $r['orderby'],
		'page'              => (int) $r['page'],
		'per_page'          => (int) $r['per_page'],
		'max'               => (int) $r['max'],
		'show_hidden'       => $r['show_hidden'],
		'page_arg'          => $r['page_arg'],
		'user_id'           => (int) $r['user_id'],
		'slug'              => $r['slug'],
		'search_terms'      => $r['search_terms'],
		'meta_query'        => $r['meta_query'],
		'include'           => $r['include'],
		'exclude'           => $r['exclude'],
		'populate_extras'   => (bool) $r['populate_extras'],
		'update_meta_cache' => (bool) $r['update_meta_cache'],
	) );

	return apply_filters( 'bp_has_experiments', $experiments_template->has_experiments(), $experiments_template, $r );
}

function bp_experiments() {
	global $experiments_template;
	return $experiments_template->experiments();
}

function bp_the_experiment() {
	global $experiments_template;
	return $experiments_template->the_experiment();
}

function bp_experiment_is_visible( $experiment = false ) {
	global $experiments_template;

	if ( bp_current_user_can( 'bp_moderate' ) )
		return true;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( 'public' == $experiment->status ) {
		return true;
	} else {
		if ( experiments_is_user_member( bp_loggedin_user_id(), $experiment->id ) ) {
			return true;
		}
	}

	return false;
}

function bp_experiment_id( $experiment = false ) {
	echo bp_get_experiment_id( $experiment );
}
	function bp_get_experiment_id( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_id', $experiment->id );
	}

/**
 * Output the row class of a experiment
 *
 * @since BuddyPress (1.7)
 */
function bp_experiment_class() {
	echo bp_get_experiment_class();
}
	/**
	 * Return the row class of a experiment
	 *
	 * @global BP_Experiments_Template $experiments_template
	 * @return string Row class of the experiment
	 * @since BuddyPress (1.7)
	 */
	function bp_get_experiment_class() {
		global $experiments_template;

		$classes      = array();
		$pos_in_loop  = (int) $experiments_template->current_experiment;

		// If we've only one experiment in the loop, don't both with odd and even.
		if ( $experiments_template->experiment_count > 1 )
			$classes[] = ( $pos_in_loop % 2 ) ? 'even' : 'odd';
		else
			$classes[] = 'bp-single-experiment';

		// Experiment type - public, private, hidden.
		$classes[] = esc_attr( $experiments_template->experiment->status );

		// User's experiment status
		if ( bp_is_user_active() ) {
			if ( bp_experiment_is_admin() )
				$classes[] = 'is-admin';

			if ( bp_experiment_is_member() )
				$classes[] = 'is-member';

			if ( bp_experiment_is_mod() )
				$classes[] = 'is-mod';
		}

		$classes = apply_filters( 'bp_get_experiment_class', $classes );
		$classes = array_merge( $classes, array() );
		$retval = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

function bp_experiment_name( $experiment = false ) {
	echo bp_get_experiment_name( $experiment );
}
	function bp_get_experiment_name( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_name', $experiment->name );
	}

function bp_experiment_type( $experiment = false ) {
	echo bp_get_experiment_type( $experiment );
}
	function bp_get_experiment_type( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		if ( 'public' == $experiment->status ) {
			$type = __( "Public Experiment", "buddypress" );
		} else if ( 'hidden' == $experiment->status ) {
			$type = __( "Hidden Experiment", "buddypress" );
		} else if ( 'private' == $experiment->status ) {
			$type = __( "Private Experiment", "buddypress" );
		} else {
			$type = ucwords( $experiment->status ) . ' ' . __( 'Experiment', 'buddypress' );
		}

		return apply_filters( 'bp_get_experiment_type', $type );
	}

function bp_experiment_status( $experiment = false ) {
	echo bp_get_experiment_status( $experiment );
}
	function bp_get_experiment_status( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_status', $experiment->status );
	}

function bp_experiment_avatar( $args = '' ) {
	echo bp_get_experiment_avatar( $args );
}
	function bp_get_experiment_avatar( $args = '' ) {
		global $bp, $experiments_template;

		$defaults = array(
			'type'   => 'full',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => sprintf( __( 'Experiment logo of %s', 'buddypress' ), $experiments_template->experiment->name )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/* Fetch the avatar from the folder, if not provide backwards compat. */
		if ( !$avatar = bp_core_fetch_avatar( array( 'item_id' => $experiments_template->experiment->id, 'object' => 'experiment', 'type' => $type, 'avatar_dir' => 'experiment-avatars', 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height, 'title' => $experiments_template->experiment->name, 'alt' => $alt ) ) )
			$avatar = '<img src="' . esc_url( $experiments_template->experiment->avatar_thumb ) . '" class="avatar" alt="' . esc_attr( $experiments_template->experiment->name ) . '" />';

		return apply_filters( 'bp_get_experiment_avatar', $avatar );
	}

function bp_experiment_avatar_thumb( $experiment = false ) {
	echo bp_get_experiment_avatar_thumb( $experiment );
}
	function bp_get_experiment_avatar_thumb( $experiment = false ) {
		return bp_get_experiment_avatar( 'type=thumb' );
	}

function bp_experiment_avatar_mini( $experiment = false ) {
	echo bp_get_experiment_avatar_mini( $experiment );
}
	function bp_get_experiment_avatar_mini( $experiment = false ) {
		return bp_get_experiment_avatar( 'type=thumb&width=30&height=30' );
	}

function bp_experiment_last_active( $experiment = false ) {
	echo bp_get_experiment_last_active( $experiment );
}
	function bp_get_experiment_last_active( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		$last_active = $experiment->last_activity;

		if ( !$last_active )
			$last_active = experiments_get_experimentmeta( $experiment->id, 'last_activity' );

		if ( empty( $last_active ) ) {
			return __( 'not yet active', 'buddypress' );
		} else {
			return apply_filters( 'bp_get_experiment_last_active', bp_core_time_since( $last_active ) );
		}
	}

function bp_experiment_permalink( $experiment = false ) {
	echo bp_get_experiment_permalink( $experiment );
}
	function bp_get_experiment_permalink( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/' . $experiment->slug . '/' ) );
	}

function bp_experiment_admin_permalink( $experiment = false ) {
	echo bp_get_experiment_admin_permalink( $experiment );
}
	function bp_get_experiment_admin_permalink( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_admin_permalink', trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin' ) );
	}

function bp_experiment_slug( $experiment = false ) {
	echo bp_get_experiment_slug( $experiment );
}
	function bp_get_experiment_slug( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_slug', $experiment->slug );
	}

function bp_experiment_description( $experiment = false ) {
	echo bp_get_experiment_description( $experiment );
}
	function bp_get_experiment_description( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_description', stripslashes($experiment->description) );
	}

function bp_experiment_description_editable( $experiment = false ) {
	echo bp_get_experiment_description_editable( $experiment );
}
	function bp_get_experiment_description_editable( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_description_editable', $experiment->description );
	}

/**
 * Output an excerpt of the experiment description.
 *
 * @param object $experiment Optional. The experiment being referenced. Defaults to the
 *        experiment currently being iterated on in the experiments loop.
 */
function bp_experiment_description_excerpt( $experiment = false ) {
	echo bp_get_experiment_description_excerpt( $experiment );
}
	/**
	 * Get an excerpt of a experiment description.
	 *
	 * @param object $experiment Optional. The experiment being referenced. Defaults
	 *        to the experiment currently being iterated on in the experiments loop.
	 * @return string Excerpt.
	 */
	function bp_get_experiment_description_excerpt( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) ) {
			$experiment =& $experiments_template->experiment;
		}

		return apply_filters( 'bp_get_experiment_description_excerpt', bp_create_excerpt( $experiment->description ), $experiment );
	}


function bp_experiment_public_status( $experiment = false ) {
	echo bp_get_experiment_public_status( $experiment );
}
	function bp_get_experiment_public_status( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		if ( $experiment->is_public ) {
			return __( 'Public', 'buddypress' );
		} else {
			return __( 'Private', 'buddypress' );
		}
	}

function bp_experiment_is_public( $experiment = false ) {
	echo bp_get_experiment_is_public( $experiment );
}
	function bp_get_experiment_is_public( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_is_public', $experiment->is_public );
	}

function bp_experiment_date_created( $experiment = false ) {
	echo bp_get_experiment_date_created( $experiment );
}
	function bp_get_experiment_date_created( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_date_created', bp_core_time_since( strtotime( $experiment->date_created ) ) );
	}

function bp_experiment_creator_username( $experiment = false ) {
	echo bp_get_experiment_creator_username( $experiment );
}
	function bp_get_experiment_creator_username( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_creator_username', bp_core_get_user_displayname( $experiment->creator_id ) );
	}

function bp_experiment_creator_id( $experiment = false ) {
	echo bp_get_experiment_creator_id( $experiment );
}
	function bp_get_experiment_creator_id( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_creator_id', $experiment->creator_id );
	}

function bp_experiment_creator_permalink( $experiment = false ) {
	echo bp_get_experiment_creator_permalink( $experiment );
}
	function bp_get_experiment_creator_permalink( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_creator_permalink', bp_core_get_user_domain( $experiment->creator_id ) );
	}

function bp_is_experiment_creator( $experiment = false, $user_id = 0 ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	return (bool) ( $experiment->creator_id == $user_id );
}

function bp_experiment_creator_avatar( $experiment = false, $args = array() ) {
	echo bp_get_experiment_creator_avatar( $experiment, $args );
}
	function bp_get_experiment_creator_avatar( $experiment = false, $args = array() ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		$defaults = array(
			'type'   => 'full',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => sprintf( __( 'Experiment creator avatar of %s', 'buddypress' ),  bp_core_get_user_displayname( $experiment->creator_id ) )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		$avatar = bp_core_fetch_avatar( array( 'item_id' => $experiment->creator_id, 'type' => $type, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height, 'alt' => $alt ) );

		return apply_filters( 'bp_get_experiment_creator_avatar', $avatar );
	}


function bp_experiment_is_admin() {
	return bp_is_item_admin();
}

function bp_experiment_is_mod() {
	return bp_is_item_mod();
}

function bp_experiment_list_admins( $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) ) {
		$experiment =& $experiments_template->experiment;
	}

	// fetch experiment admins if 'populate_extras' flag is false
	if ( empty( $experiment->args['populate_extras'] ) ) {
		$query = new BP_Experiment_Member_Query( array(
			'experiment_id'   => $experiment->id,
			'experiment_role' => 'admin',
			'type'       => 'first_joined',
		) );

		if ( ! empty( $query->results ) ) {
			$experiment->admins = $query->results;
		}
	}

	if ( ! empty( $experiment->admins ) ) { ?>
		<ul id="experiment-admins">
			<?php foreach( (array) $experiment->admins as $admin ) { ?>
				<li>
					<a href="<?php echo bp_core_get_user_domain( $admin->user_id, $admin->user_nicename, $admin->user_login ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'email' => $admin->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $admin->user_id ) ) ) ) ?></a>
				</li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		<span class="activity"><?php _e( 'No Admins', 'buddypress' ) ?></span>
	<?php } ?>
<?php
}

function bp_experiment_list_mods( $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) ) {
		$experiment =& $experiments_template->experiment;
	}

	// fetch experiment mods if 'populate_extras' flag is false
	if ( empty( $experiment->args['populate_extras'] ) ) {
		$query = new BP_Experiment_Member_Query( array(
			'experiment_id'   => $experiment->id,
			'experiment_role' => 'mod',
			'type'       => 'first_joined',
		) );

		if ( ! empty( $query->results ) ) {
			$experiment->mods = $query->results;
		}
	}

	if ( ! empty( $experiment->mods ) ) : ?>

		<ul id="experiment-mods">

			<?php foreach( (array) $experiment->mods as $mod ) { ?>

				<li>
					<a href="<?php echo bp_core_get_user_domain( $mod->user_id, $mod->user_nicename, $mod->user_login ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'email' => $mod->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $mod->user_id ) ) ) ) ?></a>
				</li>

			<?php } ?>

		</ul>

<?php else : ?>

		<span class="activity"><?php _e( 'No Mods', 'buddypress' ) ?></span>

<?php endif;

}

/**
 * Return a list of user_ids for a experiment's admins
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param BP_Experiments_Experiment $experiment (optional) The experiment being queried. Defaults to the current experiment in the loop
 * @param string $format 'string' to get a comma-separated string, 'array' to get an array
 * @return mixed $admin_ids A string or array of user_ids
 */
function bp_experiment_admin_ids( $experiment = false, $format = 'string' ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	$admin_ids = array();

	if ( $experiment->admins ) {
		foreach( $experiment->admins as $admin ) {
			$admin_ids[] = $admin->user_id;
		}
	}

	if ( 'string' == $format )
		$admin_ids = implode( ',', $admin_ids );

	return apply_filters( 'bp_experiment_admin_ids', $admin_ids );
}

/**
 * Return a list of user_ids for a experiment's moderators
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param BP_Experiments_Experiment $experiment (optional) The experiment being queried. Defaults to the current experiment in the loop
 * @param string $format 'string' to get a comma-separated string, 'array' to get an array
 * @return mixed $mod_ids A string or array of user_ids
 */
function bp_experiment_mod_ids( $experiment = false, $format = 'string' ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	$mod_ids = array();

	if ( $experiment->mods ) {
		foreach( $experiment->mods as $mod ) {
			$mod_ids[] = $mod->user_id;
		}
	}

	if ( 'string' == $format )
		$mod_ids = implode( ',', $mod_ids );

	return apply_filters( 'bp_experiment_mod_ids', $mod_ids );
}

function bp_experiment_all_members_permalink() {
	echo bp_get_experiment_all_members_permalink();
}
	function bp_get_experiment_all_members_permalink( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_all_members_permalink', bp_get_experiment_permalink( $experiment ) . 'members' );
	}

function bp_experiment_search_form() {
	global $bp;

	$action = bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/search/';
	$label = __('Filter Experiments', 'buddypress');
	$name = 'experiment-filter-box';

	$search_form_html = '<form action="' . $action . '" id="experiment-search-form" method="post">
		<label for="'. $name .'" id="'. $name .'-label">'. $label .'</label>
		<input type="search" name="'. $name . '" id="'. $name .'" value="'. $value .'"'.  $disabled .' />

		'. wp_nonce_field( 'experiment-filter-box', '_wpnonce_experiment_filter', true, false ) .'
		</form>';

	echo apply_filters( 'bp_experiment_search_form', $search_form_html );
}

function bp_experiment_show_no_experiments_message() {
	if ( !experiments_total_experiments_for_user( bp_displayed_user_id() ) )
		return true;

	return false;
}

function bp_experiment_is_activity_permalink() {

	if ( !bp_is_single_item() || !bp_is_experiments_component() || !bp_is_current_action( bp_get_activity_slug() ) )
		return false;

	return true;
}

function bp_experiments_pagination_links() {
	echo bp_get_experiments_pagination_links();
}
	function bp_get_experiments_pagination_links() {
		global $experiments_template;

		return apply_filters( 'bp_get_experiments_pagination_links', $experiments_template->pag_links );
	}

function bp_experiments_pagination_count() {
	echo bp_get_experiments_pagination_count();
}
	function bp_get_experiments_pagination_count() {
		global $experiments_template;

		$start_num = intval( ( $experiments_template->pag_page - 1 ) * $experiments_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $experiments_template->pag_num - 1 ) > $experiments_template->total_experiment_count ) ? $experiments_template->total_experiment_count : $start_num + ( $experiments_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $experiments_template->total_experiment_count );

		return apply_filters( 'bp_get_experiments_pagination_count', sprintf( _n( 'Viewing experiment %1$s to %2$s (of %3$s experiment)', 'Viewing experiment %1$s to %2$s (of %3$s experiments)', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
	}

function bp_experiments_auto_join() {
	global $bp;

	return apply_filters( 'bp_experiments_auto_join', (bool)$bp->experiments->auto_join );
}

function bp_experiment_total_members( $experiment = false ) {
	echo bp_get_experiment_total_members( $experiment );
}
	function bp_get_experiment_total_members( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_total_members', $experiment->total_member_count );
	}

function bp_experiment_member_count() {
	echo bp_get_experiment_member_count();
}
	function bp_get_experiment_member_count() {
		global $experiments_template;

		if ( 1 == (int) $experiments_template->experiment->total_member_count )
			return apply_filters( 'bp_get_experiment_member_count', sprintf( __( '%s member', 'buddypress' ), bp_core_number_format( $experiments_template->experiment->total_member_count ) ) );
		else
			return apply_filters( 'bp_get_experiment_member_count', sprintf( __( '%s members', 'buddypress' ), bp_core_number_format( $experiments_template->experiment->total_member_count ) ) );
	}
    
    
    
    
    
    function bp_experiment_total_variables( $experiment = false ) {
        echo bp_get_experiment_total_variables( $experiment );
    }
    
    function bp_get_experiment_total_variables( $experiment = false ) {
        global $experiments_template;
        
        if ( empty( $experiment ) )
            $experiment =& $experiments_template->experiment;
        
        return apply_filters( 'bp_get_experiment_total_variables', $experiment->total_variable_count );
    }
    
    function bp_experiment_variable_count() {
        echo bp_get_experiment_variable_count();
    }
    
    function bp_get_experiment_variable_count() {
        global $experiments_template;
        
        if ( 1 == (int) $experiments_template->experiment->total_variable_count )
            return apply_filters( 'bp_get_experiment_variable_count', sprintf( __( '%s variable', 'buddypress' ), bp_core_number_format( $experiments_template->experiment->total_variable_count ) ) );
        else
            return apply_filters( 'bp_get_experiment_variable_count', sprintf( __( '%s variables', 'buddypress' ), bp_core_number_format( $experiments_template->experiment->total_variable_count ) ) );
    }
    

function bp_experiment_forum_permalink() {
	echo bp_get_experiment_forum_permalink();
}
	function bp_get_experiment_forum_permalink( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_forum_permalink', bp_get_experiment_permalink( $experiment ) . 'forum' );
	}

function bp_experiment_forum_topic_count( $args = '' ) {
	echo bp_get_experiment_forum_topic_count( $args );
}
	function bp_get_experiment_forum_topic_count( $args = '' ) {
		global $experiments_template;

		$defaults = array(
			'showtext' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( !$forum_id = experiments_get_experimentmeta( $experiments_template->experiment->id, 'forum_id' ) )
			return false;

		if ( !bp_is_active( 'forums' ) )
			return false;

		if ( !$experiments_template->experiment->forum_counts )
			$experiments_template->experiment->forum_counts = bp_forums_get_forum_topicpost_count( (int) $forum_id );

		if ( (bool) $showtext ) {
			if ( 1 == (int) $experiments_template->experiment->forum_counts[0]->topics )
				$total_topics = sprintf( __( '%d topic', 'buddypress' ), (int) $experiments_template->experiment->forum_counts[0]->topics );
			else
				$total_topics = sprintf( __( '%d topics', 'buddypress' ), (int) $experiments_template->experiment->forum_counts[0]->topics );
		} else {
			$total_topics = (int) $experiments_template->experiment->forum_counts[0]->topics;
		}

		return apply_filters( 'bp_get_experiment_forum_topic_count', $total_topics, (bool)$showtext );
	}

function bp_experiment_forum_post_count( $args = '' ) {
	echo bp_get_experiment_forum_post_count( $args );
}
	function bp_get_experiment_forum_post_count( $args = '' ) {
		global $experiments_template;

		$defaults = array(
			'showtext' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( !$forum_id = experiments_get_experimentmeta( $experiments_template->experiment->id, 'forum_id' ) )
			return false;

		if ( !bp_is_active( 'forums' ) )
			return false;

		if ( !$experiments_template->experiment->forum_counts )
			$experiments_template->experiment->forum_counts = bp_forums_get_forum_topicpost_count( (int) $forum_id );

		if ( (bool) $showtext ) {
			if ( 1 == (int) $experiments_template->experiment->forum_counts[0]->posts )
				$total_posts = sprintf( __( '%d post', 'buddypress' ), (int) $experiments_template->experiment->forum_counts[0]->posts );
			else
				$total_posts = sprintf( __( '%d posts', 'buddypress' ), (int) $experiments_template->experiment->forum_counts[0]->posts );
		} else {
			$total_posts = (int) $experiments_template->experiment->forum_counts[0]->posts;
		}

		return apply_filters( 'bp_get_experiment_forum_post_count', $total_posts, (bool)$showtext );
	}

function bp_experiment_is_forum_enabled( $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( ! empty( $experiment->enable_forum ) )
		return true;

	return false;
}

function bp_experiment_show_forum_setting( $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( $experiment->enable_forum )
		echo ' checked="checked"';
}

function bp_experiment_show_status_setting( $setting, $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( $setting == $experiment->status )
		echo ' checked="checked"';
}

/**
 * Get the 'checked' value, if needed, for a given invite_status on the experiment create/admin screens
 *
 * @package BuddyPress
 * @subpackage Experiments Template
 * @since BuddyPress (1.5)
 *
 * @param string $setting The setting you want to check against ('members', 'mods', or 'admins')
 * @param BP_Experiments_Experiment $experiment (optional) The experiment whose status you want to check
 */
function bp_experiment_show_invite_status_setting( $setting, $experiment = false ) {
	$experiment_id = isset( $experiment->id ) ? $experiment->id : false;

	$invite_status = bp_experiment_get_invite_status( $experiment_id );

	if ( $setting == $invite_status )
		echo ' checked="checked"';
}

/**
 * Get the invite status of a experiment
 *
 * 'invite_status' became part of BuddyPress in BP 1.5. In order to provide backward compatibility,
 * experiments without a status set will default to 'members', ie all members in a experiment can send
 * invitations. Filter 'bp_experiment_invite_status_fallback' to change this fallback behavior.
 *
 * This function can be used either in or out of the loop.
 *
 * @package BuddyPress
 * @subpackage Experiments Template
 * @since BuddyPress (1.5)
 *
 * @param int $experiment_id (optional) The id of the experiment whose status you want to check
 * @return mixed Returns false when no experiment can be found. Otherwise returns the experiment invite
 *    status, from among 'members', 'mods', and 'admins'
 */
function bp_experiment_get_invite_status( $experiment_id = false ) {
	global $bp, $experiments_template;

	if ( !$experiment_id ) {
		if ( isset( $bp->experiments->current_experiment->id ) ) {
			// Default to the current experiment first
			$experiment_id = $bp->experiments->current_experiment->id;
		} else if ( isset( $experiments_template->experiment->id ) ) {
			// Then see if we're in the loop
			$experiment_id = $experiments_template->experiment->id;
		} else {
			return false;
		}
	}

	$invite_status = experiments_get_experimentmeta( $experiment_id, 'invite_status' );

	// Backward compatibility. When 'invite_status' is not set, fall back to a default value
	if ( !$invite_status ) {
		$invite_status = apply_filters( 'bp_experiment_invite_status_fallback', 'members' );
	}

	return apply_filters( 'bp_experiment_get_invite_status', $invite_status, $experiment_id );
}

/**
 * Can the logged-in user send invitations in the specified experiment?
 *
 * @package BuddyPress
 * @subpackage Experiments Template
 * @since BuddyPress (1.5)
 *
 * @param int $experiment_id (optional) The id of the experiment whose status you want to check
 * @return bool $can_send_invites
 */
function bp_experiments_user_can_send_invites( $experiment_id = false ) {
	global $bp;

	$can_send_invites = false;
	$invite_status    = false;

	if ( is_user_logged_in() ) {
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			// Super admins can always send invitations
			$can_send_invites = true;

		} else {
			// If no $experiment_id is provided, default to the current experiment id
			if ( !$experiment_id )
				$experiment_id = isset( $bp->experiments->current_experiment->id ) ? $bp->experiments->current_experiment->id : 0;

			// If no experiment has been found, bail
			if ( !$experiment_id )
				return false;

			$invite_status = bp_experiment_get_invite_status( $experiment_id );
			if ( !$invite_status )
				return false;

			switch ( $invite_status ) {
				case 'admins' :
					if ( experiments_is_user_admin( bp_loggedin_user_id(), $experiment_id ) )
						$can_send_invites = true;
					break;

				case 'mods' :
					if ( experiments_is_user_mod( bp_loggedin_user_id(), $experiment_id ) || experiments_is_user_admin( bp_loggedin_user_id(), $experiment_id ) )
						$can_send_invites = true;
					break;

				case 'members' :
					if ( experiments_is_user_member( bp_loggedin_user_id(), $experiment_id ) )
						$can_send_invites = true;
					break;
			}
		}
	}

	return apply_filters( 'bp_experiments_user_can_send_invites', $can_send_invites, $experiment_id, $invite_status );
}

/**
 * Since BuddyPress 1.0, this generated the experiment settings admin/member screen.
 * As of BuddyPress 1.5 (r4489), and because this function outputs HTML, it was moved into /bp-default/experiments/single/admin.php.
 *
 * @deprecated 1.5
 * @deprecated No longer used.
 * @since BuddyPress (1.0)
 * @todo Remove in 1.4
 */
function bp_experiment_admin_memberlist( $admin_list = false, $experiment = false ) {
	global $experiments_template;

	_deprecated_function( __FUNCTION__, '1.5', 'No longer used. See /bp-default/experiments/single/admin.php' );

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;


	if ( $admins = experiments_get_experiment_admins( $experiment->id ) ) : ?>

		<ul id="admins-list" class="item-list<?php if ( !empty( $admin_list ) ) : ?> single-line<?php endif; ?>">

		<?php foreach ( (array) $admins as $admin ) { ?>

			<?php if ( !empty( $admin_list ) ) : ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $admin->user_id ) ) ) ) ?>

				<h5>

					<?php echo bp_core_get_userlink( $admin->user_id ); ?>

					<span class="small">
						<a class="button confirm admin-demote-to-member" href="<?php bp_experiment_member_demote_link($admin->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php else : ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $admin->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $admin->user_id ) ) ) ) ?>

				<h5><?php echo bp_core_get_userlink( $admin->user_id ) ?></h5>
				<span class="activity">
					<?php echo bp_core_get_last_activity( strtotime( $admin->date_modified ), __( 'joined %s', 'buddypress') ); ?>
				</span>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					<div class="action">

						<?php bp_add_friend_button( $admin->user_id ); ?>

					</div>

				<?php endif; ?>

			</li>

			<?php endif;
		} ?>

		</ul>

	<?php else : ?>

		<div id="message" class="info">
			<p><?php _e( 'This experiment has no administrators', 'buddypress' ); ?></p>
		</div>

	<?php endif;
}

function bp_experiment_mod_memberlist( $admin_list = false, $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( $experiment_mods = experiments_get_experiment_mods( $experiment->id ) ) { ?>

		<ul id="mods-list" class="item-list<?php if ( $admin_list ) { ?> single-line<?php } ?>">

		<?php foreach ( (array) $experiment_mods as $mod ) { ?>

			<?php if ( !empty( $admin_list ) ) { ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $mod->user_id ) ) ) ) ?>

				<h5>
					<?php echo bp_core_get_userlink( $mod->user_id ); ?>

					<span class="small">
						<a href="<?php bp_experiment_member_promote_admin_link( array( 'user_id' => $mod->user_id ) ) ?>" class="button confirm mod-promote-to-admin" title="<?php esc_attr_e( 'Promote to Admin', 'buddypress' ); ?>"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a>
						<a class="button confirm mod-demote-to-member" href="<?php bp_experiment_member_demote_link($mod->user_id) ?>"><?php _e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php } else { ?>

			<li>

				<?php echo bp_core_fetch_avatar( array( 'item_id' => $mod->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $mod->user_id ) ) ) ) ?>

				<h5><?php echo bp_core_get_userlink( $mod->user_id ) ?></h5>

				<span class="activity"><?php echo bp_core_get_last_activity( strtotime( $mod->date_modified ), __( 'joined %s', 'buddypress') ); ?></span>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					<div class="action">
						<?php bp_add_friend_button( $mod->user_id ) ?>
					</div>

				<?php endif; ?>

			</li>

			<?php } ?>
		<?php } ?>

		</ul>

	<?php } else { ?>

		<div id="message" class="info">
			<p><?php _e( 'This experiment has no moderators', 'buddypress' ); ?></p>
		</div>

	<?php }
}

function bp_experiment_has_moderators( $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	return apply_filters( 'bp_experiment_has_moderators', experiments_get_experiment_mods( $experiment->id ) );
}

function bp_experiment_member_promote_mod_link( $args = '' ) {
	echo bp_get_experiment_member_promote_mod_link( $args );
}
	function bp_get_experiment_member_promote_mod_link( $args = '' ) {
		global $members_template, $experiments_template;

		$defaults = array(
			'user_id' => $members_template->member->user_id,
			'experiment'   => &$experiments_template->experiment
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_experiment_member_promote_mod_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members/promote/mod/' . $user_id, 'experiments_promote_member' ) );
	}

function bp_experiment_member_promote_admin_link( $args = '' ) {
	echo bp_get_experiment_member_promote_admin_link( $args );
}
	function bp_get_experiment_member_promote_admin_link( $args = '' ) {
		global $members_template, $experiments_template;

		$defaults = array(
			'user_id' => !empty( $members_template->member->user_id ) ? $members_template->member->user_id : false,
			'experiment'   => &$experiments_template->experiment
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_experiment_member_promote_admin_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members/promote/admin/' . $user_id, 'experiments_promote_member' ) );
	}

function bp_experiment_member_demote_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_experiment_member_demote_link( $user_id );
}
	function bp_get_experiment_member_demote_link( $user_id = 0, $experiment = false ) {
		global $members_template, $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;

		return apply_filters( 'bp_get_experiment_member_demote_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members/demote/' . $user_id, 'experiments_demote_member' ) );
	}

function bp_experiment_member_ban_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_experiment_member_ban_link( $user_id );
}
	function bp_get_experiment_member_ban_link( $user_id = 0, $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_member_ban_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members/ban/' . $user_id, 'experiments_ban_member' ) );
	}

function bp_experiment_member_unban_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_experiment_member_unban_link( $user_id );
}
	function bp_get_experiment_member_unban_link( $user_id = 0, $experiment = false ) {
		global $members_template, $experiments_template;

		if ( !$user_id )
			$user_id = $members_template->member->user_id;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_member_unban_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members/unban/' . $user_id, 'experiments_unban_member' ) );
	}


function bp_experiment_member_remove_link( $user_id = 0 ) {
	global $members_template;

	if ( !$user_id )
		$user_id = $members_template->member->user_id;

	echo bp_get_experiment_member_remove_link( $user_id );
}
	function bp_get_experiment_member_remove_link( $user_id = 0, $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_member_remove_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members/remove/' . $user_id, 'experiments_remove_member' ) );
	}

function bp_experiment_admin_tabs( $experiment = false ) {
	global $bp, $experiments_template;

	if ( empty( $experiment ) )
		$experiment = ( $experiments_template->experiment ) ? $experiments_template->experiment : $bp->experiments->current_experiment;

	$current_tab = bp_get_experiment_current_admin_tab();

	if ( bp_is_item_admin() ) : ?>

		<li<?php if ( 'edit-details' == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin/edit-details' ) ?>"><?php _e( 'Details', 'buddypress' ); ?></a></li>

	<?php endif; ?>

	<?php if ( ! bp_is_item_admin() )
			return false; ?>

	<li<?php if ( 'experiment-settings' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin/experiment-settings' ) ?>"><?php _e( 'Settings', 'buddypress' ); ?></a></li>

	<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?>

		<li<?php if ( 'experiment-avatar'   == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin/experiment-avatar' ) ?>"><?php _e( 'Avatar', 'buddypress' ); ?></a></li>

	<?php endif; ?>

	<li<?php if ( 'manage-members' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin/manage-members' ) ?>"><?php _e( 'Members', 'buddypress' ); ?></a></li>

	<?php if ( $experiments_template->experiment->status == 'private' ) : ?>

		<li<?php if ( 'membership-requests' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin/membership-requests' ) ?>"><?php _e( 'Requests', 'buddypress' ); ?></a></li>

	<?php endif; ?>

	<?php do_action( 'experiments_admin_tabs', $current_tab, $experiment->slug ) ?>

	<li<?php if ( 'delete-experiment' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_get_experiment_permalink( $experiment ) . 'admin/delete-experiment' ) ?>"><?php _e( 'Delete', 'buddypress' ); ?></a></li>

<?php
}

function bp_experiment_total_for_member() {
	echo bp_get_experiment_total_for_member();
}
	function bp_get_experiment_total_for_member() {
		return apply_filters( 'bp_get_experiment_total_for_member', BP_Experiments_Member::total_experiment_count() );
	}

    
function bp_experiment_form_action( $page ) {
	echo bp_get_experiment_form_action( $page );
}
	function bp_get_experiment_form_action( $page, $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_experiment_form_action', bp_get_experiment_permalink( $experiment ) . $page );
	}

function bp_experiment_admin_form_action( $page = false ) {
	echo bp_get_experiment_admin_form_action( $page );
}
	function bp_get_experiment_admin_form_action( $page = false, $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		if ( empty( $page ) )
			$page = bp_action_variable( 0 );

		return apply_filters( 'bp_experiment_admin_form_action', bp_get_experiment_permalink( $experiment ) . 'admin/' . $page );
	}

function bp_experiment_has_requested_membership( $experiment = false ) {
	global $experiments_template;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( experiments_check_for_membership_request( bp_loggedin_user_id(), $experiment->id ) )
		return true;

	return false;
}

/**
 * bp_experiment_is_member()
 *
 * Checks if current user is member of a experiment.
 *
 * @uses bp_current_user_can() Check if current user is super admin
 * @uses apply_filters Creates bp_experiment_is_member filter and passes $is_member
 * @usedby experiments/activity.php, experiments/single/forum/edit.php, experiments/single/forum/topic.php to determine template part visibility
 * @global object $experiments_template Current Experiment (usually in template loop)
 * @param object $experiment Experiment to check is_member
 * @return bool If user is member of experiment or not
 */
function bp_experiment_is_member( $experiment = false ) {
	global $experiments_template;

	// Site admins always have access
	if ( bp_current_user_can( 'bp_moderate' ) )
		return true;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	return apply_filters( 'bp_experiment_is_member', !empty( $experiment->is_member ) );
}

/**
 * Checks if a user is banned from a experiment.
 *
 * If this function is invoked inside the experiments template loop (e.g. the experiment directory), then
 * check $experiments_template->experiment->is_banned instead of making another SQL query.
 * However, if used in a single experiment's pages, we must use experiments_is_user_banned().
 *
 * @global BP_Experiments_Template $experiments_template Experiment template loop object
 * @param object $experiment Experiment to check if user is banned from the experiment
 * @param int $user_id
 * @return bool If user is banned from the experiment or not
 * @since BuddyPress (1.5)
 */
function bp_experiment_is_user_banned( $experiment = false, $user_id = 0 ) {
	global $experiments_template;

	// Site admins always have access
	if ( bp_current_user_can( 'bp_moderate' ) )
		return false;

	if ( empty( $experiment ) ) {
		$experiment =& $experiments_template->experiment;

		if ( !$user_id && isset( $experiment->is_banned ) )
			return apply_filters( 'bp_experiment_is_user_banned', $experiment->is_banned );
	}

	if ( !$user_id )
		$user_id = bp_loggedin_user_id();

	return apply_filters( 'bp_experiment_is_user_banned', experiments_is_user_banned( $user_id, $experiment->id ) );
}

function bp_experiment_accept_invite_link() {
	echo bp_get_experiment_accept_invite_link();
}
	function bp_get_experiment_accept_invite_link( $experiment = false ) {
		global $experiments_template, $bp;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_accept_invite_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() . '/invites/accept/' . $experiment->id ), 'experiments_accept_invite' ) );
	}

function bp_experiment_reject_invite_link() {
	echo bp_get_experiment_reject_invite_link();
}
	function bp_get_experiment_reject_invite_link( $experiment = false ) {
		global $experiments_template, $bp;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_reject_invite_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() . '/invites/reject/' . $experiment->id ), 'experiments_reject_invite' ) );
	}

function bp_experiment_leave_confirm_link() {
	echo bp_get_experiment_leave_confirm_link();
}
	function bp_get_experiment_leave_confirm_link( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_experiment_leave_confirm_link', wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'leave-experiment/yes', 'experiments_leave_experiment' ) );
	}

function bp_experiment_leave_reject_link() {
	echo bp_get_experiment_leave_reject_link();
}
	function bp_get_experiment_leave_reject_link( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_get_experiment_leave_reject_link', bp_get_experiment_permalink( $experiment ) );
	}

function bp_experiment_send_invite_form_action() {
	echo bp_get_experiment_send_invite_form_action();
}
	function bp_get_experiment_send_invite_form_action( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		return apply_filters( 'bp_experiment_send_invite_form_action', bp_get_experiment_permalink( $experiment ) . 'send-invites/send' );
	}

function bp_has_friends_to_invite_experiment( $experiment = false ) {
	global $experiments_template;

	if ( !bp_is_active( 'friends' ) )
		return false;

	if ( empty( $experiment ) )
		$experiment =& $experiments_template->experiment;

	if ( !friends_check_user_has_friends( bp_loggedin_user_id() ) || !friends_count_invitable_friends( bp_loggedin_user_id(), $experiment->id ) )
		return false;

	return true;
}

/**
 * Outputs a 'New Topic' button for a experiment.
 *
 * @since BuddyPress (1.2.7)
 *
 * @param BP_Experiments_Experiment|bool $experiment The BP Experiments_Experiment object if passed, boolean false if not passed.
 * @uses bp_get_experiment_new_topic_button() Returns the 'New Topic' button
 */
function bp_experiment_new_topic_button( $experiment = false ) {
	echo bp_get_experiment_new_topic_button( $experiment );
}
	/**
	 * Returns a 'New Topic' button for a experiment.
	 *
	 * @since BuddyPress (1.2.7)
	 *
	 * @param BP_Experiments_Experiment|bool $experiment The BP Experiments_Experiment object if passed, boolean false if not passed.
	 * @uses is_user_logged_in() Is there a user logged in?
	 * @uses bp_experiment_is_user_banned() Is the current user banned from the current experiment?
	 * @uses bp_is_experiment_forum() Are we on a experiment forum page?
	 * @uses bp_is_experiment_forum_topic() Are we on a experiment topic page?
	 * @uses bp_get_button() Renders a button
	 * @return string HTML code for the button
	 */
	function bp_get_experiment_new_topic_button( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		if ( !is_user_logged_in() || bp_experiment_is_user_banned() || !bp_is_experiment_forum() || bp_is_experiment_forum_topic() )
			return false;

		$button = array(
			'id'                => 'new_topic',
			'component'         => 'experiments',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_class'     => 'experiment-button',
			'link_href'         => '#post-new',
			'link_class'        => 'experiment-button show-hide-new',
			'link_id'           => 'new-topic-button',
			'link_text'         => __( 'New Topic', 'buddypress' ),
			'link_title'        => __( 'New Topic', 'buddypress' ),
		);

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_experiment_new_topic_button', $button ) );
	}

function bp_experiment_join_button( $experiment = false ) {
	echo bp_get_experiment_join_button( $experiment );
}
	function bp_get_experiment_join_button( $experiment = false ) {
		global $experiments_template;

		if ( empty( $experiment ) )
			$experiment =& $experiments_template->experiment;

		if ( !is_user_logged_in() || bp_experiment_is_user_banned( $experiment ) )
			return false;

		// Experiment creation was not completed or status is unknown
		if ( !$experiment->status )
			return false;

		// Already a member
		if ( isset( $experiment->is_member ) && $experiment->is_member ) {

			// Stop sole admins from abandoning their experiment
	 		$experiment_admins = experiments_get_experiment_admins( $experiment->id );
		 	if ( 1 == count( $experiment_admins ) && $experiment_admins[0]->user_id == bp_loggedin_user_id() )
				return false;

			$button = array(
				'id'                => 'leave_experiment',
				'component'         => 'experiments',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'experiment-button ' . $experiment->status,
				'wrapper_id'        => 'experimentbutton-' . $experiment->id,
				'link_href'         => wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'leave-experiment', 'experiments_leave_experiment' ),
				'link_text'         => __( 'Leave Experiment', 'buddypress' ),
				'link_title'        => __( 'Leave Experiment', 'buddypress' ),
				'link_class'        => 'experiment-button leave-experiment',
			);

		// Not a member
		} else {

			// Show different buttons based on experiment status
			switch ( $experiment->status ) {
				case 'hidden' :
					return false;
					break;

				case 'public':
					$button = array(
						'id'                => 'join_experiment',
						'component'         => 'experiments',
						'must_be_logged_in' => true,
						'block_self'        => false,
						'wrapper_class'     => 'experiment-button ' . $experiment->status,
						'wrapper_id'        => 'experimentbutton-' . $experiment->id,
						'link_href'         => wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'join', 'experiments_join_experiment' ),
						'link_text'         => __( 'Join Experiment', 'buddypress' ),
						'link_title'        => __( 'Join Experiment', 'buddypress' ),
						'link_class'        => 'experiment-button join-experiment',
					);
					break;

				case 'private' :

					// Member has outstanding invitation -
					// show an "Accept Invitation" button
					if ( $experiment->is_invited ) {
						$button = array(
							'id'                => 'accept_invite',
							'component'         => 'experiments',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'experiment-button ' . $experiment->status,
							'wrapper_id'        => 'experimentbutton-' . $experiment->id,
							'link_href'         => add_query_arg( 'redirect_to', bp_get_experiment_permalink( $experiment ), bp_get_experiment_accept_invite_link( $experiment ) ),
							'link_text'         => __( 'Accept Invitation', 'buddypress' ),
							'link_title'        => __( 'Accept Invitation', 'buddypress' ),
							'link_class'        => 'experiment-button accept-invite',
						);

					// Member has requested membership but request is pending -
					// show a "Request Sent" button
					} elseif ( $experiment->is_pending ) {
						$button = array(
							'id'                => 'membership_requested',
							'component'         => 'experiments',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'experiment-button pending ' . $experiment->status,
							'wrapper_id'        => 'experimentbutton-' . $experiment->id,
							'link_href'         => bp_get_experiment_permalink( $experiment ),
							'link_text'         => __( 'Request Sent', 'buddypress' ),
							'link_title'        => __( 'Request Sent', 'buddypress' ),
							'link_class'        => 'experiment-button pending membership-requested',
						);

					// Member has not requested membership yet -
					// show a "Request Membership" button
					} else {
						$button = array(
							'id'                => 'request_membership',
							'component'         => 'experiments',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'experiment-button ' . $experiment->status,
							'wrapper_id'        => 'experimentbutton-' . $experiment->id,
							'link_href'         => wp_nonce_url( bp_get_experiment_permalink( $experiment ) . 'request-membership', 'experiments_request_membership' ),
							'link_text'         => __( 'Request Membership', 'buddypress' ),
							'link_title'        => __( 'Request Membership', 'buddypress' ),
							'link_class'        => 'experiment-button request-membership',
						);
					}

					break;
			}
		}

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_experiment_join_button', $button ) );
	}

/**
 * Output the Create a Experiment button.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_experiment_create_button() {
	echo bp_get_experiment_create_button();
}
	/**
	 * Get the Create a Experiment button.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
function bp_get_experiment_create_button() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! bp_user_can_create_experiments() ) {
			return false;
		}

		$button_args = array(
			'id'         => 'create_experiment',
			'component'  => 'experiments',
            'link_text'  => __( 'Create an Experiment', 'buddypress' ),
			'link_title' => __( 'Create an Experiment', 'buddypress' ),
			'link_class' => 'button experiment-create bp-title-button',
			'link_href'  => trailingslashit( bp_get_root_domain() ) . trailingslashit( bp_get_experiments_root_slug() ) . trailingslashit( 'create' ),
			'wrapper'    => false,
		);

		return bp_get_button( apply_filters( 'bp_get_experiment_create_button', $button_args ) );
	}

/**
 * Prints a message if the experiment is not visible to the current user (it is a
 * hidden or private experiment, and the user does not have access).
 *
 * @global BP_Experiments_Template $experiments_template Experiments template object
 * @param object $experiment Experiment to get status message for. Optional; defaults to current experiment.
 * @since BuddyPress (1.0)
 */
function bp_experiment_status_message( $experiment = null ) {
	global $experiments_template;

	if ( ! $experiment )
		$experiment =& $experiments_template->experiment;

	if ( 'private' == $experiment->status ) {
 		if ( ! bp_experiment_has_requested_membership() ) {
			if ( is_user_logged_in() )
				$message = __( 'This is a private experiment and you must request experiment membership in order to join.', 'buddypress' );
			else
				$message = __( 'This is a private experiment. To join you must be a registered site member and request experiment membership.', 'buddypress' );

		} else {
			$message = __( 'This is a private experiment. Your membership request is awaiting approval from the experiment administrator.', 'buddypress' );
		}

	} else {
		$message = __( 'This is a hidden experiment and only invited members can join.', 'buddypress' );
	}

	echo apply_filters( 'bp_experiment_status_message', $message, $experiment );
}

function bp_experiment_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ) . '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['experiments_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['experiments_search'] ) . '" name="search_terms" />';
	}
}

function bp_total_experiment_count() {
	echo bp_get_total_experiment_count();
}
	function bp_get_total_experiment_count() {
		return apply_filters( 'bp_get_total_experiment_count', experiments_get_total_experiment_count() );
	}

function bp_total_experiment_count_for_user( $user_id = 0 ) {
	echo bp_get_total_experiment_count_for_user( $user_id );
}
	function bp_get_total_experiment_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bp_get_total_experiment_count_for_user', experiments_total_experiments_for_user( $user_id ), $user_id );
	}
	add_filter( 'bp_get_total_experiment_count_for_user', 'bp_core_number_format' );


/***************************************************************************
 * Experiment Members Template Tags
 **/

class BP_Experiments_Experiment_Members_Template {
	var $current_member = -1;
	var $member_count;
	var $members;
	var $member;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_experiment_count;

	/**
	 * Constructor.
	 *
	 * @param array $args {
	 *     An array of optional arguments.
	 *     @type int $experiment_id ID of the experiment whose members are being
	 *	     queried. Default: current experiment ID.
	 *     @type int $page Page of results to be queried. Default: 1.
	 *     @type int $per_page Number of items to return per page of
	 *           results. Default: 20.
	 *     @type int $max Optional. Max number of items to return.
	 *     @type array $exclude Optional. Array of user IDs to exclude.
	 *     @type bool|int True (or 1) to exclude admins and mods from
	 *           results. Default: 1.
	 *     @type bool|int True (or 1) to exclude banned users from results.
	 *           Default: 1.
	 *     @type array $experiment_role Optional. Array of experiment roles to include.
	 *     @type string $search_terms Optional. Search terms to match.
	 * }
	 */
	function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'experiment_id',
				1 => 'per_page',
				2 => 'max',
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
			'page'                => 1,
			'per_page'            => 20,
			'max'                 => false,
			'exclude'             => false,
			'exclude_admins_mods' => 1,
			'exclude_banned'      => 1,
			'experiment_role'          => false,
			'search_terms'        => false,
			'type'                => 'last_joined',
		) );

		// @todo No
		extract( $r );

		$this->pag_page = isset( $_REQUEST['mlpage'] ) ? intval( $_REQUEST['mlpage'] ) : $r['page'];
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		/**
		 * Check the current experiment is the same as the supplied experiment ID.
		 * It can differ when using {@link bp_experiment_has_members()} outside the Experiments screens.
		 */
		$current_experiment = experiments_get_current_experiment();
		if ( ! $current_experiment || $current_experiment && $current_experiment->id !== bp_get_current_experiment_id() ) {
			$current_experiment = experiments_get_experiment( array( 'experiment_id' => $r['experiment_id'] ) );
		}

		// Assemble the base URL for pagination
		$base_url = trailingslashit( bp_get_experiment_permalink( $current_experiment ) . bp_current_action() );
		if ( bp_action_variable() ) {
			$base_url = trailingslashit( $base_url . bp_action_variable() );
		}

		$members_args = $r;

		$members_args['page']     = $this->pag_page;
		$members_args['per_page'] = $this->pag_num;

		$this->members = experiments_get_experiment_members( $members_args );

		if ( !$max || $max >= (int) $this->members['count'] )
			$this->total_member_count = (int) $this->members['count'];
		else
			$this->total_member_count = (int) $max;

		$this->members = $this->members['members'];

		if ( $max ) {
			if ( $max >= count($this->members) )
				$this->member_count = count($this->members);
			else
				$this->member_count = (int) $max;
		} else {
			$this->member_count = count($this->members);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( array( 'mlpage' => '%#%' ), $base_url ),
			'format' => '',
			'total' => !empty( $this->pag_num ) ? ceil( $this->total_member_count / $this->pag_num ) : $this->total_member_count,
			'current' => $this->pag_page,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size' => 1
		));
	}

	function has_members() {
		if ( $this->member_count )
			return true;

		return false;
	}

	function next_member() {
		$this->current_member++;
		$this->member = $this->members[$this->current_member];

		return $this->member;
	}

	function rewind_members() {
		$this->current_member = -1;
		if ( $this->member_count > 0 ) {
			$this->member = $this->members[0];
		}
	}

	function members() {
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 == $this->member_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_member() {

		$this->in_the_loop = true;
		$this->member      = $this->next_member();

		if ( 0 == $this->current_member ) // loop has just started
			do_action('loop_start');
	}
}

/**
 * Initialize a experiment member query loop.
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
 *     @type string $type Optional. Sort order of results. 'last_joined',
 *           'first_joined', or any of the $type params available in
 *           {@link BP_User_Query}. Default: 'last_joined'.
 *     @type string $search_terms Optional. Search terms to match.
 * }
 */
function bp_experiment_has_members( $args = '' ) {
	global $members_template;

	$exclude_admins_mods = 1;

	if ( bp_is_experiment_members() ) {
		$exclude_admins_mods = 0;
	}

	$r = wp_parse_args( $args, array(
		'experiment_id'            => bp_get_current_experiment_id(),
		'page'                => 1,
		'per_page'            => 20,
		'max'                 => false,
		'exclude'             => false,
		'exclude_admins_mods' => $exclude_admins_mods,
		'exclude_banned'      => 1,
		'experiment_role'          => false,
		'search_terms'        => false,
		'type'                => 'last_joined',
	) );

	if ( empty( $r['search_terms'] ) && ! empty( $_REQUEST['s'] ) )
		$r['search_terms'] = $_REQUEST['s'];

	$members_template = new BP_Experiments_Experiment_Members_Template( $r );
	return apply_filters( 'bp_experiment_has_members', $members_template->has_members(), $members_template );
}

function bp_experiment_members() {
	global $members_template;

	return $members_template->members();
}

function bp_experiment_the_member() {
	global $members_template;

	return $members_template->the_member();
}

function bp_experiment_member_avatar() {
	echo bp_get_experiment_member_avatar();
}
	function bp_get_experiment_member_avatar() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_avatar', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'full', 'email' => $members_template->member->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name ) ) ) );
	}

function bp_experiment_member_avatar_thumb() {
	echo bp_get_experiment_member_avatar_thumb();
}
	function bp_get_experiment_member_avatar_thumb() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'thumb', 'email' => $members_template->member->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name ) ) ) );
	}

function bp_experiment_member_avatar_mini( $width = 30, $height = 30 ) {
	echo bp_get_experiment_member_avatar_mini( $width, $height );
}
	function bp_get_experiment_member_avatar_mini( $width = 30, $height = 30 ) {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_avatar_mini', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->user_id, 'type' => 'thumb', 'width' => $width, 'height' => $height, 'email' => $members_template->member->user_email, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name ) ) ) );
	}

function bp_experiment_member_name() {
	echo bp_get_experiment_member_name();
}
	function bp_get_experiment_member_name() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_name', $members_template->member->display_name );
	}

function bp_experiment_member_url() {
	echo bp_get_experiment_member_url();
}
	function bp_get_experiment_member_url() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_url', bp_core_get_user_domain( $members_template->member->user_id, $members_template->member->user_nicename, $members_template->member->user_login ) );
	}

function bp_experiment_member_link() {
	echo bp_get_experiment_member_link();
}
	function bp_get_experiment_member_link() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_link', '<a href="' . bp_core_get_user_domain( $members_template->member->user_id, $members_template->member->user_nicename, $members_template->member->user_login ) . '">' . $members_template->member->display_name . '</a>' );
	}

function bp_experiment_member_domain() {
	echo bp_get_experiment_member_domain();
}
	function bp_get_experiment_member_domain() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_domain', bp_core_get_user_domain( $members_template->member->user_id, $members_template->member->user_nicename, $members_template->member->user_login ) );
	}

function bp_experiment_member_is_friend() {
	echo bp_get_experiment_member_is_friend();
}
	function bp_get_experiment_member_is_friend() {
		global $members_template;

		if ( !isset( $members_template->member->is_friend ) )
			$friend_status = 'not_friends';
		else
			$friend_status = ( 0 == $members_template->member->is_friend ) ? 'pending' : 'is_friend';

		return apply_filters( 'bp_get_experiment_member_is_friend', $friend_status );
	}

function bp_experiment_member_is_banned() {
	echo bp_get_experiment_member_is_banned();
}
	function bp_get_experiment_member_is_banned() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_is_banned', $members_template->member->is_banned );
	}

function bp_experiment_member_css_class() {
	global $members_template;

	if ( $members_template->member->is_banned )
		echo apply_filters( 'bp_experiment_member_css_class', 'banned-user' );
}

function bp_experiment_member_joined_since() {
	echo bp_get_experiment_member_joined_since();
}
	function bp_get_experiment_member_joined_since() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_joined_since', bp_core_get_last_activity( $members_template->member->date_modified, __( 'joined %s', 'buddypress') ) );
	}

function bp_experiment_member_id() {
	echo bp_get_experiment_member_id();
}
	function bp_get_experiment_member_id() {
		global $members_template;

		return apply_filters( 'bp_get_experiment_member_id', $members_template->member->user_id );
	}

function bp_experiment_member_needs_pagination() {
	global $members_template;

	if ( $members_template->total_member_count > $members_template->pag_num )
		return true;

	return false;
}

function bp_experiment_pag_id() {
	echo bp_get_experiment_pag_id();
}
	function bp_get_experiment_pag_id() {
		return apply_filters( 'bp_get_experiment_pag_id', 'pag' );
	}

function bp_experiment_member_pagination() {
	echo bp_get_experiment_member_pagination();
	wp_nonce_field( 'bp_experiments_member_list', '_member_pag_nonce' );
}
	function bp_get_experiment_member_pagination() {
		global $members_template;
		return apply_filters( 'bp_get_experiment_member_pagination', $members_template->pag_links );
	}

function bp_experiment_member_pagination_count() {
	echo bp_get_experiment_member_pagination_count();
}
	function bp_get_experiment_member_pagination_count() {
		global $members_template;

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num = bp_core_number_format( $start_num );
		$to_num = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total = bp_core_number_format( $members_template->total_member_count );

		return apply_filters( 'bp_get_experiment_member_pagination_count', sprintf( _n( 'Viewing member %1$s to %2$s (of %3$s member)', 'Viewing members %1$s to %2$s (of %3$s members)', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
	}

function bp_experiment_member_admin_pagination() {
	echo bp_get_experiment_member_admin_pagination();
	wp_nonce_field( 'bp_experiments_member_admin_list', '_member_admin_pag_nonce' );
}
	function bp_get_experiment_member_admin_pagination() {
		global $members_template;

		return $members_template->pag_links;
	}

/**
 * Output the Experiment members template
 *
 * @since BuddyPress (?)
 *
 * @return string html output
 */
function bp_experiments_members_template_part() {
	?>
	<div class="item-list-tabs" id="subnav" role="navigation">
		<ul>
			<li class="experiments-members-search" role="search">
				<?php bp_directory_members_search_form(); ?>
			</li>

			<?php bp_experiments_members_filter(); ?>
			<?php do_action( 'bp_members_directory_member_sub_types' ); ?>

		</ul>
	</div>

	<div id="members-experiment-list" class="experiment_members dir-list">

		<?php bp_get_template_part( 'experiments/single/members' ); ?>

	</div>
	<?php
}

/**
 * Output the Experiment members filters
 *
 * @since BuddyPress (?)
 *
 * @return string html output
 */
function bp_experiments_members_filter() {
	?>
	<li id="experiment_members-order-select" class="last filter">
		<label for="experiment_members-order-by"><?php _e( 'Order By:', 'buddypress' ); ?></label>
		<select id="experiment_members-order-by">
			<option value="last_joined"><?php _e( 'Newest', 'buddypress' ); ?></option>
			<option value="first_joined"><?php _e( 'Oldest', 'buddypress' ); ?></option>
			<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ); ?></option>

			<?php do_action( 'bp_experiments_members_order_options' ); ?>

		</select>
	</li>
	<?php
}

/***************************************************************************
 * Experiment Creation Process Template Tags
 **/

/**
 * Determine if the current logged in user can create experiments.
 *
 * @package BuddyPress Experiments
 * @since BuddyPress (1.5)
 *
 * @uses apply_filters() To call 'bp_user_can_create_experiments'.
 * @uses bp_get_option() To retrieve value of 'bp_restrict_experiment_creation'. Defaults to 0.
 * @uses bp_current_user_can() To determine if current user if super admin.
 *
 * @return bool True if user can create experiments. False otherwise.
 */
function bp_user_can_create_experiments() {
	// Super admin can always create experiments
	if ( bp_current_user_can( 'bp_moderate' ) )
		return true;

	// Get experiment creation option, default to 0 (allowed)
	$restricted = (int) bp_get_option( 'bp_restrict_experiment_creation', 0 );

	// Allow by default
	$can_create = true;

	// Are regular users restricted?
	if ( $restricted )
		$can_create = false;

	return apply_filters( 'bp_user_can_create_experiments', $can_create, $restricted );
}

function bp_experiment_creation_tabs() {
	global $bp;

	if ( !is_array( $bp->experiments->experiment_creation_steps ) )
		return false;

	if ( !bp_get_experiments_current_create_step() ) {
		$keys = array_keys( $bp->experiments->experiment_creation_steps );
		$bp->experiments->current_create_step = array_shift( $keys );
	}

	$counter = 1;

	foreach ( (array) $bp->experiments->experiment_creation_steps as $slug => $step ) {
		$is_enabled = bp_are_previous_experiment_creation_steps_complete( $slug ); ?>

		<li<?php if ( bp_get_experiments_current_create_step() == $slug ) : ?> class="current"<?php endif; ?>><?php if ( $is_enabled ) : ?><a href="<?php echo bp_get_root_domain() . '/' . bp_get_experiments_root_slug() ?>/create/step/<?php echo $slug ?>/"><?php else: ?><span><?php endif; ?><?php echo $counter ?>. <?php echo $step['name'] ?><?php if ( $is_enabled ) : ?></a><?php else: ?></span><?php endif ?></li><?php
		$counter++;
	}

	unset( $is_enabled );

	do_action( 'experiments_creation_tabs' );
}

function bp_experiment_creation_stage_title() {
	global $bp;

	echo apply_filters( 'bp_experiment_creation_stage_title', '<span>&mdash; ' . $bp->experiments->experiment_creation_steps[bp_get_experiments_current_create_step()]['name'] . '</span>' );
}
   
    function bp_experiment_creation_single_form_action() {
        echo bp_get_experiment_creation_single_form_action();
    }
            
    function bp_get_experiment_creation_single_form_action() {
        global $bp;
                
        if ( !bp_action_variable( 1 ) ) {
            $keys = array_keys( $bp->experiments->experiment_creation_steps );
            $bp->action_variables[1] = array_shift( $keys );
        }
                
        return apply_filters( 'bp_get_experiment_creation_single_form_action', trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_action_variable( 1 ) ) );
    }

            
function bp_experiment_creation_form_action() {
	echo bp_get_experiment_creation_form_action();
}
	function bp_get_experiment_creation_form_action() {
		global $bp;

		if ( !bp_action_variable( 1 ) ) {
			$keys = array_keys( $bp->experiments->experiment_creation_steps );
			$bp->action_variables[1] = array_shift( $keys );
		}

		return apply_filters( 'bp_get_experiment_creation_form_action', trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_action_variable( 1 ) ) );
	}


            
            
function bp_is_experiment_creation_step( $step_slug ) {
	global $bp;

	/* Make sure we are in the experiments component */
	if ( !bp_is_experiments_component() || !bp_is_current_action( 'create' ) )
		return false;

	/* If this the first step, we can just accept and return true */
	$keys = array_keys( $bp->experiments->experiment_creation_steps );
	if ( !bp_action_variable( 1 ) && array_shift( $keys ) == $step_slug )
		return true;

	/* Before allowing a user to see a experiment creation step we must make sure previous steps are completed */
	if ( !bp_is_first_experiment_creation_step() ) {
		if ( !bp_are_previous_experiment_creation_steps_complete( $step_slug ) )
			return false;
	}

	/* Check the current step against the step parameter */
	if ( bp_is_action_variable( $step_slug ) )
		return true;

	return false;
}

function bp_is_experiment_creation_step_complete( $step_slugs ) {
	global $bp;

	if ( !isset( $bp->experiments->completed_create_steps ) )
		return false;

	if ( is_array( $step_slugs ) ) {
		$found = true;

		foreach ( (array) $step_slugs as $step_slug ) {
			if ( !in_array( $step_slug, $bp->experiments->completed_create_steps ) )
				$found = false;
		}

		return $found;
	} else {
		return in_array( $step_slugs, $bp->experiments->completed_create_steps );
	}

	return true;
}

function bp_are_previous_experiment_creation_steps_complete( $step_slug ) {
	global $bp;

	/* If this is the first experiment creation step, return true */
	$keys = array_keys( $bp->experiments->experiment_creation_steps );
	if ( array_shift( $keys ) == $step_slug )
		return true;

	reset( $bp->experiments->experiment_creation_steps );
	unset( $previous_steps );

	/* Get previous steps */
	foreach ( (array) $bp->experiments->experiment_creation_steps as $slug => $name ) {
		if ( $slug == $step_slug )
			break;

		$previous_steps[] = $slug;
	}

	return bp_is_experiment_creation_step_complete( $previous_steps );
}

function bp_new_experiment_id() {
	echo bp_get_new_experiment_id();
}
	function bp_get_new_experiment_id() {
		global $bp;

		if ( isset( $bp->experiments->new_experiment_id ) )
			$new_experiment_id = $bp->experiments->new_experiment_id;
		else
			$new_experiment_id = 0;

		return apply_filters( 'bp_get_new_experiment_id', $new_experiment_id );
	}

function bp_new_experiment_name() {
	echo bp_get_new_experiment_name();
}
	function bp_get_new_experiment_name() {
		global $bp;

		if ( isset( $bp->experiments->current_experiment->name ) )
			$name = $bp->experiments->current_experiment->name;
		else
			$name = '';

		return apply_filters( 'bp_get_new_experiment_name', $name );
	}

function bp_new_experiment_description() {
	echo bp_get_new_experiment_description();
}
	function bp_get_new_experiment_description() {
		global $bp;

		if ( isset( $bp->experiments->current_experiment->description ) )
			$description = $bp->experiments->current_experiment->description;
		else
			$description = '';

		return apply_filters( 'bp_get_new_experiment_description', $description );
	}
            
            
            
            function bp_new_experiment_variable() {
                echo bp_get_new_experiment_variable();
            }
            function bp_get_new_experiment_variable() {
                global $bp;
                
                if ( isset( $bp->experiments->current_experiment->variable ) )
                    $variable = $bp->experiments->current_experiment->variable;
                else
                    $variable = '';
                
                return apply_filters( 'bp_get_new_experiment_variable', $variable );
            }
            

function bp_new_experiment_enable_forum() {
	echo bp_get_new_experiment_enable_forum();
}
	function bp_get_new_experiment_enable_forum() {
		global $bp;
		return (int) apply_filters( 'bp_get_new_experiment_enable_forum', $bp->experiments->current_experiment->enable_forum );
	}

function bp_new_experiment_status() {
	echo bp_get_new_experiment_status();
}
	function bp_get_new_experiment_status() {
		global $bp;
		return apply_filters( 'bp_get_new_experiment_status', $bp->experiments->current_experiment->status );
	}

function bp_new_experiment_avatar( $args = '' ) {
	echo bp_get_new_experiment_avatar( $args );
}
	function bp_get_new_experiment_avatar( $args = '' ) {
		global $bp;

		$defaults = array(
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => 'avatar-crop-preview',
			'alt' => __( 'Experiment avatar', 'buddypress' ),
			'no_grav' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_new_experiment_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->experiments->current_experiment->id, 'object' => 'experiment', 'type' => $type, 'avatar_dir' => 'experiment-avatars', 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class, 'no_grav' => $no_grav ) ) );
	}

function bp_experiment_creation_previous_link() {
	echo bp_get_experiment_creation_previous_link();
}
	function bp_get_experiment_creation_previous_link() {
		global $bp;

		foreach ( (array) $bp->experiments->experiment_creation_steps as $slug => $name ) {
			if ( bp_is_action_variable( $slug ) )
				break;

			$previous_steps[] = $slug;
		}

		return apply_filters( 'bp_get_experiment_creation_previous_link', trailingslashit( bp_get_root_domain() ) . bp_get_experiments_root_slug() . '/create/step/' . array_pop( $previous_steps ) );
	}

/**
 * Echoes the current experiment creation step
 *
 * @since BuddyPress (1.6)
 */
function bp_experiments_current_create_step() {
	echo bp_get_experiments_current_create_step();
}
	/**
	 * Returns the current experiment creation step. If none is found, returns an empty string
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses apply_filters() Filter bp_get_experiments_current_create_step to modify
	 * @return string $current_create_step
	 */
	function bp_get_experiments_current_create_step() {
		global $bp;

		if ( !empty( $bp->experiments->current_create_step ) ) {
			$current_create_step = $bp->experiments->current_create_step;
		} else {
			$current_create_step = '';
		}

		return apply_filters( 'bp_get_experiments_current_create_step', $current_create_step );
	}

function bp_is_last_experiment_creation_step() {
	global $bp;

	$keys      = array_keys( $bp->experiments->experiment_creation_steps );
	$last_step = array_pop( $keys );

	if ( $last_step == bp_get_experiments_current_create_step() )
		return true;

	return false;
}

function bp_is_first_experiment_creation_step() {
	global $bp;

	$keys       = array_keys( $bp->experiments->experiment_creation_steps );
	$first_step = array_shift( $keys );

	if ( $first_step == bp_get_experiments_current_create_step() )
		return true;

	return false;
}

function bp_new_experiment_invite_friend_list() {
	echo bp_get_new_experiment_invite_friend_list();
}
	function bp_get_new_experiment_invite_friend_list( $args = '' ) {
		global $bp;

		if ( !bp_is_active( 'friends' ) )
			return false;

		$defaults = array(
			'experiment_id'  => false,
			'separator' => 'li'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( empty( $experiment_id ) )
			$experiment_id = !empty( $bp->experiments->new_experiment_id ) ? $bp->experiments->new_experiment_id : $bp->experiments->current_experiment->id;

		if ( $friends = friends_get_friends_invite_list( bp_loggedin_user_id(), $experiment_id ) ) {
			$invites = experiments_get_invites_for_experiment( bp_loggedin_user_id(), $experiment_id );

			for ( $i = 0, $count = count( $friends ); $i < $count; ++$i ) {
				$checked = '';

				if ( !empty( $invites ) ) {
					if ( in_array( $friends[$i]['id'], $invites ) )
						$checked = ' checked="checked"';
				}

				$items[] = '<' . $separator . '><input' . $checked . ' type="checkbox" tabindex="-1" name="friends[]" id="f-' . $friends[$i]['id'] . '" value="' . esc_attr( $friends[$i]['id'] ) . '" /> ' . $friends[$i]['full_name'] . '</' . $separator . '>';
			}
		}

		if ( !empty( $items ) )
			return implode( "\n", (array) $items );

		return false;
	}

function bp_directory_experiments_search_form() {

	$default_search_value = bp_get_search_default_text( 'experiments' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value;

	$search_form_html = '<form action="" method="get" id="search-experiments-form">
		<label><input type="text" name="s" id="experiments_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="experiments_search_submit" name="experiments_search_submit" value="'. __( 'Search', 'buddypress' ) .'" />
	</form>';

	echo apply_filters( 'bp_directory_experiments_search_form', $search_form_html );

}

/**
 * Displays experiment header tabs
 *
 * @package BuddyPress
 * @todo Deprecate?
 */
function bp_experiments_header_tabs() {
	global $bp;?>

	<li<?php if ( !bp_action_variable( 0 ) || bp_is_action_variable( 'recently-active', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/recently-active' ) ?>"><?php _e( 'Recently Active', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'recently-joined', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/recently-joined' ) ?>"><?php _e( 'Recently Joined', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'most-popular', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/most-popular' ) ?>"><?php _e( 'Most Popular', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'admin-of', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/admin-of' ) ?>"><?php _e( 'Administrator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'mod-of', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/mod-of' ) ?>"><?php _e( 'Moderator Of', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_action_variable( 'alphabetically' ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_experiments_slug() . '/my-experiments/alphabetically' ) ?>"><?php _e( 'Alphabetically', 'buddypress' ) ?></a></li>

<?php
	do_action( 'experiments_header_tabs' );
}

/**
 * Displays experiment filter titles
 *
 * @package BuddyPress
 * @todo Deprecate?
 */
function bp_experiments_filter_title() {
	$current_filter = bp_action_variable( 0 );

	switch ( $current_filter ) {
		case 'recently-active': default:
			_e( 'Recently Active', 'buddypress' );
			break;
		case 'recently-joined':
			_e( 'Recently Joined', 'buddypress' );
			break;
		case 'most-popular':
			_e( 'Most Popular', 'buddypress' );
			break;
		case 'admin-of':
			_e( 'Administrator Of', 'buddypress' );
			break;
		case 'mod-of':
			_e( 'Moderator Of', 'buddypress' );
			break;
		case 'alphabetically':
			_e( 'Alphabetically', 'buddypress' );
		break;
	}
	do_action( 'bp_experiments_filter_title' );
}

function bp_is_experiment_admin_screen( $slug ) {
	if ( !bp_is_experiments_component() || !bp_is_current_action( 'admin' ) )
		return false;

	if ( bp_is_action_variable( $slug ) )
		return true;

	return false;
}

/**
 * Echoes the current experiment admin tab slug
 *
 * @since BuddyPress (1.6)
 */
function bp_experiment_current_admin_tab() {
	echo bp_get_experiment_current_admin_tab();
}
	/**
	 * Returns the current experiment admin tab slug
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses apply_filters() Filter bp_get_current_experiment_admin_tab to modify return value
	 * @return string $tab The current tab's slug
	 */
	function bp_get_experiment_current_admin_tab() {
		if ( bp_is_experiments_component() && bp_is_current_action( 'admin' ) ) {
			$tab = bp_action_variable( 0 );
		} else {
			$tab = '';
		}

		return apply_filters( 'bp_get_current_experiment_admin_tab', $tab );
	}

/************************************************************************************
 * Experiment Avatar Template Tags
 **/

/**
 * Outputs the current experiment avatar
 *
 * @since BuddyPress (1.0)
 * @param string $type thumb or full ?
 * @uses bp_get_experiment_current_avatar() to get the avatar of the current experiment
 */
function bp_experiment_current_avatar( $type = 'thumb' ) {
	echo bp_get_experiment_current_avatar( $type );
}
	/**
	 * Returns the current experiment avatar
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param string $type thumb or full ?
	 * @return string $tab The current tab's slug.
	 */
	function bp_get_experiment_current_avatar( $type = 'thumb' ) {

		$experiment_avatar = bp_core_fetch_avatar( array(
			'item_id'    => bp_get_current_experiment_id(),
			'object'     => 'experiment',
			'type'       => $type,
			'avatar_dir' => 'experiment-avatars',
			'alt'        => __( 'Experiment avatar', 'buddypress' ),
			'class'      => 'avatar'
		) );

		return apply_filters( 'bp_get_experiment_current_avatar', $experiment_avatar );
	}

function bp_get_experiment_has_avatar( $experiment_id = false ) {
	global $bp;

	if ( false === $experiment_id ) {
		$experiment_id = bp_get_current_experiment_id();
	}

	// Todo - this looks like an overgeneral check
	if ( ! empty( $_FILES ) ) {
		return false;
	}

	$experiment_avatar = bp_core_fetch_avatar( array(
		'item_id' => $experiment_id,
		'object' => 'experiment',
		'no_grav' => true,
		'html' => false,
	) );

	if ( bp_core_avatar_default( 'local' ) === $experiment_avatar ) {
		return false;
	}

	return true;
}

function bp_experiment_avatar_delete_link() {
	echo bp_get_experiment_avatar_delete_link();
}
	function bp_get_experiment_avatar_delete_link() {
		global $bp;

		return apply_filters( 'bp_get_experiment_avatar_delete_link', wp_nonce_url( bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'admin/experiment-avatar/delete', 'bp_experiment_avatar_delete' ) );
	}

function bp_experiment_avatar_edit_form() {
	experiments_avatar_upload();
}

function bp_custom_experiment_boxes() {
	do_action( 'experiments_custom_experiment_boxes' );
}

function bp_custom_experiment_admin_tabs() {
	do_action( 'experiments_custom_experiment_admin_tabs' );
}

function bp_custom_experiment_fields_editable() {
	do_action( 'experiments_custom_experiment_fields_editable' );
}

function bp_custom_experiment_fields() {
	do_action( 'experiments_custom_experiment_fields' );
}


/************************************************************************************
 * Membership Requests Template Tags
 **/

class BP_Experiments_Membership_Requests_Template {
	var $current_request = -1;
	var $request_count;
	var $requests;
	var $request;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_request_count;

	/**
	 * Constructor method.
	 *
	 * @param array $args {
	 *     @type int $experiment_id ID of the experiment whose membership requests
	 *           are being queried. Default: current experiment id.
	 *     @type int $per_page Number of records to return per page of
	 *           results. Default: 10.
	 *     @type int $page Page of results to show. Default: 1.
	 *     @type int $max Max items to return. Default: false (show all)
	 * }
	 */
	function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'experiment_id',
				1 => 'per_page',
				2 => 'max',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$r = wp_parse_args( $args, array(
			'experiment_id' => bp_get_current_experiment_id(),
			'per_page' => 10,
			'page'     => 1,
			'max'      => false,
			'type'     => 'first_joined',
		) );

		$this->pag_page = isset( $_REQUEST['mrpage'] ) ? intval( $_REQUEST['mrpage'] ) : $r['page'];
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $r['per_page'];

		$mquery = new BP_Experiment_Member_Query( array(
			'experiment_id' => $r['experiment_id'],
			'type'     => $r['type'],
			'per_page' => $this->pag_num,
			'page'     => $this->pag_page,

			// These filters ensure we only get pending requests
			'is_confirmed' => false,
			'inviter_id'   => 0,
		) );

		$this->requests      = array_values( $mquery->results );
		$this->request_count = count( $this->requests );

		// Compatibility with legacy format of request data objects
		foreach ( $this->requests as $rk => $rv ) {
			// For legacy reasons, the 'id' property of each
			// request must match the membership id, not the ID of
			// the user (as it's returned by BP_Experiment_Member_Query)
			$this->requests[ $rk ]->user_id = $rv->ID;
			$this->requests[ $rk ]->id      = $rv->membership_id;

			// Miscellaneous values
			$this->requests[ $rk ]->experiment_id   = $r['experiment_id'];
		}

		if ( !$r['max'] || $r['max'] >= (int) $mquery->total_users )
			$this->total_request_count = (int) $mquery->total_users;
		else
			$this->total_request_count = (int) $r['max'];

		if ( $r['max'] ) {
			if ( $r['max'] >= count($this->requests) )
				$this->request_count = count($this->requests);
			else
				$this->request_count = (int) $r['max'];
		} else {
			$this->request_count = count($this->requests);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'mrpage', '%#%' ),
			'format' => '',
			'total' => ceil( $this->total_request_count / $this->pag_num ),
			'current' => $this->pag_page,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size' => 1
		) );
	}

	function has_requests() {
		if ( $this->request_count )
			return true;

		return false;
	}

	function next_request() {
		$this->current_request++;
		$this->request = $this->requests[$this->current_request];

		return $this->request;
	}

	function rewind_requests() {
		$this->current_request = -1;

		if ( $this->request_count > 0 )
			$this->request = $this->requests[0];
	}

	function requests() {
		if ( $this->current_request + 1 < $this->request_count ) {
			return true;
		} elseif ( $this->current_request + 1 == $this->request_count ) {
			do_action('experiment_request_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_requests();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_request() {
		$this->in_the_loop = true;
		$this->request     = $this->next_request();

		if ( 0 == $this->current_request ) // loop has just started
			do_action('experiment_request_loop_start');
	}
}

/**
 * Initialize a experiment membership request template loop.
 *
 * @param array $args {
 *     @type int $experiment_id ID of the experiment. Defaults to current experiment.
 *     @type int $per_page Number of records to return per page. Default: 10.
 *     @type int $page Page of results to return. Default: 1.
 *     @type int $max Max number of items to return. Default: false.
 * }
 * @return bool True if there are requests, otherwise false.
 */
function bp_experiment_has_membership_requests( $args = '' ) {
	global $requests_template;

	$defaults = array(
		'experiment_id' => bp_get_current_experiment_id(),
		'per_page' => 10,
		'page'     => 1,
		'max'      => false
	);

	$r = wp_parse_args( $args, $defaults );

	$requests_template = new BP_Experiments_Membership_Requests_Template( $r );
	return apply_filters( 'bp_experiment_has_membership_requests', $requests_template->has_requests(), $requests_template );
}

function bp_experiment_membership_requests() {
	global $requests_template;

	return $requests_template->requests();
}

function bp_experiment_the_membership_request() {
	global $requests_template;

	return $requests_template->the_request();
}

function bp_experiment_request_user_avatar_thumb() {
	global $requests_template;

	echo apply_filters( 'bp_experiment_request_user_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $requests_template->request->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $requests_template->request->user_id ) ) ) ) );
}

function bp_experiment_request_reject_link() {
	echo bp_get_experiment_request_reject_link();
}
	function bp_get_experiment_request_reject_link() {
		global $requests_template;

		return apply_filters( 'bp_get_experiment_request_reject_link', wp_nonce_url( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/membership-requests/reject/' . $requests_template->request->membership_id, 'experiments_reject_membership_request' ) );
	}

function bp_experiment_request_accept_link() {
	echo bp_get_experiment_request_accept_link();
}
	function bp_get_experiment_request_accept_link() {
		global $requests_template;

		return apply_filters( 'bp_get_experiment_request_accept_link', wp_nonce_url( bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'admin/membership-requests/accept/' . $requests_template->request->membership_id, 'experiments_accept_membership_request' ) );
	}

function bp_experiment_request_user_link() {
	echo bp_get_experiment_request_user_link();
}
	function bp_get_experiment_request_user_link() {
		global $requests_template;

		return apply_filters( 'bp_get_experiment_request_user_link', bp_core_get_userlink( $requests_template->request->user_id ) );
	}

function bp_experiment_request_time_since_requested() {
	global $requests_template;

	echo apply_filters( 'bp_experiment_request_time_since_requested', sprintf( __( 'requested %s', 'buddypress' ), bp_core_time_since( strtotime( $requests_template->request->date_modified ) ) ) );
}

function bp_experiment_request_comment() {
	global $requests_template;

	echo apply_filters( 'bp_experiment_request_comment', strip_tags( stripslashes( $requests_template->request->comments ) ) );
}

/**
 * Output pagination links for experiment membership requests.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_experiment_requests_pagination_links() {
	echo bp_get_experiment_requests_pagination_links();
}
	/**
	 * Get pagination links for experiment membership requests.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_experiment_requests_pagination_links() {
		global $requests_template;
		return apply_filters( 'bp_get_experiment_requests_pagination_links', $requests_template->pag_links );
	}

/**
 * Output pagination count text for experiment membership requests.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_experiment_requests_pagination_count() {
	echo bp_get_experiment_requests_pagination_count();
}
	/**
	 * Get pagination count text for experiment membership requests.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_experiment_requests_pagination_count() {
		global $requests_template;

		$start_num = intval( ( $requests_template->pag_page - 1 ) * $requests_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $requests_template->pag_num - 1 ) > $requests_template->total_request_count ) ? $requests_template->total_request_count : $start_num + ( $requests_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $requests_template->total_request_count );

		return apply_filters( 'bp_get_experiment_requests_pagination_count', sprintf( _n( 'Viewing requests %1$s to %2$s (of %3$s request)', 'Viewing request %1$s to %2$s (of %3$s requests)', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
	}

/************************************************************************************
 * Invite Friends Template Tags
 **/

class BP_Experiments_Invite_Template {
	var $current_invite = -1;
	var $invite_count;
	var $invites;
	var $invite;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_invite_count;

	public function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'user_id',
				1  => 'experiment_id',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$r = wp_parse_args( $args, array(
			'user_id'  => bp_loggedin_user_id(),
			'experiment_id' => bp_get_current_experiment_id(),
			'page'     => 1,
			'per_page' => 10,
		) );

		$this->pag_num  = intval( $r['per_page'] );
		$this->pag_page = isset( $_REQUEST['invitepage'] ) ? intval( $_REQUEST['invitepage'] ) : $r['page'];

		$iquery = new BP_Experiment_Member_Query( array(
			'experiment_id' => $r['experiment_id'],
			'type'     => 'first_joined',
			'per_page' => $this->pag_num,
			'page'     => $this->pag_page,

			// These filters ensure we get only pending invites
			'is_confirmed' => false,
			'inviter_id'   => $r['user_id'],
		) );
		$this->invite_data = $iquery->results;

		$this->total_invite_count = $iquery->total_users;
		$this->invites		  = array_values( wp_list_pluck( $this->invite_data, 'ID' ) );
		$this->invite_count       = count( $this->invites );

		// If per_page is set to 0 (show all results), don't generate
		// pag_links
		if ( ! empty( $this->pag_num ) ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'invitepage', '%#%' ),
				'format'    => '',
				'total'     => ceil( $this->total_invite_count / $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size'  => 1,
			) );
		} else {
			$this->pag_links = '';
		}
	}

	function has_invites() {
		if ( $this->invite_count )
			return true;

		return false;
	}

	function next_invite() {
		$this->current_invite++;
		$this->invite = $this->invites[$this->current_invite];

		return $this->invite;
	}

	function rewind_invites() {
		$this->current_invite = -1;
		if ( $this->invite_count > 0 )
			$this->invite = $this->invites[0];
	}

	function invites() {
		if ( $this->current_invite + 1 < $this->invite_count ) {
			return true;
		} elseif ( $this->current_invite + 1 == $this->invite_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_invites();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_invite() {
		global $experiment_id;
		$this->in_the_loop      = true;
		$user_id                = $this->next_invite();

		$this->invite           = new stdClass;
		$this->invite->user     = $this->invite_data[ $user_id ];

		// This method previously populated the user object with
		// BP_Core_User. We manually configure BP_Core_User data for
		// backward compatibility.
		if ( bp_is_active( 'xprofile' ) ) {
			$this->invite->user->profile_data = BP_XProfile_ProfileData::get_all_for_user( $user_id );
		}

		$this->invite->user->avatar       = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full', 'alt' => sprintf( __( 'Avatar of %s', 'buddypress' ), $this->invite->user->fullname ) ) );
		$this->invite->user->avatar_thumb = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Avatar of %s', 'buddypress' ), $this->invite->user->fullname ) ) );
		$this->invite->user->avatar_mini  = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Avatar of %s', 'buddypress' ), $this->invite->user->fullname ), 'width' => 30, 'height' => 30 ) );
		$this->invite->user->email        = $this->invite->user->user_email;
		$this->invite->user->user_url     = bp_core_get_user_domain( $user_id, $this->invite->user->user_nicename, $this->invite->user->user_login );
		$this->invite->user->user_link    = "<a href='{$this->invite->user->user_url}' title='{$this->invite->user->fullname}'>{$this->invite->user->fullname}</a>";
		$this->invite->user->last_active  = bp_core_get_last_activity( $this->invite->user->last_activity, __( 'active %s', 'buddypress' ) );

		if ( bp_is_active( 'experiments' ) ) {
			$total_experiments = BP_Experiments_Member::total_experiment_count( $user_id );
			$this->invite->user->total_experiments = sprintf( _n( '%d experiment', '%d experiments', $total_experiments, 'buddypress' ), $total_experiments );
		}

		if ( bp_is_active( 'friends' ) ) {
			$this->invite->user->total_friends = BP_Friends_Friendship::total_friend_count( $user_id );
		}

		if ( bp_is_active( 'friends' ) ) {
			$this->invite->user->total_friends = BP_Friends_Friendship::total_friend_count( $user_id );
		}

		$this->invite->user->total_blogs = null;

		$this->invite->experiment_id = $experiment_id; // Globaled in bp_experiment_has_invites()

		if ( 0 == $this->current_invite ) // loop has just started
			do_action('loop_start');
	}
}

function bp_experiment_has_invites( $args = '' ) {
	global $invites_template, $experiment_id;

	$r = wp_parse_args( $args, array(
		'experiment_id' => false,
		'user_id'  => bp_loggedin_user_id(),
		'per_page' => false,
		'page'     => 1,
	) );

	if ( empty( $r['experiment_id'] ) ) {
		if ( ! empty( buddypress()->experiments->current_experiment ) ) {
			$r['experiment_id'] = bp_get_current_experiment_id();
		} else if ( ! empty( buddypress()->experiments->new_experiment_id ) ) {
			$r['experiment_id'] = buddypress()->experiments->new_experiment_id;
		}
	}

	// Set the global (for use in BP_Experiments_Invite_Template::the_invite())
	if ( empty( $experiment_id ) ) {
		$experiment_id = $r['experiment_id'];
	}

	if ( ! $experiment_id ) {
		return false;
	}

	$invites_template = new BP_Experiments_Invite_Template( $r );
	return apply_filters( 'bp_experiment_has_invites', $invites_template->has_invites(), $invites_template );
}

function bp_experiment_invites() {
	global $invites_template;

	return $invites_template->invites();
}

function bp_experiment_the_invite() {
	global $invites_template;

	return $invites_template->the_invite();
}

function bp_experiment_invite_item_id() {
	echo bp_get_experiment_invite_item_id();
}
	function bp_get_experiment_invite_item_id() {
		global $invites_template;

		return apply_filters( 'bp_get_experiment_invite_item_id', 'uid-' . $invites_template->invite->user->id );
	}

function bp_experiment_invite_user_avatar() {
	echo bp_get_experiment_invite_user_avatar();
}
	function bp_get_experiment_invite_user_avatar() {
		global $invites_template;

		return apply_filters( 'bp_get_experiment_invite_user_avatar', $invites_template->invite->user->avatar_thumb );
	}

function bp_experiment_invite_user_link() {
	echo bp_get_experiment_invite_user_link();
}
	function bp_get_experiment_invite_user_link() {
		global $invites_template;

		return apply_filters( 'bp_get_experiment_invite_user_link', bp_core_get_userlink( $invites_template->invite->user->id ) );
	}

function bp_experiment_invite_user_last_active() {
	echo bp_get_experiment_invite_user_last_active();
}
	function bp_get_experiment_invite_user_last_active() {
		global $invites_template;

		return apply_filters( 'bp_get_experiment_invite_user_last_active', $invites_template->invite->user->last_active );
	}

function bp_experiment_invite_user_remove_invite_url() {
	echo bp_get_experiment_invite_user_remove_invite_url();
}
	function bp_get_experiment_invite_user_remove_invite_url() {
		global $invites_template;

		$user_id = intval( $invites_template->invite->user->id );

		if ( bp_is_current_action( 'create' ) ) {
			$uninvite_url = bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/experiment-invites/?user_id=' . $user_id;
		} else {
			$uninvite_url = bp_get_experiment_permalink( experiments_get_current_experiment() ) . 'send-invites/remove/' . $user_id;
		}

		return wp_nonce_url( $uninvite_url, 'experiments_invite_uninvite_user' );
	}

/**
 * Output pagination links for experiment invitations.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_experiment_invite_pagination_links() {
	echo bp_get_experiment_invite_pagination_links();
}
	/**
	 * Get pagination links for experiment invitations.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_experiment_invite_pagination_links() {
		global $invites_template;
		return apply_filters( 'bp_get_experiment_invite_pagination_links', $invites_template->pag_links );
	}

/**
 * Output pagination count text for experiment invitations.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_experiment_invite_pagination_count() {
	echo bp_get_experiment_invite_pagination_count();
}
	/**
	 * Get pagination count text for experiment invitations.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @return string
	 */
	function bp_get_experiment_invite_pagination_count() {
		global $invites_template;

		$start_num = intval( ( $invites_template->pag_page - 1 ) * $invites_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $invites_template->pag_num - 1 ) > $invites_template->total_invite_count ) ? $invites_template->total_invite_count : $start_num + ( $invites_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $invites_template->total_invite_count );

		return apply_filters( 'bp_get_experiments_pagination_count', sprintf( _n( 'Viewing invitation %1$s to %2$s (of %3$s invitation)', 'Viewing invitation %1$s to %2$s (of %3$s invitations)', $total, 'buddypress' ), $from_num, $to_num, $total ), $from_num, $to_num, $total );
	}

/***
 * Experiments RSS Feed Template Tags
 */

/**
 * Hook experiment activity feed to <head>
 *
 * @since BuddyPress (1.5)
 */
function bp_experiments_activity_feed() {
	if ( !bp_is_active( 'experiments' ) || !bp_is_active( 'activity' ) || !bp_is_experiment() )
		return; ?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php bp_current_experiment_name() ?> | <?php _e( 'Experiment Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_experiment_activity_feed_link() ?>" />

<?php
}
add_action( 'bp_head', 'bp_experiments_activity_feed' );

function bp_experiment_activity_feed_link() {
	echo bp_get_experiment_activity_feed_link();
}
	function bp_get_experiment_activity_feed_link() {
		global $bp;

		return apply_filters( 'bp_get_experiment_activity_feed_link', bp_get_experiment_permalink( $bp->experiments->current_experiment ) . 'feed/' );
	}

/**
 * Echoes the output of bp_get_current_experiment_id()
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 */
function bp_current_experiment_id() {
	echo bp_get_current_experiment_id();
}
	/**
	 * Returns the ID of the current experiment
	 *
	 * @package BuddyPress
	 * @since BuddyPress (1.5)
	 * @uses apply_filters() Filter bp_get_current_experiment_id to modify this output
	 *
	 * @return int $current_experiment_id The id of the current experiment, if there is one
	 */
	function bp_get_current_experiment_id() {
		$current_experiment = experiments_get_current_experiment();

		$current_experiment_id = isset( $current_experiment->id ) ? (int) $current_experiment->id : 0;

		return apply_filters( 'bp_get_current_experiment_id', $current_experiment_id, $current_experiment );
	}

/**
 * Echoes the output of bp_get_current_experiment_slug()
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 */
function bp_current_experiment_slug() {
	echo bp_get_current_experiment_slug();
}
	/**
	 * Returns the slug of the current experiment
	 *
	 * @package BuddyPress
	 * @since BuddyPress (1.5)
	 * @uses apply_filters() Filter bp_get_current_experiment_slug to modify this output
	 *
	 * @return string $current_experiment_slug The slug of the current experiment, if there is one
	 */
	function bp_get_current_experiment_slug() {
		$current_experiment = experiments_get_current_experiment();

		$current_experiment_slug = isset( $current_experiment->slug ) ? $current_experiment->slug : '';

		return apply_filters( 'bp_get_current_experiment_slug', $current_experiment_slug, $current_experiment );
	}

/**
 * Echoes the output of bp_get_current_experiment_name()
 *
 * @package BuddyPress
 */
function bp_current_experiment_name() {
	echo bp_get_current_experiment_name();
}
	/**
	 * Returns the name of the current experiment
	 *
	 * @package BuddyPress
	 * @since BuddyPress (1.5)
	 * @uses apply_filters() Filter bp_get_current_experiment_name to modify this output
	 *
	 * @return string The name of the current experiment, if there is one
	 */
	function bp_get_current_experiment_name() {
		global $bp;

		$name = apply_filters( 'bp_get_experiment_name', $bp->experiments->current_experiment->name );
		return apply_filters( 'bp_get_current_experiment_name', $name );
	}

function bp_experiments_action_link( $action = '', $query_args = '', $nonce = false ) {
	echo bp_get_experiments_action_link( $action, $query_args, $nonce );
}
	function bp_get_experiments_action_link( $action = '', $query_args = '', $nonce = false ) {
		global $bp;

		// Must be a experiment
		if ( empty( $bp->experiments->current_experiment->id ) )
			return;

		// Append $action to $url if provided
		if ( !empty( $action ) )
			$url = bp_get_experiment_permalink( experiments_get_current_experiment() ) . $action;
		else
			$url = bp_get_experiment_permalink( experiments_get_current_experiment() );

		// Add a slash at the end of our user url
		$url = trailingslashit( $url );

		// Add possible query arg
		if ( !empty( $query_args ) && is_array( $query_args ) )
			$url = add_query_arg( $query_args, $url );

		// To nonce, or not to nonce...
		if ( true === $nonce )
			$url = wp_nonce_url( $url );
		elseif ( is_string( $nonce ) )
			$url = wp_nonce_url( $url, $nonce );

		// Return the url, if there is one
		if ( !empty( $url ) )
			return $url;
	}

/** Stats **********************************************************************/

/**
 * Display the number of experiments in user's profile.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $args before|after|user_id
 * @uses bp_experiments_get_profile_stats() to get the stats
 */
function bp_experiments_profile_stats( $args = '' ) {
	echo bp_experiments_get_profile_stats( $args );
}
add_action( 'bp_members_admin_user_stats', 'bp_experiments_profile_stats', 8, 1 );

/**
 * Return the number of experiments in user's profile.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $args before|after|user_id
 * @return string HTML for stats output.
 */
function bp_experiments_get_profile_stats( $args = '' ) {

	// Parse the args
	$r = bp_parse_args( $args, array(
		'before'  => '<li class="bp-experiments-profile-stats">',
		'after'   => '</li>',
		'user_id' => bp_displayed_user_id(),
		'experiments'  => 0,
		'output'  => ''
	), 'experiments_get_profile_stats' );

	// Allow completely overloaded output
	if ( empty( $r['output'] ) ) {

		// Only proceed if a user ID was passed
		if ( ! empty( $r['user_id'] ) ) {

			// Get the user experiments
			if ( empty( $r['experiments'] ) ) {
				$r['experiments'] = absint( bp_get_total_experiment_count_for_user( $r['user_id'] ) );
			}

			// If experiments exist, show some formatted output
			$r['output'] = $r['before'] . sprintf( _n( '%s experiment', '%s experiments', $r['experiments'], 'buddypress' ), '<strong>' . $r['experiments'] . '</strong>' ) . $r['after'];
		}
	}

	// Filter and return
	return apply_filters( 'bp_experiments_get_profile_stats', $r['output'], $r );
}
