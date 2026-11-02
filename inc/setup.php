<?php
/**
 * Theme supports, menus, widget areas, image sizes and upgrade routine.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

function sbek_setup(): void {
	load_theme_textdomain( 'sbek', SBEK_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-formats', array( 'video' ) ); // Drives the homepage video block.
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	// 16:9 crops used by the cards. WordPress builds srcsets from these plus
	// the core sizes, so each card ships the smallest file that fits.
	add_image_size( 'sbek-card', 360, 203, true );
	add_image_size( 'sbek-wide', 768, 432, true );

	register_nav_menus(
		array(
			'primary'    => __( 'Primary menu (Khmer)', 'sbek' ),
			'primary-en' => __( 'Primary menu (English)', 'sbek' ),
			'footer'     => __( 'Footer links (Khmer)', 'sbek' ),
			'footer-en'  => __( 'Footer links (English)', 'sbek' ),
		)
	);
}
add_action( 'after_setup_theme', 'sbek_setup' );

/**
 * Max width of embedded media inside article bodies.
 */
function sbek_content_width(): void {
	$GLOBALS['content_width'] = 730; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
add_action( 'after_setup_theme', 'sbek_content_width', 0 );

/**
 * Sidebar + advertising widget areas. Ad areas render nothing when empty, and
 * the layouts around them reclaim the space.
 */
function sbek_widgets_init(): void {
	$defaults = array(
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="sbek-rule-title">',
		'after_title'   => '</h2>',
	);

	$areas = array(
		'sbek-sidebar'   => array( __( 'Sidebar', 'sbek' ), __( 'Below the Popular / Latest lists on articles and archives.', 'sbek' ) ),
		'sbek-ad-header' => array( __( 'Ad: header (728×90)', 'sbek' ), __( 'Right of the logo in the black top bar (desktop).', 'sbek' ) ),
		'sbek-ad-hero'   => array( __( 'Ad: beside hero (300×250 ×2)', 'sbek' ), __( 'Right of the homepage hero grid (desktop). Add up to two widgets.', 'sbek' ) ),
		'sbek-ad-video'  => array( __( 'Ad: beside video (300×250)', 'sbek' ), __( 'Right of the homepage video grid (large screens).', 'sbek' ) ),
	);

	foreach ( $areas as $id => $area ) {
		register_sidebar(
			array_merge(
				$defaults,
				array(
					'id'          => $id,
					'name'        => $area[0],
					'description' => $area[1],
				)
			)
		);
	}
}
add_action( 'widgets_init', 'sbek_widgets_init' );

/**
 * One-off data setup on activation and on theme version changes.
 */
function sbek_maybe_upgrade(): void {
	if ( get_option( 'sbek_theme_version' ) === SBEK_VERSION ) {
		return;
	}

	sbek_ensure_language_terms();

	// 1.x shipped an en/jp layout: drop the obsolete Japanese term.
	$jp = get_term_by( 'slug', 'jp', 'language' );
	if ( $jp instanceof WP_Term ) {
		wp_delete_term( $jp->term_id, 'language' );
	}

	update_option( 'sbek_theme_version', SBEK_VERSION, false );
}
add_action( 'init', 'sbek_maybe_upgrade', 20 );
add_action( 'after_switch_theme', 'sbek_maybe_upgrade' );

/**
 * Language + layout hooks on <body>.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function sbek_body_classes( array $classes ): array {
	$classes[] = 'lang-' . sanitize_html_class( sbek_lang() );

	return $classes;
}
add_filter( 'body_class', 'sbek_body_classes' );

/**
 * The theme has no comment UI, so refuse new comments and pingbacks rather
 * than silently collecting submissions (spam) that nobody can see. Existing
 * comments stay in the database untouched.
 */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );

/**
 * Hide the comment feed links from <head> (automatic-feed-links adds them).
 */
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Trim the auto-excerpt marker to a plain ellipsis.
 */
function sbek_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'sbek_excerpt_more' );
