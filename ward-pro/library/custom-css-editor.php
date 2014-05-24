<?php
class bavotasan_custom_css_Editor {
	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 1000 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Add a 'customize' menu item to the admin bar
	 *
	 * This function is attached to the 'admin_bar_menu' action hook.
	 *
	 * @since 1.0.0
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
	    if ( current_user_can( 'edit_theme_options' ) && is_admin_bar_showing() )
	       	$wp_admin_bar->add_node( array( 'parent' => 'bavotasan_toolbar', 'id' => 'custom_css', 'title' => __( 'Custom CSS', 'ward' ), 'href' => admin_url( 'themes.php?page=custom_css_editor_page' ) ) );
	}

	/**
	 * Add a 'customize' menu item to the Appearance panel
	 *
	 * This function is attached to the 'admin_menu' action hook.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_theme_page( __( 'Custom CSS Editor', 'ward' ),  __( 'Custom CSS', 'ward' ), 'edit_theme_options', 'custom_css_editor_page', array( $this, 'custom_css_editor_page' ) );
	}

	/**
	 * Registering the settings for the Custom CSS editor
	 *
	 * This function is attached to the 'admin_init' action hook.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		register_setting( 'ward_custom_css', 'ward_custom_css',  array( $this, 'custom_css_validation' ) );
	}

	/**
	 * Add JS file to admin only on Custom CSS editor page.
	 *
	 * This function is attached to the 'admin_enqueue_scripts' action hook.
	 *
	 * @param	$hook  The page template file for the current page
	 *
	 * @uses	wp_enqueue_script()
	 * @uses	BAVOTASAN_THEME_URL
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'appearance_page_custom_css_editor_page' == $hook ) {
		    wp_enqueue_script( 'ace_code_highlighter_js', 'http://d1n0x3qji82z53.cloudfront.net/src-min-noconflict/ace.js', '', '1.0.0', true );
		    wp_enqueue_script( 'custom_css_js', BAVOTASAN_THEME_URL . '/library/js/custom-css.js', array( 'jquery', 'ace_code_highlighter_js' ), '1.0.0', true );
		}
	}

	/**
	 * The Custom CSS appearance page
	 *
	 * @since 1.0.0
	 */
	public function custom_css_editor_page() {
		$custom_css_default = __( '/*
Welcome to the Custom CSS editor!

Please add all your custom CSS here and avoid modifying the core theme files, since that\'ll make upgrading the theme problematic. Your custom CSS will be loaded after the theme\'s stylesheets, which means that your rules will take precedence. Just add your CSS here for what you want to change, you don\'t need to copy all the theme\'s style.css content.
*/', 'ward' );
		$custom_css = get_option( 'ward_custom_css', $custom_css_default );
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php echo get_admin_page_title(); ?></h2>
			<?php if ( ! empty( $_GET['settings-updated'] ) ) echo '<div id="message" class="updated"><p><strong>' . __( 'Custom CSS updated.', 'ward' ) . '</strong></p></div>'; ?>

	        <p><?php printf( __( 'If you\'re new to CSS, start with a %sbeginner tutorial%s.', 'ward' ), '<a href="http://www.htmldog.com/guides/cssbeginner/">', '</a>' ); ?></p>

			<form id="custom_css_form" method="post" action="options.php">

	            <?php settings_fields( 'ward_custom_css' ); ?>

	            <div id="custom_css_container">
		            <div name="ward_custom_css" id="ward_custom_css" style="border: 1px solid #DFDFDF; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; width: 100%; height: 400px; position: relative;">
	    	        </div>
	    	    </div>

	            <textarea id="custom_css_textarea" name="ward_custom_css" style="display: none;"><?php echo $custom_css; ?></textarea>

	   			<p><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ward' ) ?>" /></p>

	        </form>

	    </div>
	<?php
	}

	/**
	 * The Custom CSS validation
	 *
	 * @since 1.0.0
	 */
	public function custom_css_validation( $input ) {
		if ( ! empty( $input['ward_custom_css'] ) )
			$input['ward_custom_css'] = trim( $input['ward_custom_css'] );
		return $input;
	}
}
$bavotasan_custom_css_editor = new bavotasan_custom_css_Editor;