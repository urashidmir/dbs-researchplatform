<?php
/**
 * BuddyPress Experiments Classes
 *
 * @package BuddyPress
 * @subpackage ExperimentsClasses
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


    /**
     * BuddyPress Experiment Report object.
     */
    class BP_Experiments_Report {
        
        /**
         * ID of the report.
         *
         * @access public
         * @var string
         */
        var $id;
        
        /**
         * ID of the variable.
         *
         * @access public
         * @var int
         */
        var $variable_id;
        
        /**
         * Value of the variable.
         *
         * @access public
         * @var string
         */
        var $variable_value;
        
        /**
         * ID of the experiment associated with the variable.
         *
         * @access public
         * @var int
         */
        var $experiment_id;
        
        /**
         * Last modified date of the variable.
         *
         * This value is updated when, eg, invitations are accepted.
         *
         * @access public
         * @var string
         */
        var $date_modified;
        
        
        /**
         * Comments associated with the report.
         
         * @access public
         * @var string
         */
        var $comments;
        
        
        /**
         * ID of the user modifying the variable.
         *
         * @access public
         * @var int
         */
        var $user_id;
        
        /**
         * WP_User object representing the user modifying the variable.
         *
         * @access public
         * @var WP_User
         */
        var $user;
        
        /**
         * Experiment object representing the experiment the report refers to.
         *
         * @access public
         * @var BP_Experiments_Experiment
         */
        var $experiment;
        
        /**
         * Experiment object representing the variable of the referred experiment.
         *
         * @access public
         * @var BP_Experiments_Variable
         */
        var $variable;
        
        
        /**
         * Constructor method.
         * @param int $user_id Optional. Along with experiment_id and variable_id can be
         * used to look up a variable.
         *
         * @param int $experiment_id Optional. Can be used to
         *        look up a variable.
         * @param int $id Optional. The unique ID of the variable object.
         * @param bool $populate Whether to populate the properties of the
         *        located variable. Default: true.
         */
        public function __construct( $user_id = 0, $experiment_id = 0, $variable_id = 0, $id = false, $populate = true ) {
            
            // User, experiment and variable are not empty, and ID is
            if ( !empty( $user_id ) && !empty( $experiment_id ) && !empty( $variable_id ) && empty( $id ) ) {
                $this->user_id  = $user_id;
                $this->experiment_id = $experiment_id;
                $this->variable_id  = $variable_id;
                
                if ( !empty( $populate ) ) {
                    $this->populate();
                }
            }
            
            // ID is not empty
            if ( !empty( $id ) ) {
                $this->id = $id;
                
                if ( !empty( $populate ) ) {
                    $this->populate();
                }
            }
        }
        
        /**
         * Populate the object's properties.
         */
        public function populate() {
            global $wpdb, $bp;
            
            if ( $this->user_id && $this->experiment_id && !$this->id )
                $sql = $wpdb->prepare( "SELECT * FROM wp_bp_experiments_report WHERE user_id = %d AND experiment_id = %d", $this->user_id, $this->experiment_id );
            
            if ( !empty( $this->id ) )
                $sql = $wpdb->prepare( "SELECT * FROM wp_bp_experiments_report WHERE id = %d", $this->id );
            
            $report = $wpdb->get_row($sql);
            
            if ( !empty( $report ) ) {
                $this->id            = $report->id;
                $this->experiment_id      = $report->experiment_id;
                $this->user_id       = $report->user_id;
                $this->variable_id   = $report->variable_id;
                $this->variable_value      = $report->variable_value;
                $this->date_modified = $report->date_modified;
                
                
                $this->user = new BP_Core_User( $this->user_id );
                $this->experiment = new BP_Experiments_Experiment( $this->experiment_id );
                $this->variable = new BP_Experiments_Variable($this->experiment_id );
            }
        }//end populate
        
        
        
        
        /**
         * Save the variable data to the database.
         *
         * @return bool True on success, false on failure.
         */
        public function save() {
            global $wpdb, $bp;
            
            
            $this->experiment_id      = apply_filters( 'experiments_report_experiment_id_before_save',      $this->experiment_id,      $this->id );
            $this->user_id    = apply_filters( 'experiments_report_user_id_before_save',    $this->user_id,    $this->id );
            $this->variable_id    = apply_filters( 'experiments_report_variable_id_before_save',    $this->variable_id,    $this->id );
            $this->variable_value    = apply_filters( 'experiments_report_variable_value_before_save',    $this->variable_value,    $this->id );
            $this->date_modified    = apply_filters( 'experiments_report_variable_id_before_save',    $this->date_modified,    $this->id );
            
            do_action_ref_array( 'experiments_report_before_save', array( &$this ) );
            
            if ( !empty( $this->id ) )
            {
                $sql = $wpdb->prepare( "UPDATE wp_bp_experiments_report SET experiment_id = %d, user_id=%d, variable_id=%d, variable_value = %s, date_modified=%s WHERE id = %d", $this->experiment_id, $this->user_id, $this->variable_id, $this->variable_value, $this->date_modified );
            } else {
                // Ensure that variable is not already a variable of the experiment before inserting
                //if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM wp_bp_experiments_report WHERE id = %d AND experiment_id = %d", $this->id, $this->experiment_id ) ) ) {
                //    return false;
                // }
                
                $sql = $wpdb->prepare( "INSERT INTO wp_bp_experiments_report (experiment_id, user_id, variable_id, variable_value, date_modified  ) VALUES ( %d, %d, %d, %s, %s)", $this->experiment_id, $this->user_id, $this->variable_id, $this->variable_value, $this->date_modified);
            }
            
            if ( !$wpdb->query( $sql ) )
                return false;
            
            $this->id = $wpdb->insert_id;
            
            // Update the experiment’s report count
            self::refresh_total_report_count_for_experiment( $this->id );
            
            
            do_action_ref_array( 'experiments_report_after_save', array( &$this ) );
            
            return true;
        }
        
        
        
        /**
         * Remove the current report of an experiment.
         *
         * @return bool True on success, false on failure.
         */
        public function remove() {
            global $wpdb, $bp;
            
            $sql = $wpdb->prepare( "DELETE FROM wp_bp_experiments_report WHERE id = %d AND experiment_id = %d", $this->id, $this->experiment_id );
            
            if ( !$result = $wpdb->query( $sql ) )
                return false;
            
            // Update the experiment's report count
            self::refresh_total_report_count_for_experiment( $this->experiment_id );
            
            return $result;
        }
        
        /** Static Methods ****************************************************/
        
        
        
        /**
         * Refresh the total_report_count for a experiment.
         *
         * @since BuddyPress (1.8.0)
         *
         * @param int $experiment_id ID of the experiment.
         * @return bool True on success, false on failure.
         */
        public static function refresh_total_report_count_for_experiment( $experiment_id ) {
            return experiments_update_experimentmeta( $experiment_id, 'total_report_count', (int) 	    BP_Experiments_Experiment::get_total_report_count( $experiment_id ) );
        }
        
        
        /**
         * Get the IDs of all a given experiment's reports.
         *
         * @param int $experiment_id ID of the experiment.
         * @return array IDs of all experiment reports.
         */
        public static function get_experiment_report_ids( $experiment_id ) {
            global $bp, $wpdb;
            
            return $wpdb->get_col( $wpdb->prepare( "SELECT id FROM wp_bp_experiments_report WHERE experiment_id = %d", $experiment_id ) );
        }
        
        
        
        /**
         * Get reports of an experiment.
         *
         * @deprecated BuddyPress (1.8.0)
         */
        public static function get_all_for_experiment( $experiment_id, $limit = false, $page = false ) {
            global $bp, $wpdb;
            
            _deprecated_function( __METHOD__, '1.8', 'BP_Experiment_Report_Query' );
            
            $pag_sql = '';
            if ( !empty( $limit ) && !empty( $page ) )
                $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
            
            
            
            if ( empty( $reports ) ) {
                return false;
            }
            
            if ( empty( $pag_sql ) ) {
                $total_report_count = count( $reports );
            } else {
                $total_report_count = $wpdb->get_var( apply_filters( 'bp_experiment_reports_count', $wpdb->prepare( "SELECT COUNT(id) FROM wp_bp_experiments_report m WHERE experiment_id = %d", $experiment_id ) ) );
            }
            
            
            return array( 'reports' => $reports, 'count' => $total_report_count );
        }
        
        /**
         * Delete all reports for a given experiment.
         *
         * @param int $experiment_id ID of the experiment.
         * @return int Number of records deleted.
         */
        public static function delete_all( $experiment_id ) {
            global $wpdb, $bp;
            
            return $wpdb->query( $wpdb->prepare( "DELETE FROM wp_bp_experiments_report WHERE experiment_id = %d", $experiment_id ) );
        }
        
    }//end BP_Experiments_Report class
    
    
/**
 * BuddyPress Experiment object.
 */
class BP_Experiments_Experiment {

	/**
	 * ID of the experiment.
	 *
	 * @access public
	 * @var int
	 */
	public $id;

	/**
	 * User ID of the experiment's creator.
	 *
	 * @access public
	 * @var int
	 */
	public $creator_id;

	/**
	 * Name of the experiment.
	 *
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * Experiment slug.
	 *
	 * @access public
	 * @var string
	 */
	public $slug;

	/**
	 * Experiment description.
	 *
	 * @access public
	 * @var string
	 */
	public $description;
    
    
    
    /**
	 * Total count of experiment variables.
	 *
	 * @access public
	 * @var int
	 */
	public $total_variable_count;
    
	/**
	 * Is the current factor a variable of this experiment?
	 *
	 * @since BuddyPress (1.2.0)
	 * @var bool
	 */
	public $is_variable;

	/**
	 * Experiment status.
	 *
	 * Core statuses are 'public', 'private', and 'hidden'.
	 *
	 * @access public
	 * @var string
	 */
	public $status;

	/**
	 * Should (legacy) bbPress forums be enabled for this experiment?
	 *
	 * @access public
	 * @var int
	 */
	public $enable_forum;

	/**
	 * Date the experiment was created.
	 *
	 * @access public
	 * @var string
	 */
	public $date_created;

	/**
	 * Data about the experiment's admins.
	 *
	 * @access public
	 * @var array
	 */
	public $admins;

	/**
	 * Data about the experiment's moderators.
	 *
	 * @access public
	 * @var array
	 */
	public $mods;

	/**
	 * Total count of experiment members.
	 *
	 * @access public
	 * @var int
	 */
	public $total_member_count;

	/**
	 * Is the current user a member of this experiment?
	 *
	 * @since BuddyPress (1.2.0)
	 * @var bool
	 */
	public $is_member;

	/**
	 * Does the current user have an outstanding invitation to this experiment?
	 *
	 * @since BuddyPress (1.9.0)
	 * @var bool
	 */
	public $is_invited;

	/**
	 * Does the current user have a pending membership request to this experiment?
	 *
	 * @since BuddyPress (1.9.0)
	 * @var bool
	 */
	public $is_pending;

	/**
	 * Timestamp of the last activity that happened in this experiment.
	 *
	 * @since BuddyPress (1.2.0)
	 * @var string
	 */
	public $last_activity;

	/**
	 * If this is a private or hidden experiment, does the current user have access?
	 *
	 * @since BuddyPress (1.6.0)
	 * @var bool
	 */
	public $user_has_access;

	/**
	 * Raw arguments passed to the constructor.
	 *
	 * @since BuddyPress (2.0.0)
	 * @var array
	 */
	public $args;

	/**
	 * Constructor method.
	 *
	 * @param int $id Optional. If the ID of an existing experiment is provided,
	 *        the object will be pre-populated with info about that experiment.
	 */
	public function __construct( $id = null, $args = array() ) {
		$this->args = wp_parse_args( $args, array(
			'populate_extras' => false,
		) );

		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Set up data about the current experiment.
	 */
	public function populate() {
		global $wpdb;

		// Get BuddyPress
		$bp    = buddypress();

		// Check cache for experiment data
		$experiment = wp_cache_get( $this->id, 'bp_experiments' );

		// Cache missed, so query the DB
		if ( false === $experiment ) {
			$experiment = $wpdb->get_row( $wpdb->prepare( "SELECT g.* FROM {$bp->experiments->table_name} g WHERE g.id = %d", $this->id ) );

			wp_cache_set( $this->id, $experiment, 'bp_experiments' );
		}

		// No experiment found so set the ID and bail
		if ( empty( $experiment ) || is_wp_error( $experiment ) ) {
			$this->id = 0;
			return;
		}

		// Experiment found so setup the object variables
		$this->id           = $experiment->id;
		$this->creator_id   = $experiment->creator_id;
		$this->name         = stripslashes( $experiment->name );
		$this->slug         = $experiment->slug;
		$this->description  = stripslashes( $experiment->description );
		$this->status       = $experiment->status;
		$this->enable_forum = $experiment->enable_forum;
		$this->date_created = $experiment->date_created;

		// Are we getting extra experiment data?
		if ( ! empty( $this->args['populate_extras'] ) ) {

			// Get experiment admins and mods
			$admin_mods = $wpdb->get_results( apply_filters( 'bp_experiment_admin_mods_user_join_filter', $wpdb->prepare( "SELECT u.ID as user_id, u.user_login, u.user_email, u.user_nicename, m.is_admin, m.is_mod FROM {$wpdb->users} u, {$bp->experiments->table_name_members} m WHERE u.ID = m.user_id AND m.experiment_id = %d AND ( m.is_admin = 1 OR m.is_mod = 1 )", $this->id ) ) );

			// Add admins and moderators to their respective arrays
			foreach ( (array) $admin_mods as $user ) {
				if ( !empty( $user->is_admin ) ) {
					$this->admins[] = $user;
				} else {
					$this->mods[] = $user;
				}
			}

			// Set up some specific experiment vars from meta. Excluded
			// from the bp_experiments cache because it's cached independently
			$this->last_activity      = experiments_get_experimentmeta( $this->id, 'last_activity' );
			$this->total_member_count = experiments_get_experimentmeta( $this->id, 'total_member_count' );
            $this->total_variable_count = experiments_get_experimentmeta( $this->id, 'total_variable_count' );

			// Set user-specific data
			$user_id          = bp_loggedin_user_id();
			$this->is_member  = BP_Experiments_Member::check_is_member( $user_id, $this->id );
			$this->is_invited = BP_Experiments_Member::check_has_invite( $user_id, $this->id );
			$this->is_pending = BP_Experiments_Member::check_for_membership_request( $user_id, $this->id );

			// If this is a private or hidden experiment, does the current user have access?
			if ( ( 'private' === $this->status ) || ( 'hidden' === $this->status ) ) {

				// Assume user does not have access to hidden/private experiments
				$this->user_has_access = false;

				// Experiment members or community moderators have access
				if ( ( $this->is_member && is_user_logged_in() ) || bp_current_user_can( 'bp_moderate' ) ) {
					$this->user_has_access = true;
				}
			} else {
				$this->user_has_access = true;
			}
		}
	}

	/**
	 * Save the current experiment to the database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		global $wpdb, $bp;

		$this->creator_id   = apply_filters( 'experiments_experiment_creator_id_before_save',   $this->creator_id,   $this->id );
		$this->name         = apply_filters( 'experiments_experiment_name_before_save',         $this->name,         $this->id );
 		$this->slug         = apply_filters( 'experiments_experiment_slug_before_save',         $this->slug,         $this->id );
		$this->description  = apply_filters( 'experiments_experiment_description_before_save',  $this->description,  $this->id );
 		$this->status       = apply_filters( 'experiments_experiment_status_before_save',       $this->status,       $this->id );
		$this->enable_forum = apply_filters( 'experiments_experiment_enable_forum_before_save', $this->enable_forum, $this->id );
		$this->date_created = apply_filters( 'experiments_experiment_date_created_before_save', $this->date_created, $this->id );

		do_action_ref_array( 'experiments_experiment_before_save', array( &$this ) );

		// Experiments need at least a name
		if ( empty( $this->name ) ) {
			return false;
		}

		// Set slug with experiment title if not passed
		if ( empty( $this->slug ) ) {
			$this->slug = sanitize_title( $this->name );
		}

		// Sanity check
		if ( empty( $this->slug ) ) {
			return false;
		}

		// Check for slug conflicts if creating new experiment
		if ( empty( $this->id ) ) {
			$this->slug = experiments_check_slug( $this->slug );
		}

		if ( !empty( $this->id ) ) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->experiments->table_name} SET
					creator_id = %d,
					name = %s,
					slug = %s,
					description = %s,
					status = %s,
					enable_forum = %d,
					date_created = %s
				WHERE
					id = %d
				",
					$this->creator_id,
					$this->name,
					$this->slug,
					$this->description,
					$this->status,
					$this->enable_forum,
					$this->date_created,
					$this->id
			);
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$bp->experiments->table_name} (
					creator_id,
					name,
					slug,
					description,
					status,
					enable_forum,
					date_created
				) VALUES (
					%d, %s, %s, %s, %s, %d, %s
				)",
					$this->creator_id,
					$this->name,
					$this->slug,
					$this->description,
					$this->status,
					$this->enable_forum,
					$this->date_created
			);
		}

		if ( false === $wpdb->query($sql) )
			return false;

		if ( empty( $this->id ) )
			$this->id = $wpdb->insert_id;

		do_action_ref_array( 'experiments_experiment_after_save', array( &$this ) );

		wp_cache_delete( $this->id, 'bp_experiments' );

		return true;
	}

	/**
	 * Delete the current experiment.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete() {
		global $wpdb, $bp;

		// Delete experimentmeta for the experiment
		experiments_delete_experimentmeta( $this->id );

		// Fetch the user IDs of all the members of the experiment
		$user_ids    = BP_Experiments_Member::get_experiment_member_ids( $this->id );
		$user_id_str = esc_sql( implode( ',', wp_parse_id_list( $user_ids ) ) );

		// Modify experiment count usermeta for members
		$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_value = meta_value - 1 WHERE meta_key = 'total_experiment_count' AND user_id IN ( {$user_id_str} )" );

		// Now delete all experiment member entries
		BP_Experiments_Member::delete_all( $this->id );

		do_action_ref_array( 'bp_experiments_delete_experiment', array( &$this, $user_ids ) );

		wp_cache_delete( $this->id, 'bp_experiments' );

		// Finally remove the experiment entry from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name} WHERE id = %d", $this->id ) ) )
			return false;

		return true;
	}

	/** Static Methods ****************************************************/

	/**
	 * Get whether a experiment exists for a given slug.
	 *
	 * @param string $slug Slug to check.
	 * @param string $table_name Optional. Name of the table to check
	 *        against. Default: $bp->experiments->table_name.
	 * @return string|null ID of the experiment, if one is found, else null.
	 */
	public static function experiment_exists( $slug, $table_name = false ) {
		global $wpdb, $bp;

		if ( empty( $table_name ) )
			$table_name = $bp->experiments->table_name;

		if ( empty( $slug ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE slug = %s", strtolower( $slug ) ) );
	}

	/**
	 * Get the ID of a experiment by the experiment's slug.
	 *
	 * Alias of {@link BP_Experiments_Experiment::experiment_exists()}.
	 *
	 * @param string $slug See {@link BP_Experiments_Experiment::experiment_exists()}.
	 * @return string|null See {@link BP_Experiments_Experiment::experiment_exists()}.
	 */
	public static function get_id_from_slug( $slug ) {
		return BP_Experiments_Experiment::experiment_exists( $slug );
	}

	/**
	 * Get IDs of users with outstanding invites to a given experiment from a specified user.
	 *
	 * @param int $user_id ID of the inviting user.
	 * @param int $experiment_id ID of the experiment.
	 * @return array IDs of users who have been invited to the experiment by the
	 *         user but have not yet accepted.
	 */
	public static function get_invites( $user_id, $experiment_id ) {
		global $wpdb, $bp;
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d and is_confirmed = 0 AND inviter_id = %d", $experiment_id, $user_id ) );
	}

	/**
	 * Get a list of a user's experiments, filtered by a search string.
	 *
	 * @param string $filter Search term. Matches against 'name' and
	 *        'description' fields.
	 * @param int $user_id ID of the user whose experiments are being searched.
	 *        Default: the displayed user.
	 * @param mixed $order Not used.
	 * @param int $limit Optional. The max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. The page offset of results to return.
	 *        Default: null (no limit).
	 * @return array {
	 *     @type array $experiments Array of matched and paginated experiment objects.
	 *     @type int $total Total count of experiments matching the query.
	 * }
	 */
	public static function filter_user_experiments( $filter, $user_id = 0, $order = false, $limit = null, $page = null ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$filter = esc_sql( like_escape( $filter ) );

		$pag_sql = $order_sql = $hidden_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// Get all the experiment ids for the current user's experiments.
		$gids = BP_Experiments_Member::get_experiment_ids( $user_id );

		if ( empty( $gids['experiments'] ) )
			return false;

		$gids = esc_sql( implode( ',', wp_parse_id_list( $gids['experiments'] ) ) );

		$paged_experiments = $wpdb->get_results( "SELECT id as experiment_id FROM {$bp->experiments->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids}) {$pag_sql}" );
		$total_experiments = $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->experiments->table_name} WHERE ( name LIKE '{$filter}%%' OR description LIKE '{$filter}%%' ) AND id IN ({$gids})" );

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get a list of experiments, filtered by a search string.
	 *
	 * @param string $filter Search term. Matches against 'name' and
	 *        'description' fields.
	 * @param int $limit Optional. The max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. The page offset of results to return.
	 *        Default: null (no limit).
	 * @param string $sort_by Column to sort by. Default: false (default
	 *        sort).
	 * @param string $order ASC or DESC. Default: false (default sort).
	 * @return array {
	 *     @type array $experiments Array of matched and paginated experiment objects.
	 *     @type int $total Total count of experiments matching the query.
	 * }
	 */
	public static function search_experiments( $filter, $limit = null, $page = null, $sort_by = false, $order = false ) {
		global $wpdb, $bp;

		$filter = esc_sql( like_escape( $filter ) );

		$pag_sql = $order_sql = $hidden_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $sort_by ) && !empty( $order ) ) {
			$sort_by   = esc_sql( $sort_by );
			$order     = esc_sql( $order );
			$order_sql = "ORDER BY {$sort_by} {$order}";
		}

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "AND status != 'hidden'";

		$paged_experiments = $wpdb->get_results( "SELECT id as experiment_id FROM {$bp->experiments->table_name} WHERE ( name LIKE '%%{$filter}%%' OR description LIKE '%%{$filter}%%' ) {$hidden_sql} {$order_sql} {$pag_sql}" );
		$total_experiments = $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->experiments->table_name} WHERE ( name LIKE '%%{$filter}%%' OR description LIKE '%%{$filter}%%' ) {$hidden_sql}" );

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Check for the existence of a slug.
	 *
	 * @param string $slug Slug to check.
	 * @return string|null The slug, if found. Otherwise null.
	 */
	public static function check_slug( $slug ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$bp->experiments->table_name} WHERE slug = %s", $slug ) );
	}

	/**
	 * Get the slug for a given experiment ID.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return string|null The slug, if found. Otherwise null.
	 */
	public static function get_slug( $experiment_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$bp->experiments->table_name} WHERE id = %d", $experiment_id ) );
	}

	/**
	 * Check whether a given experiment has any members.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return bool True if the experiment has members, otherwise false.
	 */
	public static function has_members( $experiment_id ) {
		global $wpdb, $bp;

		$members = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d", $experiment_id ) );

		if ( empty( $members ) )
			return false;

		return true;
	}

	/**
	 * Check whether a experiment has outstanding membership requests.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return int|null The number of outstanding requests, or null if
	 *         none are found.
	 */
	public static function has_membership_requests( $experiment_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_confirmed = 0", $experiment_id ) );
	}

	/**
	 * Get outstanding membership requests for a experiment.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @param int $limit Optional. Max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. Page offset of results returned. Default:
	 *        null (no limit).
	 * @return array {
	 *     @type array $requests The requested page of located requests.
	 *     @type int $total Total number of requests outstanding for the
	 *           experiment.
	 * }
	 */
	public static function get_membership_requests( $experiment_id, $limit = null, $page = null ) {
		global $wpdb, $bp;

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$paged_requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_confirmed = 0 AND inviter_id = 0{$pag_sql}", $experiment_id ) );
		$total_requests = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_confirmed = 0 AND inviter_id = 0", $experiment_id ) );

		return array( 'requests' => $paged_requests, 'total' => $total_requests );
	}

	/**
	 * Query for experiments.
	 *
	 * @see WP_Meta_Query::queries for a description of the 'meta_query'
	 *      parameter format.
	 *
	 * @param array {
	 *     Array of parameters. All items are optional.
	 *     @type string $type Optional. Shorthand for certain orderby/
	 *           order combinations. 'newest', 'active', 'popular',
	 *           'alphabetical', 'random'. When present, will override
	 *           orderby and order params. Default: null.
	 *     @type string $orderby Optional. Property to sort by.
	 *           'date_created', 'last_activity', 'total_member_count',
	 *           'name', 'random'. Default: 'date_created'.
	 *     @type string $order Optional. Sort order. 'ASC' or 'DESC'.
	 *           Default: 'DESC'.
	 *     @type int $per_page Optional. Number of items to return per page
	 *           of results. Default: null (no limit).
	 *     @type int $page Optional. Page offset of results to return.
	 *           Default: null (no limit).
	 *     @type int $user_id Optional. If provided, results will be limited
	 *           to experiments of which the specified user is a member. Default:
	 *           null.
	 *     @type string $search_terms Optional. If provided, only experiments
	 *           whose names or descriptions match the search terms will be
	 *           returned. Default: false.
	 *     @type array $meta_query Optional. An array of meta_query
	 *           conditions. See {@link WP_Meta_Query::queries} for
	 *           description.
	 *     @type array|string Optional. Array or comma-separated list of
	 *           experiment IDs. Results will be limited to experiments within the
	 *           list. Default: false.
	 *     @type bool $populate_extras Whether to fetch additional
	 *           information (such as member count) about experiments. Default:
	 *           true.
	 *     @type array|string Optional. Array or comma-separated list of
	 *           experiment IDs. Results will exclude the listed experiments.
	 *           Default: false.
	 *     @type bool $show_hidden Whether to include hidden experiments in
	 *           results. Default: false.
	 * }
	 * @return array {
	 *     @type array $experiments Array of experiment objects returned by the
	 *           paginated query.
	 *     @type int $total Total count of all experiments matching non-
	 *           paginated query params.
	 * }
	 */
	public static function get( $args = array() ) {
		global $wpdb, $bp;

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.7', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'type',
				1 => 'per_page',
				2 => 'page',
				3 => 'user_id',
				4 => 'search_terms',
				5 => 'include',
				6 => 'populate_extras',
				7 => 'exclude',
				8 => 'show_hidden',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'type'              => null,
			'orderby'           => 'date_created',
			'order'             => 'DESC',
			'per_page'          => null,
			'page'              => null,
			'user_id'           => 0,
			'search_terms'      => false,
			'meta_query'        => false,
			'include'           => false,
			'populate_extras'   => true,
			'update_meta_cache' => true,
			'exclude'           => false,
			'show_hidden'       => false,
		);

		$r = wp_parse_args( $args, $defaults );

		$sql       = array();
		$total_sql = array();

		$sql['select'] = "SELECT DISTINCT g.id, g.*, gm1.meta_value AS total_member_count, gm2.meta_value AS last_activity";
		$sql['from']   = " FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2,";

		if ( ! empty( $r['user_id'] ) ) {
			$sql['members_from'] = " {$bp->experiments->table_name_members} m,";
		}

		$sql['experiment_from'] = " {$bp->experiments->table_name} g WHERE";

		if ( ! empty( $r['user_id'] ) ) {
			$sql['user_where'] = " g.id = m.experiment_id AND";
		}

		$sql['where'] = " g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'";

		if ( empty( $r['show_hidden'] ) ) {
			$sql['hidden'] = " AND g.status != 'hidden'";
		}

		if ( ! empty( $r['search_terms'] ) ) {
			$search_terms = esc_sql( like_escape( $r['search_terms'] ) );
			$sql['search'] = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$sql['from'] .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$sql['meta'] = $meta_query_sql['where'];
		}

		if ( ! empty( $r['user_id'] ) ) {
			$sql['user'] = $wpdb->prepare( " AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $r['user_id'] );
		}

		if ( ! empty( $r['include'] ) ) {
			$include        = implode( ',', wp_parse_id_list( $r['include'] ) );
			$sql['include'] = " AND g.id IN ({$include})";
		}

		if ( ! empty( $r['exclude'] ) ) {
			$exclude        = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$sql['exclude'] = " AND g.id NOT IN ({$exclude})";
		}

		/** Order/orderby ********************************************/

		$order   = $r['order'];
		$orderby = $r['orderby'];

		// If a 'type' parameter was passed, parse it and overwrite
		// 'order' and 'orderby' params passed to the function
		if (  ! empty( $r['type'] ) ) {
			$order_orderby = self::convert_type_to_order_orderby( $r['type'] );

			// If an invalid type is passed, $order_orderby will be
			// an array with empty values. In this case, we stick
			// with the default values of $order and $orderby
			if ( ! empty( $order_orderby['order'] ) ) {
				$order = $order_orderby['order'];
			}

			if ( ! empty( $order_orderby['orderby'] ) ) {
				$orderby = $order_orderby['orderby'];
			}
		}

		// Sanitize 'order'
		$order = bp_esc_sql_order( $order );

		// Convert 'orderby' into the proper ORDER BY term
		$orderby = self::convert_orderby_to_order_by_term( $orderby );

		// Random order is a special case
		if ( 'rand()' === $orderby ) {
			$sql[] = "ORDER BY rand()";
		} else {
			$sql[] = "ORDER BY {$orderby} {$order}";
		}

		if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) ) {
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $r['page'] - 1 ) * $r['per_page']), intval( $r['per_page'] ) );
		}

		// Get paginated results
		$paged_experiments_sql = apply_filters( 'bp_experiments_get_paged_experiments_sql', join( ' ', (array) $sql ), $sql, $r );
		$paged_experiments     = $wpdb->get_results( $paged_experiments_sql );

		$total_sql['select'] = "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name} g, {$bp->experiments->table_name_members} gm1, {$bp->experiments->table_name_experimentmeta} gm2";

		if ( ! empty( $r['user_id'] ) ) {
			$total_sql['select'] .= ", {$bp->experiments->table_name_members} m";
		}

		if ( ! empty( $sql['hidden'] ) ) {
			$total_sql['where'][] = "g.status != 'hidden'";
		}

		if ( ! empty( $sql['search'] ) ) {
			$total_sql['where'][] = "( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( ! empty( $r['user_id'] ) ) {
			$total_sql['where'][] = $wpdb->prepare( "m.experiment_id = g.id AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $r['user_id'] );
		}

		// Temporary implementation of meta_query for total count
		// See #5099
		if ( ! empty( $meta_query_sql['where'] ) ) {
			// Join the experimentmeta table
			$total_sql['select'] .= ", ". substr( $meta_query_sql['join'], 0, -2 );

			// Modify the meta_query clause from paged_sql for our syntax
			$meta_query_clause = preg_replace( '/^\s*AND/', '', $meta_query_sql['where'] );
			$total_sql['where'][] = $meta_query_clause;
		}

		// Already escaped in the paginated results block
		if ( ! empty( $include ) ) {
			$total_sql['where'][] = "g.id IN ({$include})";
		}

		// Already escaped in the paginated results block
		if ( ! empty( $exclude ) ) {
			$total_sql['where'][] = "g.id NOT IN ({$exclude})";
		}

		$total_sql['where'][] = "g.id = gm1.experiment_id";
		$total_sql['where'][] = "g.id = gm2.experiment_id";
		$total_sql['where'][] = "gm2.meta_key = 'last_activity'";

		$t_sql = $total_sql['select'];

		if ( ! empty( $total_sql['where'] ) ) {
			$t_sql .= " WHERE " . join( ' AND ', (array) $total_sql['where'] );
		}

		// Get total experiment results
		$total_experiments_sql = apply_filters( 'bp_experiments_get_total_experiments_sql', $t_sql, $total_sql, $r );
		$total_experiments     = $wpdb->get_var( $total_experiments_sql );

		$experiment_ids = array();
		foreach ( (array) $paged_experiments as $experiment ) {
			$experiment_ids[] = $experiment->id;
		}

		// Populate some extra information instead of querying each time in the loop
		if ( !empty( $r['populate_extras'] ) ) {
			$paged_experiments = BP_Experiments_Experiment::get_experiment_extras( $paged_experiments, $experiment_ids, $r['type'] );
		}

		// Grab all experimentmeta
		if ( ! empty( $r['update_meta_cache'] ) ) {
			bp_experiments_update_meta_cache( $experiment_ids );
		}

		unset( $sql, $total_sql );

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Activity_Activity::get()
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Activity_Activity::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since BuddyPress (1.8.0)
	 * @access protected
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *        documentation for {@link WP_Meta_Query} for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	protected static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$experiments_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->experiment
			$wpdb->experimentmeta = buddypress()->experiments->table_name_experimentmeta;

			$meta_sql = $experiments_meta_query->get_sql( 'experiment', 'g', 'id' );

			// BP_Experiments_Experiment::get uses the comma syntax for table
			// joins, which means that we have to do some regex to
			// convert the INNER JOIN and move the ON clause to a
			// WHERE condition
			//
			// @todo It may be better in the long run to refactor
			// the more general query syntax to accord better with
			// BP/WP convention
			preg_match_all( '/INNER JOIN (.*) ON/', $meta_sql['join'], $matches_a );
			preg_match_all( '/ON \((.*)\)/', $meta_sql['join'], $matches_b );

			if ( ! empty( $matches_a[1] ) && ! empty( $matches_b[1] ) ) {
				$sql_array['join']  = implode( ',', $matches_a[1] ). ', ';

				$sql_array['where'] = '';

				$meta_query_where_clauses = explode( "\n", $meta_sql['where'] );
				foreach( $matches_b[1] as $key => $experiment_id_clause ) {
					$sql_array['where'] .= ' ' . preg_replace( '/^(AND\s+[\(\s]+)/', '$1' . $experiment_id_clause . ' AND ', ltrim( $meta_query_where_clauses[ $key ] ) );
				}

			}
		}

		return $sql_array;
	}

	/**
	 * Convert the 'type' parameter to 'order' and 'orderby'.
	 *
	 * @since BuddyPress (1.8.0)
	 * @access protected
	 *
	 * @param string $type The 'type' shorthand param.
	 * @return array {
	 *	@type string $order SQL-friendly order string.
	 *	@type string $orderby SQL-friendly orderby column name.
	 * }
	 */
	protected static function convert_type_to_order_orderby( $type = '' ) {
		$order = $orderby = '';

		switch ( $type ) {
			case 'newest' :
				$order   = 'DESC';
				$orderby = 'date_created';
				break;

			case 'active' :
				$order   = 'DESC';
				$orderby = 'last_activity';
				break;

			case 'popular' :
				$order   = 'DESC';
				$orderby = 'total_member_count';
				break;

			case 'alphabetical' :
				$order   = 'ASC';
				$orderby = 'name';
				break;

			case 'random' :
				$order   = '';
				$orderby = 'random';
				break;
		}

		return array( 'order' => $order, 'orderby' => $orderby );
	}

	/**
	 * Convert the 'orderby' param into a proper SQL term/column.
	 *
	 * @since BuddyPress (1.8.0)
	 * @access protected
	 *
	 * @param string $orderby Orderby term as passed to get().
	 * @return string $order_by_term SQL-friendly orderby term.
	 */
	protected static function convert_orderby_to_order_by_term( $orderby ) {
		$order_by_term = '';

		switch ( $orderby ) {
			case 'date_created' :
			default :
				$order_by_term = 'g.date_created';
				break;

			case 'last_activity' :
				$order_by_term = 'last_activity';
				break;

			case 'total_member_count' :
				$order_by_term = 'CONVERT(gm1.meta_value, SIGNED)';
				break;

			case 'name' :
				$order_by_term = 'g.name';
				break;

			case 'random' :
				$order_by_term = 'rand()';
				break;
		}

		return $order_by_term;
	}

	/**
	 * Get a list of experiments, sorted by those that have the most legacy forum topics.
	 *
	 * @param int $limit Optional. The max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. The page offset of results to return.
	 *        Default: null (no limit).
	 * @param int $user_id Optional. If present, experiments will be limited to
	 *        those of which the specified user is a member.
	 * @param string $search_terms Optional. Limit experiments to those whose
	 *        name or description field contain the search string.
	 * @param bool $populate_extras Optional. Whether to fetch extra
	 *        information about the experiments. Default: true.
	 * @param string|array Optional. Array or comma-separated list of experiment
	 *        IDs to exclude from results.
	 * @return array {
	 *     @type array $experiments Array of experiment objects returned by the
	 *           paginated query.
	 *     @type int $total Total count of all experiments matching non-
	 *           paginated query params.
	 * }
	 */
	public static function get_by_most_forum_topics( $limit = null, $page = null, $user_id = 0, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp, $bbdb;

		if ( empty( $bbdb ) )
			do_action( 'bbpress_init' );

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = esc_sql( like_escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !empty( $user_id ) ) {
			$user_id      = absint( esc_sql( $user_id ) );
			$paged_experiments = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bp->experiments->table_name_members} m, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_experiments = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql}" );
		} else {
			$paged_experiments = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_experiments = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_experiments as $experiment ) {
				$experiment_ids[] = $experiment->id;
			}
			$paged_experiments = BP_Experiments_Experiment::get_experiment_extras( $paged_experiments, $experiment_ids, 'newest' );
		}

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get a list of experiments, sorted by those that have the most legacy forum posts.
	 *
	 * @param int $limit Optional. The max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. The page offset of results to return.
	 *        Default: null (no limit).
	 * @param int $user_id Optional. If present, experiments will be limited to
	 *        those of which the specified user is a member.
	 * @param string $search_terms Optional. Limit experiments to those whose
	 *        name or description field contain the search string.
	 * @param bool $populate_extras Optional. Whether to fetch extra
	 *        information about the experiments. Default: true.
	 * @param string|array Optional. Array or comma-separated list of experiment
	 *        IDs to exclude from results.
	 * @return array {
	 *     @type array $experiments Array of experiment objects returned by the
	 *           paginated query.
	 *     @type int $total Total count of all experiments matching non-
	 *           paginated query params.
	 * }
	 */
	public static function get_by_most_forum_posts( $limit = null, $page = null, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp, $bbdb;

		if ( empty( $bbdb ) )
			do_action( 'bbpress_init' );

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = esc_sql( like_escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !empty( $user_id ) ) {
			$user_id = esc_sql( $user_id );
			$paged_experiments = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bp->experiments->table_name_members} m, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_experiments = $wpdb->get_results( "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bp->experiments->table_name_members} m, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} " );
		} else {
			$paged_experiments = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_experiments = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_experimentmeta} gm3, {$bbdb->forums} f, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND g.id = gm3.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_experiments as $experiment ) {
				$experiment_ids[] = $experiment->id;
			}
			$paged_experiments = BP_Experiments_Experiment::get_experiment_extras( $paged_experiments, $experiment_ids, 'newest' );
		}

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get a list of experiments whose names start with a given letter.
	 *
	 * @param string $letter The letter.
	 * @param int $limit Optional. The max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. The page offset of results to return.
	 *        Default: null (no limit).
	 * @param bool $populate_extras Optional. Whether to fetch extra
	 *        information about the experiments. Default: true.
	 * @param string|array Optional. Array or comma-separated list of experiment
	 *        IDs to exclude from results.
	 * @return array {
	 *     @type array $experiments Array of experiment objects returned by the
	 *           paginated query.
	 *     @type int $total Total count of all experiments matching non-
	 *           paginated query params.
	 * }
	 */
	public static function get_by_letter( $letter, $limit = null, $page = null, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $exclude_sql = '';

		// Multibyte compliance
		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $letter, 'UTF-8' ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		} else {
			if ( strlen( $letter ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		}

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = " AND status != 'hidden'";

		$letter = esc_sql( like_escape( $letter ) );

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql      = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$total_experiments = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE '{$letter}%%' {$hidden_sql} {$exclude_sql}" );

		$paged_experiments = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE '{$letter}%%' {$hidden_sql} {$exclude_sql} ORDER BY g.name ASC {$pag_sql}" );

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_experiments as $experiment ) {
				$experiment_ids[] = $experiment->id;
			}
			$paged_experiments = BP_Experiments_Experiment::get_experiment_extras( $paged_experiments, $experiment_ids, 'newest' );
		}

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get a list of random experiments.
	 *
	 * Use BP_Experiments_Experiment::get() with 'type' = 'random' instead.
	 *
	 * @param int $limit Optional. The max number of results to return.
	 *        Default: null (no limit).
	 * @param int $page Optional. The page offset of results to return.
	 *        Default: null (no limit).
	 * @param int $user_id Optional. If present, experiments will be limited to
	 *        those of which the specified user is a member.
	 * @param string $search_terms Optional. Limit experiments to those whose
	 *        name or description field contain the search string.
	 * @param bool $populate_extras Optional. Whether to fetch extra
	 *        information about the experiments. Default: true.
	 * @param string|array Optional. Array or comma-separated list of experiment
	 *        IDs to exclude from results.
	 * @return array {
	 *     @type array $experiments Array of experiment objects returned by the
	 *           paginated query.
	 *     @type int $total Total count of all experiments matching non-
	 *           paginated query params.
	 * }
	 */
	public static function get_random( $limit = null, $page = null, $user_id = 0, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $search_sql = $exclude_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = "AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms = esc_sql( like_escape( $search_terms ) );
			$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
		}

		if ( !empty( $exclude ) ) {
			$exclude     = wp_parse_id_list( $exclude );
			$exclude     = esc_sql( implode( ',', $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !empty( $user_id ) ) {
			$user_id = esc_sql( $user_id );
			$paged_experiments = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY rand() {$pag_sql}" );
			$total_experiments = $wpdb->get_var( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m LEFT JOIN {$bp->experiments->table_name_experimentmeta} gm ON m.experiment_id = gm.experiment_id INNER JOIN {$bp->experiments->table_name} g ON m.experiment_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql}" );
		} else {
			$paged_experiments = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name} g WHERE g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY rand() {$pag_sql}" );
			$total_experiments = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->experiments->table_name_experimentmeta} gm INNER JOIN {$bp->experiments->table_name} g ON gm.experiment_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_experiments as $experiment ) {
				$experiment_ids[] = $experiment->id;
			}
			$paged_experiments = BP_Experiments_Experiment::get_experiment_extras( $paged_experiments, $experiment_ids, 'newest' );
		}

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Fetch extra data for a list of experiments.
	 *
	 * This method is used throughout the class, by methods that take a
	 * $populate_extras parameter.
	 *
	 * Data fetched:
	 *
	 *     - Logged-in user's status within each experiment (is_member,
	 *       is_confirmed, is_pending, is_banned)
	 *
	 * @param array $paged_experiments Array of experiments.
	 * @param string|array Array or comma-separated list of IDs matching
	 *        $paged_experiments.
	 * @param string $type Not used.
	 * @return array $paged_experiments
	 */
	public static function get_experiment_extras( &$paged_experiments, &$experiment_ids, $type = false ) {
		global $bp, $wpdb;

		if ( empty( $experiment_ids ) )
			return $paged_experiments;

		// Sanitize experiment IDs
		$experiment_ids = implode( ',', wp_parse_id_list( $experiment_ids ) );

		// Fetch the logged-in user's status within each experiment
		if ( is_user_logged_in() ) {
			$user_status_results = $wpdb->get_results( $wpdb->prepare( "SELECT experiment_id, is_confirmed, invite_sent FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id IN ( {$experiment_ids} ) AND is_banned = 0", bp_loggedin_user_id() ) );
		} else {
			$user_status_results = array();
		}

		// Reindex
		$user_status = array();
		foreach ( $user_status_results as $user_status_result ) {
			$user_status[ $user_status_result->experiment_id ] = $user_status_result;
		}

		for ( $i = 0, $count = count( $paged_experiments ); $i < $count; ++$i ) {
			$is_member = $is_invited = $is_pending = '0';
			$gid = $paged_experiments[ $i ]->id;

			if ( isset( $user_status[ $gid ] ) ) {

				// is_confirmed means the user is a member
				if ( $user_status[ $gid ]->is_confirmed ) {
					$is_member = '1';

				// invite_sent means the user has been invited
				} else if ( $user_status[ $gid ]->invite_sent ) {
					$is_invited = '1';

				// User has sent request, but has not been confirmed
				} else {
					$is_pending = '1';
				}
			}

			$paged_experiments[ $i ]->is_member = $is_member;
			$paged_experiments[ $i ]->is_invited = $is_invited;
			$paged_experiments[ $i ]->is_pending = $is_pending;
		}

		if ( is_user_logged_in() ) {
			$user_banned = $wpdb->get_col( $wpdb->prepare( "SELECT experiment_id FROM {$bp->experiments->table_name_members} WHERE is_banned = 1 AND user_id = %d AND experiment_id IN ( {$experiment_ids} )", bp_loggedin_user_id() ) );
		} else {
			$user_banned = array();
		}

		for ( $i = 0, $count = count( $paged_experiments ); $i < $count; ++$i ) {
			$paged_experiments[$i]->is_banned = false;

			foreach ( (array) $user_banned as $experiment_id ) {
				if ( $experiment_id == $paged_experiments[$i]->id ) {
					$paged_experiments[$i]->is_banned = true;
				}
			}
		}

		return $paged_experiments;
	}

	/**
	 * Delete all invitations to a given experiment.
	 *
	 * @param int $experiment_id ID of the experiment whose invitations are being
	 *        deleted.
	 * @return int|null Number of rows records deleted on success, null on
	 *         failure.
	 */
	public static function delete_all_invites( $experiment_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND invite_sent = 1", $experiment_id ) );
	}

	/**
	 * Get a total experiment count for the site.
	 *
	 * Will include hidden experiments in the count only if
	 * current_user_can( 'bp_moderate' ).
	 *
	 * @return int Experiment count.
	 */
	public static function get_total_experiment_count() {
		global $wpdb, $bp;

		$hidden_sql = '';
		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "WHERE status != 'hidden'";

		return $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->experiments->table_name} {$hidden_sql}" );
	}

	/**
	 * Get global count of forum topics in public experiments (legacy forums).
	 *
	 * @param $type Optional. If 'unreplied', count will be limited to
	 *        those topics that have received no replies.
	 * @return int Forum topic count.
	 */
	public static function get_global_forum_topic_count( $type ) {
		global $bbdb, $wpdb, $bp;

		if ( 'unreplied' == $type )
			$bp->experiments->filter_sql = ' AND t.topic_posts = 1';

		// https://buddypress.trac.wordpress.org/ticket/4306
		$extra_sql = apply_filters( 'get_global_forum_topic_count_extra_sql', $bp->experiments->filter_sql, $type );

		// Make sure the $extra_sql begins with an AND
		if ( 'AND' != substr( trim( strtoupper( $extra_sql ) ), 0, 3 ) )
			$extra_sql = ' AND ' . $extra_sql;

		return $wpdb->get_var( "SELECT COUNT(t.topic_id) FROM {$bbdb->topics} AS t, {$bp->experiments->table_name} AS g LEFT JOIN {$bp->experiments->table_name_experimentmeta} AS gm ON g.id = gm.experiment_id WHERE (gm.meta_key = 'forum_id' AND gm.meta_value = t.forum_id) AND g.status = 'public' AND t.topic_status = '0' AND t.topic_sticky != '2' {$extra_sql} " );
	}

	/**
	 * Get the member count for a experiment.
	 *
	 * @param int $experiment_id Experiment ID.
	 * @return int Count of confirmed members for the experiment.
	 */
	public static function get_total_member_count( $experiment_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_confirmed = 1 AND is_banned = 0", $experiment_id ) );
	}

    
	/**
	 * Get the variable count for a experiment.
	 *
	 * @param int $experiment_id experiment ID.
	 * @return int Count of confirmed variables for the experiment.
	 */
	public static function get_total_variable_count( $experiment_id ) {
		global $wpdb, $bp;
        
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM wp_bp_experiments_variables WHERE experiment_id = %d", $experiment_id ) );
	}
    
	/**
	 * Get the report count for a experiment.
	 *
	 * @param int $experiment_id experiment ID.
	 * @return int Count of confirmed reports for the experiment.
	 */
	public static function get_total_report_count( $experiment_id ) {
		global $wpdb, $bp;
        
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM wp_bp_experiments_report WHERE experiment_id = %d", $experiment_id ) );
	}
    
    
	/**
	 * Get a total count of all topics of a given status, across experiments/forums
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @param string $status Which experiment type to count. 'public', 'private',
	 *        'hidden', or 'all'. Default: 'public'.
	 * @return int The topic count
	 */
	public static function get_global_topic_count( $status = 'public', $search_terms = false ) {
		global $bbdb, $wpdb, $bp;

		switch ( $status ) {
			case 'all' :
				$status_sql = '';
				break;

			case 'hidden' :
				$status_sql = "AND g.status = 'hidden'";
				break;

			case 'private' :
				$status_sql = "AND g.status = 'private'";
				break;

			case 'public' :
			default :
				$status_sql = "AND g.status = 'public'";
				break;
		}

		$sql = array();

		$sql['select'] = "SELECT COUNT(t.topic_id)";
		$sql['from']   = "FROM {$bbdb->topics} AS t INNER JOIN {$bp->experiments->table_name_experimentmeta} AS gm ON t.forum_id = gm.meta_value INNER JOIN {$bp->experiments->table_name} AS g ON gm.experiment_id = g.id";
		$sql['where']  = "WHERE gm.meta_key = 'forum_id' {$status_sql} AND t.topic_status = '0' AND t.topic_sticky != '2'";

		if ( !empty( $search_terms ) ) {
			$st = esc_sql( like_escape( $search_terms ) );
			$sql['where'] .= " AND (  t.topic_title LIKE '%{$st}%' )";
		}

		return $wpdb->get_var( implode( ' ', $sql ) );
	}

	/**
	 * Get an array containing ids for each experiment type.
	 *
	 * A bit of a kludge workaround for some issues
	 * with bp_has_experiments().
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @return array
	 */
	public static function get_experiment_type_ids() {
		global $wpdb, $bp;

		$ids = array();

		$ids['all']     = $wpdb->get_col( "SELECT id FROM {$bp->experiments->table_name}" );
		$ids['public']  = $wpdb->get_col( "SELECT id FROM {$bp->experiments->table_name} WHERE status = 'public'" );
		$ids['private'] = $wpdb->get_col( "SELECT id FROM {$bp->experiments->table_name} WHERE status = 'private'" );
		$ids['hidden']  = $wpdb->get_col( "SELECT id FROM {$bp->experiments->table_name} WHERE status = 'hidden'" );

		return $ids;
	}
}

/**
 * Query for the members of a experiment.
 *
 * Special notes about the experiment members data schema:
 * - *Members* are entries with is_confirmed = 1
 * - *Pending requests* are entries with is_confirmed = 0 and inviter_id = 0
 * - *Pending and sent invitations* are entries with is_confirmed = 0 and
 *   inviter_id != 0 and invite_sent = 1
 * - *Pending and unsent invitations* are entries with is_confirmed = 0 and
 *   inviter_id != 0 and invite_sent = 0
 * - *Membership requests* are entries with is_confirmed = 0 and
 *   inviter_id = 0 (and invite_sent = 0)
 *
 * @since BuddyPress (1.8.0)
 *
 * @param array $args {
 *     Array of arguments. Accepts all arguments from
 *     {@link BP_User_Query}, with the following additions:
 *     @type int $experiment_id ID of the experiment to limit results to.
 *     @type array $experiment_role Array of experiment roles to match ('member',
 *           'mod', 'admin', 'banned'). Default: array( 'member' ).
 *     @type bool $is_confirmed Whether to limit to confirmed members.
 *           Default: true.
 *     @type string $type Sort order. Accepts any value supported by
 *           {@link BP_User_Query}, in addition to 'last_joined' and
 *           'first_joined'. Default: 'last_joined'.
 * }
 */
class BP_Experiment_Member_Query extends BP_User_Query {

	/**
	 * Array of experiment member ids, cached to prevent redundant lookups.
	 *
	 * @since BuddyPress (1.8.1)
	 * @var null|array Null if not yet defined, otherwise an array of ints.
	 */
	protected $experiment_member_ids;

	/**
	 * Set up action hooks.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function setup_hooks() {
		// Take this early opportunity to set the default 'type' param
		// to 'last_joined', which will ensure that BP_User_Query
		// trusts our order and does not try to apply its own
		if ( empty( $this->query_vars_raw['type'] ) ) {
			$this->query_vars_raw['type'] = 'last_joined';
		}

		// Set the sort order
		add_action( 'bp_pre_user_query', array( $this, 'set_orderby' ) );

		// Set up our populate_extras method
		add_action( 'bp_user_query_populate_extras', array( $this, 'populate_experiment_member_extras' ), 10, 2 );
	}

	/**
	 * Get a list of user_ids to include in the IN clause of the main query.
	 *
	 * Overrides BP_User_Query::get_include_ids(), adding our additional
	 * experiment-member logic.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param array $include Existing experiment IDs in the $include parameter,
	 *        as calculated in BP_User_Query.
	 * @return array
	 */
	public function get_include_ids( $include = array() ) {
		// The following args are specific to experiment member queries, and
		// are not present in the query_vars of a normal BP_User_Query.
		// We loop through to make sure that defaults are set (though
		// values passed to the constructor will, as usual, override
		// these defaults).
		$this->query_vars = wp_parse_args( $this->query_vars, array(
			'experiment_id'     => 0,
			'experiment_role'   => array( 'member' ),
			'is_confirmed' => true,
			'invite_sent'  => null,
			'inviter_id'   => null,
			'type'         => 'last_joined',
		) );

		$experiment_member_ids = $this->get_experiment_member_ids();

		// If the experiment member query returned no users, bail with an
		// array that will guarantee no matches for BP_User_Query
		if ( empty( $experiment_member_ids ) ) {
			return array( 0 );
		}

		if ( ! empty( $include ) ) {
			$experiment_member_ids = array_intersect( $include, $experiment_member_ids );
		}

		return $experiment_member_ids;
	}

	/**
	 * Get the members of the queried experiment.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @return array $ids User IDs of relevant experiment member ids.
	 */
	protected function get_experiment_member_ids() {
		global $wpdb;

		if ( is_array( $this->experiment_member_ids ) ) {
			return $this->experiment_member_ids;
		}

		$bp  = buddypress();
		$sql = array(
			'select'  => "SELECT user_id FROM {$bp->experiments->table_name_members}",
			'where'   => array(),
			'orderby' => '',
			'order'   => '',
		);

		/** WHERE clauses *****************************************************/

		// Experiment id
		$sql['where'][] = $wpdb->prepare( "experiment_id = %d", $this->query_vars['experiment_id'] );

		// is_confirmed
		$is_confirmed = ! empty( $this->query_vars['is_confirmed'] ) ? 1 : 0;
		$sql['where'][] = $wpdb->prepare( "is_confirmed = %d", $is_confirmed );

		// invite_sent
		if ( ! is_null( $this->query_vars['invite_sent'] ) ) {
			$invite_sent = ! empty( $this->query_vars['invite_sent'] ) ? 1 : 0;
			$sql['where'][] = $wpdb->prepare( "invite_sent = %d", $invite_sent );
		}

		// inviter_id
		if ( ! is_null( $this->query_vars['inviter_id'] ) ) {
			$inviter_id = $this->query_vars['inviter_id'];

			// Empty: inviter_id = 0. (pass false, 0, or empty array)
			if ( empty( $inviter_id ) ) {
				$sql['where'][] = "inviter_id = 0";

			// The string 'any' matches any non-zero value (inviter_id != 0)
			} else if ( 'any' === $inviter_id ) {
				$sql['where'][] = "inviter_id != 0";

			// Assume that a list of inviter IDs has been passed
			} else {
				// Parse and sanitize
				$inviter_ids = wp_parse_id_list( $inviter_id );
				if ( ! empty( $inviter_ids ) ) {
					$inviter_ids_sql = implode( ',', $inviter_ids );
					$sql['where'][] = "inviter_id IN ({$inviter_ids_sql})";
				}
			}
		}

		// Role information is stored as follows: admins have
		// is_admin = 1, mods have is_mod = 1, banned have is_banned =
		// 1, and members have all three set to 0.
		$roles = !empty( $this->query_vars['experiment_role'] ) ? $this->query_vars['experiment_role'] : array();
		if ( is_string( $roles ) ) {
			$roles = explode( ',', $roles );
		}

		// Sanitize: Only 'admin', 'mod', 'member', and 'banned' are valid
		$allowed_roles = array( 'admin', 'mod', 'member', 'banned' );
		foreach ( $roles as $role_key => $role_value ) {
			if ( ! in_array( $role_value, $allowed_roles ) ) {
				unset( $roles[ $role_key ] );
			}
		}

		$roles = array_unique( $roles );

		// When querying for a set of roles containing 'member' (for
		// which there is no dedicated is_ column), figure out a list
		// of columns *not* to match
		$roles_sql = '';
		if ( in_array( 'member', $roles ) ) {
			$role_columns = array();
			foreach ( array_diff( $allowed_roles, $roles ) as $excluded_role ) {
				$role_columns[] = 'is_' . $excluded_role . ' = 0';
			}

			if ( ! empty( $role_columns ) ) {
				$roles_sql = '(' . implode( ' AND ', $role_columns ) . ')';
			}

		// When querying for a set of roles *not* containing 'member',
		// simply construct a list of is_* = 1 clauses
		} else {
			$role_columns = array();
			foreach ( $roles as $role ) {
				$role_columns[] = 'is_' . $role . ' = 1';
			}

			if ( ! empty( $role_columns ) ) {
				$roles_sql = '(' . implode( ' OR ', $role_columns ) . ')';
			}
		}

		if ( ! empty( $roles_sql ) ) {
			$sql['where'][] = $roles_sql;
		}

		$sql['where'] = ! empty( $sql['where'] ) ? 'WHERE ' . implode( ' AND ', $sql['where'] ) : '';

		// We fetch experiment members in order of last_joined, regardless
		// of 'type'. If the 'type' value is not 'last_joined' or
		// 'first_joined', the order will be overridden in
		// BP_Experiment_Member_Query::set_orderby()
		$sql['orderby'] = "ORDER BY date_modified";
		$sql['order']   = 'first_joined' === $this->query_vars['type'] ? 'ASC' : 'DESC';

		$this->experiment_member_ids = $wpdb->get_col( "{$sql['select']} {$sql['where']} {$sql['orderby']} {$sql['order']}" );

		/**
		 * Use this filter to build a custom query (such as when you've
		 * defined a custom 'type').
		 */
		$this->experiment_member_ids = apply_filters( 'bp_experiment_member_query_experiment_member_ids', $this->experiment_member_ids, $this );

		return $this->experiment_member_ids;
	}

	/**
	 * Tell BP_User_Query to order by the order of our query results.
	 *
	 * We only override BP_User_Query's native ordering in case of the
	 * 'last_joined' and 'first_joined' $type parameters.
	 *
	 * @param BP_User_Query $query BP_User_Query object.
	 */
	public function set_orderby( $query ) {
		$gm_ids = $this->get_experiment_member_ids();
		if ( empty( $gm_ids ) ) {
			$gm_ids = array( 0 );
		}

		// For 'last_joined' and 'first_joined' types, we force
		// the order according to the query performed in
		// BP_Experiment_Member_Query::get_experiment_members(). Otherwise, fall
		// through and let BP_User_Query do its own ordering.
		if ( in_array( $query->query_vars['type'], array( 'last_joined', 'first_joined' ) ) ) {

			// The first param in the FIELD() clause is the sort column id
			$gm_ids = array_merge( array( 'u.id' ), wp_parse_id_list( $gm_ids ) );
			$gm_ids_sql = implode( ',', $gm_ids );

			$query->uid_clauses['orderby'] = "ORDER BY FIELD(" . $gm_ids_sql . ")";
		}

		// Prevent this filter from running on future BP_User_Query
		// instances on the same page
		remove_action( 'bp_pre_user_query', array( $this, 'set_orderby' ) );
	}

	/**
	 * Fetch additional data required in bp_experiment_has_members() loops.
	 *
	 * Additional data fetched:
	 *
	 *      - is_banned
	 *      - date_modified
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param object $query BP_User_Query object. Because we're filtering
	 *   the current object, we use $this inside of the method instead
	 * @param string $user_ids_sql Sanitized, comma-separated string of
	 *   the user ids returned by the main query
	 */
	public function populate_experiment_member_extras( $query, $user_ids_sql ) {
		global $wpdb;

		$bp     = buddypress();
		$extras = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, date_modified, is_admin, is_mod, comments, user_title, invite_sent, is_confirmed, inviter_id, is_banned FROM {$bp->experiments->table_name_members} WHERE user_id IN ({$user_ids_sql}) AND experiment_id = %d", $this->query_vars['experiment_id'] ) );

		foreach ( (array) $extras as $extra ) {
			if ( isset( $this->results[ $extra->user_id ] ) ) {
				// user_id is provided for backward compatibility
				$this->results[ $extra->user_id ]->user_id       = (int) $extra->user_id;
				$this->results[ $extra->user_id ]->is_admin      = (int) $extra->is_admin;
				$this->results[ $extra->user_id ]->is_mod        = (int) $extra->is_mod;
				$this->results[ $extra->user_id ]->is_banned     = (int) $extra->is_banned;
				$this->results[ $extra->user_id ]->date_modified = $extra->date_modified;
				$this->results[ $extra->user_id ]->user_title    = $extra->user_title;
				$this->results[ $extra->user_id ]->comments      = $extra->comments;
				$this->results[ $extra->user_id ]->invite_sent   = (int) $extra->invite_sent;
				$this->results[ $extra->user_id ]->inviter_id    = (int) $extra->inviter_id;
				$this->results[ $extra->user_id ]->is_confirmed  = (int) $extra->is_confirmed;
				$this->results[ $extra->user_id ]->membership_id = (int) $extra->id;
			}
		}

		// Don't filter other BP_User_Query objects on the same page
		remove_action( 'bp_user_query_populate_extras', array( $this, 'populate_experiment_member_extras' ), 10, 2 );
	}
}

    
    
    /**
     * BuddyPress Experiment Variable objects.
     */
    
    class BP_Experiments_Variable {
        
        /**
         * ID of the variable.
         *
         * @access public
         * @var string
         */
        var $id;
        
        /**
         * Name of the variable.
         *
         * @access public
         * @var string
         */
        var $name;
        
        /**
         * Type of the variable.
         *
         * @access public
         * @var string
         */
        var $type;
        
        
        /**
         * ID of the experiment associated with the variable.
         *
         * @access public
         * @var int
         */
        var $experiment_id;
        
        
        
        /**
         * Constructor method.
         *
         * @param int $experiment_id Optional. Can be used to
         *        look up a variable.
         * @param int $id Optional. The unique ID of the variable object.
         * @param bool $populate Whether to populate the properties of the
         *        located variable. Default: true.
         */
        public function __construct($experiment_id = 0, $id = false, $populate = true ) {
            
            // Experiment ID is not empty, and ID is
            if ( !empty( $experiment_id ) && empty( $id ) ) {
                $this->experiment_id = $experiment_id;
                
                if ( !empty( $populate ) ) {
                    $this->populate();
                }
            }
            
            // ID is not empty
            if ( !empty( $id ) ) {
                $this->id = $id;
                
                if ( !empty( $populate ) ) {
                    $this->populate();
                }
            }
        }
        
        /**
         * Populate the object's properties.
         */
        public function populate() {
            global $wpdb, $bp;
            
            if ( $this->experiment_id && !$this->id )
                $sql = $wpdb->prepare( "SELECT * FROM wp_bp_experiments_variables WHERE id = %d AND experiment_id = %d", $this->id, $this->experiment_id );
            
            if ( !empty( $this->id ) )
                $sql = $wpdb->prepare( "SELECT * FROM wp_bp_experiments_variables WHERE id = %d", $this->id );
            
            $variable = $wpdb->get_row($sql);
            
            if ( !empty( $variable ) ) {
                $this->id            = $variable->id;
                $this->experiment_id      = $variable->experiment_id;
                $this->name       = $variable->name;
                $this->type       = $variable->type;
                
                
                /*
                 $this->user = new BP_Core_User( $this->user_id );
                 */
            }
        }
        
        /**
         * Save the variable data to the database.
         *
         * @return bool True on success, false on failure.
         */
        public function save() {
            global $wpdb, $bp;
            
            
            $this->experiment_id      = apply_filters( 'experiments_variable_experiment_id_before_save',      $this->experiment_id,      $this->id );
            $this->name    = apply_filters( 'experiments_variable_name_before_save',    $this->name,    $this->id );
            $this->type    = apply_filters( 'experiments_variable_type_before_save',    $this->type,    $this->id );
            
            do_action_ref_array( 'experiments_variable_before_save', array( &$this ) );
            
            if ( !empty( $this->id ) ) {
                $sql = $wpdb->prepare( "UPDATE wp_bp_experiments_variables SET name = %s, type = %s WHERE id = %d", $this->name, $this->type, $this->id );
            } else {
                /*
                 // Ensure that variable is not already a variable of the experiment before inserting
                 if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name_variables} WHERE id = %d AND experiment_id = %d LIMIT 1", $this->id, $this->experiment_id ) ) ) {
                 return false;
                 }
                 */
                
                $sql = $wpdb->prepare( "INSERT INTO wp_bp_experiments_variables (experiment_id, name, type) VALUES (%d, %s, %s)", $this->experiment_id, $this->name, $this->type );
                
                //$sql = $wpdb->prepare( "INSERT INTO wp_bp_experiments_variables (experiment_id, name, type) VALUES (%d, %s, %s)", 23, 'happiness', 'count' );
            }
            
            
            if ( !$wpdb->query( $sql ) )
                return false;
            
            if ( empty( $this->id ) )
                $this->id = $wpdb->insert_id;
            
            // Update the experiment’s variable count
            //self::refresh_total_variable_count_for_experiment( $this->id );
            
            do_action_ref_array( 'experiments_variable_after_save', array( &$this ) );
            
            return true;
        }
        
        
        
        /**
         * Remove the current variable of an experiment.
         *
         * @return bool True on success, false on failure.
         */
        public function remove() {
            global $wpdb, $bp;
            
            $sql = $wpdb->prepare( "DELETE FROM wp_bp_experiments_variables WHERE id = %d AND experiment_id = %d", $this->id, $this->experiment_id );
            
            if ( !$result = $wpdb->query( $sql ) )
                return false;
            
            // Update the experiment's variable count
            self::refresh_total_variable_count_for_experiment( $this->experiment_id );
            
            return $result;
        }
        
        /** Static Methods ****************************************************/
        
        
        
        /**
         * Refresh the total_variable_count for a experiment.
         *
         * @since BuddyPress (1.8.0)
         *
         * @param int $experiment_id ID of the experiment.
         * @return bool True on success, false on failure.
         */
        public static function refresh_total_variable_count_for_experiment( $experiment_id ) {
            return experiments_update_experimentmeta( $experiment_id, 'total_variable_count', (int) 	    BP_Experiments_Experiment::get_total_variable_count( $experiment_id ) );
        }
        
        
        /**
         * Get the IDs of all a given experiment's variables.
         *
         * @param int $experiment_id ID of the experiment.
         * @return array IDs of all experiment variables.
         */
        public static function get_experiment_variable_ids( $experiment_id ) {
            global $bp, $wpdb;
            
            return $wpdb->get_col( $wpdb->prepare( "SELECT id FROM wp_bp_experiments_variables} WHERE experiment_id = %d", $experiment_id ) );
        }
        
        
        
        /**
         * Get variables of an experiment.
         *
         * @deprecated BuddyPress (1.8.0)
         */
        public static function get_all_for_experiment( $experiment_id, $limit = false, $page = false ) {
            global $bp, $wpdb;
            
            _deprecated_function( __METHOD__, '1.8', 'BP_Experiment_Variable_Query' );
            
            $pag_sql = '';
            if ( !empty( $limit ) && !empty( $page ) )
                $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
            
            
            
            if ( empty( $variables ) ) {
                return false;
            }
            
            if ( empty( $pag_sql ) ) {
                $total_variable_count = count( $variables );
            } else {
                $total_variable_count = $wpdb->get_var( apply_filters( 'bp_experiment_variables_count', $wpdb->prepare( "SELECT COUNT(id) FROM wp_bp_experiments_variables m WHERE experiment_id = %d", $experiment_id ) ) );
            }
            
            
            return array( 'variables' => $variables, 'count' => $total_variable_count );
        }
        
        /**
         * Delete all variables for a given experiment.
         *
         * @param int $experiment_id ID of the experiment.
         * @return int Number of records deleted.
         */
        public static function delete_all( $experiment_id ) {
            global $wpdb, $bp;
            
            return $wpdb->query( $wpdb->prepare( "DELETE FROM wp_bp_experiments_variables WHERE experiment_id = %d", $experiment_id ) );
        }
        
        
    }//end class BP_Experiments_Variable
    
    
/**
 * BuddyPress Experiment Membership objects.
 */
class BP_Experiments_Member {

	/**
	 * ID of the membership.
	 *
	 * @access public
	 * @var int
	 */
	var $id;

	/**
	 * ID of the experiment associated with the membership.
	 *
	 * @access public
	 * @var int
	 */
	var $experiment_id;

	/**
	 * ID of the user associated with the membership.
	 *
	 * @access public
	 * @var int
	 */
	var $user_id;

	/**
	 * ID of the user whose invitation initiated the membership.
	 *
	 * @access public
	 * @var int
	 */
	var $inviter_id;

	/**
	 * Whether the member is an admin of the experiment.
	 *
	 * @access public
	 * @var int
	 */
	var $is_admin;

	/**
	 * Whether the member is a mod of the experiment.
	 *
	 * @access public
	 * @var int
	 */
	var $is_mod;

	/**
	 * Whether the member is banned from the experiment.
	 *
	 * @access public
	 * @var int
	 */
	var $is_banned;

	/**
	 * Title used to describe the experiment member's role in the experiment.
	 *
	 * Eg, 'Experiment Admin'.
	 *
	 * @access public
	 * @var int
	 */
	var $user_title;

	/**
	 * Last modified date of the membership.
	 *
	 * This value is updated when, eg, invitations are accepted.
	 *
	 * @access public
	 * @var string
	 */
	var $date_modified;

	/**
	 * Whether the membership has been confirmed.
	 *
	 * @access public
	 * @var int
	 */
	var $is_confirmed;

	/**
	 * Comments associated with the membership.
	 *
	 * In BP core, these are limited to the optional message users can
	 * include when requesting membership to a private experiment.
	 *
	 * @access public
	 * @var string
	 */
	var $comments;

	/**
	 * Whether an invitation has been sent for this membership.
	 *
	 * The purpose of this flag is to mark when an invitation has been
	 * "drafted" (the user has been added via the interface at Send
	 * Invites), but the Send button has not been pressed, so the
	 * invitee has not yet been notified.
	 *
	 * @access public
	 * @var int
	 */
	var $invite_sent;

	/**
	 * WP_User object representing the membership's user.
	 *
	 * @access public
	 * @var WP_User
	 */
	var $user;

	/**
	 * Constructor method.
	 *
	 * @param int $user_id Optional. Along with $experiment_id, can be used to
	 *        look up a membership.
	 * @param int $experiment_id Optional. Along with $user_id, can be used to
	 *        look up a membership.
	 * @param int $id Optional. The unique ID of the membership object.
	 * @param bool $populate Whether to populate the properties of the
	 *        located membership. Default: true.
	 */
	public function __construct( $user_id = 0, $experiment_id = 0, $id = false, $populate = true ) {

		// User and experiment are not empty, and ID is
		if ( !empty( $user_id ) && !empty( $experiment_id ) && empty( $id ) ) {
			$this->user_id  = $user_id;
			$this->experiment_id = $experiment_id;

			if ( !empty( $populate ) ) {
				$this->populate();
			}
		}

		// ID is not empty
		if ( !empty( $id ) ) {
			$this->id = $id;

			if ( !empty( $populate ) ) {
				$this->populate();
			}
		}
	}

	/**
	 * Populate the object's properties.
	 */
	public function populate() {
		global $wpdb, $bp;

		if ( $this->user_id && $this->experiment_id && !$this->id )
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d", $this->user_id, $this->experiment_id );

		if ( !empty( $this->id ) )
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->experiments->table_name_members} WHERE id = %d", $this->id );

		$member = $wpdb->get_row($sql);

		if ( !empty( $member ) ) {
			$this->id            = $member->id;
			$this->experiment_id      = $member->experiment_id;
			$this->user_id       = $member->user_id;
			$this->inviter_id    = $member->inviter_id;
			$this->is_admin      = $member->is_admin;
			$this->is_mod        = $member->is_mod;
			$this->is_banned     = $member->is_banned;
			$this->user_title    = $member->user_title;
			$this->date_modified = $member->date_modified;
			$this->is_confirmed  = $member->is_confirmed;
			$this->comments      = $member->comments;
			$this->invite_sent   = $member->invite_sent;

			$this->user = new BP_Core_User( $this->user_id );
		}
	}

	/**
	 * Save the membership data to the database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		global $wpdb, $bp;

		$this->user_id       = apply_filters( 'experiments_member_user_id_before_save',       $this->user_id,       $this->id );
		$this->experiment_id      = apply_filters( 'experiments_member_experiment_id_before_save',      $this->experiment_id,      $this->id );
		$this->inviter_id    = apply_filters( 'experiments_member_inviter_id_before_save',    $this->inviter_id,    $this->id );
		$this->is_admin      = apply_filters( 'experiments_member_is_admin_before_save',      $this->is_admin,      $this->id );
		$this->is_mod        = apply_filters( 'experiments_member_is_mod_before_save',        $this->is_mod,        $this->id );
		$this->is_banned     = apply_filters( 'experiments_member_is_banned_before_save',     $this->is_banned,     $this->id );
		$this->user_title    = apply_filters( 'experiments_member_user_title_before_save',    $this->user_title,    $this->id );
		$this->date_modified = apply_filters( 'experiments_member_date_modified_before_save', $this->date_modified, $this->id );
		$this->is_confirmed  = apply_filters( 'experiments_member_is_confirmed_before_save',  $this->is_confirmed,  $this->id );
		$this->comments      = apply_filters( 'experiments_member_comments_before_save',      $this->comments,      $this->id );
		$this->invite_sent   = apply_filters( 'experiments_member_invite_sent_before_save',   $this->invite_sent,   $this->id );

		do_action_ref_array( 'experiments_member_before_save', array( &$this ) );

		if ( !empty( $this->id ) ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->experiments->table_name_members} SET inviter_id = %d, is_admin = %d, is_mod = %d, is_banned = %d, user_title = %s, date_modified = %s, is_confirmed = %d, comments = %s, invite_sent = %d WHERE id = %d", $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent, $this->id );
		} else {
			// Ensure that user is not already a member of the experiment before inserting
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_confirmed = 1 LIMIT 1", $this->user_id, $this->experiment_id ) ) ) {
				return false;
			}

			$sql = $wpdb->prepare( "INSERT INTO {$bp->experiments->table_name_members} ( user_id, experiment_id, inviter_id, is_admin, is_mod, is_banned, user_title, date_modified, is_confirmed, comments, invite_sent ) VALUES ( %d, %d, %d, %d, %d, %d, %s, %s, %d, %s, %d )", $this->user_id, $this->experiment_id, $this->inviter_id, $this->is_admin, $this->is_mod, $this->is_banned, $this->user_title, $this->date_modified, $this->is_confirmed, $this->comments, $this->invite_sent );
		}

		if ( !$wpdb->query( $sql ) )
			return false;

		$this->id = $wpdb->insert_id;

		// Update the user's experiment count
		self::refresh_total_experiment_count_for_user( $this->user_id );

		// Update the experiment's member count
		self::refresh_total_member_count_for_experiment( $this->experiment_id );

		do_action_ref_array( 'experiments_member_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Promote a member to a new status.
	 *
	 * @param string $status The new status. 'mod' or 'admin'.
	 * @return bool True on success, false on failure.
	 */
	public function promote( $status = 'mod' ) {
		if ( 'mod' == $status ) {
			$this->is_admin   = 0;
			$this->is_mod     = 1;
			$this->user_title = __( 'Experiment Mod', 'buddypress' );
		}

		if ( 'admin' == $status ) {
			$this->is_admin   = 1;
			$this->is_mod     = 0;
			$this->user_title = __( 'Experiment Admin', 'buddypress' );
		}

		return $this->save();
	}

	/**
	 * Demote membership to Member status (non-admin, non-mod).
	 *
	 * @return bool True on success, false on failure.
	 */
	public function demote() {
		$this->is_mod     = 0;
		$this->is_admin   = 0;
		$this->user_title = false;

		return $this->save();
	}

	/**
	 * Ban the user from the experiment.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function ban() {
		if ( !empty( $this->is_admin ) )
			return false;

		$this->is_mod = 0;
		$this->is_banned = 1;

		return $this->save();
	}

	/**
	 * Unban the user from the experiment.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function unban() {
		if ( !empty( $this->is_admin ) )
			return false;

		$this->is_banned = 0;

		return $this->save();
	}

	/**
	 * Mark a pending invitation as accepted.
	 */
	public function accept_invite() {
		$this->inviter_id    = 0;
		$this->is_confirmed  = 1;
		$this->date_modified = bp_core_current_time();
	}

	/**
	 * Confirm a membership request.
	 */
	public function accept_request() {
		$this->is_confirmed = 1;
		$this->date_modified = bp_core_current_time();
	}

	/**
	 * Remove the current membership.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function remove() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d", $this->user_id, $this->experiment_id );

		if ( !$result = $wpdb->query( $sql ) )
			return false;

		// Update the user's experiment count
		self::refresh_total_experiment_count_for_user( $this->user_id );

		// Update the experiment's member count
		self::refresh_total_member_count_for_experiment( $this->experiment_id );

		return $result;
	}

	/** Static Methods ****************************************************/

	/**
	 * Refresh the total_experiment_count for a user.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param int $user_id ID of the user.
	 * @return bool True on success, false on failure.
	 */
	public static function refresh_total_experiment_count_for_user( $user_id ) {
		return bp_update_user_meta( $user_id, 'total_experiment_count', (int) self::total_experiment_count( $user_id ) );
	}

	/**
	 * Refresh the total_member_count for a experiment.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return bool True on success, false on failure.
	 */
	public static function refresh_total_member_count_for_experiment( $experiment_id ) {
		return experiments_update_experimentmeta( $experiment_id, 'total_member_count', (int) BP_Experiments_Experiment::get_total_member_count( $experiment_id ) );
	}

	/**
	 * Delete a membership, based on user + experiment IDs.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @return True on success, false on failure.
	 */
	public static function delete( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d", $user_id, $experiment_id ) );

		// Update the user's experiment count
		self::refresh_total_experiment_count_for_user( $user_id );

		// Update the experiment's member count
		self::refresh_total_member_count_for_experiment( $experiment_id );

		return $remove;
	}

	/**
	 * Get the IDs of the experiments of which a specified user is a member.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $limit Optional. Max number of results to return.
	 *        Default: false (no limit).
	 * @param int $page Optional. Page offset of results to return.
	 *        Default: false (no limit).
	 * @return array {
	 *     @type array $experiments Array of experiments returned by paginated query.
	 *     @type int $total Count of experiments matching query.
	 * }
	 */
	public static function get_experiment_ids( $user_id, $limit = false, $page = false ) {
		global $wpdb, $bp;

		$pag_sql = '';
		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// If the user is logged in and viewing their own experiments, we can show hidden and private experiments
		if ( $user_id != bp_loggedin_user_id() ) {
			$experiment_sql = $wpdb->prepare( "SELECT DISTINCT m.experiment_id FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0{$pag_sql}", $user_id );
			$total_experiments = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		} else {
			$experiment_sql = $wpdb->prepare( "SELECT DISTINCT experiment_id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0{$pag_sql}", $user_id );
			$total_experiments = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT experiment_id) FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id ) );
		}

		$experiments = $wpdb->get_col( $experiment_sql );

		return array( 'experiments' => $experiments, 'total' => (int) $total_experiments );
	}

	/**
	 * Get the IDs of the experiments of which a specified user is a member, sorted by the date joined.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $limit Optional. Max number of results to return.
	 *        Default: false (no limit).
	 * @param int $page Optional. Page offset of results to return.
	 *        Default: false (no limit).
	 * @param string $filter Optional. Limit results to experiments whose name or
	 *        description field matches search terms.
	 * @return array {
	 *     @type array $experiments Array of experiments returned by paginated query.
	 *     @type int $total Count of experiments matching query.
	 * }
	 */
	public static function get_recently_joined( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $filter_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $filter ) ) {
			$filter     = esc_sql( like_escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != bp_loggedin_user_id() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_experiments = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY m.date_modified DESC {$pag_sql}", $user_id ) );
		$total_experiments = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_banned = 0 AND m.is_confirmed = 1 ORDER BY m.date_modified DESC", $user_id ) );

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get the IDs of the experiments of which a specified user is an admin.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $limit Optional. Max number of results to return.
	 *        Default: false (no limit).
	 * @param int $page Optional. Page offset of results to return.
	 *        Default: false (no limit).
	 * @param string $filter Optional. Limit results to experiments whose name or
	 *        description field matches search terms.
	 * @return array {
	 *     @type array $experiments Array of experiments returned by paginated query.
	 *     @type int $total Count of experiments matching query.
	 * }
	 */
	public static function get_is_admin_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $filter_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $filter ) ) {
			$filter     = esc_sql( like_escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != bp_loggedin_user_id() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_experiments = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_experiments = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_admin = 1 ORDER BY date_modified ASC", $user_id ) );

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get the IDs of the experiments of which a specified user is a moderator.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $limit Optional. Max number of results to return.
	 *        Default: false (no limit).
	 * @param int $page Optional. Page offset of results to return.
	 *        Default: false (no limit).
	 * @param string $filter Optional. Limit results to experiments whose name or
	 *        description field matches search terms.
	 * @return array {
	 *     @type array $experiments Array of experiments returned by paginated query.
	 *     @type int $total Count of experiments matching query.
	 * }
	 */
	public static function get_is_mod_of( $user_id, $limit = false, $page = false, $filter = false ) {
		global $wpdb, $bp;

		$pag_sql = $hidden_sql = $filter_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $filter ) ) {
			$filter     = esc_sql( like_escape( $filter ) );
			$filter_sql = " AND ( g.name LIKE '%%{$filter}%%' OR g.description LIKE '%%{$filter}%%' )";
		}

		if ( $user_id != bp_loggedin_user_id() )
			$hidden_sql = " AND g.status != 'hidden'";

		$paged_experiments = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count'{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );
		$total_experiments = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id{$hidden_sql}{$filter_sql} AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 AND m.is_mod = 1 ORDER BY date_modified ASC", $user_id ) );

		return array( 'experiments' => $paged_experiments, 'total' => $total_experiments );
	}

	/**
	 * Get the count of experiments of which the specified user is a member.
	 *
	 * @param int $user_id Optional. Default: ID of the displayed user.
	 * @return int Experiment count.
	 */
	public static function total_experiment_count( $user_id = 0 ) {
		global $bp, $wpdb;

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		if ( $user_id != bp_loggedin_user_id() && !bp_current_user_can( 'bp_moderate' ) ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		} else {
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id ) );
		}
	}

	/**
	 * Get a user's outstanding experiment invitations.
	 *
	 * @param int $user_id ID of the invitee.
	 * @param int $limit Optional. Max number of results to return.
	 *        Default: false (no limit).
	 * @param int $page Optional. Page offset of results to return.
	 *        Default: false (no limit).
	 * @param string|array $exclude Optional. Array or comma-separated list
	 *        of experiment IDs to exclude from results.
	 * @return array {
	 *     @type array $experiments Array of experiments returned by paginated query.
	 *     @type int $total Count of experiments matching query.
	 * }
	 */
	public static function get_invites( $user_id, $limit = false, $page = false, $exclude = false ) {
		global $wpdb, $bp;

		$pag_sql = ( !empty( $limit ) && !empty( $page ) ) ? $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) ) : '';

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		} else {
			$exclude_sql = '';
		}

		$paged_experiments = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->experiments->table_name_experimentmeta} gm1, {$bp->experiments->table_name_experimentmeta} gm2, {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE g.id = m.experiment_id AND g.id = gm1.experiment_id AND g.id = gm2.experiment_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND m.is_confirmed = 0 AND m.inviter_id != 0 AND m.invite_sent = 1 AND m.user_id = %d {$exclude_sql} ORDER BY m.date_modified ASC {$pag_sql}", $user_id ) );

		return array( 'experiments' => $paged_experiments, 'total' => self::get_invite_count_for_user( $user_id ) );
	}

	/**
	 * Gets the total experiment invite count for a user.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param int $user_id The user ID
	 * @return int
	 */
	public static function get_invite_count_for_user( $user_id = 0 ) {
		global $wpdb;

		$bp = buddypress();

		$count = wp_cache_get( $user_id, 'bp_experiment_invite_count' );

		if ( false === $count ) {
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT m.experiment_id) FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id AND m.is_confirmed = 0 AND m.inviter_id != 0 AND m.invite_sent = 1 AND m.user_id = %d", $user_id ) );
			wp_cache_set( $user_id, $count, 'bp_experiment_invite_count' );
		}

		return $count;
	}

	/**
	 * Check whether a user has an outstanding invitation to a given experiment.
	 *
	 * @param int $user_id ID of the potential invitee.
	 * @param int $experiment_id ID of the experiment.
	 * @param string $type If 'sent', results are limited to those
	 *        invitations that have actually been sent (non-draft).
	 *        Default: 'sent'.
	 * @return int|null The ID of the invitation if found, otherwise null.
	 */
	public static function check_has_invite( $user_id, $experiment_id, $type = 'sent' ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		$sql = "SELECT id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_confirmed = 0 AND inviter_id != 0";

		if ( 'sent' == $type )
			$sql .= " AND invite_sent = 1";

		return $wpdb->get_var( $wpdb->prepare( $sql, $user_id, $experiment_id ) );
	}

	/**
	 * Delete an invitation, by specifying user ID and experiment ID.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @return int Number of records deleted.
	 */
	public static function delete_invite( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_confirmed = 0 AND inviter_id != 0 AND invite_sent = 1", $user_id, $experiment_id ) );
	}

	/**
	 * Delete an unconfirmed membership request, by user ID and experiment ID.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @return int Number of records deleted.
	 */
	public static function delete_request( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

 		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_confirmed = 0 AND inviter_id = 0 AND invite_sent = 0", $user_id, $experiment_id ) );
	}

	/**
	 * Check whether a user is an admin of a given experiment.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @param int|null ID of the membership if the user is an admin,
	 *        otherwise null.
	 */
	public static function check_is_admin( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_admin = 1 AND is_banned = 0", $user_id, $experiment_id ) );
	}

	/**
	 * Check whether a user is a mod of a given experiment.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @param int|null ID of the membership if the user is a mod,
	 *        otherwise null.
	 */
	public static function check_is_mod( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_mod = 1 AND is_banned = 0", $user_id, $experiment_id ) );
	}

	/**
	 * Check whether a user is a member of a given experiment.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @param int|null ID of the membership if the user is a member,
	 *        otherwise null.
	 */
	public static function check_is_member( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_confirmed = 1 AND is_banned = 0", $user_id, $experiment_id ) );
	}

	/**
	 * Check whether a user is banned from a given experiment.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @param int|null ID of the membership if the user is banned,
	 *        otherwise null.
	 */
	public static function check_is_banned( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT is_banned FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d", $user_id, $experiment_id ) );
	}

	/**
	 * Is the specified user the creator of the experiment?
	 *
	 * @since BuddyPress (1.2.6)
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @return int|null ID of the experiment if the user is the creator,
	 *         otherwise false.
	 */
	public static function check_is_creator( $user_id, $experiment_id ) {
		global $bp, $wpdb;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name} WHERE creator_id = %d AND id = %d", $user_id, $experiment_id ) );
	}

	/**
	 * Check whether a user has an outstanding membership request for a given experiment.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $experiment_id ID of the experiment.
	 * @return int|null ID of the membership if found, otherwise false.
	 */
	public static function check_for_membership_request( $user_id, $experiment_id ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "SELECT id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND experiment_id = %d AND is_confirmed = 0 AND is_banned = 0 AND inviter_id = 0", $user_id, $experiment_id ) );
	}

	/**
	 * Get a list of randomly selected IDs of experiments that the member belongs to.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $total_experiments Max number of experiment IDs to return. Default: 5.
	 * @return array Experiment IDs.
	 */
	public static function get_random_experiments( $user_id = 0, $total_experiments = 5 ) {
		global $wpdb, $bp;

		// If the user is logged in and viewing their random experiments, we can show hidden and private experiments
		if ( bp_is_my_profile() ) {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT experiment_id FROM {$bp->experiments->table_name_members} WHERE user_id = %d AND is_confirmed = 1 AND is_banned = 0 ORDER BY rand() LIMIT %d", $user_id, $total_experiments ) );
		} else {
			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT m.experiment_id FROM {$bp->experiments->table_name_members} m, {$bp->experiments->table_name} g WHERE m.experiment_id = g.id AND g.status != 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY rand() LIMIT %d", $user_id, $total_experiments ) );
		}
	}

	/**
	 * Get the IDs of all a given experiment's members.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return array IDs of all experiment members.
	 */
	public static function get_experiment_member_ids( $experiment_id ) {
		global $bp, $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_confirmed = 1 AND is_banned = 0", $experiment_id ) );
	}

	/**
	 * Get a list of all a given experiment's admins.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return array Info about experiment admins (user_id + date_modified).
	 */
	public static function get_experiment_administrator_ids( $experiment_id ) {
		global $bp, $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_admin = 1 AND is_banned = 0", $experiment_id ) );
	}

	/**
	 * Get a list of all a given experiment's moderators.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return array Info about experiment mods (user_id + date_modified).
	 */
	public static function get_experiment_moderator_ids( $experiment_id ) {
		global $bp, $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id, date_modified FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_mod = 1 AND is_banned = 0", $experiment_id ) );
	}

	/**
	 * Get the IDs users with outstanding membership requests to the experiment.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return array IDs of users with outstanding membership requests.
	 */
	public static function get_all_membership_request_user_ids( $experiment_id ) {
		global $bp, $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d AND is_confirmed = 0 AND inviter_id = 0", $experiment_id ) );
	}

	/**
	 * Get members of a experiment.
	 *
	 * @deprecated BuddyPress (1.8.0)
	 */
	public static function get_all_for_experiment( $experiment_id, $limit = false, $page = false, $exclude_admins_mods = true, $exclude_banned = true, $exclude = false ) {
		global $bp, $wpdb;

		_deprecated_function( __METHOD__, '1.8', 'BP_Experiment_Member_Query' );

		$pag_sql = '';
		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$exclude_admins_sql = '';
		if ( !empty( $exclude_admins_mods ) )
			$exclude_admins_sql = "AND is_admin = 0 AND is_mod = 0";

		$banned_sql = '';
		if ( !empty( $exclude_banned ) )
			$banned_sql = " AND is_banned = 0";

		$exclude_sql = '';
		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND m.user_id NOT IN ({$exclude})";
		}

		if ( bp_is_active( 'xprofile' ) ) {
			$members = $wpdb->get_results( apply_filters( 'bp_experiment_members_user_join_filter', $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, pd.value as display_name FROM {$bp->experiments->table_name_members} m, {$wpdb->users} u, {$bp->profile->table_name_data} pd WHERE u.ID = m.user_id AND u.ID = pd.user_id AND pd.field_id = 1 AND experiment_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $experiment_id ) ) );
		} else {
			$members = $wpdb->get_results( apply_filters( 'bp_experiment_members_user_join_filter', $wpdb->prepare( "SELECT m.user_id, m.date_modified, m.is_banned, u.user_login, u.user_nicename, u.user_email, u.display_name FROM {$bp->experiments->table_name_members} m, {$wpdb->users} u WHERE u.ID = m.user_id AND experiment_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql} ORDER BY m.date_modified DESC {$pag_sql}", $experiment_id ) ) );
		}

		if ( empty( $members ) ) {
			return false;
		}

		if ( empty( $pag_sql ) ) {
			$total_member_count = count( $members );
		} else {
			$total_member_count = $wpdb->get_var( apply_filters( 'bp_experiment_members_count_user_join_filter', $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->experiments->table_name_members} m WHERE experiment_id = %d AND is_confirmed = 1 {$banned_sql} {$exclude_admins_sql} {$exclude_sql}", $experiment_id ) ) );
		}

		// Fetch whether or not the user is a friend
		foreach ( (array) $members as $user )
			$user_ids[] = $user->user_id;

		$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

		if ( bp_is_active( 'friends' ) ) {
			$friend_status = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id IN ( {$user_ids} ) ) OR (initiator_user_id IN ( {$user_ids} ) AND friend_user_id = %d )", bp_loggedin_user_id(), bp_loggedin_user_id() ) );
			for ( $i = 0, $count = count( $members ); $i < $count; ++$i ) {
				foreach ( (array) $friend_status as $status ) {
					if ( $status->initiator_user_id == $members[$i]->user_id || $status->friend_user_id == $members[$i]->user_id ) {
						$members[$i]->is_friend = $status->is_confirmed;
					}
				}
			}
		}

		return array( 'members' => $members, 'count' => $total_member_count );
	}

	/**
	 * Delete all memberships for a given experiment.
	 *
	 * @param int $experiment_id ID of the experiment.
	 * @return int Number of records deleted.
	 */
	public static function delete_all( $experiment_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE experiment_id = %d", $experiment_id ) );
	}

	/**
	 * Delete all experiment membership information for the specified user.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $user_id ID of the user.
	 */
	public static function delete_all_for_user( $user_id ) {
		global $bp, $wpdb;

		// Get all the experiment ids for the current user's experiments and update counts
		$experiment_ids = BP_Experiments_Member::get_experiment_ids( $user_id );
		foreach ( $experiment_ids['experiments'] as $experiment_id ) {
			experiments_update_experimentmeta( $experiment_id, 'total_member_count', experiments_get_total_member_count( $experiment_id ) - 1 );

			// If current user is the creator of a experiment and is the sole admin, delete that experiment to avoid counts going out-of-sync
			if ( experiments_is_user_admin( $user_id, $experiment_id ) && count( experiments_get_experiment_admins( $experiment_id ) ) < 2 && experiments_is_user_creator( $user_id, $experiment_id ) )
				experiments_delete_experiment( $experiment_id );
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->experiments->table_name_members} WHERE user_id = %d", $user_id ) );
	}
}

/**
 * API for creating experiment extensions without having to hardcode the content into
 * the theme.
 *
 * To implement, extend this class. In your constructor, pass an optional array
 * of arguments to parent::init() to configure your widget. The config array
 * supports the following values:
 *   - 'slug' A unique identifier for your extension. This value will be used
 *     to build URLs, so make it URL-safe
 *   - 'name' A translatable name for your extension. This value is used to
       populate the navigation tab, as well as the default titles for admin/
       edit/create tabs.
 *   - 'visibility' Set to 'public' (default) for your extension (the main tab
 *     as well as the widget) to be available to anyone who can access the
 *     experiment, 'private' otherwise.
 *   - 'nav_item_position' An integer explaining where the nav item should
 *     appear in the tab list
 *   - 'enable_nav_item' Set to true for your extension's main tab to be
 *     available to anyone who can access the experiment.
 *   - 'nav_item_name' The translatable text you want to appear in the nav tab.
 *     Defaults to the value of 'name'.
 *   - 'display_hook' The WordPress action that the widget_display() method is
 *     hooked to
 *   - 'template_file' The template file that will be used to load the content
 *     of your main extension tab. Defaults to 'experiments/single/plugins.php'.
 *   - 'screens' A multi-dimensional array, described below
 *
 * BP_Experiment_Extension uses the concept of "settings screens". There are three
 * contexts for settings screens:
 *   - 'create', which inserts a new step into the experiment creation process
 *   - 'edit', which adds a tab for your extension into the Admin section of
 *     a experiment
 *   - 'admin', which adds a metabox to the Experiments administration panel in the
 *     WordPress Dashboard
 * Each of these settings screens is populated by a pair of methods: one that
 * creates the markup for the screen, and one that processes form data
 * submitted from the screen. If your plugin needs screens in all three
 * contexts, and if the markup and form processing logic will be the same in
 * each case, you can define two methods to handle all of the screens:
 *   function settings_screen() {}
 *   function settings_screen_save() {}
 * If one or more of your settings screen needs separate logic, you may define
 * context-specific methods, for example:
 *   function edit_screen() {}
 *   function edit_screen_save() {}
 * BP_Experiment_Extension will use the more specific methods if they are available.
 *
 * You can further customize the settings screens (tab names, etc) by passing
 * an optional 'screens' parameter to the init array. The format is as follows:
 *   'screens' => array(
 *       'create' => array(
 *	     'slug' => 'foo',
 *	     'name' => 'Foo',
 *	     'position' => 55,
 *	     'screen_callback' => 'my_create_screen_callback',
 *	     'screen_save_callback' => 'my_create_screen_save_callback',
 *	 ),
 *	 'edit' => array( // ...
 *   ),
 * Only provide those arguments that you actually want to change from the
 * default configuration. BP_Experiment_Extension will do the rest.
 *
 * Note that the 'edit' screen accepts an additional parameter: 'submit_text',
 * which defines the text of the Submit button automatically added to the Edit
 * screen of the extension (defaults to 'Save Changes'). Also, the 'admin'
 * screen accepts two additional parameters: 'metabox_priority' and
 * 'metabox_context'. See the docs for add_meta_box() for more details on these
 * arguments.
 *
 * Prior to BuddyPress 1.7, experiment extension configurations were set slightly
 * differently. The legacy method is still supported, though deprecated.
 *
 * @package BuddyPress
 * @subpackage Experiments
 * @since BuddyPress (1.1.0)
 */
class BP_Experiment_Extension {

	/** Public ************************************************************/

	/**
	 * Information about this extension's screens.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var array
	 */
	public $screens = array();

	/**
	 * The name of the extending class.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var string
	 */
	public $class_name = '';

	/**
	 * A ReflectionClass object of the current extension.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var ReflectionClass
	 */
	public $class_reflection = null;

	/**
	 * Parsed configuration paramaters for the extension.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var array
	 */
	public $params = array();

	/**
	 * The ID of the current experiment.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var int
	 */
	public $experiment_id = 0;

	/**
	 * The slug of the current extension.
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * The translatable name of the current extension.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The visibility of the extension tab. 'public' or 'private'.
	 *
	 * @var string
	 */
	public $visibility = 'public';

	/**
	 * The numeric position of the main nav item.
	 *
	 * @var int
	 */
	public $nav_item_position = 81;

	/**
	 * Whether to show the nav item.
	 *
	 * @var bool
	 */
	public $enable_nav_item = true;

	/**
	 * The text of the nav item. Defaults to self::name.
	 *
	 * @var string
	 */
	public $nav_item_name = '';

	/**
	 * The WP action that self::widget_display() is attached to.
	 *
	 * Default: 'experiments_custom_experiment_boxes'.
	 *
	 * @var string
	 */
	public $display_hook = 'experiments_custom_experiment_boxes';

	/**
	 * The template file used to load the plugin content.
	 *
	 * Default: 'experiments/single/plugins'.
	 *
	 * @var string
	 */
	public $template_file = 'experiments/single/plugins';

	/** Protected *********************************************************/

	/**
	 * Has the extension been initialized?
	 *
	 * @since BuddyPress (1.8.0)
	 * @var bool
	 */
	protected $initialized = false;

	/**
	 * Extension properties as set by legacy extensions.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var array
	 */
	protected $legacy_properties = array();

	/**
	 * Converted legacy parameters.
	 *
	 * These are the extension properties as set by legacy extensions, but
	 * then converted to match the new format for params.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var array
	 */
	protected $legacy_properties_converted = array();

	/**
	 * Miscellaneous data as set by the __set() magic method.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var array
	 */
	protected $data = array();

	/** Screen Overrides **************************************************/

	/*
	 * Screen override methods are how your extension will display content
	 * and handle form submits. Your extension should only override those
	 * methods that it needs for its purposes.
	 */

	// The content of the experiment tab
	public function display() {}

	// Content displayed in a widget sidebar, if applicable
	public function widget_display() {}

	// *_screen() displays the settings form for the given context
	// *_screen_save() processes data submitted via the settings form
	// The settings_* methods are generic fallbacks, which can optionally
	// be overridden by the more specific edit_*, create_*, and admin_*
	// versions.
	public function settings_screen( $experiment_id = null ) {}
	public function settings_screen_save( $experiment_id = null ) {}
	public function edit_screen( $experiment_id = null ) {}
	public function edit_screen_save( $experiment_id = null ) {}
	public function create_screen( $experiment_id = null ) {}
	public function create_screen_save( $experiment_id = null ) {}
	public function admin_screen( $experiment_id = null ) {}
	public function admin_screen_save( $experiment_id = null ) {}

	/** Setup *************************************************************/

	/**
	 * Initialize the extension, using your config settings
	 *
	 * Your plugin should call this method at the very end of its
	 * constructor, like so:
	 *
	 *   public function __construct() {
	 *       $args = array(
	 *           'slug' => 'my-experiment-extension',
	 *           'name' => 'My Experiment Extension',
	 *           // ...
	 *       );
	 *
	 *       parent::init( $args );
	 *   }
	 *
	 * @since BuddyPress (1.8)
	 * @param array $args {
	 *     Array of initialization arguments.
	 *     @type string $slug Unique, URL-safe identifier for your
	 *           extension.
	 *     @type string $name Translatable name for your extension. Used to
	 *           populate navigation items.
	 *     @type string $visibility Optional. Set to 'public' for your
	 *           extension (the main tab as well as the widget) to be
	 *           available to anyone who can access the experiment; set to
	 *           'private' otherwise. Default: 'public'.
	 *     @type int $nav_item_position Optional. Location of the nav item
	 *           in the tab list. Default: 81.
	 *     @type bool $enable_nav_item Optional. Whether the extension's
	 *           tab should be accessible to anyone who can view the experiment.
	 *           Default: true.
	 *     @type string $nav_item_name Optional. The translatable text you
	 *           want to appear in the nav tab. Default: the value of $name.
	 *     @type string $display_hook Optional. The WordPress action that
	 *           the widget_display() method is hooked to.
	 *           Default: 'experiments_custom_experiment_boxes'.
	 *     @type string $template_file Optional. Theme-relative path to the
	 *           template file BP should use to load the content of your
	 *           main extension tab. Default: 'experiments/single/plugins.php'.
	 *     @type array $screens A multi-dimensional array of configuration
	 *           information for the extension screens. See docblock of
	 *           {@link BP_Experiment_Extension} for more details.
	 * }
	 */
	public function init( $args = array() ) {

		// Before this init() method was introduced, plugins were
		// encouraged to set their config directly. For backward
		// compatibility with these plugins, we detect whether this is
		// one of those legacy plugins, and parse any legacy arguments
		// with those passed to init()
		$this->parse_legacy_properties();
		$args = $this->parse_args_r( $args, $this->legacy_properties_converted );

		// Parse with defaults
		$this->params = $this->parse_args_r( $args, array(
			'slug'              => $this->slug,
			'name'              => $this->name,
			'visibility'        => $this->visibility,
			'nav_item_position' => $this->nav_item_position,
			'enable_nav_item'   => (bool) $this->enable_nav_item,
			'nav_item_name'     => $this->nav_item_name,
			'display_hook'      => $this->display_hook,
			'template_file'     => $this->template_file,
			'screens'           => $this->get_default_screens(),
		) );

		$this->initialized = true;
	}

	/**
	 * The main setup routine for the extension.
	 *
	 * This method contains the primary logic for setting up an extension's
	 * configuration, setting up backward compatibility for legacy plugins,
	 * and hooking the extension's screen functions into WP and BP.
	 *
	 * Marked 'public' because it must be accessible to add_action().
	 * However, you should never need to invoke this method yourself - it
	 * is called automatically at the right point in the load order by
	 * bp_register_experiment_extension().
	 *
	 * @since BuddyPress (1.1.0)
	 */
	public function _register() {

		// Detect and parse properties set by legacy extensions
		$this->parse_legacy_properties();

		// Initialize, if necessary. This should only happen for
		// legacy extensions that don't call parent::init() themselves
		if ( true !== $this->initialized ) {
			$this->init();
		}

		// Set some config values, based on the parsed params
		$this->experiment_id          = $this->get_experiment_id();
		$this->slug              = $this->params['slug'];
		$this->name              = $this->params['name'];
		$this->visibility        = $this->params['visibility'];
		$this->nav_item_position = $this->params['nav_item_position'];
		$this->nav_item_name     = $this->params['nav_item_name'];
		$this->display_hook      = $this->params['display_hook'];
		$this->template_file     = $this->params['template_file'];

		// Configure 'screens': create, admin, and edit contexts
		$this->setup_screens();

		// Mirror configuration data so it's accessible to plugins
		// that look for it in its old locations
		$this->setup_legacy_properties();

		// Hook the extension into BuddyPress
		$this->setup_display_hooks();
		$this->setup_create_hooks();
		$this->setup_edit_hooks();
		$this->setup_admin_hooks();
	}

	/**
	 * Set up some basic info about the Extension.
	 *
	 * Here we collect the name of the extending class, as well as a
	 * ReflectionClass that is used in get_screen_callback() to determine
	 * whether your extension overrides certain callback methods.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function setup_class_info() {
		if ( empty( $this->class_name ) ) {
			$this->class_name = get_class( $this );
		}

		if ( is_null( $this->class_reflection ) ) {
			$this->class_reflection = new ReflectionClass( $this->class_name );
		}
	}

	/**
	 * Get the current experiment ID.
	 *
	 * Check for:
	 *   - current experiment
	 *   - new experiment
	 *   - experiment admin
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public static function get_experiment_id() {

		// Usually this will work
		$experiment_id = bp_get_current_experiment_id();

		// On the admin, get the experiment id out of the $_GET params
		if ( empty( $experiment_id ) && is_admin() && ( isset( $_GET['page'] ) && ( 'bp-experiments' === $_GET['page'] ) ) && ! empty( $_GET['gid'] ) ) {
			$experiment_id = (int) $_GET['gid'];
		}

		// This fallback will only be hit when the create step is very
		// early
		if ( empty( $experiment_id ) && bp_get_new_experiment_id() ) {
			$experiment_id = bp_get_new_experiment_id();
		}

		// On some setups, the experiment id has to be fetched out of the
		// $_POST array
		// @todo Figure out why this is happening during experiment creation
		if ( empty( $experiment_id ) && isset( $_POST['experiment_id'] ) ) {
			$experiment_id = (int) $_POST['experiment_id'];
		}

		return $experiment_id;
	}

	/**
	 * Gather configuration data about your screens.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function get_default_screens() {
		$this->setup_class_info();

		$screens = array(
			'create' => array(
				'position' => 81,
			),
			'edit'   => array(
				'submit_text' => __( 'Save Changes', 'buddypress' ),
			),
			'admin'  => array(
				'metabox_context'  => 'normal',
				'metabox_priority' => 'core',
			),
		);

		foreach ( $screens as $context => &$screen ) {
			$screen['enabled']     = true;
			$screen['name']        = $this->name;
			$screen['slug']        = $this->slug;

			$screen['screen_callback']      = $this->get_screen_callback( $context, 'screen'      );
			$screen['screen_save_callback'] = $this->get_screen_callback( $context, 'screen_save' );
		}

		return $screens;
	}

	/**
	 * Set up screens array based on params.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function setup_screens() {
		foreach ( (array) $this->params['screens'] as $context => $screen ) {
			if ( empty( $screen['slug'] ) ) {
				$screen['slug'] = $this->slug;
			}

			if ( empty( $screen['name'] ) ) {
				$screen['name'] = $this->name;
			}

			$this->screens[ $context ] = $screen;
		}
	}

	/** Display ***********************************************************/

	/**
	 * Hook this extension's experiment tab into BuddyPress, if necessary.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function setup_display_hooks() {

		// Bail if not a experiment
		if ( ! bp_is_experiment() ) {
			return;
		}

		// Bail if the current user doesn't have access
		if ( ( 'public' !== $this->visibility ) && ! buddypress()->experiments->current_experiment->user_has_access ) {
			return;
		}

		if ( true === $this->enable_nav_item ) {
			bp_core_new_subnav_item( array(
				'name'            => ! $this->nav_item_name ? $this->name : $this->nav_item_name,
				'slug'            => $this->slug,
				'parent_slug'     => bp_get_current_experiment_slug(),
				'parent_url'      => bp_get_experiment_permalink( experiments_get_current_experiment() ),
				'position'        => $this->nav_item_position,
				'item_css_id'     => 'nav-' . $this->slug,
				'screen_function' => array( &$this, '_display_hook' ),
				'user_has_access' => $this->enable_nav_item
			) );

			// When we are viewing the extension display page, set the title and options title
			if ( bp_is_current_action( $this->slug ) ) {
				add_action( 'bp_template_content_header', create_function( '', 'echo "' . esc_attr( $this->name ) . '";' ) );
				add_action( 'bp_template_title',          create_function( '', 'echo "' . esc_attr( $this->name ) . '";' ) );
			}
		}

		// Hook the experiment home widget
		if ( ! bp_current_action() && bp_is_current_action( 'home' ) ) {
			add_action( $this->display_hook, array( &$this, 'widget_display' ) );
		}
	}

	/**
	 * Hook the main display method, and loads the template file
	 */
	public function _display_hook() {
		add_action( 'bp_template_content', array( &$this, 'display' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', $this->template_file ) );
	}

	/** Create ************************************************************/

	/**
	 * Hook this extension's Create step into BuddyPress, if necessary.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function setup_create_hooks() {
		if ( ! $this->is_screen_enabled( 'create' ) ) {
			return;
		}

		$screen = $this->screens['create'];

		// Insert the experiment creation step for the new experiment extension
		buddypress()->experiments->experiment_creation_steps[ $screen['slug'] ] = array(
			'name'     => $screen['name'],
			'slug'     => $screen['slug'],
			'position' => $screen['position'],
		);

		// The maybe_ methods check to see whether the create_*
		// callbacks should be invoked (ie, are we on the
		// correct experiment creation step). Hooked in separate
		// methods because current creation step info not yet
		// available at this point
		add_action( 'experiments_custom_create_steps', array( $this, 'maybe_create_screen' ) );
		add_action( 'experiments_create_experiment_step_save_' . $screen['slug'], array( $this, 'maybe_create_screen_save' ) );
	}

	/**
	 * Call the create_screen() method, if we're on the right page.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function maybe_create_screen() {
		if ( ! bp_is_experiment_creation_step( $this->screens['create']['slug'] ) ) {
			return;
		}

		call_user_func( $this->screens['create']['screen_callback'], $this->experiment_id );
		$this->nonce_field( 'create' );

		// The create screen requires an additional nonce field
		// due to a quirk in the way the templates are built
		wp_nonce_field( 'experiments_create_save_' . bp_get_experiments_current_create_step(), '_wpnonce', false );
	}

	/**
	 * Call the create_screen_save() method, if we're on the right page.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function maybe_create_screen_save() {
		if ( ! bp_is_experiment_creation_step( $this->screens['create']['slug'] ) ) {
			return;
		}

		$this->check_nonce( 'create' );
		call_user_func( $this->screens['create']['screen_save_callback'], $this->experiment_id );
	}

	/** Edit **************************************************************/

	/**
	 * Hook this extension's Edit panel into BuddyPress, if necessary.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function setup_edit_hooks() {

		// Bail if not an edit screen
		if ( ! $this->is_screen_enabled( 'edit' ) || ! bp_is_item_admin() ) {
			return;
		}

		$screen = $this->screens['edit'];

		$position = isset( $screen['position'] ) ? (int) $screen['position'] : 10;

		// Add the tab
		// @todo BP should be using bp_core_new_subnav_item()
		add_action( 'experiments_admin_tabs', create_function( '$current, $experiment_slug',
			'$selected = "";
			if ( "' . esc_attr( $screen['slug'] ) . '" == $current )
				$selected = " class=\"current\"";
			echo "<li{$selected}><a href=\"' . trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/{$experiment_slug}/admin/' . esc_attr( $screen['slug'] ) ) . '\">' . esc_attr( $screen['name'] ) . '</a></li>";'
		), $position, 2 );

		// Catch the edit screen and forward it to the plugin template
		if ( bp_is_experiments_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( $screen['slug'], 0 ) ) {
			$this->call_edit_screen_save( $this->experiment_id );

			add_action( 'experiments_custom_edit_steps', array( &$this, 'call_edit_screen' ) );

			// Determine the proper template and save for later
			// loading
			if ( '' !== bp_locate_template( array( 'experiments/single/home.php' ), false ) ) {
				$this->edit_screen_template = '/experiments/single/home';
			} else {
				add_action( 'bp_template_content_header', create_function( '', 'echo "<ul class=\"content-header-nav\">"; bp_experiment_admin_tabs(); echo "</ul>";' ) );
				add_action( 'bp_template_content', array( &$this, 'call_edit_screen' ) );
				$this->edit_screen_template = '/experiments/single/plugins';
			}

			// We load the template at bp_screens, to give all
			// extensions a chance to load
			add_action( 'bp_screens', array( $this, 'call_edit_screen_template_loader' ) );
		}
	}

	/**
	 * Call the edit_screen() method.
	 *
	 * Previous versions of BP_Experiment_Extension required plugins to provide
	 * their own Submit button and nonce fields when building markup. In
	 * BP 1.8, this requirement was lifted - BP_Experiment_Extension now handles
	 * all required submit buttons and nonces.
	 *
	 * We put the edit screen markup into an output buffer before echoing.
	 * This is so that we can check for the presence of a hardcoded submit
	 * button, as would be present in legacy plugins; if one is found, we
	 * do not auto-add our own button.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function call_edit_screen() {
		ob_start();
		call_user_func( $this->screens['edit']['screen_callback'], $this->experiment_id );
		$screen = ob_get_contents();
		ob_end_clean();

		echo $this->maybe_add_submit_button( $screen );

		$this->nonce_field( 'edit' );
	}

	/**
	 * Check the nonce, and call the edit_screen_save() method.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function call_edit_screen_save() {
		if ( empty( $_POST ) ) {
			return;
		}

		// When DOING_AJAX, the POST global will be populated, but we
		// should assume it's a save
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$this->check_nonce( 'edit' );
		call_user_func( $this->screens['edit']['screen_save_callback'], $this->experiment_id );
	}

	/**
	 * Load the template that houses the Edit screen.
	 *
	 * Separated out into a callback so that it can run after all other
	 * Experiment Extensions have had a chance to register their navigation, to
	 * avoid missing tabs.
	 *
	 * Hooked to 'bp_screens'.
	 *
	 * @since BuddyPress (1.8.0)
	 * @access public So that do_action() has access. Do not call directly.
	 *
	 * @see BP_Experiment_Extension::setup_edit_hooks()
	 */
	public function call_edit_screen_template_loader() {
		bp_core_load_template( $this->edit_screen_template );
	}

	/**
	 * Add a submit button to the edit form, if it needs one.
	 *
	 * There's an inconsistency in the way that the experiment Edit and Create
	 * screens are rendered: the Create screen has a submit button built
	 * in, but the Edit screen does not. This function allows plugin
	 * authors to write markup that does not contain the submit button for
	 * use on both the Create and Edit screens - BP will provide the button
	 * if one is not found.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $screen The screen markup, captured in the output
	 *        buffer.
	 * @param string $screen The same markup, with a submit button added.
	 */
	protected function maybe_add_submit_button( $screen = '' ) {
		if ( $this->has_submit_button( $screen ) ) {
			return $screen;
		}

		return $screen . sprintf(
			'<div id="%s"><input type="submit" name="save" value="%s" id="%s"></div>',
			'bp-experiment-edit-' . $this->slug . '-submit-wrapper',
			$this->screens['edit']['submit_text'],
			'bp-experiment-edit-' . $this->slug . '-submit'
		);
	}

	/**
	 * Does the given markup have a submit button?
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $screen The markup to check.
	 * @return bool True if a Submit button is found, otherwise false.
	 */
	public static function has_submit_button( $screen = '' ) {
		$pattern = "/<input[^>]+type=[\'\"]submit[\'\"]/";
		preg_match( $pattern, $screen, $matches );
		return ! empty( $matches[0] );
	}

	/** Admin *************************************************************/

	/**
	 * Hook this extension's Admin metabox into BuddyPress, if necessary.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function setup_admin_hooks() {
		if ( ! $this->is_screen_enabled( 'admin' ) || ! is_admin() ) {
			return;
		}

		// Hook the admin screen markup function to the content hook
		add_action( 'bp_experiments_admin_meta_box_content_' . $this->slug, array( $this, 'call_admin_screen' ) );

		// Initialize the metabox
		add_action( 'bp_experiments_admin_meta_boxes', array( $this, '_meta_box_display_callback' ) );

		// Catch the metabox save
		add_action( 'bp_experiment_admin_edit_after', array( $this, 'call_admin_screen_save' ), 10 );
	}

	/**
	 * Call the admin_screen() method, and add a nonce field.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function call_admin_screen() {
		call_user_func( $this->screens['admin']['screen_callback'], $this->experiment_id );
		$this->nonce_field( 'admin' );
	}

	/**
	 * Check the nonce, and call the admin_screen_save() method
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function call_admin_screen_save() {
		$this->check_nonce( 'admin' );
		call_user_func( $this->screens['admin']['screen_save_callback'], $this->experiment_id );
	}

	/**
	 * Create the Dashboard meta box for this extension.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function _meta_box_display_callback() {
		$experiment_id = isset( $_GET['gid'] ) ? (int) $_GET['gid'] : 0;
		$screen   = $this->screens['admin'];

		add_meta_box(
			$screen['slug'],
			$screen['name'],
			create_function( '', 'do_action( "bp_experiments_admin_meta_box_content_' . $this->slug . '", ' . $experiment_id . ' );' ),
			get_current_screen()->id,
			$screen['metabox_context'],
			$screen['metabox_priority']
		);
	}


	/** Utilities *********************************************************/

	/**
	 * Generate the nonce fields for a settings form.
	 *
	 * The nonce field name (the second param passed to wp_nonce_field)
	 * contains this extension's slug and is thus unique to this extension.
	 * This is necessary because in some cases (namely, the Dashboard),
	 * more than one extension may generate nonces on the same page, and we
	 * must avoid name clashes.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @uses wp_nonce_field()
	 *
	 * @param string $context Screen context. 'create', 'edit', or 'admin'.
	 */
	public function nonce_field( $context = '' ) {
		wp_nonce_field( 'bp_experiment_extension_' . $this->slug . '_' . $context, '_bp_experiment_' . $context . '_nonce_' . $this->slug );
	}

	/**
	 * Check the nonce on a submitted settings form.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @uses check_admin_referer()
	 *
	 * @param string $context Screen context. 'create', 'edit', or 'admin'.
	 */
	public function check_nonce( $context = '' ) {
		check_admin_referer( 'bp_experiment_extension_' . $this->slug . '_' . $context, '_bp_experiment_' . $context . '_nonce_' . $this->slug );
	}

	/**
	 * Is the specified screen enabled?
	 *
	 * To be enabled, a screen must both have the 'enabled' key set to true
	 * (legacy: $this->enable_create_step, etc), and its screen_callback
	 * must also exist and be callable.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $context Screen context. 'create', 'edit', or 'admin'.
	 *
	 * @return bool True if the screen is enabled, otherwise false.
	 */
	public function is_screen_enabled( $context = '' ) {
		$enabled = false;

		if ( isset( $this->screens[ $context ] ) ) {
			$enabled = $this->screens[ $context ]['enabled'] && is_callable( $this->screens[ $context ]['screen_callback'] );
		}

		return (bool) $enabled;
	}

	/**
	 * Get the appropriate screen callback for the specified context/type.
	 *
	 * BP Experiment Extensions have three special "screen contexts": create,
	 * admin, and edit. Each of these contexts has a corresponding
	 * _screen() and _screen_save() method, which allow experiment extension
	 * plugins to define different markup and logic for each context.
	 *
	 * BP also supports fallback settings_screen() and
	 * settings_screen_save() methods, which can be used to define markup
	 * and logic that is shared between context. For each context, you may
	 * either provide context-specific methods, or you can let BP fall back
	 * on the shared settings_* callbacks.
	 *
	 * For example, consider a BP_Experiment_Extension implementation that looks
	 * like this:
	 *
	 *   // ...
	 *   function create_screen( $experiment_id ) { ... }
	 *   function create_screen_save( $experiment_id ) { ... }
	 *   function settings_screen( $experiment_id ) { ... }
	 *   function settings_screen_save( $experiment_id ) { ... }
	 *   // ...
	 *
	 * BP_Experiment_Extension will use your create_* methods for the Create
	 * steps, and will use your generic settings_* methods for the Edit
	 * and Admin contexts. This schema allows plugin authors maximum
	 * flexibility without having to repeat themselves.
	 *
	 * The get_screen_callback() method uses a ReflectionClass object to
	 * determine whether your extension has provided a given callback.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $context Screen context. 'create', 'edit', or 'admin'.
	 * @param string $type Screen type. 'screen' or 'screen_save'. Default:
	 *        'screen'.
	 * @return callable A callable function handle.
	 */
	public function get_screen_callback( $context = '', $type = 'screen' ) {
		$callback = '';

		// Try the context-specific callback first
		$method  = $context . '_' . $type;
		$rmethod = $this->class_reflection->getMethod( $method );
		if ( isset( $rmethod->class ) && $this->class_name === $rmethod->class ) {
			$callback = array( $this, $method );
		}

		if ( empty( $callback ) ) {
			$fallback_method  = 'settings_' . $type;
			$rfallback_method = $this->class_reflection->getMethod( $fallback_method );
			if ( isset( $rfallback_method->class ) && $this->class_name === $rfallback_method->class ) {
				$callback = array( $this, $fallback_method );
			}
		}

		return $callback;
	}

	/**
	 * Recursive argument parsing.
	 *
	 * This acts like a multi-dimensional version of wp_parse_args() (minus
	 * the querystring parsing - you must pass arrays).
	 *
	 * Values from $a override those from $b; keys in $b that don't exist
	 * in $a are passed through.
	 *
	 * This is different from array_merge_recursive(), both because of the
	 * order of preference ($a overrides $b) and because of the fact that
	 * array_merge_recursive() combines arrays deep in the tree, rather
	 * than overwriting the b array with the a array.
	 *
	 * The implementation of this function is specific to the needs of
	 * BP_Experiment_Extension, where we know that arrays will always be
	 * associative, and that an argument under a given key in one array
	 * will be matched by a value of identical depth in the other one. The
	 * function is NOT designed for general use, and will probably result
	 * in unexpected results when used with data in the wild. See, eg,
	 * http://core.trac.wordpress.org/ticket/19888
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param array $a First set of arguments.
	 * @param array $b Second set of arguments.
	 * @return array Parsed arguments.
	 */
	public static function parse_args_r( &$a, $b ) {
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;

		foreach ( $a as $k => &$v ) {
			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = self::parse_args_r( $v, $r[ $k ] );
			} else {
				$r[ $k ] = $v;
			}
		}

		return $r;
	}

	/** Legacy Support ********************************************************/

	/*
	 * In BuddyPress 1.8, the recommended technique for configuring
	 * extensions changed from directly setting various object properties
	 * in the class constructor, to passing a configuration array to
	 * parent::init(). The following methods ensure that extensions created
	 * in the old way continue to work, by converting legacy configuration
	 * data to the new format.
	 */

	/**
	 * Provide access to otherwise unavailable object properties.
	 *
	 * This magic method is here for backward compatibility with plugins
	 * that refer to config properties that have moved to a different
	 * location (such as enable_create_step, which is now at
	 * $this->screens['create']['enabled']
	 *
	 * The legacy_properties array is set up in
	 * self::setup_legacy_properties().
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $key Property name.
	 * @return mixed The value if found, otherwise null.
	 */
	public function __get( $key ) {
		if ( isset( $this->legacy_properties[ $key ] ) ) {
			return $this->legacy_properties[ $key ];
		} elseif ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		} else {
			return null;
		}
	}

	/**
	 * Provide a fallback for isset( $this->foo ) when foo is unavailable.
	 *
	 * This magic method is here for backward compatibility with plugins
	 * that have set their class config options directly in the class
	 * constructor. The parse_legacy_properties() method of the current
	 * class needs to check whether any legacy keys have been put into the
	 * $this->data array.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $key Property name.
	 * @return bool True if the value is set, otherwise false.
	 */
	public function __isset( $key ) {
		if ( isset( $this->legacy_properties[ $key ] ) ) {
			return true;
		} elseif ( isset( $this->data[ $key ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Allow plugins to set otherwise unavailable object properties.
	 *
	 * This magic method is here for backward compatibility with plugins
	 * that may attempt to modify the experiment extension by manually assigning
	 * a value to an object property that no longer exists, such as
	 * $this->enable_create_step.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param string $key Property name.
	 * @param mixed $value Property value.
	 */
	public function __set( $key, $value ) {

		if ( empty( $this->initialized ) ) {
			$this->data[ $key ] = $value;
		}

		switch ( $key ) {
			case 'enable_create_step' :
				$this->screens['create']['enabled'] = $value;
				break;

			case 'enable_edit_item' :
				$this->screens['edit']['enabled'] = $value;
				break;

			case 'enable_admin_item' :
				$this->screens['admin']['enabled'] = $value;
				break;

			case 'create_step_position' :
				$this->screens['create']['position'] = $value;
				break;

			// Note: 'admin' becomes 'edit' to distinguish from Dashboard 'admin'
			case 'admin_name' :
				$this->screens['edit']['name'] = $value;
				break;

			case 'admin_slug' :
				$this->screens['edit']['slug'] = $value;
				break;

			case 'create_name' :
				$this->screens['create']['name'] = $value;
				break;

			case 'create_slug' :
				$this->screens['create']['slug'] = $value;
				break;

			case 'admin_metabox_context' :
				$this->screens['admin']['metabox_context'] = $value;
				break;

			case 'admin_metabox_priority' :
				$this->screens['admin']['metabox_priority'] = $value;
				break;

			default :
				$this->data[ $key ] = $value;
				break;
		}
	}

	/**
	 * Return a list of legacy properties.
	 *
	 * The legacy implementation of BP_Experiment_Extension used all of these
	 * object properties for configuration. Some have been moved.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @return array List of legacy property keys.
	 */
	protected function get_legacy_property_list() {
		return array(
			'name',
			'slug',
			'admin_name',
			'admin_slug',
			'create_name',
			'create_slug',
			'visibility',
			'create_step_position',
			'nav_item_position',
			'admin_metabox_context',
			'admin_metabox_priority',
			'enable_create_step',
			'enable_nav_item',
			'enable_edit_item',
			'enable_admin_item',
			'nav_item_name',
			'display_hook',
			'template_file',
		);
	}

	/**
	 * Parse legacy properties.
	 *
	 * The old standard for BP_Experiment_Extension was for plugins to register
	 * their settings as properties in their constructor. The new method is
	 * to pass a config array to the init() method. In order to support
	 * legacy plugins, we slurp up legacy properties, and later on we'll
	 * parse them into the new init() array.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	protected function parse_legacy_properties() {

		// Only run this one time
		if ( ! empty( $this->legacy_properties_converted ) ) {
			return;
		}

		$properties = $this->get_legacy_property_list();

		// By-reference variable for convenience
		$lpc =& $this->legacy_properties_converted;

		foreach ( $properties as $property ) {

			// No legacy config exists for this key
			if ( ! isset( $this->{$property} ) ) {
				continue;
			}

			// Grab the value and record it as appropriate
			$value = $this->{$property};

			switch ( $property ) {
				case 'enable_create_step' :
					$lpc['screens']['create']['enabled'] = (bool) $value;
					break;

				case 'enable_edit_item' :
					$lpc['screens']['edit']['enabled'] = (bool) $value;
					break;

				case 'enable_admin_item' :
					$lpc['screens']['admin']['enabled'] = (bool) $value;
					break;

				case 'create_step_position' :
					$lpc['screens']['create']['position'] = $value;
					break;

				// Note: 'admin' becomes 'edit' to distinguish from Dashboard 'admin'
				case 'admin_name' :
					$lpc['screens']['edit']['name'] = $value;
					break;

				case 'admin_slug' :
					$lpc['screens']['edit']['slug'] = $value;
					break;

				case 'create_name' :
					$lpc['screens']['create']['name'] = $value;
					break;

				case 'create_slug' :
					$lpc['screens']['create']['slug'] = $value;
					break;

				case 'admin_metabox_context' :
					$lpc['screens']['admin']['metabox_context'] = $value;
					break;

				case 'admin_metabox_priority' :
					$lpc['screens']['admin']['metabox_priority'] = $value;
					break;

				default :
					$lpc[ $property ] = $value;
					break;
			}
		}
	}

	/**
	 * Set up legacy properties.
	 *
	 * This method is responsible for ensuring that all legacy config
	 * properties are stored in an array $this->legacy_properties, so that
	 * they remain available to plugins that reference the variables at
	 * their old locations.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @see BP_Experiment_Extension::__get()
	 */
	protected function setup_legacy_properties() {

		// Only run this one time
		if ( ! empty( $this->legacy_properties ) ) {
			return;
		}

		$properties = $this->get_legacy_property_list();
		$params     = $this->params;
		$lp         =& $this->legacy_properties;

		foreach ( $properties as $property ) {
			switch ( $property ) {
				case 'enable_create_step' :
					$lp['enable_create_step'] = $params['screens']['create']['enabled'];
					break;

				case 'enable_edit_item' :
					$lp['enable_edit_item'] = $params['screens']['edit']['enabled'];
					break;

				case 'enable_admin_item' :
					$lp['enable_admin_item'] = $params['screens']['admin']['enabled'];
					break;

				case 'create_step_position' :
					$lp['create_step_position'] = $params['screens']['create']['position'];
					break;

				// Note: 'admin' becomes 'edit' to distinguish from Dashboard 'admin'
				case 'admin_name' :
					$lp['admin_name'] = $params['screens']['edit']['name'];
					break;

				case 'admin_slug' :
					$lp['admin_slug'] = $params['screens']['edit']['slug'];
					break;

				case 'create_name' :
					$lp['create_name'] = $params['screens']['create']['name'];
					break;

				case 'create_slug' :
					$lp['create_slug'] = $params['screens']['create']['slug'];
					break;

				case 'admin_metabox_context' :
					$lp['admin_metabox_context'] = $params['screens']['admin']['metabox_context'];
					break;

				case 'admin_metabox_priority' :
					$lp['admin_metabox_priority'] = $params['screens']['admin']['metabox_priority'];
					break;

				default :
					// All other items get moved over
					$lp[ $property ] = $params[ $property ];

					// Also reapply to the object, for backpat
					$this->{$property} = $params[ $property ];

					break;
			}
		}
	}
}

/**
 * Register a new Experiment Extension.
 *
 * @param string Name of the Extension class.
 * @return bool|null Returns false on failure, otherwise null.
 */
function bp_register_experiment_extension( $experiment_extension_class = '' ) {

	if ( ! class_exists( $experiment_extension_class ) ) {
		return false;
	}

	// Register the experiment extension on the bp_init action so we have access
	// to all plugins.
	add_action( 'bp_init', create_function( '', '
		$extension = new ' . $experiment_extension_class . ';
		add_action( "bp_actions", array( &$extension, "_register" ), 8 );
		add_action( "admin_init", array( &$extension, "_register" ) );
	' ), 11 );
}
