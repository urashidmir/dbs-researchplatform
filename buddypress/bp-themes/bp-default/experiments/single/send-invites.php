<?php do_action( 'bp_before_experiment_send_invites_content' ); ?>

<?php if ( bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

	<form action="<?php bp_experiment_send_invite_form_action(); ?>" method="post" id="send-invite-form" class="standard-form" role="main">

		<div class="left-menu">

			<div id="invite-list">
				<ul>
					<?php bp_new_experiment_invite_friend_list(); ?>
				</ul>

				<?php wp_nonce_field( 'experiments_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ); ?>
			</div>

		</div><!-- .left-menu -->

		<div class="main-column">

			<div id="message" class="info">
				<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
			</div>

			<?php do_action( 'bp_before_experiment_send_invites_list' ); ?>

			<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
			<ul id="friend-list" class="item-list">
			<?php if ( bp_experiment_has_invites() ) : ?>

				<?php while ( bp_experiment_invites() ) : bp_experiment_the_invite(); ?>

					<li id="<?php bp_experiment_invite_item_id(); ?>">
						<?php bp_experiment_invite_user_avatar(); ?>

						<h4><?php bp_experiment_invite_user_link(); ?></h4>
						<span class="activity"><?php bp_experiment_invite_user_last_active(); ?></span>

						<?php do_action( 'bp_experiment_send_invites_item' ); ?>

						<div class="action">
							<a class="button remove" href="<?php bp_experiment_invite_user_remove_invite_url(); ?>" id="<?php bp_experiment_invite_item_id(); ?>"><?php _e( 'Remove Invite', 'buddypress' ); ?></a>

							<?php do_action( 'bp_experiment_send_invites_item_action' ); ?>
						</div>
					</li>

				<?php endwhile; ?>

			<?php endif; ?>
			</ul><!-- #friend-list -->

			<?php do_action( 'bp_after_experiment_send_invites_list' ); ?>

		</div><!-- .main-column -->

		<div class="clear"></div>

		<div class="submit">
			<input type="submit" name="submit" id="submit" value="<?php esc_attr_e( 'Send Invites', 'buddypress' ); ?>" />
		</div>

		<?php wp_nonce_field( 'experiments_send_invites', '_wpnonce_send_invites'); ?>

		<?php /* This is important, don't forget it */ ?>
		<input type="hidden" name="experiment_id" id="experiment_id" value="<?php bp_experiment_id(); ?>" />

	</form><!-- #send-invite-form -->

<?php else : ?>

	<div id="message" class="info" role="main">
		<p><?php _e( 'Once you have built up friend connections you will be able to invite others to your experiment.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_experiment_send_invites_content' ); ?>
