<?php

/**
 * BuddyPress - Experiments Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php do_action( 'bp_before_experiments_loop' ); ?>

<?php if ( bp_has_experiments( bp_ajax_querystring( 'experiments' ) ) ) : ?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="experiment-dir-count-top">

			<?php bp_experiments_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="experiment-dir-pag-top">

			<?php bp_experiments_pagination_links(); ?>

		</div>

	</div>

	<?php do_action( 'bp_before_directory_experiments_list' ); ?>

	<ul id="experiments-list" class="item-list" role="main">

	<?php while ( bp_experiments() ) : bp_the_experiment(); ?>

		<li <?php bp_experiment_class(); ?>>
			<div class="item-avatar">
				<a href="<?php bp_experiment_permalink(); ?>"><?php bp_experiment_avatar( 'type=thumb&width=50&height=50' ); ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_experiment_permalink(); ?>"><?php bp_experiment_name(); ?></a></div>
				<div class="item-meta"><span class="activity"><?php printf( __( 'active %s', 'buddypress' ), bp_get_experiment_last_active() ); ?></span></div>

				<div class="item-desc"><?php bp_experiment_description_excerpt(); ?></div>

				<?php do_action( 'bp_directory_experiments_item' ); ?>

			</div>

			<div class="action">

				<?php do_action( 'bp_directory_experiments_actions' ); ?>

				<div class="meta">

					<?php bp_experiment_type(); ?> / <?php bp_experiment_member_count(); ?>

				</div>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_experiments_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="experiment-dir-count-bottom">

			<?php bp_experiments_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="experiment-dir-pag-bottom">

			<?php bp_experiments_pagination_links(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no experiments found.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_experiments_loop' ); ?>
