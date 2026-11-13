<?php
/**
 * Sbek theme bootstrap.
 *
 * The theme is presentation only: templates, design settings (Appearance →
 * Sbek Theme), menus and widget areas. Non-design features — Khmer / English
 * URLs and translations, news fields, newsletter, sharing, SEO, analytics,
 * demo content — live in the Sbek Companion plugin, which hooks into the
 * actions and filters documented in CLAUDE.md. Without it the theme serves
 * one language and simply leaves those parts out.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

define( 'SBEK_VERSION', '1.0.0' );
define( 'SBEK_DIR', get_template_directory() );
define( 'SBEK_URI', get_template_directory_uri() );

$sbek_modules = array(
	// Shared (admin + front end). language.php first: its fallbacks are used everywhere.
	'inc/language.php',
	'inc/setup.php',
	'inc/design.php',
	'inc/block-editor.php',
	'inc/icons.php',
	'inc/queries.php',
	'inc/template-tags.php',

	// wp-admin: settings page and category fields.
	'inc/admin/theme-options.php',
	'inc/admin/design-reset.php',
	'inc/admin/term-meta.php',

	// Public site.
	'inc/frontend/assets.php',
	'inc/frontend/class-sbek-menu-walker.php',
);

foreach ( $sbek_modules as $sbek_module ) {
	require SBEK_DIR . '/' . $sbek_module;
}
unset( $sbek_modules, $sbek_module );
