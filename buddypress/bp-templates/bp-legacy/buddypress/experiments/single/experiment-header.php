<?php

do_action( 'bp_before_experiment_header' );

?>

<div id="item-actions">

	<?php if ( bp_experiment_is_visible() ) : ?>

		<h3><?php _e( 'Experiment Admins', 'buddypress' ); ?></h3>

		<?php bp_experiment_list_admins();

		do_action( 'bp_after_experiment_menu_admins' );

		if ( bp_experiment_has_moderators() ) :
			do_action( 'bp_before_experiment_menu_mods' ); ?>

			<h3><?php _e( 'Experiment Mods' , 'buddypress' ); ?></h3>

			<?php bp_experiment_list_mods();

			do_action( 'bp_after_experiment_menu_mods' );

		endif;

	endif; ?>

</div><!-- #item-actions -->

<div id="item-header-avatar">
	<a href="<?php bp_experiment_permalink(); ?>" title="<?php bp_experiment_name(); ?>">

		<?php bp_experiment_avatar(); ?>

	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">
	<span class="highlight"><?php bp_experiment_type(); ?></span>
	<span class="activity"><?php printf( __( 'active %s', 'buddypress' ), bp_get_experiment_last_active() ); ?></span>

	<?php do_action( 'bp_before_experiment_header_meta' ); ?>

	<div id="item-meta">

		<?php bp_experiment_description(); ?>

		<div id="item-buttons">

			<?php do_action( 'bp_experiment_header_actions' ); ?>

		</div><!-- #item-buttons -->

		<?php do_action( 'bp_experiment_header_meta' ); ?>

	</div>
</div><!-- #item-header-content -->

<?php
do_action( 'bp_after_experiment_header' );
do_action( 'template_notices' );
?>