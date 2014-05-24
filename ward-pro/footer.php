<?php
/**
 * The template for displaying the footer.
 *
 * Contains footer content and the closing of the
 * main, .grid and #page div elements.
 *
 * @since 1.0.0
 */
$bavotasan_theme_options = bavotasan_theme_options();
		/* Do not display sidebars if full width option selected on single
		   post/page templates */
		if ( is_singular() || is_404() || ( function_exists( 'is_bbpress' ) && is_bbpress() ) ) {
			if ( ! is_bavotasan_full_width() ) {
				$layout = $bavotasan_theme_options['layout'];
				if ( 5 != $layout && 6 != $layout )
					get_sidebar();

				if ( 3 == $layout || 4 == $layout || 5 == $layout )
					get_sidebar( 'second' );
			}
		 ?>
		</div> <!-- .row -->
		<?php } ?>
	</main> <!-- main -->
</div> <!-- #page -->

<footer id="footer" role="contentinfo">
	<div id="footer-content" class="container">
		<div class="row">
			<?php dynamic_sidebar( 'extended-footer' ); ?>
		</div><!-- .row -->

		<div class="row">
			<div class="col-lg-12">
				<?php $class = ( is_active_sidebar( 'extended-footer' ) ) ? ' active' : ''; ?>
				<span class="line<?php echo $class; ?>"></span>
				<span class="pull-left"><?php echo $bavotasan_theme_options['copyright']; ?></span>
				<span class="credit-link pull-right"><i class="icon-leaf"></i><?php printf( __( 'Designed by %s.', 'ward' ), '<a href="https://themes.bavotasan.com/">bavotasan.com</a>' ); ?></span>
			</div><!-- .col-lg-12 -->
		</div><!-- .row -->
	</div><!-- #footer-content.container -->
</footer><!-- #footer -->

<?php wp_footer(); ?>
</body>
</html>