<?php do_action( 'bp_before_experiment_invites_content' ); ?>

<?php if ( bp_has_experiments( 'type=invites&user_id=' . bp_loggedin_user_id() ) ) : ?>

	<ul id="experiment-list" class="invites item-list" role="main">

		<?php while ( bp_experiments() ) : bp_the_experiment(); ?>

			<li>
				<div class="item-avatar">
					<a href="<?php bp_experiment_permalink(); ?>"><?php bp_experiment_avatar( 'type=thumb&width=50&height=50' ); ?></a>
				</div>

				<h4><a href="<?php bp_experiment_permalink(); ?>"><?php bp_experiment_name(); ?></a><span class="small"> - <?php printf( _n( '1 member', '%d members', bp_get_experiment_total_members( false ), 'buddypress' ), bp_get_experiment_total_members( false )  ); ?></span></h4>

				<p class="desc">
					<?php bp_experiment_description_excerpt(); ?>
				</p>

				<?php do_action( 'bp_experiment_invites_item' ); ?>

				<div class="action">
					<a class="button accept" href="<?php bp_experiment_accept_invite_link(); ?>"><?php _e( 'Accept', 'buddypress' ); ?></a> &nbsp;
					<a class="button reject confirm" href="<?php bp_experiment_reject_invite_link(); ?>"><?php _e( 'Reject', 'buddypress' ); ?></a>

					<?php do_action( 'bp_experiment_invites_item_action' ); ?>

				</div>
			</li>

		<?php endwhile; ?>
	</ul>

<?php else: ?>

	<div id="message" class="info" role="main">
		<p><?php _e( 'You have no outstanding experiment invites.', 'buddypress' ); ?></p>
	</div>

<?php endif;?>

<?php do_action( 'bp_after_experiment_invites_content' ); ?>