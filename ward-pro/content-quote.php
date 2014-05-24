<?php
/**
 * The template for displaying posts in the Quote post format
 *
 * @since 1.0.0
 */
$bavotasan_theme_options = bavotasan_theme_options();
?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php if ( ! is_single() ) { ?>
		<div class="container">
			<div class="row">
				<div class="col-md-8 col-md-offset-2">
		<?php } ?>
			    <i class="icon-quote-left quote"></i>
			    <div class="entry-content">
				    <?php the_content( $bavotasan_theme_options['read_more'] ); ?>
			    </div><!-- .entry-content -->

			    <?php get_template_part( 'content', 'footer' ); ?>
		<?php if ( ! is_single() ) { ?>
				</div>
			</div>
		</div>
		<?php } ?>
	</article>