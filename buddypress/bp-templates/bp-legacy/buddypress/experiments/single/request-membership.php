<?php do_action( 'bp_before_experiment_request_membership_content' ); ?>

<?php if ( !bp_experiment_has_requested_membership() ) : ?>
	<p><?php printf( __( "You are requesting to become a member of the experiment '%s'.", "buddypress" ), bp_get_experiment_name( false ) ); ?></p>

	<form action="<?php bp_experiment_form_action('request-membership' ); ?>" method="post" name="request-membership-form" id="request-membership-form" class="standard-form">
		<label for="experiment-request-membership-comments"><?php _e( 'Comments (optional)', 'buddypress' ); ?></label>
		<textarea name="experiment-request-membership-comments" id="experiment-request-membership-comments"></textarea>

		<?php do_action( 'bp_experiment_request_membership_content' ); ?>

		<p><input type="submit" name="experiment-request-send" id="experiment-request-send" value="<?php esc_attr_e( 'Send Request', 'buddypress' ); ?>" />

		<?php wp_nonce_field( 'experiments_request_membership' ); ?>
	</form><!-- #request-membership-form -->
<?php endif; ?>

<?php do_action( 'bp_after_experiment_request_membership_content' ); ?>
