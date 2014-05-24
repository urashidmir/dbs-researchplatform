<?php if ( bp_experiment_has_members( bp_ajax_querystring( 'experiment_members' ) ) ) : ?>

	<?php do_action( 'bp_before_experiment_members_content' ); ?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="member-count-top">

			<?php bp_members_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="member-pag-top">

			<?php bp_members_pagination_links(); ?>

		</div>

	</div>

	<?php do_action( 'bp_before_experiment_members_list' ); ?>

	<ul id="member-list" class="item-list" role="main">

		<?php while ( bp_experiment_members() ) : bp_experiment_the_member(); ?>

			<li>
				<a href="<?php bp_experiment_member_domain(); ?>">

					<?php bp_experiment_member_avatar_thumb(); ?>

				</a>

				<h5><?php bp_experiment_member_link(); ?></h5>
				<span class="activity"><?php bp_experiment_member_joined_since(); ?></span>

				<?php do_action( 'bp_experiment_members_list_item' ); ?>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					<div class="action">

						<?php bp_add_friend_button( bp_get_experiment_member_id(), bp_get_experiment_member_is_friend() ); ?>

						<?php do_action( 'bp_experiment_members_list_item_action' ); ?>

					</div>

				<?php endif; ?>
			</li>

		<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_experiment_members_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="member-count-bottom">

			<?php bp_members_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="member-pag-bottom">

			<?php bp_members_pagination_links(); ?>

		</div>

	</div>

	<?php do_action( 'bp_after_experiment_members_content' ); ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'This experiment has no members.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>
