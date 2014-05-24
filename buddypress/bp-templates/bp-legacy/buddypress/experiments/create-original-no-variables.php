<?php do_action( 'bp_before_create_experiment_page' ); ?>

<div id="buddypress">

	<?php do_action( 'bp_before_create_experiment_content_template' ); ?>

	<form action="<?php bp_experiment_creation_form_action(); ?>" method="post" id="create-experiment-form" class="standard-form" enctype="multipart/form-data">

		<?php do_action( 'bp_before_create_experiment' ); ?>

		<div class="item-list-tabs no-ajax" id="experiment-create-tabs" role="navigation">
			<ul>

				<?php bp_experiment_creation_tabs(); ?>

			</ul>
		</div>

		<?php do_action( 'template_notices' ); ?>

		<div class="item-body" id="experiment-create-body">

			<?php /* Experiment creation step 1: Basic experiment details */ ?>
			<?php if ( bp_is_experiment_creation_step( 'experiment-details' ) ) : ?>

				<?php do_action( 'bp_before_experiment_details_creation_step' ); ?>

				<div>
					<label for="experiment-name"><?php _e( 'Experiment Name (required)', 'buddypress' ); ?></label>
					<input type="text" name="experiment-name" id="experiment-name" aria-required="true" value="<?php bp_new_experiment_name(); ?>" />
				</div>

				<div>
					<label for="experiment-desc"><?php _e( 'Experiment Description (required)', 'buddypress' ); ?></label>
					<textarea name="experiment-desc" id="experiment-desc" aria-required="true"><?php bp_new_experiment_description(); ?></textarea>
				</div>

				<?php
				do_action( 'bp_after_experiment_details_creation_step' );
				do_action( 'experiments_custom_experiment_fields_editable' ); // @Deprecated

				wp_nonce_field( 'experiments_create_save_experiment-details' ); ?>

			<?php endif; ?>

			<?php /* Experiment creation step 2: Experiment settings */ ?>
			<?php if ( bp_is_experiment_creation_step( 'experiment-settings' ) ) : ?>

				<?php do_action( 'bp_before_experiment_settings_creation_step' ); ?>

				<h4><?php _e( 'Privacy Options', 'buddypress' ); ?></h4>

				<div class="radio">
					<label><input type="radio" name="experiment-status" value="public"<?php if ( 'public' == bp_get_new_experiment_status() || !bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e( 'This is a public experiment', 'buddypress' ); ?></strong>
						<ul>
							<li><?php _e( 'Any site member can join this experiment.', 'buddypress' ); ?></li>
							<li><?php _e( 'This experiment will be listed in the experiments directory and in search results.', 'buddypress' ); ?></li>
							<li><?php _e( 'Experiment content and activity will be visible to any site member.', 'buddypress' ); ?></li>
						</ul>
					</label>

					<label><input type="radio" name="experiment-status" value="private"<?php if ( 'private' == bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e( 'This is a private experiment', 'buddypress' ); ?></strong>
						<ul>
							<li><?php _e( 'Only users who request membership and are accepted can join the experiment.', 'buddypress' ); ?></li>
							<li><?php _e( 'This experiment will be listed in the experiments directory and in search results.', 'buddypress' ); ?></li>
							<li><?php _e( 'Experiment content and activity will only be visible to members of the experiment.', 'buddypress' ); ?></li>
						</ul>
					</label>

					<label><input type="radio" name="experiment-status" value="hidden"<?php if ( 'hidden' == bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e('This is a hidden experiment', 'buddypress' ); ?></strong>
						<ul>
							<li><?php _e( 'Only users who are invited can join the experiment.', 'buddypress' ); ?></li>
							<li><?php _e( 'This experiment will not be listed in the experiments directory or search results.', 'buddypress' ); ?></li>
							<li><?php _e( 'Experiment content and activity will only be visible to members of the experiment.', 'buddypress' ); ?></li>
						</ul>
					</label>
				</div>

				<h4><?php _e( 'Experiment Invitations', 'buddypress' ); ?></h4>

				<p><?php _e( 'Which members of this experiment are allowed to invite others?', 'buddypress' ); ?></p>

				<div class="radio">
					<label>
						<input type="radio" name="experiment-invite-status" value="members"<?php bp_experiment_show_invite_status_setting( 'members' ); ?> />
						<strong><?php _e( 'All experiment members', 'buddypress' ); ?></strong>
					</label>

					<label>
						<input type="radio" name="experiment-invite-status" value="mods"<?php bp_experiment_show_invite_status_setting( 'mods' ); ?> />
						<strong><?php _e( 'Experiment admins and mods only', 'buddypress' ); ?></strong>
					</label>

					<label>
						<input type="radio" name="experiment-invite-status" value="admins"<?php bp_experiment_show_invite_status_setting( 'admins' ); ?> />
						<strong><?php _e( 'Experiment admins only', 'buddypress' ); ?></strong>
					</label>
				</div>

				<?php if ( bp_is_active( 'forums' ) ) : ?>

					<h4><?php _e( 'Experiment Forums', 'buddypress' ); ?></h4>

					<?php if ( bp_forums_is_installed_correctly() ) : ?>

						<p><?php _e( 'Should this experiment have a forum?', 'buddypress' ); ?></p>

						<div class="checkbox">
							<label><input type="checkbox" name="experiment-show-forum" id="experiment-show-forum" value="1"<?php checked( bp_get_new_experiment_enable_forum(), true, true ); ?> /> <?php _e( 'Enable discussion forum', 'buddypress' ); ?></label>
						</div>
					<?php elseif ( is_super_admin() ) : ?>

						<p><?php printf( __( '<strong>Attention Site Admin:</strong> Experiment forums require the <a href="%s">correct setup and configuration</a> of a bbPress installation.', 'buddypress' ), bp_core_do_network_admin() ? network_admin_url( 'settings.php?page=bb-forums-setup' ) :  admin_url( 'admin.php?page=bb-forums-setup' ) ); ?></p>

					<?php endif; ?>

				<?php endif; ?>

				<?php do_action( 'bp_after_experiment_settings_creation_step' ); ?>

				<?php wp_nonce_field( 'experiments_create_save_experiment-settings' ); ?>

			<?php endif; ?>

			<?php /* Experiment creation step 3: Avatar Uploads */ ?>
			<?php if ( bp_is_experiment_creation_step( 'experiment-avatar' ) ) : ?>

				<?php do_action( 'bp_before_experiment_avatar_creation_step' ); ?>

				<?php if ( 'upload-image' == bp_get_avatar_admin_step() ) : ?>

					<div class="left-menu">

						<?php bp_new_experiment_avatar(); ?>

					</div><!-- .left-menu -->

					<div class="main-column">
						<p><?php _e( "Upload an image to use as an avatar for this experiment. The image will be shown on the main experiment page, and in search results.", 'buddypress' ); ?></p>

						<p>
							<input type="file" name="file" id="file" />
							<input type="submit" name="upload" id="upload" value="<?php esc_attr_e( 'Upload Image', 'buddypress' ); ?>" />
							<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
						</p>

						<p><?php _e( 'To skip the avatar upload process, hit the "Next Step" button.', 'buddypress' ); ?></p>
					</div><!-- .main-column -->

				<?php endif; ?>

				<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

					<h4><?php _e( 'Crop Experiment Avatar', 'buddypress' ); ?></h4>

					<img src="<?php bp_avatar_to_crop(); ?>" id="avatar-to-crop" class="avatar" alt="<?php esc_attr_e( 'Avatar to crop', 'buddypress' ); ?>" />

					<div id="avatar-crop-pane">
						<img src="<?php bp_avatar_to_crop(); ?>" id="avatar-crop-preview" class="avatar" alt="<?php esc_attr_e( 'Avatar preview', 'buddypress' ); ?>" />
					</div>

					<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php esc_attr_e( 'Crop Image', 'buddypress' ); ?>" />

					<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src(); ?>" />
					<input type="hidden" name="upload" id="upload" />
					<input type="hidden" id="x" name="x" />
					<input type="hidden" id="y" name="y" />
					<input type="hidden" id="w" name="w" />
					<input type="hidden" id="h" name="h" />

				<?php endif; ?>

				<?php do_action( 'bp_after_experiment_avatar_creation_step' ); ?>

				<?php wp_nonce_field( 'experiments_create_save_experiment-avatar' ); ?>

			<?php endif; ?>

			<?php /* Experiment creation step 4: Invite friends to experiment */ ?>
			<?php if ( bp_is_experiment_creation_step( 'experiment-invites' ) ) : ?>

				<?php do_action( 'bp_before_experiment_invites_creation_step' ); ?>

				<?php if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

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
							<p><?php _e('Select people to invite from your friends list.', 'buddypress' ); ?></p>
						</div>

						<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
						<ul id="friend-list" class="item-list" role="main">

						<?php if ( bp_experiment_has_invites() ) : ?>

							<?php while ( bp_experiment_invites() ) : bp_experiment_the_invite(); ?>

								<li id="<?php bp_experiment_invite_item_id(); ?>">

									<?php bp_experiment_invite_user_avatar(); ?>

									<h4><?php bp_experiment_invite_user_link(); ?></h4>
									<span class="activity"><?php bp_experiment_invite_user_last_active(); ?></span>

									<div class="action">
										<a class="remove" href="<?php bp_experiment_invite_user_remove_invite_url(); ?>" id="<?php bp_experiment_invite_item_id(); ?>"><?php _e( 'Remove Invite', 'buddypress' ); ?></a>
									</div>
								</li>

							<?php endwhile; ?>

							<?php wp_nonce_field( 'experiments_send_invites', '_wpnonce_send_invites' ); ?>

						<?php endif; ?>

						</ul>

					</div><!-- .main-column -->

				<?php else : ?>

					<div id="message" class="info">
						<p><?php _e( 'Once you have built up friend connections you will be able to invite others to your experiment.', 'buddypress' ); ?></p>
					</div>

				<?php endif; ?>

				<?php wp_nonce_field( 'experiments_create_save_experiment-invites' ); ?>

				<?php do_action( 'bp_after_experiment_invites_creation_step' ); ?>

			<?php endif; ?>

			<?php do_action( 'experiments_custom_create_steps' ); // Allow plugins to add custom experiment creation steps ?>

			<?php do_action( 'bp_before_experiment_creation_step_buttons' ); ?>

			<?php if ( 'crop-image' != bp_get_avatar_admin_step() ) : ?>

				<div class="submit" id="previous-next">

					<?php /* Previous Button */ ?>
					<?php if ( !bp_is_first_experiment_creation_step() ) : ?>

						<input type="button" value="<?php esc_attr_e( 'Back to Previous Step', 'buddypress' ); ?>" id="experiment-creation-previous" name="previous" onclick="location.href='<?php bp_experiment_creation_previous_link(); ?>'" />

					<?php endif; ?>

					<?php /* Next Button */ ?>
					<?php if ( !bp_is_last_experiment_creation_step() && !bp_is_first_experiment_creation_step() ) : ?>

						<input type="submit" value="<?php esc_attr_e( 'Next Step', 'buddypress' ); ?>" id="experiment-creation-next" name="save" />

					<?php endif;?>

					<?php /* Create Button */ ?>
					<?php if ( bp_is_first_experiment_creation_step() ) : ?>

						<input type="submit" value="<?php esc_attr_e( 'Create Experiment and Continue', 'buddypress' ); ?>" id="experiment-creation-create" name="save" />

					<?php endif; ?>

					<?php /* Finish Button */ ?>
					<?php if ( bp_is_last_experiment_creation_step() ) : ?>

						<input type="submit" value="<?php esc_attr_e( 'Finish', 'buddypress' ); ?>" id="experiment-creation-finish" name="save" />

					<?php endif; ?>
				</div>

			<?php endif;?>

			<?php do_action( 'bp_after_experiment_creation_step_buttons' ); ?>

			<?php /* Don't leave out this hidden field */ ?>
			<input type="hidden" name="experiment_id" id="experiment_id" value="<?php bp_new_experiment_id(); ?>" />

			<?php do_action( 'bp_directory_experiments_content' ); ?>

		</div><!-- .item-body -->

		<?php do_action( 'bp_after_create_experiment' ); ?>

	</form>

	<?php do_action( 'bp_after_create_experiment_content_template' ); ?>

</div>

<?php do_action( 'bp_after_create_experiment_page' ); ?>