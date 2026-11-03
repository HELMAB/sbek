<?php
/**
 * Branded wp-login.php (split layout: brand panel left, form right) — hooks
 * and CSS only, so it survives core updates.
 *
 * Language: core's own login-screen language dropdown handles switching
 * (it appears once more than one language pack is installed), and every
 * string here is a plain __() call, so it follows that choice automatically.
 * The site name, home link and privacy-policy link follow it too
 * (sbek_login_language()).
 *
 * Colors and fonts come from Sbek Theme → Design (sbek_login_design() in
 * inc/design.php).
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

/**
 * Theme language matching the login screen's locale (core's language
 * dropdown / wp_lang cookie), falling back to the default language.
 */
function sbek_login_language(): string {
	$locale = determine_locale();

	foreach ( sbek_languages() as $slug => $language ) {
		if ( $language['locale'] === $locale ) {
			return $slug;
		}
	}

	return SBEK_DEFAULT_LANGUAGE;
}

/**
 * Site title in the login screen's language (English title from Sbek Theme
 * → General on the English screen).
 */
function sbek_login_site_name(): string {
	$lang = sbek_login_language();
	$name = SBEK_DEFAULT_LANGUAGE !== $lang ? trim( (string) sbek_site_setting( 'name_' . $lang, '' ) ) : '';

	return '' !== $name ? $name : (string) get_bloginfo( 'name' );
}

add_filter( 'login_headerurl', static fn(): string => sbek_home_url( sbek_login_language() ) );
add_filter( 'login_headertext', 'sbek_login_site_name' );

/**
 * "← Go to {site}" footer link: home page and site name in the screen's
 * language. Core builds it with the default-language title and home URL.
 *
 * @param string $link Core link HTML.
 * @return string
 */
function sbek_login_site_link( string $link ): string {
	$lang = sbek_login_language();

	if ( SBEK_DEFAULT_LANGUAGE === $lang ) {
		return $link;
	}

	$link = str_replace( esc_url( home_url( '/' ) ), esc_url( sbek_home_url( $lang ) ), $link );

	return str_replace( get_bloginfo( 'title', 'display' ), esc_html( sbek_login_site_name() ), $link );
}
add_filter( 'login_site_html_link', 'sbek_login_site_link' );

/**
 * Privacy-policy link on the login screen: point at the policy page's
 * translation (with its own title) when the screen is in another language.
 *
 * @param string $link Core link HTML ('' when no policy page is set).
 * @return string
 */
function sbek_login_privacy_link( string $link ): string {
	if ( '' === $link || ! did_action( 'login_init' ) ) {
		return $link;
	}

	$lang = sbek_login_language();

	if ( SBEK_DEFAULT_LANGUAGE === $lang ) {
		return $link;
	}

	$translation = sbek_get_translation( (int) get_option( 'wp_page_for_privacy_policy' ), $lang );

	if ( ! $translation ) {
		return $link;
	}

	return sprintf(
		'<a class="privacy-policy-link" href="%s" rel="privacy-policy">%s</a>',
		esc_url( sbek_localize_url( (string) get_permalink( $translation ), $lang ) ),
		esc_html( get_the_title( $translation ) )
	);
}
add_filter( 'the_privacy_policy_link', 'sbek_login_privacy_link' );

/**
 * Swap the WordPress mark for the site logo (no filter exists for that
 * markup, so an inline style is the update-safe way). Without a logo the
 * site name below it is enough, so the mark is hidden.
 */
function sbek_login_logo_styles(): void {
	$logo = sbek_site_setting_image_url( 'logo', 'medium' );

	if ( $logo ) {
		printf( '<style>#login h1.wp-login-logo a{background-image:url(%s);}</style>', esc_url( $logo ) );
		return;
	}

	echo '<style>#login h1.wp-login-logo{display:none;}</style>';
}
add_action( 'login_head', 'sbek_login_logo_styles' );

function sbek_login_message( string $message ): string {
	$intro = sprintf(
		'<div class="sbek-login-intro"><p class="sbek-login-intro__site">%1$s</p><p class="sbek-login-intro__desc">%2$s</p></div>',
		esc_html( sbek_login_site_name() ),
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
