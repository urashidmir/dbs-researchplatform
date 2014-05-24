<?php

/**
 * BuddyPress - Users experiments
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<?php if ( bp_is_my_profile() ) bp_get_options_nav(); ?>

		<?php if ( !bp_is_current_action( 'invites' ) ) : ?>

			<li id="experiments-order-select" class="last filter">

				<label for="experiments-sort-by"><?php _e( 'Order By:', 'buddypress' ); ?></label>
				<select id="experiments-sort-by">
					<option value="active"><?php _e( 'Last Active', 'buddypress' ); ?></option>
					<option value="popular"><?php _e( 'Most Members', 'buddypress' ); ?></option>
					<option value="newest"><?php _e( 'Newly Created', 'buddypress' ); ?></option>
					<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ); ?></option>

					<?php do_action( 'bp_member_experiment_order_options' ); ?>

				</select>
			</li>

		<?php endif; ?>

	</ul>
</div><!-- .item-list-tabs -->

<?php

switch ( bp_current_action() ) :

	// Home/My experiments
	case 'my-experiments' :
		do_action( 'bp_before_member_experiments_content' ); ?>

		<div class="experiments myexperiments">

			<?php bp_get_template_part( 'experiments/experiments-loop' ); ?>

		</div>

		<?php do_action( 'bp_after_member_experiments_content' );
		break;

	// experiment Invitations
	case 'invites' :
		bp_get_template_part( 'members/single/experiments/invites' );
		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;