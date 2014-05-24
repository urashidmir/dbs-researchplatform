<?php
class Bavotasan_Shortcodes {
	public function __construct() {
		// Process shortcodes in widgets
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'the_content', array( $this, 'clean_shortcodes' ) );

		// New shortcodes
		add_shortcode( 'button', array( $this, 'theme_button_shortcode' ) );
		add_shortcode( 'alert', array( $this, 'theme_alert_shortcode' ) );
		add_shortcode( 'label', array( $this, 'theme_label_shortcode' ) );
		add_shortcode( 'badge', array( $this, 'theme_badge_shortcode' ) );
		add_shortcode( 'jumbotron', array( $this, 'theme_jumbotron_shortcode' ) );
		add_shortcode( 'image', array( $this, 'theme_image_shortcode' ) );
		add_shortcode( 'author_info', array( $this, 'theme_author_info_shortcode' ) );
		add_shortcode( 'columns', array( $this, 'theme_columns_shortcode' ) );
		add_shortcode( 'carousel', array( $this, 'theme_carousel_shortcode' ) );
		add_shortcode( 'carousel_image', array( $this, 'theme_carousel_image_shortcode' ) );
		add_shortcode( 'tooltip', array( $this, 'theme_tooltip_shortcode' ) );
		add_shortcode( 'tabs', array( $this, 'theme_tabs_shortcode' ) );
		add_shortcode( 'tabs_nav', array( $this, 'theme_tabs_nav_shortcode' ) );
		add_shortcode( 'tabs_nav_item', array( $this, 'theme_tabs_nav_item_shortcode' ) );
		add_shortcode( 'tabs_content', array( $this, 'theme_tabs_content_shortcode' ) );
		add_shortcode( 'tabs_content_item', array( $this, 'theme_tabs_content_item_shortcode' ) );
		add_shortcode( 'widetext', array( $this, 'widetext_shortcode' ) );
	}

	public function clean_shortcodes( $content ){
	    return strtr( $content, array (
	        '<p>[' => '[',
	        ']</p>' => ']',
	        ']<br />' => ']'
	    ) );
	}

	####################
	##   SHORTCODES   ##
	####################

	// Button shortcode
	public function theme_button_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'type' => 'button',
			'id' => '',
			'style' => '',
			'size' => ''
		), $atts ) );
		$style = ( $style ) ? ' btn-' . $style : ' btn-default';
		$size_array = array( 'large' => 'lg', 'small' => 'sm', 'mini' => 'xs' );
		$size = ( $size ) ? ' btn-' . $size_array[$size] : '';
		$id = ( $id ) ? ' id="' . $id . '"' : '';

		return '<button' . $id . ' type="' . $type . '" class="btn'. $style . $size . '">' . trim( $content ) . '</button>';
	}

	// Alert shortcode
	public function theme_alert_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'style' => 'warning',
		), $atts ) );
		$style = ( $style ) ? ' alert-' . $style : '';

		return '<div class="alert'. $style . '">' . do_shortcode( trim( $content ) ) . '</div>';
	}

	// Label shortcode
	public function theme_label_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'style' => 'default',
		), $atts ) );
		$style = ( $style ) ? ' label-' . $style : '';

		return '<span class="label'. $style . '">' . trim( $content ) . '</span>';
	}

	// Badge shortcode
	public function theme_badge_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'style' => '',
		), $atts ) );
		$style = ( $style ) ? ' badge-' . $style : '';

		return '<span class="badge'. $style . '">' . trim( $content ) . '</span>';
	}

	// Jumbotron shortcode
	public function theme_jumbotron_shortcode( $atts = null, $content = null ) {
		return '<div class="jumbotron">' . do_shortcode( trim( $content ) ) . '</div>';
	}

	// Image shortcode
	public function theme_image_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'style' => 'rounded',
		), $atts ) );

		return '<img src="' . trim( $content ) . '" alt="" class="img-' . $style . '" />';
	}

	// Author Info shortcode
	public function theme_author_info_shortcode() {
		global $post;
		$author = $post->post_author;

		return '<div id="author-info" class="well">' . get_avatar( get_the_author_meta( 'email', $author ), '80' ) . '<div class="author-text"><h4>' . get_the_author_meta( 'display_name', $author ) . '</h4><p>' . get_the_author_meta( 'description', $author ) . '</p></div></div>';
	}

	// Columns shortcode
	public function theme_columns_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'size' => '2',
			'gap' => '1em'
		), $atts ) );

		return '<div class="columns" style="-moz-column-count:' . $size . ';-webkit-column-count:' . $size . ';column-count:' . $size . ';-moz-column-gap:' . $gap . ';-webkit-column-gap:' . $gap . ';column-gap:' . $gap . '">' . do_shortcode( trim( $content ) ) . '</div>';
	}

	// Carousel shortcode
	public function theme_carousel_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'interval' => '5000',
			'slide' => ''
		), $atts ) );

		return '<div id="myCarousel" data-interval="' . $interval . '" class="carousel ' . $slide . '"><div class="carousel-inner">' . do_shortcode( trim( $content ) ) . '</div><a class="left carousel-control" href="#myCarousel" data-slide="prev"><span class="icon-prev"></span></a><a class="right carousel-control" href="#myCarousel" data-slide="next"><span class="icon-next"></span></a></div>';
	}

	// Carousel Image shortcode
	public function theme_carousel_image_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'title' => '',
			'caption' => '',
			'active' => ''
		), $atts ) );
		$caption = ( $caption ) ? '<p>' . $caption . '</p>' : '';
		$title = ( $title ) ? '<h3>' . $title . '</h3>' : '';
		$ticap = ( $title || $caption ) ? '<div class="carousel-caption">' . $title . $caption . '</div>' : '';

		return '<div class="item ' . $active . '"><img src="' . trim( $content ) . '" alt="" />' . $ticap . '</div>';
	}

	// Tooltip shortcode
	public function theme_tooltip_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'tip' => '',
			'placement' => 'top'
		), $atts ) );

		return '<a href="#" rel="tooltip" data-placement="' . $placement . '" title="' . $tip . '">' . trim( $content ) . '</a>';
	}

	// Tabs shortcode
	public function theme_tabs_shortcode( $atts, $content = null ) {
		return do_shortcode( trim( $content ) );
	}

	// Tabs Nav shortcode
	public function theme_tabs_nav_shortcode( $atts, $content = null ) {
		return '<ul class="nav nav-tabs" id="myTab">' . do_shortcode( trim( $content ) ) . '</ul>';
	}

	// Tabs Nav Item shortcode
	public function theme_tabs_nav_item_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'active' => '',
			'id' => ''
		), $atts ) );

		return '<li class="' . $active . '"><a href="#' . $id . '">' . do_shortcode( trim( $content ) ) . '</a></li>';
	}

	// Tabs content shortcode
	public function theme_tabs_content_shortcode( $atts, $content = null ) {
		return '<div class="tab-content">' . do_shortcode( trim( $content ) ) . '</div>';
	}

	// Tabs Content Item shortcode
	public function theme_tabs_content_item_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'active' => '',
			'id' => ''
		), $atts ) );

		return '<div class="tab-pane ' . $active . '" id="' . $id . '">' . do_shortcode( trim( $content ) ) . '</div>';
	}

	// Widetext shortcode
	public function widetext_shortcode( $atts = null, $content = null ) {
		return '<span class="widetext">' . trim( $content ) . '</span>';
	}
}
$bavotasan_shortcodes = new Bavotasan_Shortcodes;