<?php
/**
 * BuddyPress Experiments Filters
 *
 * @package BuddyPress
 * @subpackage ExperimentsFilters
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Filter bbPress template locations

add_filter( 'bp_experiments_get_directory_template', 'bp_add_template_locations' );
add_filter( 'bp_get_single_experiment_template',    'bp_add_template_locations' );

/* Apply WordPress defined filters */
add_filter( 'bp_get_experiment_description',         'wptexturize' );
add_filter( 'bp_get_experiment_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_experiment_name',                'wptexturize' );

add_filter( 'bp_get_experiment_description',         'convert_smilies' );
add_filter( 'bp_get_experiment_description_excerpt', 'convert_smilies' );

add_filter( 'bp_get_experiment_description',         'convert_chars' );
add_filter( 'bp_get_experiment_description_excerpt', 'convert_chars' );
add_filter( 'bp_get_experiment_name',                'convert_chars' );

add_filter( 'bp_get_experiment_description',         'wpautop' );
add_filter( 'bp_get_experiment_description_excerpt', 'wpautop' );

add_filter( 'bp_get_experiment_description',         'make_clickable', 9 );
add_filter( 'bp_get_experiment_description_excerpt', 'make_clickable', 9 );

add_filter( 'bp_get_experiment_name',                    'wp_filter_kses', 1 );
add_filter( 'bp_get_experiment_permalink',               'wp_filter_kses', 1 );
add_filter( 'bp_get_experiment_description',             'bp_experiments_filter_kses', 1 );
add_filter( 'bp_get_experiment_description_excerpt',     'wp_filter_kses', 1 );
add_filter( 'experiments_experiment_name_before_save',        'wp_filter_kses', 1 );
add_filter( 'experiments_experiment_description_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_experiment_description',         'stripslashes' );
add_filter( 'bp_get_experiment_description_excerpt', 'stripslashes' );
add_filter( 'bp_get_experiment_name',                'stripslashes' );
add_filter( 'bp_get_experiment_member_name',         'stripslashes' );
add_filter( 'bp_get_experiment_member_link',         'stripslashes' );

add_filter( 'experiments_new_experiment_forum_desc', 'bp_create_excerpt' );

add_filter( 'experiments_experiment_name_before_save',        'force_balance_tags' );
add_filter( 'experiments_experiment_description_before_save', 'force_balance_tags' );

// Trim trailing spaces from name and description when saving
add_filter( 'experiments_experiment_name_before_save',        'trim' );
add_filter( 'experiments_experiment_description_before_save', 'trim' );

// Escape output of new experiment creation details
add_filter( 'bp_get_new_experiment_id',          'esc_attr'     );
add_filter( 'bp_get_new_experiment_name',        'esc_attr'     );
add_filter( 'bp_get_new_experiment_description', 'esc_textarea' );

// Format numberical output
add_filter( 'bp_get_total_experiment_count',      'bp_core_number_format' );
add_filter( 'bp_get_experiment_total_for_member', 'bp_core_number_format' );
add_filter( 'bp_get_experiment_total_members',    'bp_core_number_format' );

function bp_experiments_filter_kses( $content ) {
	global $allowedtags;

	$experiments_allowedtags                  = $allowedtags;
	$experiments_allowedtags['a']['class']    = array();
	$experiments_allowedtags['img']           = array();
	$experiments_allowedtags['img']['src']    = array();
	$experiments_allowedtags['img']['alt']    = array();
	$experiments_allowedtags['img']['class']  = array();
	$experiments_allowedtags['img']['width']  = array();
	$experiments_allowedtags['img']['height'] = array();
	$experiments_allowedtags['img']['class']  = array();
	$experiments_allowedtags['img']['id']     = array();
	$experiments_allowedtags['code']          = array();
	$experiments_allowedtags = apply_filters( 'bp_experiments_filter_kses', $experiments_allowedtags );

	return wp_kses( $content, $experiments_allowedtags );
}

/** Experiment forums **************************************************************/

/**
 * Only filter the forum SQL on experiment pages or on the forums directory
 */
function experiments_add_forum_privacy_sql() {
	add_filter( 'get_topics_fields', 'experiments_add_forum_fields_sql' );
	add_filter( 'get_topics_join', 	 'experiments_add_forum_tables_sql' );
	add_filter( 'get_topics_where',  'experiments_add_forum_where_sql'  );
}
add_filter( 'bbpress_init', 'experiments_add_forum_privacy_sql' );

function experiments_add_forum_fields_sql( $sql = '' ) {
	$sql = 't.*, g.id as object_id, g.name as object_name, g.slug as object_slug';
	return $sql;
}

function experiments_add_forum_tables_sql( $sql = '' ) {
	global $bp;

	$sql .= 'JOIN ' . $bp->experiments->table_name . ' AS g LEFT JOIN ' . $bp->experiments->table_name_experimentmeta . ' AS gm ON g.id = gm.experiment_id ';

	return $sql;
}

function experiments_add_forum_where_sql( $sql = '' ) {
	global $bp;

	// Define locale variable
	$parts = array();

	// Set this for experiments
	$parts['experiments'] = "(gm.meta_key = 'forum_id' AND gm.meta_value = t.forum_id)";

	// Restrict to public...
	$parts['private'] = "g.status = 'public'";

	/**
	 * ...but do some checks to possibly remove public restriction.
	 *
	 * Decide if private are visible
	 */
	// Are we in our own profile?
	if ( bp_is_my_profile() )
		unset( $parts['private'] );

	// Are we a super admin?
	elseif ( bp_current_user_can( 'bp_moderate' ) )
		unset( $parts['private'] );

	// No need to filter on a single item
	elseif ( bp_is_single_item() )
		unset( $parts['private'] );

	// Check the SQL filter that was passed
	if ( !empty( $sql ) )
		$parts['passed'] = $sql;

	// Assemble Voltron
	$parts_string = implode( ' AND ', $parts );

	// Set it to the global filter
	$bp->experiments->filter_sql = $parts_string;

	// Return the global filter
	return $bp->experiments->filter_sql;
}

function experiments_filter_bbpress_caps( $value, $cap, $args ) {
	global $bp;

	if ( bp_current_user_can( 'bp_moderate' ) )
		return true;

	if ( 'add_tag_to' == $cap )
		if ( $bp->experiments->current_experiment->user_has_access ) return true;

	if ( 'manage_forums' == $cap && is_user_logged_in() )
		return true;

	return $value;
}
add_filter( 'bb_current_user_can', 'experiments_filter_bbpress_caps', 10, 3 );

/**
 * Amends the forum directory's "last active" bbPress SQL query to stop it fetching
 * information we aren't going to use. This speeds up the query.
 *
 * @see BB_Query::_filter_sql()
 * @since BuddyPress (1.5)
 */
function experiments_filter_forums_root_page_sql( $sql ) {
	return apply_filters( 'experiments_filter_bbpress_root_page_sql', 't.topic_id' );
}
add_filter( 'get_latest_topics_fields', 'experiments_filter_forums_root_page_sql' );
