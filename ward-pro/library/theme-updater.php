<?php
/**
 * Modifed version of https://github.com/jeremyclark13/automatic-theme-plugin-update
 *
 * @todo	Look into why multisite is not working
 */
//set_site_transient( 'update_themes', null );
class Bavotasan_Theme_Updater {
	public function __construct() {
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_for_update' ) );
		//add_filter( 'themes_api', array( $this, 'theme_api_call' ), 10, 3 );
	}

	/**
	 * Functionality to hook in the WordPress theme updater.
	 *
	 * This function is attached to the 'pre_set_site_transient_update_themes' filter hook.
	 *
	 * @since 1.0.0
	 */
	public function check_for_update( $checked_data ) {
		if ( isset( $checked_data->response ) ) {
			$get_theme_info = wp_remote_fopen( 'https://dl.dropboxusercontent.com/u/5917529/updater.inc' );

			$theme_check = json_decode( $get_theme_info );

			if ( $theme_check ) {
				$latest_version = $theme_check->{BAVOTASAN_THEME_CODE}->version;
				if ( version_compare( BAVOTASAN_THEME_VERSION, $latest_version, '<' ) ) {
				    $update_data['new_version'] = $latest_version;
				    $update_data['url'] = $theme_check->{BAVOTASAN_THEME_CODE}->url;
				    $update_data['package'] = 'https://dl.dropboxusercontent.com/u/5917529/' . md5( 'wpt-' . BAVOTASAN_THEME_CODE ) . '/wpt-' . BAVOTASAN_THEME_CODE . '.zip';

					$checked_data->response[BAVOTASAN_THEME_FILE] = $update_data;
				}
			}
		}

		return $checked_data;
	}

	// Take over the Theme info screen on WP multisite
	/*public function theme_api_call( $def, $action, $args ) {
		if ( $args->slug != BAVOTASAN_THEME_FILE )
			return false;

		// Get the current version
		$args->version = BAVOTASAN_THEME_VERSION;
		$request_string = prepare_request( $action, $args );
		$request = wp_remote_post( 'http://themes.bavotasan.com/updater.php', $request_string );

		if ( is_wp_error( $request ) ) {
			$res = new WP_Error( 'themes_api_failed', __( 'An Unexpected HTTP Error occurred during the API request. <a href="#" onclick="document.location.reload(); return false;">Try again</a>', 'matheson' ), $request->get_error_message() );
		} else {
			$res = unserialize( $request['body'] );

			if ( $res === false )
				$res = new WP_Error( 'themes_api_failed', __( 'An unknown error occurred', 'matheson' ), $request['body'] );
		}

		return $res;
	}*/
}
$bavotasan_theme_updater = new Bavotasan_Theme_Updater;

if ( is_admin() )
	$current = get_site_transient( 'update_themes' );