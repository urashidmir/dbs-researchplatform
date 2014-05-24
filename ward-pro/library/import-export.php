<?php
class Bavotasan_Import_Export {
	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 1000 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Add a 'customize' menu item to the admin bar
	 *
	 * This function is attached to the 'admin_bar_menu' action hook.
	 *
	 * @since 1.0.0
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
	    if ( current_user_can( 'edit_theme_options' ) && is_admin_bar_showing() ) {
	    	$wp_admin_bar->add_node( array( 'parent' => 'bavotasan_toolbar', 'id' => 'import_export', 'title' => __( 'Import/Export', 'ward' ), 'href' => admin_url( 'themes.php?page=bavotasan_import_export_page' ) ) );
	    }
	}

	/**
	 * Add a 'customize' menu item to the Appearance panel
	 *
	 * This function is attached to the 'admin_menu' action hook.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_theme_page( __( 'Import/Export Theme Options', 'ward' ), __( 'Import/Export', 'ward' ), 'edit_theme_options', 'bavotasan_import_export_page', array( $this, 'bavotasan_import_export_page' ) );
	}

	/**
	 * Theme options page
	 *
	 * @author c.bavota / Garth Gutenberg <garth@grinninggecko.com>
	 * @since 1.0.0
	 */
	public function bavotasan_import_export_page() {
		// Build export file
		$export_file = 'theme-options-export.json';
		$upload_dir = wp_upload_dir();
		$export_file_local = $upload_dir['path'] . '/' . $export_file;
		$export_file_url = $upload_dir['url'] . '/' . $export_file;
		if ( file_exists( $export_file_local ) )
			unlink( $export_file_local );

		$export = array(
			'ward_pro_theme_options' => bavotasan_theme_options(),
			'ward_custom_css' => get_option( 'ward_custom_css' ),
		);

		file_put_contents( $export_file_local, json_encode( $export ) );
		$admin_url = admin_url( 'themes.php?page=bavotasan_import_export_page' );
		?>
		<div class="wrap" id="custom-background">
			<div id="icon-themes" class="icon32"></div>
			<h2><?php echo get_admin_page_title(); ?></h2>
			<?php
			$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
			$size = size_format( $bytes );
			if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) ) {
				if ( ! empty( $upload_dir['error'] ) ) {
					?><div id="message" class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'ward' ); ?></p>
					<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
				} else {
					if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'import-upload' ) ) {
						?><div id="message" class="error"><p><strong><?php _e( 'Security issue.', 'ward' ); ?></strong></p></div><?php
					} elseif ( empty( $_FILES['import']['name'] ) ) {
						?><div id="message" class="error"><p><strong><?php _e( 'Please select a file from your desktop.', 'ward' ); ?></strong></p></div><?php
					} else {
						$import_json = file_get_contents( $_FILES['import']['tmp_name'] );
						$import = json_decode( $import_json, true );
						foreach( $import as $option_name => $option_value ) {
							update_option( $option_name, $option_value );
						}
						?><div id="message" class="updated"><p><strong><?php _e( 'Import successful.', 'ward' ); ?></strong></p></div><?php
					}
				}
			}
			?>
			<h3><?php _e( 'Import Theme Options', 'ward' ); ?></h3>
			<p><?php _e( 'Choose the JSON file named "theme-options-export.json" to upload your theme options, then click the Upload file and import button below.', 'ward' ); ?></p>
			<?php wp_import_upload_form( $admin_url ); ?>

			<h3><?php _e( 'Export Theme Options', 'ward' ); ?></h3>
			<p><?php _e( 'When you click the button below, WordPress will create a JSON file for you to save to your computer.', 'ward' ); ?></p>
			<p><?php _e( 'This format will contain your current theme options and custom CSS.', 'ward' ); ?></p>
			<p><?php _e( 'Once you\'ve saved the download file, you can use the Import function in another WordPress installation to import the theme options from this site.', 'ward' ); ?></p>
			<p class="submit"><a href="<?php echo esc_attr( wp_nonce_url( $admin_url . '&file=' . urlencode( $export_file_url ), 'export') ); ?>" class="button-secondary">
				<?php _e( 'Download export file', 'ward' ); ?>
			</a></p>
		</div>
		<?php
	}
}
$bavotasan_import_export = new Bavotasan_Import_Export;

/**
 * Export functionality
 *
 * @since 1.0.0
 */
if ( ! empty( $_GET['page'] ) && 'bavotasan_import_export_page' == $_GET['page'] && ! empty( $_GET['file'] ) ) {
	if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'export' ) )
		die( __( 'Security check', 'ward' ) );

	$file_url = urldecode( $_GET['file'] );

	if ( empty( $file_url ) )
		die();

	header( 'Content-disposition: attachment; filename=theme-options-export.json' );
	header( 'Content-type: text/plain' );
	readfile( $file_url );
	die();
}