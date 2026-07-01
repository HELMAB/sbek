<?php
/**
 * Third-party integrations: Google Analytics 4, Google Tag Manager, and
 * custom header/footer scripts — all driven by the Options page.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * Head output: GTM container, GA4 gtag, and raw header scripts.
 * Skipped in the admin and for logged-in editors to keep analytics clean.
 */
function sbek_integrations_head(): void {
	if ( is_admin() ) {
		return;
	}

	$gtm_id         = sbek_site_setting( 'gtm_id', '' );
	$ga4_id         = sbek_site_setting( 'ga4_id', '' );
	$header_scripts = sbek_site_setting( 'header_scripts', '' );

	// Google Tag Manager (head half).
	if ( $gtm_id ) {
		printf(
			"<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','%s');</script>\n<!-- End Google Tag Manager -->\n",
			esc_js( $gtm_id )
		);
	}

	// Google Analytics 4 (gtag.js) — only when GTM is not handling it.
	if ( $ga4_id && ! $gtm_id ) {
		printf(
			"<!-- Google Analytics 4 -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=%s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%s');</script>\n",
			esc_attr( rawurlencode( $ga4_id ) ),
			esc_js( $ga4_id )
		);
	}

	// Raw custom header scripts (already capability-gated on save).
	if ( $header_scripts ) {
		echo "\n" . $header_scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'sbek_integrations_head', 20 );

/**
 * Immediately-after-<body> output: the GTM <noscript> fallback.
 */
function sbek_integrations_body_open(): void {
	if ( is_admin() ) {
		return;
	}

	$gtm_id = sbek_site_setting( 'gtm_id', '' );

	if ( $gtm_id ) {
		printf(
			'<!-- Google Tag Manager (noscript) --><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n",
			esc_attr( rawurlencode( $gtm_id ) )
		);
	}
}
add_action( 'wp_body_open', 'sbek_integrations_body_open' );

/**
 * Footer output: raw custom footer scripts.
 */
function sbek_integrations_footer(): void {
	if ( is_admin() ) {
		return;
	}

	$footer_scripts = sbek_site_setting( 'footer_scripts', '' );

	if ( $footer_scripts ) {
		echo "\n" . $footer_scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'sbek_integrations_footer', 20 );
