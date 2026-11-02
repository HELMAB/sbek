<?php
/**
 * Front-end styles, scripts and resource hints.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a theme asset: its modification time, so every
 * rebuild invalidates browser caches (falls back to the theme version).
 */
function sbek_asset_version( string $relative_path ): string {
	$file = SBEK_DIR . '/' . ltrim( $relative_path, '/' );

	return file_exists( $file ) ? (string) filemtime( $file ) : SBEK_VERSION;
}

function sbek_enqueue_assets(): void {
	wp_enqueue_style( 'sbek-main', SBEK_URI . '/assets/css/main.css', array(), sbek_asset_version( 'assets/css/main.css' ) );

	wp_enqueue_script(
		'sbek-theme',
		SBEK_URI . '/assets/js/theme.js',
		array(),
		sbek_asset_version( 'assets/js/theme.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'sbek_enqueue_assets' );

/**
 * Tiny blocking script at the top of <head>: flags JS support (`sbek-js`,
 * which switches the desktop menu to one row + "More") and restores the
 * saved list/grid feed view (`sbek-grid`) before first paint, so a grid
 * preference doesn't flash the list layout first.
 */
function sbek_head_boot_script(): void {
	wp_print_inline_script_tag(
		"(function(d){d.classList.add('sbek-js');try{if(localStorage.getItem('sbekFeedView')==='grid'){d.classList.add('sbek-grid');}}catch(e){}})(document.documentElement);"
	);
}
add_action( 'wp_head', 'sbek_head_boot_script', 1 );

/**
 * The emoji polyfill (inline script + stylesheet on every page) isn't needed
 * by any modern browser.
 */
function sbek_disable_emoji(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'sbek_disable_emoji' );
