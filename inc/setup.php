<?php
/**
 * Theme supports, menus, widget areas, image sizes and the logo migration.
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
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 192,
			'width'       => 192,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support(
		'custom-background',
		array(
			'default-color' => 'f6f6f6', // --color-page in input.css.
		)
	);
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	// 16:9 crops used by the cards. WordPress builds srcsets from these plus
	// the core sizes, so each card ships the smallest file that fits.
	add_image_size( 'sbek-card', 360, 203, true );
	add_image_size( 'sbek-wide', 768, 432, true );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'sbek' ),
			'footer'  => __( 'Footer links', 'sbek' ),
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
 * Earlier versions stored the logo in the theme option (`logo`); it now is
 * the core custom logo, editable here, in the Customizer and on the Sbek
 * Theme page. Copy it over once and drop the old key.
 */
function sbek_migrate_logo(): void {
	$settings = get_option( 'sbek_site_settings' );

	if ( ! is_array( $settings ) || ! array_key_exists( 'logo', $settings ) ) {
		return;
	}

	$logo = absint( $settings['logo'] );
	if ( $logo && ! get_theme_mod( 'custom_logo' ) && wp_attachment_is_image( $logo ) ) {
		set_theme_mod( 'custom_logo', $logo );
	}

	unset( $settings['logo'] );
	update_option( 'sbek_site_settings', $settings );
}
add_action( 'after_setup_theme', 'sbek_migrate_logo', 20 );

/**
 * Language hook on <body> (`lang-km`, `lang-en`).
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
 * Trim the auto-excerpt marker to a plain ellipsis.
 */
function sbek_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'sbek_excerpt_more' );
