<?php
/**
 * Security hardening for Sbek — shared by admin and frontend requests.
 * Headers, information-disclosure reduction, and login rate limiting.
 */

// Must run at include time (before admin_menu/capability checks), not on a hook.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Baseline security response headers.
 */
function sbek_security_headers() {
    if ( headers_sent() ) {
        return;
    }

    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
}
add_action( 'send_headers', 'sbek_security_headers' );

/**
 * Reduce version-disclosure surface: no generator meta tag, no ?ver= query
 * strings on core-enqueued assets (left alone when SCRIPT_DEBUG is on).
 */
remove_action( 'wp_head', 'wp_generator' );

function sbek_remove_asset_version_query( $src ) {
    if ( ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) && $src && false !== strpos( $src, 'ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'script_loader_src', 'sbek_remove_asset_version_query' );
add_filter( 'style_loader_src', 'sbek_remove_asset_version_query' );

/**
 * Theme doesn't use XML-RPC.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Login rate limiting: lock an IP out for 15 minutes after 5 failed attempts.
 */
function sbek_login_attempts_key() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    return $ip ? 'sbek_login_attempts_' . md5( $ip ) : '';
}

function sbek_check_login_lockout( $user, $username, $password ) {
    if ( empty( $username ) && empty( $password ) ) {
        return $user; // Not a login submission (e.g. initial form render).
    }

    $key = sbek_login_attempts_key();

    if ( $key && (int) get_transient( $key ) >= 5 ) {
        return new WP_Error( 'sbek_too_many_attempts', esc_html( t( 'login_rate_limited' ) ) );
    }

    return $user;
}
add_filter( 'authenticate', 'sbek_check_login_lockout', 30, 3 );

function sbek_record_login_failure() {
    $key = sbek_login_attempts_key();

    if ( ! $key ) {
        return;
    }

    $attempts = (int) get_transient( $key );
    set_transient( $key, $attempts + 1, 15 * MINUTE_IN_SECONDS );
}
add_action( 'wp_login_failed', 'sbek_record_login_failure' );

function sbek_clear_login_attempts() {
    $key = sbek_login_attempts_key();

    if ( $key ) {
        delete_transient( $key );
    }
}
add_action( 'wp_login', 'sbek_clear_login_attempts' );
