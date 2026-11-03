<?php
/**
 * Design settings (Sbek Theme → Design): brand colors and fonts.
 *
 * The stylesheet stays static; these settings only override a handful of CSS
 * custom properties in a tiny inline <style> (see sbek_design_css()). Every
 * value is validated — colors with sanitize_hex_color(), fonts against the
 * whitelist below — so no free text ever reaches the page.
 *
 * Fonts are self-hosted Khmer subsets (assets/fonts/, SIL OFL). All of them
 * are declared with @font-face in assets/css/src/fonts.css, but browsers only
 * download a font file once text actually uses it, so offering more fonts
 * costs visitors nothing.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_BRAND_COLOR  = '#fa1939';
const SBEK_BRAND_DARK   = '#d90c16';
const SBEK_TOPBAR_COLOR = '#161616';
const SBEK_FOOTER_COLOR = '#000000';

/**
 * Khmer fonts: slug => label, CSS family, roles, display (true = a display
 * face used at weight 400 for headings), file to preload, description.
 *
 * @return array<string,array{label:string,family:string,roles:string[],display:bool,file:string,note:string}>
 */
function sbek_font_choices(): array {
	return array(
		'battambang'      => array( 'label' => 'Battambang', 'family' => 'Battambang', 'roles' => array( 'body', 'heading' ), 'display' => false, 'file' => 'battambang-khmer-400.woff2', 'note' => __( 'Classic, very readable (default body)', 'sbek' ) ),
		'kantumruy-pro'   => array( 'label' => 'Kantumruy Pro', 'family' => 'Kantumruy Pro', 'roles' => array( 'body', 'heading' ), 'display' => false, 'file' => 'kantumruy-pro-khmer-var.woff2', 'note' => __( 'Modern sans', 'sbek' ) ),
		'noto-sans-khmer' => array( 'label' => 'Noto Sans Khmer', 'family' => 'Noto Sans Khmer', 'roles' => array( 'body', 'heading' ), 'display' => false, 'file' => 'noto-sans-khmer-khmer-var.woff2', 'note' => __( 'Neutral, matches Android', 'sbek' ) ),
		'hanuman'         => array( 'label' => 'Hanuman', 'family' => 'Hanuman', 'roles' => array( 'body', 'heading' ), 'display' => false, 'file' => 'hanuman-khmer-var.woff2', 'note' => __( 'Serif, bookish', 'sbek' ) ),
		'nokora'          => array( 'label' => 'Nokora', 'family' => 'Nokora', 'roles' => array( 'body', 'heading' ), 'display' => false, 'file' => 'nokora-khmer-var.woff2', 'note' => __( 'Clean sans', 'sbek' ) ),
		'suwannaphum'     => array( 'label' => 'Suwannaphum', 'family' => 'Suwannaphum', 'roles' => array( 'body', 'heading' ), 'display' => false, 'file' => 'suwannaphum-khmer-400.woff2', 'note' => __( 'Traditional serif', 'sbek' ) ),
		'siemreap'        => array( 'label' => 'Siemreap', 'family' => 'Siemreap', 'roles' => array( 'body' ), 'display' => false, 'file' => 'siemreap-khmer-400.woff2', 'note' => __( 'Simple, regular weight only', 'sbek' ) ),
		'koulen'          => array( 'label' => 'Koulen', 'family' => 'Koulen', 'roles' => array( 'heading' ), 'display' => true, 'file' => 'koulen-khmer-400.woff2', 'note' => __( 'Bold display (default headings)', 'sbek' ) ),
		'moul'            => array( 'label' => 'Moul', 'family' => 'Moul', 'roles' => array( 'heading' ), 'display' => true, 'file' => 'moul-khmer-400.woff2', 'note' => __( 'Ornate, ceremonial', 'sbek' ) ),
		'bayon'           => array( 'label' => 'Bayon', 'family' => 'Bayon', 'roles' => array( 'heading' ), 'display' => true, 'file' => 'bayon-khmer-400.woff2', 'note' => __( 'Condensed display', 'sbek' ) ),
		'kdam-thmor-pro'  => array( 'label' => 'Kdam Thmor Pro', 'family' => 'Kdam Thmor Pro', 'roles' => array( 'heading' ), 'display' => true, 'file' => 'kdam-thmor-pro-khmer-400.woff2', 'note' => __( 'Rounded display', 'sbek' ) ),
	);
}

/**
 * Latin font stacks (system fonts only — nothing to download).
 *
 * @return array<string,array{label:string,stack:string}>
 */
function sbek_latin_font_choices(): array {
	return array(
		'arial'   => array( 'label' => __( 'Arial (default)', 'sbek' ), 'stack' => 'Arial, Helvetica, sans-serif' ),
		'system'  => array( 'label' => __( 'System UI', 'sbek' ), 'stack' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif' ),
		'verdana' => array( 'label' => 'Verdana', 'stack' => 'Verdana, Geneva, sans-serif' ),
		'tahoma'  => array( 'label' => 'Tahoma', 'stack' => 'Tahoma, Geneva, sans-serif' ),
		'georgia' => array( 'label' => __( 'Georgia (serif)', 'sbek' ), 'stack' => 'Georgia, "Times New Roman", serif' ),
	);
}

/**
 * Validated design settings with defaults applied.
 *
 * @return array{brand:string,brand_dark:string,top:string,footer:string,font_body:string,font_heading:string,font_latin:string}
 */
function sbek_design(): array {
	$s     = function_exists( 'sbek_site_setting' ) ? sbek_site_setting() : array();
	$fonts = sbek_font_choices();

	$brand  = sbek_normalize_hex( (string) ( $s['color_brand'] ?? '' ) ) ?: SBEK_BRAND_COLOR;
	$top    = sbek_normalize_hex( (string) ( $s['color_top'] ?? '' ) ) ?: SBEK_TOPBAR_COLOR;
	$footer = sbek_normalize_hex( (string) ( $s['color_footer'] ?? '' ) ) ?: SBEK_FOOTER_COLOR;

	$body    = (string) ( $s['font_body'] ?? '' );
	$heading = (string) ( $s['font_heading'] ?? '' );
	$latin   = (string) ( $s['font_latin'] ?? '' );

	return array(
		'brand'        => strtolower( $brand ),
		// The default red keeps the original hand-picked shade; custom colors get a derived one.
		'brand_dark'   => 0 === strcasecmp( $brand, SBEK_BRAND_COLOR ) ? SBEK_BRAND_DARK : sbek_mix_hex( $brand, '#000000', 0.15 ),
		'top'          => strtolower( $top ),
		'footer'       => strtolower( $footer ),
		'font_body'    => isset( $fonts[ $body ] ) && in_array( 'body', $fonts[ $body ]['roles'], true ) ? $body : 'battambang',
		'font_heading' => isset( $fonts[ $heading ] ) && in_array( 'heading', $fonts[ $heading ]['roles'], true ) ? $heading : 'koulen',
		'font_latin'   => isset( sbek_latin_font_choices()[ $latin ] ) ? $latin : 'arial',
	);
}

/**
 * The current brand color (menu bar, buttons, default accents).
 */
function sbek_brand_color(): string {
	return sbek_design()['brand'];
}

// ─── Color math ───────────────────────────────────────────────────────────────

/**
 * @return int[] [r, g, b]
 */
function sbek_hex_to_rgb( string $hex ): array {
	$hex = ltrim( (string) sanitize_hex_color( $hex ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return array( 0, 0, 0 );
	}

	return array_map( 'hexdec', str_split( $hex, 2 ) );
}

/**
 * Mix $hex toward $with by $amount (0–1).
 */
function sbek_mix_hex( string $hex, string $with, float $amount ): string {
	$a = sbek_hex_to_rgb( $hex );
	$b = sbek_hex_to_rgb( $with );

	return sprintf(
		'#%02x%02x%02x',
		(int) round( $a[0] + ( $b[0] - $a[0] ) * $amount ),
		(int) round( $a[1] + ( $b[1] - $a[1] ) * $amount ),
		(int) round( $a[2] + ( $b[2] - $a[2] ) * $amount )
	);
}

/**
 * WCAG relative luminance (0 = black, 1 = white).
 */
function sbek_luminance( string $hex ): float {
	$channels = array_map(
		static function ( int $c ): float {
			$c /= 255;
			return $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		},
		sbek_hex_to_rgb( $hex )
	);

	return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

/**
 * Text color for $background: white whenever it reaches 3:1 contrast (WCAG AA
 * for the large/bold UI text it's used on — menus, tabs, buttons; this keeps
 * white text on the default red), otherwise near-black.
 */
function sbek_readable_on( string $background ): string {
	$contrast_with_white = 1.05 / ( sbek_luminance( $background ) + 0.05 );

	return $contrast_with_white >= 3 ? '#ffffff' : '#111111';
}

/**
 * Normalize a color to lowercase 6-digit hex ("#fff" → "#ffffff"), or ''.
 */
function sbek_normalize_hex( string $value ): string {
	if ( ! sanitize_hex_color( $value ) ) {
		return '';
	}

	return vsprintf( '#%02x%02x%02x', sbek_hex_to_rgb( $value ) );
}

// ─── Output ───────────────────────────────────────────────────────────────────

/**
 * CSS custom-property overrides for the current design settings. Built only
 * from validated hex colors and whitelisted font families.
 */
function sbek_design_css(): string {
	$d     = sbek_design();
	$fonts = sbek_font_choices();
	$latin = sbek_latin_font_choices()[ $d['font_latin'] ]['stack'];

	$footer_dark = '#ffffff' === sbek_readable_on( $d['footer'] );

	$vars = array(
		'--color-menu'          => $d['brand'],
		'--color-menu-active'   => $d['brand_dark'],
		'--color-rule'          => 0 === strcasecmp( $d['brand'], SBEK_BRAND_COLOR ) ? '#e4242c' : $d['brand'],
		'--color-on-menu'       => sbek_readable_on( $d['brand'] ),
		'--color-top'           => $d['top'],
		'--color-on-top'        => sbek_readable_on( $d['top'] ),
		'--color-footer'        => $d['footer'],
		'--color-footer-fg'     => $footer_dark ? '#abaaab' : '#555555',
		'--color-footer-strong' => $footer_dark ? '#dddddd' : '#111111',
		// Khmer family first: its @font-face unicode-range covers only Khmer,
		// so Latin characters fall through to the Latin stack.
		'--font-sans'           => '"' . $fonts[ $d['font_body'] ]['family'] . '", ' . $latin,
		'--font-strong'         => '"' . $fonts[ $d['font_heading'] ]['family'] . '", ' . $latin,
		'--font-strong-weight'  => $fonts[ $d['font_heading'] ]['display'] ? '400' : '700',
	);

	$css = ':root{';
	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}

	return $css . '}';
}

/**
 * Attach the overrides to the main stylesheet and preload the body font.
 */
function sbek_enqueue_design(): void {
	wp_add_inline_style( 'sbek-main', sbek_design_css() );
}
add_action( 'wp_enqueue_scripts', 'sbek_enqueue_design', 20 );

/**
 * Preload the selected Khmer body font on Khmer pages, and tint the mobile
 * browser UI with the brand color.
 */
function sbek_design_head(): void {
	$d = sbek_design();

	if ( sbek_is_default_lang() ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( SBEK_URI . '/assets/fonts/' . sbek_font_choices()[ $d['font_body'] ]['file'] )
		);
	}

	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( $d['brand'] ) );
}
add_action( 'wp_head', 'sbek_design_head', 1 );

/**
 * Brand the login screen with the Design colors and fonts: overrides the
 * --sbek-* tokens login.css declares on body.login, and adds the
 * @font-face rules of the selected body font (login.css loads no fonts).
 */
function sbek_login_design(): void {
	$d      = sbek_design();
	$fonts  = sbek_font_choices();
	$family = $fonts[ $d['font_body'] ]['family'];
	$latin  = sbek_latin_font_choices()[ $d['font_latin'] ]['stack'];

	// The panel text sits mid-gradient; it is normal-size text, so it needs
	// 4.5:1 there (sbek_readable_on() targets 3:1 for large/bold UI text).
	$panel_end = sbek_mix_hex( $d['brand'], '#000000', 0.5 );
	$panel_mid = sbek_mix_hex( $d['brand'], '#000000', 0.25 );
	$on_panel  = 1.05 / ( sbek_luminance( $panel_mid ) + 0.05 ) >= 4.5 ? '#ffffff' : '#111111';

	$vars = array(
		'--sbek-primary'       => $d['brand'],
		'--sbek-primary-hover' => $d['brand_dark'],
		'--sbek-on-primary'    => sbek_readable_on( $d['brand'] ),
		'--sbek-ring'          => implode( ' ', sbek_hex_to_rgb( $d['brand'] ) ),
		'--sbek-panel-from'    => $d['brand'],
		'--sbek-panel-to'      => $panel_end,
		'--sbek-on-panel'      => $on_panel,
		'--sbek-font'          => '"' . $family . '", ' . $latin,
	);

	$css = 'body.login{';
	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}
	$css .= '}';

	wp_add_inline_style( 'sbek-login', sbek_font_face_css( $family ) . $css );
}
add_action( 'login_enqueue_scripts', 'sbek_login_design', 20 );

/**
 * The @font-face rules for one family, taken from assets/css/src/fonts.css
 * (the single source of truth for the font files) with absolute URLs, for
 * pages that don't load main.css.
 *
 * @param string $family Font family name as declared in fonts.css.
 * @return string CSS ('' if the family isn't declared).
 */
function sbek_font_face_css( string $family ): string {
	$file = SBEK_DIR . '/assets/css/src/fonts.css';
	$css  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.

	if ( '' === $css || ! preg_match_all( '/@font-face\s*\{[^}]*\}/', $css, $matches ) ) {
		return '';
	}

	$out = '';
	foreach ( $matches[0] as $rule ) {
		if ( false !== strpos( $rule, 'font-family: "' . $family . '";' ) ) {
			$out .= str_replace( 'url("../fonts/', 'url("' . SBEK_URI . '/assets/fonts/', $rule );
		}
	}

	return preg_replace( '/\s+/', ' ', $out );
}
