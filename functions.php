<?php
/**
 * Sbek theme bootstrap.
 *
 * Load order matters:
 *   1. inc/i18n.php     — detects the URL language and switches the locale
 *                         before anything is translated or routed.
 *   2. inc/security.php — hardening that applies to every request.
 *   3. everything else.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

define( 'SBEK_VERSION', '1.0.0' );
define( 'SBEK_DIR', get_template_directory() );
define( 'SBEK_URI', get_template_directory_uri() );

$sbek_modules = array(
	// Shared (admin + front end).
	'inc/i18n.php',
	'inc/security.php',
	'inc/setup.php',
	'inc/design.php',
	'inc/icons.php',
	'inc/queries.php',
	'inc/template-tags.php',

	// wp-admin screens, meta boxes and term fields.
	'inc/admin/theme-options.php',
	'inc/admin/custom-fields.php',
	'inc/admin/term-meta.php',
	'inc/admin/translation.php',
	'inc/admin/tools.php',
	'inc/admin/dashboard-widget.php',
	'inc/admin/login.php',

	// Public site.
	'inc/frontend/assets.php',
	'inc/frontend/class-sbek-menu-walker.php',
	'inc/frontend/content.php',
	'inc/frontend/newsletter.php',
	'inc/frontend/seo.php',
	'inc/frontend/integrations.php',
);

foreach ( $sbek_modules as $sbek_module ) {
	require SBEK_DIR . '/' . $sbek_module;
}
unset( $sbek_modules, $sbek_module );

// Demo importer: only needed in wp-admin (Sbek Theme → Tools) and WP-CLI.
if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require SBEK_DIR . '/inc/demo/class-sbek-demo-importer.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require SBEK_DIR . '/inc/cli/class-sbek-demo-command.php';
}
