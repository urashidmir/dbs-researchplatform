<div id="buddypress">

	<?php if ( bp_has_experiments() ) : while ( bp_experiments() ) : bp_the_experiment(); ?>

	<?php do_action( 'bp_before_experiment_home_content' ); ?>

	<div id="item-header" role="complementary">

		<?php bp_get_template_part( 'experiments/single/experiment-header' ); ?>

	</div><!-- #item-header -->

	<div id="item-nav">
		<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
			<ul>

				<?php bp_get_options_nav(); ?>

				<?php do_action( 'bp_experiment_options_nav' ); ?>

			</ul>
		</div>
	</div><!-- #item-nav -->

	<div id="item-body">

		<?php do_action( 'bp_before_experiment_body' );

		/**
		 * Does this next bit look familiar? If not, go check out WordPress's
		 * /wp-includes/template-loader.php file.
		 *
		 * @todo A real template hierarchy? Gasp!
		 */

		// Experiment is visible
		if ( bp_experiment_is_visible() ) : 

			// Looking at home location
			if ( bp_is_experiment_home() ) :

				// Use custom front if one exists
				$custom_front = bp_locate_template( array( 'experiments/single/front.php' ), false, true );
				if     ( ! empty( $custom_front   ) ) : load_template( $custom_front, true );

				
                // Default to report
                elseif ( bp_is_active( 'activity' ) ) : bp_get_template_part( 'experiments/single/report' );
            
                // Default to activity
				//elseif ( bp_is_active( 'activity' ) ) : bp_get_template_part( 'experiments/single/activity' );

				// Otherwise show members
				elseif ( bp_is_active( 'members'  ) ) : bp_experiments_members_template_part();

				endif;
				
			// Not looking at home
			else :

				// Experiment Admin
				if     ( bp_is_experiment_admin_page() ) : bp_get_template_part( 'experiments/single/admin'        );

				// Experiment Activity
				elseif ( bp_is_experiment_activity()   ) : bp_get_template_part( 'experiments/single/activity'     );

				// Experiment Members
				elseif ( bp_is_experiment_members()    ) : bp_experiments_members_template_part();

				// Experiment Invitations
				elseif ( bp_is_experiment_invites()    ) : bp_get_template_part( 'experiments/single/send-invites' );

				// Old experiment forums
				elseif ( bp_is_experiment_forum()      ) : bp_get_template_part( 'experiments/single/forum'        );

				// Membership request
				elseif ( bp_is_experiment_membership_request() ) : bp_get_template_part( 'experiments/single/request-membership' );

				// Anything else (plugins mostly)
				else                                : bp_get_template_part( 'experiments/single/plugins'      );

				endif;
			endif;

		// Experiment is not visible
		elseif ( ! bp_experiment_is_visible() ) :

			// Membership request
			if ( bp_is_experiment_membership_request() ) :
				bp_get_template_part( 'experiments/single/request-membership' );

			// The experiment is not visible, show the status message
			else :

				do_action( 'bp_before_experiment_status_message' ); ?>

				<div id="message" class="info">
					<p><?php bp_experiment_status_message(); ?></p>
				</div>

				<?php do_action( 'bp_after_experiment_status_message' );

			endif;
		endif;

		do_action( 'bp_after_experiment_body' ); ?>

	</div><!-- #item-body -->

	<?php do_action( 'bp_after_experiment_home_content' ); ?>

	<?php endwhile; endif; ?>

</div><!-- #buddypress -->
