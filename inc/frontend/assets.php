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
 * Core's reply-to-comment script, where threaded replies are possible.
 */
function sbek_enqueue_comment_reply(): void {
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'sbek_enqueue_comment_reply' );

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
