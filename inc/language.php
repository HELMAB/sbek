<?php
/**
 * Language helpers the templates use, in their single-language form.
 *
 * On its own the theme serves one language: the site language from
 * Settings → General. A plugin can make the site multilingual by defining
 * these functions first (plugins load before the theme) — the Sbek Companion
 * plugin does, adding /en/ URLs and linked translations — so every
 * definition here is wrapped in function_exists().
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sbek_lang' ) ) {
	/**
	 * Current language code (two letters), e.g. `km` or `en`.
	 */
	function sbek_lang(): string {
		return strtolower( substr( determine_locale(), 0, 2 ) );
	}
}

if ( ! function_exists( 'sbek_is_default_lang' ) ) {
	/**
	 * Whether the page is in the site's main language (always, on its own).
	 */
	function sbek_is_default_lang(): bool {
		return true;
	}
}

if ( ! function_exists( 'sbek_home_url' ) ) {
	/**
	 * Home URL in the current language. Use instead of home_url( '/' ).
	 *
	 * @param string|null $lang Language code (unused on its own).
	 */
	function sbek_home_url( ?string $lang = null ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- same signature as the multilingual version.
		return home_url( '/' );
	}
}

if ( ! function_exists( 'sbek_localize_url' ) ) {
	/**
	 * A site URL in the current language (unchanged on its own).
	 *
	 * @param string      $url  URL.
	 * @param string|null $lang Language code (unused on its own).
	 */
	function sbek_localize_url( string $url, ?string $lang = null ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- same signature as the multilingual version.
		return $url;
	}
}

if ( ! function_exists( 'sbek_term_description' ) ) {
	/**
	 * A category / tag description in the current language.
	 */
	function sbek_term_description( WP_Term $term ): string {
		return (string) $term->description;
	}
}

if ( ! function_exists( 'sbek_site_text' ) ) {
	/**
	 * A text setting from Sbek Theme in the current language.
	 */
	function sbek_site_text( string $key ): string {
		return (string) sbek_site_setting( $key, '' );
	}
}

/**
 * Menu location to render for a base location (`primary`, `footer`). A
 * multilingual plugin can point other languages at their own location.
 */
function sbek_menu_location( string $location ): string {
	return (string) apply_filters( 'sbek_menu_location', $location );
}
