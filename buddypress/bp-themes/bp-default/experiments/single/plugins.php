<?php get_header( 'buddypress' ); ?>

	<div id="content">
		<div class="padder">
			<?php if ( bp_has_experiments() ) : while ( bp_experiments() ) : bp_the_experiment(); ?>

			<?php do_action( 'bp_before_experiment_plugin_template' ); ?>

			<div id="item-header">
				<?php locate_template( array( 'experiments/single/experiment-header.php' ), true ); ?>
			</div><!-- #item-header -->

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
					<ul>
						<?php bp_get_options_nav(); ?>

						<?php do_action( 'bp_experiment_plugin_options_nav' ); ?>
					</ul>
				</div>
			</div><!-- #item-nav -->

			<div id="item-body">

				<?php do_action( 'bp_before_experiment_body' ); ?>

				<?php do_action( 'bp_template_content' ); ?>

				<?php do_action( 'bp_after_experiment_body' ); ?>
			</div><!-- #item-body -->

			<?php do_action( 'bp_after_experiment_plugin_template' ); ?>

			<?php endwhile; endif; ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php get_sidebar( 'buddypress' ); ?>

<?php get_footer( 'buddypress' ); ?>