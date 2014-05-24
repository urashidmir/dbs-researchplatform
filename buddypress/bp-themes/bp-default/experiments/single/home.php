<?php get_header( 'buddypress' ); ?>

	<div id="content">
		<div class="padder">

			<?php if ( bp_has_experiments() ) : while ( bp_experiments() ) : bp_the_experiment(); ?>

			<?php do_action( 'bp_before_experiment_home_content' ); ?>

			<div id="item-header" role="complementary">

				<?php locate_template( array( 'experiments/single/experiment-header.php' ), true ); ?>

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
						$custom_front = locate_template( array( 'experiments/single/front.php' ) );
						if     ( ! empty( $custom_front   ) ) : load_template( $custom_front, true );

						// Default to activity
						elseif ( bp_is_active( 'activity' ) ) : locate_template( array( 'experiments/single/activity.php' ), true );

						// Otherwise show members
						elseif ( bp_is_active( 'members'  ) ) : locate_template( array( 'experiments/single/members.php'  ), true );

						endif;

					// Not looking at home
					else :

						// Experiment Admin
						if     ( bp_is_experiment_admin_page() ) : locate_template( array( 'experiments/single/admin.php'        ), true );

						// Experiment Activity
						elseif ( bp_is_experiment_activity()   ) : locate_template( array( 'experiments/single/activity.php'     ), true );

						// Experiment Members
						elseif ( bp_is_experiment_members()    ) : locate_template( array( 'experiments/single/members.php'      ), true );

						// Experiment Invitations
						elseif ( bp_is_experiment_invites()    ) : locate_template( array( 'experiments/single/send-invites.php' ), true );

						// Old experiment forums
						elseif ( bp_is_experiment_forum()      ) : locate_template( array( 'experiments/single/forum.php'        ), true );

						// Anything else (plugins mostly)
						else                                : locate_template( array( 'experiments/single/plugins.php'      ), true );

						endif;
					endif;

				// Experiment is not visible
				elseif ( ! bp_experiment_is_visible() ) :
					// Membership request
					if ( bp_is_experiment_membership_request() ) :
						locate_template( array( 'experiments/single/request-membership.php' ), true );

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

		</div><!-- .padder -->
	</div><!-- #content -->

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>
