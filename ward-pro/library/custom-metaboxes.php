<?php
class Bavotasan_Custom_Metaboxes {
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'pre_post_update', array( $this, 'pre_post_update' ) );
	}

	/**
	 * Add option for full width posts & pages
	 *
	 * This function is attached to the 'add_meta_boxes' action hook.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box( 'alignment-options', __( 'Home Page Alignment', 'ward' ), array( $this, 'alignment_option' ), 'post', 'side', 'high' );

		add_meta_box( 'layout-options', __( 'Layout', 'ward' ), array( $this, 'layout_option' ), 'post', 'side', 'high' );
		add_meta_box( 'layout-options', __( 'Layout', 'ward' ), array( $this, 'layout_option' ), 'page', 'side', 'high' );
	}

	public function alignment_option( $post ) {
		$alignment = get_post_meta( $post->ID, 'bavotasan_home_page_alignment', true );

		// Use nonce for verification
		wp_nonce_field( 'bavotasan_nonce', 'bavotasan_nonce' );
		?>
		<input id="bavotasan_home_page_alignment" name="bavotasan_home_page_alignment" type="radio" <?php checked( $alignment, 'pull-left' ); ?> value="pull-left" /> <label for="bavotasan_home_page_alignment"><?php _e( 'Left', 'ward' ); ?></label>
		<br />
		<input id="bavotasan_home_page_alignment" name="bavotasan_home_page_alignment" type="radio" <?php checked( $alignment, 'pull-right' ); ?> value="pull-right" /> <label for="bavotasan_home_page_alignment"><?php _e( 'Right', 'ward' ); ?></label>
		<?php
	}

	public function layout_option( $post ) {
		$layout = get_post_meta( $post->ID, 'bavotasan_single_layout', true );

		// Use nonce for verification
		wp_nonce_field( 'bavotasan_nonce', 'bavotasan_nonce' );
		?>
		<input id="bavotasan_single_layout" name="bavotasan_single_layout" type="checkbox" <?php checked( $layout, 'on'); ?> /> <label for="bavotasan_single_layout"><?php _e( 'Display at full width', 'ward' ); ?></label>
		<?php
	}

	/**
	 * Save post custom fields
	 *
	 * This function is attached to the 'pre_post_update' action hook.
	 *
	 * @since 1.0.0
	 */
	public function pre_post_update( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Check if quick edit
		if ( ! empty( $_POST['_inline_edit'] ) && wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) )
			return;

		if ( ! empty( $_POST['bavotasan_nonce'] ) && ! wp_verify_nonce( $_POST['bavotasan_nonce'], 'bavotasan_nonce' ) )
			return;

		if ( ! empty( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}

		$alignment = ( empty( $_POST['bavotasan_home_page_alignment'] ) ) ? '' : $_POST['bavotasan_home_page_alignment'];
		if ( $alignment )
			update_post_meta( $post_id, 'bavotasan_home_page_alignment', $alignment );
		else
			delete_post_meta( $post_id, 'bavotasan_home_page_alignment' );

		$layout = ( empty( $_POST['bavotasan_single_layout'] ) ) ? '' : $_POST['bavotasan_single_layout'];
		if ( $layout )
			update_post_meta( $post_id, 'bavotasan_single_layout', $layout );
		else
			delete_post_meta( $post_id, 'bavotasan_single_layout' );
	}
}
$bavotasan_custom_metaboxes = new Bavotasan_Custom_Metaboxes;