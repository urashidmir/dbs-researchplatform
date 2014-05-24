<?php
/**
 * Defining constants
 *
 * @since 1.0.0
 */
$bavotasan_theme_data = wp_get_theme();
define( 'BAVOTASAN_THEME_URL', get_template_directory_uri() );
define( 'BAVOTASAN_THEME_TEMPLATE', get_template_directory() );
define( 'BAVOTASAN_THEME_VERSION', trim( $bavotasan_theme_data->Version ) );
define( 'BAVOTASAN_THEME_NAME', $bavotasan_theme_data->Name );
define( 'BAVOTASAN_THEME_FILE', get_option( 'template' ) );
define( 'BAVOTASAN_THEME_CODE', 'war' );

/**
 * Includes
 *
 * @since 1.0.0
 */
require( BAVOTASAN_THEME_TEMPLATE . '/library/theme-options.php' ); // Functions for theme options page
require( BAVOTASAN_THEME_TEMPLATE . '/library/custom-css-editor.php' ); // Custom CSS editor
require( BAVOTASAN_THEME_TEMPLATE . '/library/shortcodes.php' ); // Functions to add shortcodes
require( BAVOTASAN_THEME_TEMPLATE . '/library/import-export.php' ); // Functions for the import/export admin page
require( BAVOTASAN_THEME_TEMPLATE . '/library/custom-metaboxes.php' ); // Functions for home page alignment
require( BAVOTASAN_THEME_TEMPLATE . '/library/theme-updater.php' ); // Functions for update API

/**
 * Prepare the content width
 *
 * @since 1.0.0
 */
$bavotasan_theme_options = bavotasan_theme_options();
if ( ! isset( $content_width ) )
	$content_width = $bavotasan_theme_options['width'] - 30;

add_action( 'after_setup_theme', 'bavotasan_setup' );
if ( ! function_exists( 'bavotasan_setup' ) ) :
/**
 * Initial setup
 *
 * This function is attached to the 'after_setup_theme' action hook.
 *
 * @uses	load_theme_textdomain()
 * @uses	get_locale()
 * @uses	BAVOTASAN_THEME_TEMPLATE
 * @uses	add_theme_support()
 * @uses	add_editor_style()
 * @uses	add_custom_background()
 * @uses	add_custom_image_header()
 * @uses	register_default_headers()
 *
 * @since 1.0.0
 */
function bavotasan_setup() {
	load_theme_textdomain( 'ward', BAVOTASAN_THEME_TEMPLATE . '/library/languages' );

	// Add default posts and comments RSS feed links to <head>.
	add_theme_support( 'automatic-feed-links' );

	// This theme styles the visual editor with editor-style.css to match the theme style.
	add_editor_style();

	// This theme uses wp_nav_menu() in one location.
	register_nav_menu( 'primary', __( 'Primary Menu', 'ward' ) );

	// Add support for a variety of post formats
	add_theme_support( 'post-formats', array( 'gallery', 'image', 'video', 'audio', 'quote', 'link', 'status', 'aside' ) );

	// This theme uses Featured Images (also known as post thumbnails) for archive pages
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'home-page', 500, 400, true );

	// Add support for custom backgrounds
	add_theme_support( 'custom-background' );

	// Add HTML5 elements
	add_theme_support( 'html5', array( 'comment-list', 'search-form', 'comment-form', ) );

	// Infinite scroll
	add_theme_support( 'infinite-scroll', array(
	    'type' => 'click',
	    'container' => 'primary',
		'wrapper' => false,
	) );
}
endif; // bavotasan_setup

add_action( 'wp_head', 'bavotasan_styles' );
/**
 * Add a style block to the theme for the current link color.
 *
 * This function is attached to the 'wp_head' action hook.
 *
 * @since 1.0.0
 */
function bavotasan_styles() {
	$bavotasan_theme_options = bavotasan_theme_options();
	?>
<style>
<?php if ( 'on' == $bavotasan_theme_options['boxed'] ) { ?>
.boxed #page { max-width: <?php echo $bavotasan_theme_options['width'] + 180; ?>px; }
.container { max-width: <?php echo $bavotasan_theme_options['width']; ?>px; }
<?php } else { ?>
.container { max-width: <?php echo $bavotasan_theme_options['width']; ?>px; }
<?php } ?>
#carousel-wrap { background-image: url(<?php echo get_header_image(); ?>); background-repeat: no-repeat; background-size: cover; }
a { color: <?php echo $bavotasan_theme_options['link_color']; ?>; }
a:hover { color: <?php echo $bavotasan_theme_options['link_hover_color']; ?>; }
body { <?php echo bavotasan_prepare_font( $bavotasan_theme_options['main_text_font'] ); ?>; }
main { color: <?php echo $bavotasan_theme_options['main_text_color']; ?>; }
h1, h2, h3, h4, h5, h6, h1 a, h2 a, h3 a, h4 a, h5 a, h6 a { color: <?php echo $bavotasan_theme_options['headers_color']; ?>; <?php echo bavotasan_prepare_font( $bavotasan_theme_options['headers_font'] ); ?>; }
.entry-title, .entry-title a { <?php echo bavotasan_prepare_font( $bavotasan_theme_options['post_title_font'] ); ?>; }
.entry-meta, .entry-meta a { <?php echo bavotasan_prepare_font( $bavotasan_theme_options['post_meta_font'] ); ?>; }
.post-category, .post-category a, .post-format, post-format a, .page-title { <?php echo bavotasan_prepare_font( $bavotasan_theme_options['post_category_font'] ); ?>; }
.middle-circle { color: <?php echo $bavotasan_theme_options['widget_area_color']; ?>; }
#home-page-widgets { background-color: <?php echo $bavotasan_theme_options['widget_area_color']; ?>; }
<?php if ( $custom_css = get_option( 'ward_custom_css' ) ) { ?>
/* Custom CSS */
<?php echo $custom_css . "\n"; ?>
/* eof Custom CSS */
	<?php } ?>
</style>
	<?php
}

add_action( 'admin_bar_menu', 'bavotasan_admin_bar_menu', 999 );
/**
 * Add menu item to toolbar
 *
 * This function is attached to the 'admin_bar_menu' action hook.
 *
 * @param	array $wp_admin_bar
 *
 * @since 2.0.4
 */
function bavotasan_admin_bar_menu( $wp_admin_bar ) {
    if ( current_user_can( 'edit_theme_options' ) && is_admin_bar_showing() )
    	$wp_admin_bar->add_node( array( 'id' => 'bavotasan_toolbar', 'title' => BAVOTASAN_THEME_NAME, 'href' => admin_url( 'customize.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'bavotasan_add_js' );
if ( ! function_exists( 'bavotasan_add_js' ) ) :
/**
 * Load all JavaScript to header
 *
 * This function is attached to the 'wp_enqueue_scripts' action hook.
 *
 * @uses	is_admin()
 * @uses	is_singular()
 * @uses	get_option()
 * @uses	wp_enqueue_script()
 * @uses	BAVOTASAN_THEME_URL
 *
 * @since 1.0.0
 */
function bavotasan_add_js() {
	$bavotasan_theme_options = bavotasan_theme_options();
	$slider_options = get_option( 'ward_slider_settings' );

	$var = array(
		'carousel' => false,
		'tooltip' => false,
		'tabs' => false
	);

	if ( is_singular() ) {
		if ( get_option( 'thread_comments' ) )
			wp_enqueue_script( 'comment-reply' );

		global $post;
		$content = $post->post_content;
		if ( false !== strpos( $content, '[widetext' ) )
			wp_enqueue_script( 'widetext', BAVOTASAN_THEME_URL .'/library/js/widetext.min.js', array( 'jquery' ), '1.0.1', true );

		if ( false !== strpos( $content, '[carousel' ) )
			$var['carousel'] = true;

		if ( false !== strpos( $content, '[tooltip' ) )
			$var['tooltip'] = true;

		if ( false !== strpos( $content, '[tabs' ) )
			$var['tabs'] = true;
	}

	if ( is_front_page() && $slider_options['autoplay'] )
		$var['carousel'] = true;

	wp_enqueue_script( 'bootstrap', BAVOTASAN_THEME_URL .'/library/js/bootstrap.min.js', array( 'jquery' ), '3.0.0', true );

	wp_enqueue_script( 'theme_js', BAVOTASAN_THEME_URL .'/library/js/theme.js', array( 'bootstrap' ), '', true );
	wp_localize_script( 'theme_js', 'theme_js_vars', $var );
	wp_enqueue_style( 'theme_stylesheet', get_stylesheet_uri() );

	// Fonts stuff
	$selected_fonts = array(
		$bavotasan_theme_options['main_text_font'],
		$bavotasan_theme_options['headers_font'],
		$bavotasan_theme_options['post_title_font'],
		$bavotasan_theme_options['post_meta_font'],
		$bavotasan_theme_options['post_category_font'],
	);
	$selected_fonts = array_unique( $selected_fonts );

	$google_fonts = bavotasan_google_fonts();
	$font_string = '';
	foreach ( $selected_fonts as $font ) {
		if ( array_key_exists( $font, $google_fonts ) ) {
			$font = explode( ',', $font );
			$font = $font[0];
			switch( $font ) {
				case 'Open Sans':
					$font = 'Open+Sans:400,700';
					break;
				case 'Lato':
					$font = 'Lato:400';
					break;
				case 'Lato Bold':
					$font = 'Lato:900';
					break;
				case 'Lato Light':
					$font = 'Lato:300';
					break;
				case 'Raleway':
					$font = 'Raleway:100';
					break;
				case 'Exo':
					$font = 'Exo:100';
					break;
				case 'Arvo Bold':
					$font = 'Arvo:900';
					break;
			}
			$font = str_replace( " ", "+", $font );
			$font_string .= $font . '|';
		}
	}
	if ( $font_string )
		wp_enqueue_style( 'google_fonts', 'http://fonts.googleapis.com/css?family=' . $font_string, false, null, 'all' );

	wp_enqueue_style( 'font_awesome', 'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css', false, null, 'all' );
}
endif; // bavotasan_add_js

add_action( 'widgets_init', 'bavotasan_widgets_init' );
if ( ! function_exists( 'bavotasan_widgets_init' ) ) :
/**
 * Creating the two sidebars
 *
 * This function is attached to the 'widgets_init' action hook.
 *
 * @uses	register_sidebar()
 *
 * @since 1.0.0
 */
function bavotasan_widgets_init() {
	$bavotasan_theme_options = bavotasan_theme_options();
	require( BAVOTASAN_THEME_TEMPLATE . '/library/widgets/widget-image-icon.php' ); // Custom Image/Icon Text widget

	register_sidebar( array(
		'name' => __( 'First Sidebar', 'ward' ),
		'id' => 'sidebar',
		'description' => __( 'This is the first sidebar widgetized area. All defaults widgets work great here.', 'ward' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array(
		'name' => __( 'Second Sidebar', 'ward' ),
		'id' => 'second-sidebar',
		'description' => __( 'This is the second sidebar widgetized area. All defaults widgets work great here.', 'ward' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array(
		'name' => __( 'Home Page Top Area', 'ward' ),
		'id' => 'home-page-top-area',
		'description' => __( 'Widgetized area on the home page directly below the navigation menu. Specifically designed for 4 text widgets. Must be turned on in the Layout options on the Theme Options admin page.', 'ward' ),
		'before_widget' => '<aside id="%1$s" class="home-widget col-md-3 %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="home-widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array(
		'name' => __( 'Extended Footer', 'ward' ),
		'id' => 'extended-footer',
		'description' => __( 'This is the extended footer widgetized area. Widgets will appear in three columns. All defaults widgets work great here.', 'ward' ),
		'before_widget' => '<aside id="%1$s" class="footer-widget ' . $bavotasan_theme_options['extended_footer_columns'] . ' %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
}
endif; // bavotasan_widgets_init

if ( !function_exists( 'bavotasan_pagination' ) ) :
/**
 * Add pagination
 *
 * @uses	paginate_links()
 * @uses	add_query_arg()
 *
 * @since 1.0.0
 */
function bavotasan_pagination() {
	global $wp_query;

	// Don't print empty markup if there's only one page.
	if ( $wp_query->max_num_pages < 2 )
		return;
	?>
	<nav class="navigation" role="navigation">
		<div class="container">
			<div class="row">
				<div class="col-sm-12">
					<h1 class="screen-reader-text"><?php _e( 'Posts navigation', 'ward' ); ?></h1>
					<?php if ( get_next_posts_link() ) : ?>
					<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'ward' ) ); ?></div>
					<?php endif; ?>

					<?php if ( get_previous_posts_link() ) : ?>
					<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'ward' ) ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</nav><!-- .navigation -->
	<?php
}
endif; // bavotasan_pagination

add_filter( 'wp_title', 'bavotasan_filter_wp_title', 10, 2 );
if ( !function_exists( 'bavotasan_filter_wp_title' ) ) :
/**
 * Filters the page title appropriately depending on the current page
 *
 * @uses	get_bloginfo()
 * @uses	is_home()
 * @uses	is_front_page()
 *
 * @since 1.0.0
 */
function bavotasan_filter_wp_title( $title, $sep ) {
	global $paged, $page;

	if ( is_feed() )
		return $title;

	// Add the site name.
	$title .= get_bloginfo( 'name' );

	// Add the site description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		$title = "$title $sep $site_description";

	// Add a page number if necessary.
	if ( $paged >= 2 || $page >= 2 )
		$title = "$title $sep " . sprintf( __( 'Page %s', 'ward' ), max( $paged, $page ) );

	return $title;
}
endif; // bavotasan_filter_wp_title

if ( ! function_exists( 'bavotasan_comment' ) ) :
/**
 * Callback function for comments
 *
 * Referenced via wp_list_comments() in comments.php.
 *
 * @uses	get_avatar()
 * @uses	get_comment_author_link()
 * @uses	get_comment_date()
 * @uses	get_comment_time()
 * @uses	edit_comment_link()
 * @uses	comment_text()
 * @uses	comments_open()
 * @uses	comment_reply_link()
 *
 * @since 1.0.0
 */
function bavotasan_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;

	switch ( $comment->comment_type ) :
		case '' :
		?>
		<li <?php comment_class(); ?>>
			<div id="comment-<?php comment_ID(); ?>" class="comment-body">
				<div class="comment-avatar">
					<?php echo get_avatar( $comment, 60 ); ?>
				</div>
				<div class="comment-content">
					<div class="comment-author">
						<?php echo get_comment_author_link() . ' '; ?>
					</div>
					<div class="comment-meta">
						<?php
						printf( __( '%1$s at %2$s', 'ward' ), get_comment_date(), get_comment_time() );
						edit_comment_link( __( '(edit)', 'ward' ), '  ', '' );
						?>
					</div>
					<div class="comment-text">
						<?php if ( '0' == $comment->comment_approved ) { echo '<em>' . __( 'Your comment is awaiting moderation.', 'ward' ) . '</em>'; } ?>
						<?php comment_text() ?>
					</div>
					<?php if ( $args['max_depth'] != $depth && comments_open() && 'pingback' != $comment->comment_type ) { ?>
					<div class="reply">
						<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
					</div>
					<?php } ?>
				</div>
			</div>
			<?php
			break;

		case 'pingback'  :
		case 'trackback' :
		?>
		<li id="comment-<?php comment_ID(); ?>" class="pingback">
			<div class="comment-body">
				<i class="icon-paper-clip"></i>
				<?php _e( 'Pingback:', 'ward' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __( '(edit)', 'ward' ), ' ' ); ?>
			</div>
			<?php
			break;
	endswitch;
}
endif; // bavotasan_comment

add_filter( 'excerpt_more', 'bavotasan_excerpt' );
if ( ! function_exists( 'bavotasan_excerpt' ) ) :
/**
 * Adds a read more link to all excerpts
 *
 * This function is attached to the 'excerpt_more' filter hook.
 *
 * @param	int $more
 *
 * @return	Custom excerpt ending
 *
	 * @since 1.0.0
 */
function bavotasan_excerpt( $more ) {
	return '&hellip;';
}
endif; // bavotasan_excerpt

add_filter( 'wp_trim_excerpt', 'bavotasan_excerpt_more' );
if ( ! function_exists( 'bavotasan_excerpt_more' ) ) :
/**
 * Adds a read more link to all excerpts
 *
 * This function is attached to the 'wp_trim_excerpt' filter hook.
 *
 * @param	string $text
 *
 * @return	Custom read more link
 *
 * @since 1.0.0
 */
function bavotasan_excerpt_more( $text ) {
	$bavotasan_theme_options = bavotasan_theme_options();
	return '<p class="lead">' . $text . '</p><p class="more-link-p"><a class="btn btn-' . $bavotasan_theme_options['read_more_color'] . '" href="' . get_permalink( get_the_ID() ) . '">' . $bavotasan_theme_options['read_more'] . '</a></p>';
}
endif; // bavotasan_excerpt_more

add_filter( 'the_content_more_link', 'bavotasan_content_more_link', 10, 2 );
if ( ! function_exists( 'bavotasan_content_more_link' ) ) :
/**
 * Customize read more link for content
 *
 * This function is attached to the 'the_content_more_link' filter hook.
 *
 * @param	string $link
 * @param	string $text
 *
 * @return	Custom read more link
 *
 * @since 1.0.0
 */
function bavotasan_content_more_link( $link, $text ) {
	$bavotasan_theme_options = bavotasan_theme_options();
	return '<p class="more-link-p"><a class="btn btn-' . $bavotasan_theme_options['read_more_color'] . '" href="' . get_permalink( get_the_ID() ) . '">' . $text . '</a></p>';
}
endif; // bavotasan_content_more_link

add_filter( 'excerpt_length', 'bavotasan_excerpt_length', 999 );
if ( ! function_exists( 'bavotasan_excerpt_length' ) ) :
/**
 * Custom excerpt length
 *
 * This function is attached to the 'excerpt_length' filter hook.
 *
 * @param	int $length
 *
 * @return	Custom excerpt length
 *
 * @since 1.0.0
 */
function bavotasan_excerpt_length( $length ) {
	return 60;
}
endif; // bavotasan_excerpt_length

/*
 * Remove default gallery styles
 */
add_filter( 'use_default_gallery_style', '__return_false' );

/**
 * Full width conditional check
 *
 * @since 1.0.0
 *
 * @return	boolean
 */
function is_bavotasan_full_width() {
	$single_layout = ( is_singular() ) ? get_post_meta( get_the_ID(), 'bavotasan_single_layout', true ) : '';
	if ( 'on' == $single_layout )
		return true;
}

/**
 * Create the required attributes for the #primary container
 *
 * @since 1.0.0
 */
function bavotasan_primary_attr() {
	$bavotasan_theme_options = bavotasan_theme_options();

	$layout = $bavotasan_theme_options['layout'];
	$column = ( ! is_bavotasan_full_width() ) ? $bavotasan_theme_options['primary'] : '';
	$column = ( is_singular() && is_bavotasan_full_width() ) ? 'col-sm-12' : $column;
	$column = ( is_singular() || is_404() || ( function_exists( 'is_bbpress' ) && is_bbpress() ) ) ? $column : 'col-sm-12';
	$class = ( 6 == $layout ) ? $column . ' centered' : $column;
	$class = ( ( 1 == $layout || 3 == $layout ) && is_singular() && ! is_front_page() ) ? $class . ' pull-right' : $class;

	echo 'class="' . $class . '"';
}

/**
 * Create the required classes for the #secondary sidebar container
 *
 * @since 1.0.0
 */
function bavotasan_sidebar_class() {
	$bavotasan_theme_options = bavotasan_theme_options();

	$layout = $bavotasan_theme_options['layout'];
	if ( 1 == $layout || 2 == $layout || 6 == $layout ) {
		$end = ( 2 == $layout ) ? ' end' : '';
		$class = str_replace( 'col-md-', '', $bavotasan_theme_options['primary'] );
		$class = 'col-md-' . ( 12 - $class ) . $end;
	} else {
		$class = $bavotasan_theme_options['secondary'];
	}

	echo 'class="' . $class . '"';
}

/**
 * Create the required classes for the #tertiary sidebar container
 *
 * @since 1.0.0
 */
function bavotasan_second_sidebar_class() {
	$bavotasan_theme_options = bavotasan_theme_options();

	$layout = $bavotasan_theme_options['layout'];
	$end = ( 4 == $layout || 5 == $layout ) ? ' end' : '';
	$primary = str_replace( 'col-md-', '', $bavotasan_theme_options['primary'] );
	$secondary = str_replace( 'col-md-', '', $bavotasan_theme_options['secondary'] );
	$class = 'col-md-' . ( 12 - $primary - $secondary ) . $end;

	echo 'class="' . $class . '"';
}

add_filter( 'next_posts_link_attributes', 'bavotasan_add_attr' );
add_filter( 'previous_posts_link_attributes', 'bavotasan_add_attr' );
/**
 * Add 'btn' class to previous and next posts links
 *
 * This function is attached to the 'next_posts_link_attributes' and 'previous_posts_link_attributes' filter hook.
 *
 * @param	string $format
 *
 * @return	Modified string
 *
 * @since 1.0.0
 */
function bavotasan_add_attr() {
	return 'class="btn btn-primary btn-lg"';
}

add_filter( 'next_post_link', 'bavotasan_add_class' );
add_filter( 'previous_post_link', 'bavotasan_add_class' );
add_filter( 'next_image_link', 'bavotasan_add_class' );
add_filter( 'previous_image_link', 'bavotasan_add_class' );
/**
 * Add 'btn' class to previous and next post links
 *
 * This function is attached to the 'next_post_link' and 'previous_post_link' filter hook.
 *
 * @param	string $format
 *
 * @return	Modified string
 *
 * @since 1.0.0
 */
function bavotasan_add_class( $format ){
	return str_replace( 'href=', 'class="btn btn-primary" href=', $format );
}

/**
 * Default menu
 *
 * Referenced via wp_nav_menu() in header.php.
 *
 * @since 1.0.0
 */
function bavotasan_default_menu( $args ) {
	extract( $args );

	$output = wp_list_categories( array(
		'title_li' => '',
		'echo' => 0,
		'number' => 5,
		'depth' => 1,
	) );
	echo "<$container class='$container_class'><ul class='nav navbar-nav'>$output</ul></$container>";
}

/**
 * Add bootstrap classes to menu items
 *
 * @since 1.0.0
 */
class Bavotasan_Page_Navigation_Walker extends Walker_Nav_Menu {
	function check_current( $classes ) {
		return preg_match( '/(current[-_])|active|dropdown/', $classes );
	}

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= "\n<ul class=\"dropdown-menu\">\n";
	}

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$item_html = '';
		parent::start_el( $item_html, $item, $depth, $args );

		if ( $item->is_dropdown && ( $depth === 0 ) ) {
			$item_html = str_replace( '<a', '<a class="dropdown-toggle" data-toggle="dropdown" data-target="#"', $item_html );
			$item_html = str_replace( '</a>', ' <b class="caret"></b></a>', $item_html );
		} elseif ( stristr( $item_html, 'li class="divider' ) ) {
			$item_html = preg_replace( '/<a[^>]*>.*?<\/a>/iU', '', $item_html );
		} elseif ( stristr( $item_html, 'li class="nav-header' ) ) {
			$item_html = preg_replace( '/<a[^>]*>(.*)<\/a>/iU', '$1', $item_html );
		}

		$output .= $item_html;
	}

	function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {
		$element->is_dropdown = !empty( $children_elements[$element->ID] );

		if ( $element->is_dropdown ) {
			if ( $depth === 0 ) {
				$element->classes[] = 'dropdown';
			} elseif ( $depth > 0 ) {
				$element->classes[] = 'dropdown-submenu';
			}
		}
		$element->classes[] = ( $element->current || in_array( 'current-menu-parent', $element->classes ) ) ? 'active' : '';

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}
}

add_filter( 'wp_nav_menu_args', 'bavotasan_nav_menu_args' );
/**
 * Set our new walker only if a menu is assigned and a child theme hasn't modified it to one level deep
 *
 * This function is attached to the 'wp_nav_menu_args' filter hook.
 *
 * @author Kirk Wight <http://kwight.ca/adding-a-sub-menu-indicator-to-parent-menu-items/>
 * @since 1.0.0
 */
function bavotasan_nav_menu_args( $args ) {
    if ( 1 !== $args[ 'depth' ] && has_nav_menu( 'primary' ) )
        $args[ 'walker' ] = new Bavotasan_Page_Navigation_Walker;

    return $args;
}

/**
 * Create the default widgets that are displayed in the home page top area
 *
 * @since 1.0.0
 */
function bavotasan_home_page_default_widgets() {
	global $paged;
	$bavotasan_theme_options = bavotasan_theme_options();
	if ( $bavotasan_theme_options['home_widget'] && is_front_page() && 2 > $paged ) {
		?>
	<div id="home-page-widgets">
		<div class="container">
			<div class="row">
			<?php if ( ! dynamic_sidebar( 'home-page-top-area' ) ) : ?>

				<?php if ( current_user_can( 'edit_theme_options' ) ) { ?>
					<div class="col-md-12">
						<div class="alert alert-warning top"><?php printf( __( 'The four boxes below are Image Text Widgets that have been added to the <em>Home Page Top Area</em>. Add your own by going to the %sWidgets admin page%s or remove this section completely under the <em>Layout panel</em> on the %sTheme Options page%s.', 'ward' ), '<a href="' . admin_url( 'widgets.php' ) . '">', '</a>', '<a href="' . admin_url( 'customize.php' ) . '">', '</a>' ); ?></div>
					</div>
				<?php } ?>

				<?php
				/**
				 * Default home page top area widgets
				 */
				?>
				<aside class="home-widget col-md-3 bavotasan_custom_text_widget">
					<img src="<?php echo BAVOTASAN_THEME_URL; ?>/library/images/ex01.jpg" alt="" class="img-circle aligncenter" />
					<h3 class="home-widget-title">Responsive Design</h3>
					<div class="textwidget">
						<p>Resize your browser to see how <strong><?php echo BAVOTASAN_THEME_NAME; ?></strong> will adjust for desktops, tablets and handheld devices.</p>
					</div>
				</aside>

				<aside class="home-widget col-md-3 bavotasan_custom_text_widget">
					<img src="<?php echo BAVOTASAN_THEME_URL; ?>/library/images/ex02.jpg" alt="" class="img-circle aligncenter" />
					<h3 class="home-widget-title">Fully Customizable</h3>
					<div class="textwidget">
						<p>Take advantage of the new Theme Options customizer to preview your changes before putting them live.</p>
					</div>
				</aside>

				<aside class="home-widget col-md-3 bavotasan_custom_text_widget">
					<img src="<?php echo BAVOTASAN_THEME_URL; ?>/library/images/ex03.jpg" alt="" class="img-circle aligncenter" />
					<h3 class="home-widget-title">Color Options</h3>
					<div class="textwidget">
						<p>With so many colors to choose from for your design palette, <strong><?php echo BAVOTASAN_THEME_NAME; ?></strong> offers endless possibilities.</p>
					</div>
				</aside>

				<aside class="home-widget col-md-3 bavotasan_custom_text_widget">
					<img src="<?php echo BAVOTASAN_THEME_URL; ?>/library/images/ex04.jpg" alt="" class="img-circle aligncenter" />
					<h3 class="home-widget-title">Bold Typography</h3>
					<div class="textwidget">
						<p>Readability is key with all sites. Good thing <strong><?php echo BAVOTASAN_THEME_NAME; ?></strong> let's you choose from 20 different Google Fonts.</p>
					</div>
				</aside>
			<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	}
}

/**
 * Create the jumbo headline section on the home page
 *
 * @since 1.0.0
 */
function bavotasan_jumbotron() {
	$bavotasan_theme_options = bavotasan_theme_options();
	if ( ! empty( $bavotasan_theme_options['jumbo_headline_title'] ) ) {
	?>
	<div class="home-top">
		<div class="container">
			<div class="row">
				<div class="home-jumbotron jumbotron col-lg-10 col-lg-offset-1 col-sm-12">
					<h1><?php echo $bavotasan_theme_options['jumbo_headline_title']; ?></h1>
					<p class="lead"><?php echo $bavotasan_theme_options['jumbo_headline_text']; ?></p>
					<?php if ( ! empty( $bavotasan_theme_options['jumbo_headline_button_text'] ) ) { ?>
					<a class="btn btn-lg btn-<?php echo $bavotasan_theme_options['jumbo_headline_button_color']; ?>" href="<?php echo $bavotasan_theme_options['jumbo_headline_button_link']; ?>"><?php echo $bavotasan_theme_options['jumbo_headline_button_text']; ?></a>
					<?php } ?>
					<i class="middle-circle <?php echo $bavotasan_theme_options['widget_area_icon']; ?>"></i>
				</div>
			</div>
		</div>
	</div>
	<?php
	}
}

/*
add_action( 'init', 'blockusers_init' ); 
function blockusers_init() 
{ 
if ( is_admin() && ! current_user_can( 'administrator' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) 
{ wp_redirect( home_url() ); exit; 
}
 }
*/
function block_dashboard() {
            $file = basename($_SERVER['PHP_SELF']);
            if (is_user_logged_in() && is_admin() && !current_user_can('edit_posts') && $file != 'admin-ajax.php'){
                wp_redirect( home_url() );
                exit();
            }
        }
        
        add_action('init', 'block_dashboard');