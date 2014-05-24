<?php do_action( 'bp_before_directory_experiments_page' ); ?>

<div id="buddypress">

<?php do_action( 'bp_before_directory_experiments' ); ?>

<?php do_action( 'bp_before_directory_experiments_content' ); ?>

<div id="experiment-dir-search" class="dir-search" role="search">
<?php bp_directory_experiments_search_form(); ?>
</div><!-- #experiment-dir-search -->

<form action="" method="post" id="experiments-directory-form" class="dir-form">

<?php do_action( 'template_notices' ); ?>

<div class="item-list-tabs" role="navigation">
<ul>
<li class="selected" id="experiments-all"><a href="<?php bp_experiments_directory_permalink(); ?>"><?php printf( __( 'All Experiments <span>%s</span>', 'buddypress' ), bp_get_total_experiment_count() ); ?></a></li>

<?php if ( is_user_logged_in() && bp_get_total_experiment_count_for_user( bp_loggedin_user_id() ) ) : ?>
<li id="experiments-personal"><a href="<?php echo bp_loggedin_user_domain() . bp_get_experiments_slug() . '/my-experiments/'; ?>"><?php printf( __( 'My Experiments <span>%s</span>', 'buddypress' ), bp_get_total_experiment_count_for_user( bp_loggedin_user_id() ) ); ?></a></li>
<?php endif; ?>

<?php do_action( 'bp_experiments_directory_experiment_filter' ); ?>

</ul>
</div><!-- .item-list-tabs -->

<div class="item-list-tabs" id="subnav" role="navigation">
<ul>
<?php do_action( 'bp_experiments_directory_experiment_types' ); ?>

<li id="experiments-order-select" class="last filter">

<label for="experiments-order-by"><?php _e( 'Order By:', 'buddypress' ); ?></label>

<select id="experiments-order-by">
<option value="active"><?php _e( 'Last Active', 'buddypress' ); ?></option>
<option value="popular"><?php _e( 'Most Members', 'buddypress' ); ?></option>
<option value="newest"><?php _e( 'Newly Created', 'buddypress' ); ?></option>
<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ); ?></option>

<?php do_action( 'bp_experiments_directory_order_options' ); ?>
</select>
</li>
</ul>
</div>

<div id="experiments-dir-list" class="experiments dir-list">
<?php bp_get_template_part( 'experiments/experiments-loop' ); ?>
</div><!-- #experiments-dir-list -->

<?php do_action( 'bp_directory_experiments_content' ); ?>

<?php wp_nonce_field( 'directory_experiments', '_wpnonce-experiments-filter' ); ?>

<?php do_action( 'bp_after_directory_experiments_content' ); ?>

</form><!-- #experiments-directory-form -->

<?php do_action( 'bp_after_directory_experiments' ); ?>

</div><!-- #buddypress -->

<?php do_action( 'bp_after_directory_experiments_page' ); ?>