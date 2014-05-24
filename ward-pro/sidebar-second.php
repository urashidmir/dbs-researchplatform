<?php
/**
 * The secondary/right sidebar widgetized area.
 *
 * If no active widgets in sidebar, alert with default archive
 * widget will appear.
 *
 * @since 1.0.0
 */
?>
<div id="tertiary" <?php bavotasan_second_sidebar_class(); ?> role="complementary">
	<?php if ( ! dynamic_sidebar( 'second-sidebar' ) ) : ?>

	<?php if ( current_user_can( 'edit_theme_options' ) ) { ?>
		<div class="alert alert-warning"><?php printf( __( 'Add your own widgets by going to the %sWidgets admin page%s.', 'ward' ), '<a href="' . admin_url( 'widgets.php' ) . '">', '</a>' ); ?></div>
	<?php } ?>

	<aside id="archives" class="widget">
		<h3 class="widget-title"><?php _e( 'Archives', 'ward' ); ?></h3>
		<ul>
			<?php wp_get_archives( array( 'type' => 'monthly' ) ); ?>
		</ul>
	</aside>
	<?php endif; ?>
</div><!-- #tertiary.widget-area -->