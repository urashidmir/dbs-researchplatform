<?php
/**
 * BuddyPress Experiments Widgets
 *
 * @package BuddyPress
 * @subpackage ExperimentsWidgets
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/* Register widgets for experiments component */
function experiments_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Experiments_Widget");') );
}
add_action( 'bp_register_widgets', 'experiments_register_widgets' );

/*** EXPERIMENTS WIDGET *****************/

class BP_Experiments_Widget extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'description' => __( 'A dynamic list of recently active, popular, and newest experiments', 'buddypress' ),
			'classname' => 'widget_bp_experiments_widget buddypress widget',
		);
		parent::__construct( false, _x( '(BuddyPress) Experiments', 'widget name', 'buddypress' ), $widget_ops );

		if ( is_active_widget( false, false, $this->id_base ) && !is_admin() && !is_network_admin() ) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'experiments_widget_experiments_list-js', buddypress()->plugin_url . "bp-experiments/js/widget-experiments{$min}.js", array( 'jquery' ), bp_get_version() );
		}
	}

	/**
	 * PHP4 constructor
	 *
	 * For backward compatibility only
	 */
	function bp_experiments_widget() {
		$this->_construct();
	}

	function widget( $args, $instance ) {
		$user_id = apply_filters( 'bp_experiment_widget_user_id', '0' );

		extract( $args );

		if ( empty( $instance['experiment_default'] ) )
			$instance['experiment_default'] = 'popular';

		if ( empty( $instance['title'] ) )
			$instance['title'] = __( 'Experiments', 'buddypress' );

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;

		$title = !empty( $instance['link_title'] ) ? '<a href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_experiments_root_slug() ) . '">' . $title . '</a>' : $title;

		echo $before_title . $title . $after_title; ?>

		<?php if ( bp_has_experiments( 'user_id=' . $user_id . '&type=' . $instance['experiment_default'] . '&max=' . $instance['max_experiments'] ) ) : ?>
			<div class="item-options" id="experiments-list-options">
				<a href="<?php bp_experiments_directory_permalink(); ?>" id="newest-experiments"<?php if ( $instance['experiment_default'] == 'newest' ) : ?> class="selected"<?php endif; ?>><?php _e("Newest", 'buddypress') ?></a> |
				<a href="<?php bp_experiments_directory_permalink(); ?>" id="recently-active-experiments"<?php if ( $instance['experiment_default'] == 'active' ) : ?> class="selected"<?php endif; ?>><?php _e("Active", 'buddypress') ?></a> |
				<a href="<?php bp_experiments_directory_permalink(); ?>" id="popular-experiments" <?php if ( $instance['experiment_default'] == 'popular' ) : ?> class="selected"<?php endif; ?>><?php _e("Popular", 'buddypress') ?></a>
			</div>

			<ul id="experiments-list" class="item-list">
				<?php while ( bp_experiments() ) : bp_the_experiment(); ?>
					<li <?php bp_experiment_class(); ?>>
						<div class="item-avatar">
							<a href="<?php bp_experiment_permalink() ?>" title="<?php bp_experiment_name() ?>"><?php bp_experiment_avatar_thumb() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_experiment_permalink() ?>" title="<?php bp_experiment_name() ?>"><?php bp_experiment_name() ?></a></div>
							<div class="item-meta">
								<span class="activity">
								<?php
									if ( 'newest' == $instance['experiment_default'] )
										printf( __( 'created %s', 'buddypress' ), bp_get_experiment_date_created() );
									if ( 'active' == $instance['experiment_default'] )
										printf( __( 'active %s', 'buddypress' ), bp_get_experiment_last_active() );
									else if ( 'popular' == $instance['experiment_default'] )
										bp_experiment_member_count();
								?>
								</span>
							</div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
			<?php wp_nonce_field( 'experiments_widget_experiments_list', '_wpnonce-experiments' ); ?>
			<input type="hidden" name="experiments_widget_max" id="experiments_widget_max" value="<?php echo esc_attr( $instance['max_experiments'] ); ?>" />

		<?php else: ?>

			<div class="widget-error">
				<?php _e('There are no experiments to display.', 'buddypress') ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']         = strip_tags( $new_instance['title'] );
		$instance['max_experiments']    = strip_tags( $new_instance['max_experiments'] );
		$instance['experiment_default'] = strip_tags( $new_instance['experiment_default'] );
		$instance['link_title']    = (bool)$new_instance['link_title'];

		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title'         => __( 'Experiments', 'buddypress' ),
			'max_experiments'    => 5,
			'experiment_default' => 'active',
			'link_title'    => false
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title 	       = strip_tags( $instance['title'] );
		$max_experiments    = strip_tags( $instance['max_experiments'] );
		$experiment_default = strip_tags( $instance['experiment_default'] );
		$link_title    = (bool)$instance['link_title'];
		?>

		<p><label for="bp-experiments-widget-title"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="<?php echo $this->get_field_name('link_title') ?>"><input type="checkbox" name="<?php echo $this->get_field_name('link_title') ?>" value="1" <?php checked( $link_title ) ?> /> <?php _e( 'Link widget title to Experiments directory', 'buddypress' ) ?></label></p>

		<p><label for="bp-experiments-widget-experiments-max"><?php _e('Max experiments to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_experiments' ); ?>" name="<?php echo $this->get_field_name( 'max_experiments' ); ?>" type="text" value="<?php echo esc_attr( $max_experiments ); ?>" style="width: 30%" /></label></p>

		<p>
			<label for="bp-experiments-widget-experiments-default"><?php _e('Default experiments to show:', 'buddypress'); ?>
			<select name="<?php echo $this->get_field_name( 'experiment_default' ); ?>">
				<option value="newest" <?php if ( $experiment_default == 'newest' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Newest', 'buddypress' ) ?></option>
				<option value="active" <?php if ( $experiment_default == 'active' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Active', 'buddypress' ) ?></option>
				<option value="popular"  <?php if ( $experiment_default == 'popular' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Popular', 'buddypress' ) ?></option>
			</select>
			</label>
		</p>
	<?php
	}
}

function experiments_ajax_widget_experiments_list() {

	check_ajax_referer('experiments_widget_experiments_list');

	switch ( $_POST['filter'] ) {
		case 'newest-experiments':
			$type = 'newest';
		break;
		case 'recently-active-experiments':
			$type = 'active';
		break;
		case 'popular-experiments':
			$type = 'popular';
		break;
	}

	$per_page = isset( $_POST['max_experiments'] ) ? intval( $_POST['max_experiments'] ) : 5;

	$experiments_args = array(
		'user_id'  => 0,
		'type'     => $type,
		'per_page' => $per_page,
		'max'      => $per_page,
	);

	if ( bp_has_experiments( $experiments_args ) ) : ?>
		<?php echo "0[[SPLIT]]"; ?>
		<?php while ( bp_experiments() ) : bp_the_experiment(); ?>
			<li <?php bp_experiment_class(); ?>>
				<div class="item-avatar">
					<a href="<?php bp_experiment_permalink() ?>"><?php bp_experiment_avatar_thumb() ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php bp_experiment_permalink() ?>" title="<?php bp_experiment_name() ?>"><?php bp_experiment_name() ?></a></div>
					<div class="item-meta">
						<span class="activity">
							<?php
							if ( 'newest-experiments' == $_POST['filter'] ) {
								printf( __( 'created %s', 'buddypress' ), bp_get_experiment_date_created() );
							} else if ( 'recently-active-experiments' == $_POST['filter'] ) {
								printf( __( 'active %s', 'buddypress' ), bp_get_experiment_last_active() );
							} else if ( 'popular-experiments' == $_POST['filter'] ) {
								bp_experiment_member_count();
							}
							?>
						</span>
					</div>
				</div>
			</li>
		<?php endwhile; ?>

		<?php wp_nonce_field( 'experiments_widget_experiments_list', '_wpnonce-experiments' ); ?>
		<input type="hidden" name="experiments_widget_max" id="experiments_widget_max" value="<?php echo esc_attr( $_POST['max_experiments'] ); ?>" />

	<?php else: ?>

		<?php echo "-1[[SPLIT]]<li>" . __("No experiments matched the current filter.", 'buddypress'); ?>

	<?php endif;

}
add_action( 'wp_ajax_widget_experiments_list',        'experiments_ajax_widget_experiments_list' );
add_action( 'wp_ajax_nopriv_widget_experiments_list', 'experiments_ajax_widget_experiments_list' );
