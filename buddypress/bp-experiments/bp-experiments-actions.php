<?php
/**
 * BuddyPress Experiments Actions
 *
 * Action functions are exactly the same as screen functions, however they do
 * not have a template screen associated with them. Usually they will send the
 * user back to the default screen after execution.
 *
 * @package BuddyPress
 * @subpackage ExperimentsActions
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Catch and process experiment creation form submissions.
 */
    
    
function experiments_action_report_experiment() {
        global $bp;
        
        // If the user is already a member of that experiment
        //if (experiments_is_user_member( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) && !experiments_is_user_banned( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {
        
        
        // If the save, upload or skip button is hit
        if ( isset( $_POST['report'] ) ) {
            
            
            //if ( empty( $_POST['variable1'] ) || empty( $_POST['variable2'] ) || !strlen( trim( $_POST['variable1'] ) ) || !strlen( trim( $_POST['variable2'] ) ) ) {
            bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
            bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/'. bp_get_current_experiment_name()  . '/' );
            //}
            
            /*
             $variable1_name =  ($_POST['variable1_name'] );
             $variable1_type =  ($_POST['variable1_type'] );
             $variable2_name =  ($_POST['variable2_name'] );
             $variable2_type =  ($_POST['variable2_type'] );
             
             if ( false == experiments_create_variables( array( 'experiment_id' => $bp->experiments->new_experiment_id, 'variable1_name' => $variable1_name, 'variable1_type' => $variable1_type, 'variable2_name' => $variable2_name, 'variable2_type' => $variable2_type ) ) ) {
             bp_core_add_message( __( 'There was an error saving experiment variables, please try again.', 'buddypress' ), 'error' );
             bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
             }
             */
            //  }//end if
            
        }//end if
    }

    

    
    function experiments_action_create_experiment_single(){
        global $bp;
        // If we're not at domain.org/experiments/create/ then return false
        if ( !bp_is_experiments_component() || !bp_is_current_action( 'create' ) )
            return false;
        
        if ( !is_user_logged_in() )
            return false;
        
        if ( !bp_user_can_create_experiments() ) {
            bp_core_add_message( __( 'Sorry, you are not allowed to create experiments.', 'buddypress' ), 'error' );
            bp_core_redirect( trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() ) );
        }
        else{
            //bp_core_add_message( __( 'Umar, you are allowed to create experiments.', 'buddypress' ), 'error' );
            //bp_core_redirect( trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() ) );
        }
        
        // Make sure creation steps are in the right order
        experiments_action_sort_creation_steps();
        
        // If no current step is set, reset everything so we can start a fresh experiment creation
        $bp->experiments->current_create_step = bp_action_variable( 1 );
        if ( !bp_get_experiments_current_create_step() ) {
            unset( $bp->experiments->current_create_step );
            unset( $bp->experiments->completed_create_steps );
            
            setcookie( 'bp_new_experiment_id', false, time() - 1000, COOKIEPATH );
            setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );
            
            $reset_steps = true;
            $keys        = array_keys( $bp->experiments->experiment_creation_steps );
            bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . array_shift( $keys ) . '/' );
        }

        
        // If the save, upload or skip button is hit, lets calculate what we need to save
        if ( isset( $_POST['save'] ) ) {
            
            //bp_core_add_message( __( 'Save button pressed', 'buddypress' ), 'error' );
            
            //check_admin_referer( 'experiments_create_save_' . bp_get_experiments_current_create_step() );
            check_admin_referer( 'experiments_create_save_experiment-details');
      
            $new_experiment_id = isset( $bp->experiments->new_experiment_id ) ? $bp->experiments->new_experiment_id : 0;
            
            $bp->experiments->new_experiment_id = experiments_create_experiment( array( 'experiment_id' => $new_experiment_id, 'name' => $_POST['experiment-name'], 'description' => $_POST['experiment-desc'], 'slug' => experiments_check_slug( sanitize_title( esc_attr( $_POST['experiment-name'] ) ) ), 'date_created' => bp_core_current_time(), 'status' => 'public' ) );
            
            if($bp->experiments->new_experiment_id)
            {
                //Set the current experiment
                $bp->experiments->current_experiment = experiments_get_experiment( array( 'experiment_id' => $bp->experiments->new_experiment_id ) );
                
                $name = $_POST['name'];
                $type = $_POST['type'];
                
                //bp_core_add_message( __( 'Experiment id created '. $bp->experiments->new_experiment_id , 'buddypress' ));
                
                //Add variables
                foreach( $name as $key => $variable_name ) {
                    if ( empty( $variable_name) || !strlen( trim( $variable_name ) )
                        ) {
                        bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
                        bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                    }
                    
                }//end for
                
                foreach( $type as $key => $variable_type ) {
                    if ( empty( $variable_type) || !strlen( trim( $variable_type ) )
                        ) {
                        bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
                        bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                    }
                    
                }//end for
                
                
                if(count($name) != count($type))
                {
                    bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
                    bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                }//end if
                
                
                if ( false == experiments_create_variables( array( 'experiment_id' => $bp->experiments->new_experiment_id, 'name' => $name, 'type' => $type ) ) ) {
                    bp_core_add_message( __( 'There was an error saving experiment variables, please try again.', 'buddypress' ), 'error' );
                    bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                }
                
                
                //Add experiment settings
                $experiment_status = 'public';
                $experiment_enable_forum = 1;
                
                /*
                if ( !isset($_POST['experiment-show-forum']) ) {
                    $experiment_enable_forum = 0;
                } else {
                    // Create the forum if enable_forum = 1
                    if ( bp_is_active( 'forums' ) && !experiments_get_experimentmeta( $bp->experiments->new_experiment_id, 'forum_id' ) ) {
                        experiments_new_experiment_forum();
                    }
                }
                */
                
                if ( 'private' == $_POST['experiment-status'] )
                    $experiment_status = 'private';
                else if ( 'hidden' == $_POST['experiment-status'] )
                    $experiment_status = 'hidden';
                
                if ( !$bp->experiments->new_experiment_id = experiments_create_experiment( array( 'experiment_id' => $bp->experiments->new_experiment_id, 'status' => $experiment_status, 'enable_forum' => $experiment_enable_forum ) ) ) {
                    bp_core_add_message( __( 'There was an error saving experiment details, please try again.', 'buddypress' ), 'error' );
                    bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                }
                
                // Set the invite status
                // Checked against a whitelist for security
                $allowed_invite_status = apply_filters( 'experiments_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
                $invite_status	       = !empty( $_POST['experiment-invite-status'] ) && in_array( $_POST['experiment-invite-status'], (array) $allowed_invite_status ) ? $_POST['experiment-invite-status'] : 'members';
                
                experiments_update_experimentmeta( $bp->experiments->new_experiment_id, 'invite_status', $invite_status );
                
                //send invitations
                if ( ! empty( $_POST['friends'] ) ) {
                    foreach ( (array) $_POST['friends'] as $friend ) {
                        experiments_invite_user( array(
                                                       'user_id'  => $friend,
                                                       'experiment_id' => $bp->experiments->new_experiment_id,
                                                       ) );
                    }
                }
                
                experiments_send_invites( bp_loggedin_user_id(), $bp->experiments->new_experiment_id );
                
            }//end if
            else{
                //$message = 'Experiment id not created '+ $bp->experiments->new_experiment_id;
                //bp_core_add_message($message);
                //bp_core_add_message( __('Experiment id not created' , 'buddypress' ) );
            }
            

            //do_action( 'experiments_create_experiment_step_save_' . bp_get_experiments_current_create_step() );
            do_action( 'experiments_create_experiment_step_save_experiment-details');
            do_action( 'experiments_create_experiment_step_complete' ); // Mostly for clearing cache on a generic action name
            
            do_action( 'experiments_experiment_create_complete', $bp->experiments->new_experiment_id );
            bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );

            
            $completed_create_steps = isset( $bp->experiments->completed_create_steps ) ? $bp->experiments->completed_create_steps : array();
            if ( !in_array( bp_get_experiments_current_create_step(), $completed_create_steps ) )
                $bp->experiments->completed_create_steps[] = bp_get_experiments_current_create_step();
            
            // Reset cookie info
            setcookie( 'bp_new_experiment_id', $bp->experiments->new_experiment_id, time()+60*60*24, COOKIEPATH );
            setcookie( 'bp_completed_create_steps', serialize( $bp->experiments->completed_create_steps ), time()+60*60*24, COOKIEPATH );
        
            
            // If we have completed all steps and hit done on the final step we
            // can redirect to the completed experiment
            $keys = array_keys( $bp->experiments->experiment_creation_steps );
            if ( count( $bp->experiments->completed_create_steps ) == count( $keys ) && bp_get_experiments_current_create_step() == array_pop( $keys ) ) {
                unset( $bp->experiments->current_create_step );
                unset( $bp->experiments->completed_create_steps );
                
                // Once we compelete all steps, record the experiment creation in the activity stream.
                experiments_record_activity( array(
                                                   'type' => 'created_experiment',
                                                   'item_id' => $bp->experiments->new_experiment_id
                                                   ) );
                
                do_action( 'experiments_experiment_create_complete', $bp->experiments->new_experiment_id );
                
                bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );
            } else {
                
                foreach ( $keys as $key ) {
                    if ( $key == bp_get_experiments_current_create_step() ) {
                        $next = 1;
                        continue;
                    }
                    
                    if ( isset( $next ) ) {
                        $next_step = $key;
                        break;
                    }
                }
                
                bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . $next_step . '/' );
            }
            
        }//end if (save)
        
        
        
        
        /*
         // Remove invitations
         if ( 'experiment-invites' === bp_get_experiments_current_create_step() && ! empty( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ) {
         if ( ! check_admin_referer( 'experiments_invite_uninvite_user' ) ) {
         return false;
         }
         
         $message = __( 'Invite successfully removed', 'buddypress' );
         $error   = false;
         
         if( ! experiments_uninvite_user( (int) $_REQUEST['user_id'], $bp->experiments->new_experiment_id ) ) {
         $message = __( 'There was an error removing the invite', 'buddypress' );
         $error   = 'error';
         }
         
         bp_core_add_message( $message, $error );
         bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/experiment-invites/' );
         }//end f ( 'experiment-invites' ===
         
         // Experiment avatar is handled separately
         if ( 'experiment-avatar' == bp_get_experiments_current_create_step() && isset( $_POST['upload'] ) ) {
         if ( ! isset( $bp->avatar_admin ) ) {
         $bp->avatar_admin = new stdClass();
         }
         
         if ( !empty( $_FILES ) && isset( $_POST['upload'] ) ) {
         // Normally we would check a nonce here, but the experiment save nonce is used instead
         
         // Pass the file to the avatar upload handler
         if ( bp_core_avatar_handle_upload( $_FILES, 'experiments_avatar_upload_dir' ) ) {
         $bp->avatar_admin->step = 'crop-image';
         
         // Make sure we include the jQuery jCrop file for image cropping
         add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
         }
         }
         
         // If the image cropping is done, crop the image and save a full/thumb version
         if ( isset( $_POST['avatar-crop-submit'] ) && isset( $_POST['upload'] ) ) {
         // Normally we would check a nonce here, but the experiment save nonce is used instead
         
         if ( !bp_core_avatar_handle_crop( array( 'object' => 'experiment', 'avatar_dir' => 'experiment-avatars', 'item_id' => $bp->experiments->current_experiment->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
         bp_core_add_message( __( 'There was an error saving the experiment avatar, please try uploading again.', 'buddypress' ), 'error' );
         else
         bp_core_add_message( __( 'The experiment avatar was uploaded successfully!', 'buddypress' ) );
         }
         }//end if ( 'experiment-avatar' == bp_get_experiments_current_create_step()
         */
        
        bp_core_load_template( apply_filters( 'experiments_template_create_experiment', 'experiments/create' ) );
        
    }//end function experiments_action_create_experiment_single()
    add_action( 'bp_actions', 'experiments_action_create_experiment_single' );
    
    
    
function experiments_action_create_experiment() {
	global $bp;

	// If we're not at domain.org/experiments/create/ then return false
	if ( !bp_is_experiments_component() || !bp_is_current_action( 'create' ) )
		return false;

	if ( !is_user_logged_in() )
		return false;

 	if ( !bp_user_can_create_experiments() ) {
		bp_core_add_message( __( 'Sorry, you are not allowed to create experiments.', 'buddypress' ), 'error' );
		bp_core_redirect( trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() ) );
	}

	// Make sure creation steps are in the right order
	experiments_action_sort_creation_steps();

	// If no current step is set, reset everything so we can start a fresh experiment creation
	$bp->experiments->current_create_step = bp_action_variable( 1 );
	if ( !bp_get_experiments_current_create_step() ) {
		unset( $bp->experiments->current_create_step );
		unset( $bp->experiments->completed_create_steps );

		setcookie( 'bp_new_experiment_id', false, time() - 1000, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );

		$reset_steps = true;
		$keys        = array_keys( $bp->experiments->experiment_creation_steps );
		bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . array_shift( $keys ) . '/' );
	}

	// If this is a creation step that is not recognized, just redirect them back to the first screen
	if ( bp_get_experiments_current_create_step() && empty( $bp->experiments->experiment_creation_steps[bp_get_experiments_current_create_step()] ) ) {
		bp_core_add_message( __('There was an error saving experiment details. Please try again.', 'buddypress'), 'error' );
		bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/' );
	}

	// Fetch the currently completed steps variable
	if ( isset( $_COOKIE['bp_completed_create_steps'] ) && !isset( $reset_steps ) )
		$bp->experiments->completed_create_steps = unserialize( stripslashes( $_COOKIE['bp_completed_create_steps'] ) );

	// Set the ID of the new experiment, if it has already been created in a previous step
	if ( isset( $_COOKIE['bp_new_experiment_id'] ) ) {
		$bp->experiments->new_experiment_id = $_COOKIE['bp_new_experiment_id'];
		$bp->experiments->current_experiment = experiments_get_experiment( array( 'experiment_id' => $bp->experiments->new_experiment_id ) );

		// Only allow the experiment creator to continue to edit the new experiment
		if ( ! bp_is_experiment_creator( $bp->experiments->current_experiment, bp_loggedin_user_id() ) ) {
			bp_core_add_message( __( 'Only the experiment creator may continue editing this experiment.', 'buddypress' ), 'error' );
			bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/' );
		}
	}

	// If the save, upload or skip button is hit, lets calculate what we need to save
	if ( isset( $_POST['save'] ) ) {

		// Check the nonce
		check_admin_referer( 'experiments_create_save_' . bp_get_experiments_current_create_step() );

		if ( 'experiment-details' == bp_get_experiments_current_create_step() ) {
			if ( empty( $_POST['experiment-name'] ) || empty( $_POST['experiment-desc'] ) || !strlen( trim( $_POST['experiment-name'] ) ) || !strlen( trim( $_POST['experiment-desc'] ) ) ) {
				bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
			}

			$new_experiment_id = isset( $bp->experiments->new_experiment_id ) ? $bp->experiments->new_experiment_id : 0;

			if ( !$bp->experiments->new_experiment_id = experiments_create_experiment( array( 'experiment_id' => $new_experiment_id, 'name' => $_POST['experiment-name'], 'description' => $_POST['experiment-desc'], 'slug' => experiments_check_slug( sanitize_title( esc_attr( $_POST['experiment-name'] ) ) ), 'date_created' => bp_core_current_time(), 'status' => 'public' ) ) ) {
				bp_core_add_message( __( 'There was an error saving experiment details, please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
			}
		}//end if ( 'experiment-details' == bp_get_experiments_current_create_step() )


        
        if ( 'experiment-variables' == bp_get_experiments_current_create_step() ) {
            
            
            $name = $_POST['name'];
            $type = $_POST['type'];
            
            foreach( $name as $key => $variable_name ) {
                if ( empty( $variable_name) || !strlen( trim( $variable_name ) )
                    ) {
                    bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
                    bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                }
                
            }//end for
            
            foreach( $type as $key => $variable_type ) {
                if ( empty( $variable_type) || !strlen( trim( $variable_type ) )
                    ) {
                    bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
                    bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
                }
                
            }//end for
            
            
            if(count($name) != count($type))
            {
                bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
                bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
            }//end if
            
            
            if ( false == experiments_create_variables( array( 'experiment_id' => $bp->experiments->new_experiment_id, 'name' => $name, 'type' => $type ) ) ) {
                bp_core_add_message( __( 'There was an error saving experiment variables, please try again.', 'buddypress' ), 'error' );
                bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
            }
            
        }//end if ( 'experiment-variables' == bp_get_experiments_current_create_step() )
        
        
        
		if ( 'experiment-settings' == bp_get_experiments_current_create_step() ) {
			$experiment_status = 'public';
			$experiment_enable_forum = 1;

			if ( !isset($_POST['experiment-show-forum']) ) {
				$experiment_enable_forum = 0;
			} else {
				// Create the forum if enable_forum = 1
				if ( bp_is_active( 'forums' ) && !experiments_get_experimentmeta( $bp->experiments->new_experiment_id, 'forum_id' ) ) {
					experiments_new_experiment_forum();
				}
			}

			if ( 'private' == $_POST['experiment-status'] )
				$experiment_status = 'private';
			else if ( 'hidden' == $_POST['experiment-status'] )
				$experiment_status = 'hidden';

			if ( !$bp->experiments->new_experiment_id = experiments_create_experiment( array( 'experiment_id' => $bp->experiments->new_experiment_id, 'status' => $experiment_status, 'enable_forum' => $experiment_enable_forum ) ) ) {
				bp_core_add_message( __( 'There was an error saving experiment details, please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . bp_get_experiments_current_create_step() . '/' );
			}

			// Set the invite status
			// Checked against a whitelist for security
			$allowed_invite_status = apply_filters( 'experiments_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
			$invite_status	       = !empty( $_POST['experiment-invite-status'] ) && in_array( $_POST['experiment-invite-status'], (array) $allowed_invite_status ) ? $_POST['experiment-invite-status'] : 'members';

			experiments_update_experimentmeta( $bp->experiments->new_experiment_id, 'invite_status', $invite_status );
		}//end if ( 'experiment-settings' )

		if ( 'experiment-invites' === bp_get_experiments_current_create_step() ) {
			if ( ! empty( $_POST['friends'] ) ) {
				foreach ( (array) $_POST['friends'] as $friend ) {
					experiments_invite_user( array(
						'user_id'  => $friend,
						'experiment_id' => $bp->experiments->new_experiment_id,
					) );
				}
			}

			experiments_send_invites( bp_loggedin_user_id(), $bp->experiments->new_experiment_id );
		}//end if ( 'experiment-sinvites' )

		do_action( 'experiments_create_experiment_step_save_' . bp_get_experiments_current_create_step() );
		do_action( 'experiments_create_experiment_step_complete' ); // Mostly for clearing cache on a generic action name

		/**
		 * Once we have successfully saved the details for this step of the creation process
		 * we need to add the current step to the array of completed steps, then update the cookies
		 * holding the information
		 */
		$completed_create_steps = isset( $bp->experiments->completed_create_steps ) ? $bp->experiments->completed_create_steps : array();
		if ( !in_array( bp_get_experiments_current_create_step(), $completed_create_steps ) )
			$bp->experiments->completed_create_steps[] = bp_get_experiments_current_create_step();

		// Reset cookie info
		setcookie( 'bp_new_experiment_id', $bp->experiments->new_experiment_id, time()+60*60*24, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', serialize( $bp->experiments->completed_create_steps ), time()+60*60*24, COOKIEPATH );

		// If we have completed all steps and hit done on the final step we
		// can redirect to the completed experiment
		$keys = array_keys( $bp->experiments->experiment_creation_steps );
		if ( count( $bp->experiments->completed_create_steps ) == count( $keys ) && bp_get_experiments_current_create_step() == array_pop( $keys ) ) {
			unset( $bp->experiments->current_create_step );
			unset( $bp->experiments->completed_create_steps );

			// Once we compelete all steps, record the experiment creation in the activity stream.
			experiments_record_activity( array(
				'type' => 'created_experiment',
				'item_id' => $bp->experiments->new_experiment_id
			) );

			do_action( 'experiments_experiment_create_complete', $bp->experiments->new_experiment_id );

			bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );
		} else {
			/**
			 * Since we don't know what the next step is going to be (any plugin can insert steps)
			 * we need to loop the step array and fetch the next step that way.
			 */
			foreach ( $keys as $key ) {
				if ( $key == bp_get_experiments_current_create_step() ) {
					$next = 1;
					continue;
				}

				if ( isset( $next ) ) {
					$next_step = $key;
					break;
				}
			}

			bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/' . $next_step . '/' );
		}
	}//end if (save)

	// Remove invitations
	if ( 'experiment-invites' === bp_get_experiments_current_create_step() && ! empty( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ) {
		if ( ! check_admin_referer( 'experiments_invite_uninvite_user' ) ) {
			return false;
		}

		$message = __( 'Invite successfully removed', 'buddypress' );
		$error   = false;

		if( ! experiments_uninvite_user( (int) $_REQUEST['user_id'], $bp->experiments->new_experiment_id ) ) {
			$message = __( 'There was an error removing the invite', 'buddypress' );
			$error   = 'error';
		}

		bp_core_add_message( $message, $error );
		bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/create/step/experiment-invites/' );
	}//end f ( 'experiment-invites' ===

	// Experiment avatar is handled separately
	if ( 'experiment-avatar' == bp_get_experiments_current_create_step() && isset( $_POST['upload'] ) ) {
		if ( ! isset( $bp->avatar_admin ) ) {
			$bp->avatar_admin = new stdClass();
		}

		if ( !empty( $_FILES ) && isset( $_POST['upload'] ) ) {
			// Normally we would check a nonce here, but the experiment save nonce is used instead

			// Pass the file to the avatar upload handler
			if ( bp_core_avatar_handle_upload( $_FILES, 'experiments_avatar_upload_dir' ) ) {
				$bp->avatar_admin->step = 'crop-image';

				// Make sure we include the jQuery jCrop file for image cropping
				add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
			}
		}

		// If the image cropping is done, crop the image and save a full/thumb version
		if ( isset( $_POST['avatar-crop-submit'] ) && isset( $_POST['upload'] ) ) {
			// Normally we would check a nonce here, but the experiment save nonce is used instead

			if ( !bp_core_avatar_handle_crop( array( 'object' => 'experiment', 'avatar_dir' => 'experiment-avatars', 'item_id' => $bp->experiments->current_experiment->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
				bp_core_add_message( __( 'There was an error saving the experiment avatar, please try uploading again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'The experiment avatar was uploaded successfully!', 'buddypress' ) );
		}
	}//end if ( 'experiment-avatar' == bp_get_experiments_current_create_step()

	bp_core_load_template( apply_filters( 'experiments_template_create_experiment', 'experiments/create' ) );
}
add_action( 'bp_actions', 'experiments_action_create_experiment' );

function experiments_action_join_experiment() {
	global $bp;

	if ( !bp_is_single_item() || !bp_is_experiments_component() || !bp_is_current_action( 'join' ) )
		return false;

	// Nonce check
	if ( !check_admin_referer( 'experiments_join_experiment' ) )
		return false;

	// Skip if banned or already a member
	if ( !experiments_is_user_member( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) && !experiments_is_user_banned( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {

		// User wants to join a experiment that is not public
		if ( $bp->experiments->current_experiment->status != 'public' ) {
			if ( !experiments_check_user_has_invite( bp_loggedin_user_id(), $bp->experiments->current_experiment->id ) ) {
				bp_core_add_message( __( 'There was an error joining the experiment.', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );
			}
		}

		// User wants to join any experiment
		if ( !experiments_join_experiment( $bp->experiments->current_experiment->id ) )
			bp_core_add_message( __( 'There was an error joining the experiment.', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'You joined the experiment!', 'buddypress' ) );

		bp_core_redirect( bp_get_experiment_permalink( $bp->experiments->current_experiment ) );
	}

	bp_core_load_template( apply_filters( 'experiments_template_experiment_home', 'experiments/single/home' ) );
}
add_action( 'bp_actions', 'experiments_action_join_experiment' );

/**
 * Catch and process "Leave Experiment" button clicks.
 *
 * When a experiment member clicks on the "Leave Experiment" button from a experiment's page,
 * this function is run.
 *
 * Note: When leaving a experiment from the experiment directory, AJAX is used and
 * another function handles this. See {@link bp_legacy_theme_ajax_joinleave_experiment()}.
 *
 * @since BuddyPress (1.2.4)
 */
function experiments_action_leave_experiment() {
	if ( ! bp_is_single_item() || ! bp_is_experiments_component() || ! bp_is_current_action( 'leave-experiment' ) ) {
		return false;
	}

	// Nonce check
	if ( ! check_admin_referer( 'experiments_leave_experiment' ) ) {
		return false;
	}

	// User wants to leave any experiment
	if ( experiments_is_user_member( bp_loggedin_user_id(), bp_get_current_experiment_id() ) ) {
		$bp = buddypress();

		// Stop sole admins from abandoning their experiment
		$experiment_admins = experiments_get_experiment_admins( bp_get_current_experiment_id() );

	 	if ( 1 == count( $experiment_admins ) && $experiment_admins[0]->user_id == bp_loggedin_user_id() ) {
			bp_core_add_message( __( 'This experiment must have at least one admin', 'buddypress' ), 'error' );
		} elseif ( ! experiments_leave_experiment( $bp->experiments->current_experiment->id ) ) {
			bp_core_add_message( __( 'There was an error leaving the experiment.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'You successfully left the experiment.', 'buddypress' ) );
		}

		$redirect = bp_get_experiment_permalink( experiments_get_current_experiment() );

		if( 'hidden' == $bp->experiments->current_experiment->status ) {
			$redirect = trailingslashit( bp_loggedin_user_domain() . bp_get_experiments_slug() );
		}

		bp_core_redirect( $redirect );
	}

	bp_core_load_template( apply_filters( 'experiments_template_experiment_home', 'experiments/single/home' ) );
}
add_action( 'bp_actions', 'experiments_action_leave_experiment' );

/**
 * Sort the experiment creation steps.
 */
function experiments_action_sort_creation_steps() {
	global $bp;

	if ( !bp_is_experiments_component() || !bp_is_current_action( 'create' ) )
		return false;

	if ( !is_array( $bp->experiments->experiment_creation_steps ) )
		return false;

	foreach ( (array) $bp->experiments->experiment_creation_steps as $slug => $step ) {
		while ( !empty( $temp[$step['position']] ) )
			$step['position']++;

		$temp[$step['position']] = array( 'name' => $step['name'], 'slug' => $slug );
	}

	// Sort the steps by their position key
	ksort($temp);
	unset($bp->experiments->experiment_creation_steps);

	foreach( (array) $temp as $position => $step )
		$bp->experiments->experiment_creation_steps[$step['slug']] = array( 'name' => $step['name'], 'position' => $position );
}

/**
 * Catch requests for a random experiment page (example.com/experiments/?random-experiment) and redirect.
 */
function experiments_action_redirect_to_random_experiment() {

	if ( bp_is_experiments_component() && isset( $_GET['random-experiment'] ) ) {
		$experiment = BP_Experiments_Experiment::get_random( 1, 1 );

		bp_core_redirect( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() . '/' . $experiment['experiments'][0]->slug . '/' );
	}
}
add_action( 'bp_actions', 'experiments_action_redirect_to_random_experiment' );

/**
 * Load the activity feed for the current experiment.
 *
 * @since BuddyPress (1.2.0)
 */
function experiments_action_experiment_feed() {

	// get current experiment
	$experiment = experiments_get_current_experiment();

	if ( ! bp_is_active( 'activity' ) || ! bp_is_experiments_component() || ! $experiment || ! bp_is_current_action( 'feed' ) )
		return false;

	// if experiment isn't public or if logged-in user is not a member of the experiment, do
	// not output the experiment activity feed
	if ( ! bp_experiment_is_visible( $experiment ) ) {
		return false;
	}

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'experiment',

		/* translators: Experiment activity RSS title - "[Site Name] | [Experiment Name] | Activity" */
		'title'         => sprintf( __( '%1$s | %2$s | Activity', 'buddypress' ), bp_get_site_name(), bp_get_current_experiment_name() ),

		'link'          => bp_get_experiment_permalink( $experiment ),
		'description'   => sprintf( __( "Activity feed for the experiment, %s.", 'buddypress' ), bp_get_current_experiment_name() ),
		'activity_args' => array(
			'object'           => buddypress()->experiments->id,
			'primary_id'       => bp_get_current_experiment_id(),
			'display_comments' => 'threaded'
		)
	) );
}
add_action( 'bp_actions', 'experiments_action_experiment_feed' );