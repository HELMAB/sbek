<?php
/**
 * Branded wp-login.php (split layout: brand panel left, form right) — hooks
 * and CSS only, so it survives core updates.
 *
 * Language: core's own login-screen language dropdown handles switching
 * (it appears once more than one language pack is installed), and every
 * string here is a plain __() call, so it follows that choice automatically.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

function sbek_login_enqueue(): void {
	wp_enqueue_style( 'sbek-login', SBEK_URI . '/assets/css/login.css', array( 'login' ), sbek_login_asset_version() );
}
add_action( 'login_enqueue_scripts', 'sbek_login_enqueue' );

function sbek_login_asset_version(): string {
	$file = SBEK_DIR . '/assets/css/login.css';

	return file_exists( $file ) ? (string) filemtime( $file ) : SBEK_VERSION;
}

add_filter( 'login_headerurl', static fn(): string => home_url( '/' ) );
add_filter( 'login_headertext', static fn(): string => get_bloginfo( 'name' ) );

/**
 * Swap the WordPress mark for the site logo (no filter exists for that
 * markup, so an inline style is the update-safe way).
 */
function sbek_login_logo_styles(): void {
	$logo = sbek_site_setting_image_url( 'logo', 'medium' );

	if ( $logo ) {
		printf( '<style>#login h1.wp-login-logo a{background-image:url(%s);}</style>', esc_url( $logo ) );
		return;
	}

	echo '<style>#login h1.wp-login-logo a{background-image:none;text-indent:0;width:auto;height:auto;font-size:1.375rem;font-weight:800;color:#fa1939;}</style>';
}
add_action( 'login_head', 'sbek_login_logo_styles' );

function sbek_login_message( string $message ): string {
	$intro = sprintf(
		'<div class="sbek-login-intro text-center"><p class="sbek-login-intro__site">%1$s</p><p class="sbek-login-intro__desc">%2$s</p></div>',
		esc_html( get_bloginfo( 'name' ) ),
		esc_html__( 'Welcome back! Sign in to manage the newsroom.', 'sbek' )
	);

	return $intro . $message;
}
add_filter( 'login_message', 'sbek_login_message' );

/**
 * Placeholders on the username/password fields (core markup has no filter).
 */
function sbek_login_footer_script(): void {
	$placeholders = array(
		'user_login' => __( 'Username or email', 'sbek' ),
		'user_pass'  => __( 'Password', 'sbek' ),
	);
	?>
	<script>
	( function ( placeholders ) {
		Object.keys( placeholders ).forEach( function ( id ) {
			var field = document.getElementById( id );
			if ( field ) {
				field.setAttribute( 'placeholder', placeholders[ id ] );
			}
		} );
	} )( <?php echo wp_json_encode( $placeholders ); ?> );
	</script>
	<?php
}
add_action( 'login_footer', 'sbek_login_footer_script' );

/**
 * Left brand panel (sibling of #login, laid out by login.css).
 */
function sbek_login_brand_panel(): void {
	?>
	<div class="sbek-login-panel">
		<div class="sbek-login-panel__grid" aria-hidden="true"></div>
		<span class="sbek-login-shape sbek-login-shape--1" aria-hidden="true"></span>
		<span class="sbek-login-shape sbek-login-shape--2" aria-hidden="true"></span>
		<span class="sbek-login-shape sbek-login-shape--3" aria-hidden="true"></span>
		<div class="sbek-login-panel__content">
			<p class="sbek-login-panel__title"><?php esc_html_e( 'Fast, trusted news.', 'sbek' ); ?></p>
			<p class="sbek-login-panel__subtitle"><?php esc_html_e( 'Write, edit and publish stories for your readers.', 'sbek' ); ?></p>
		</div>
	</div>
	<?php
}
add_action( 'login_header', 'sbek_login_brand_panel' );
