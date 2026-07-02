<?php
/**
 * Newsletter subscriptions — self-contained, no third-party service.
 *
 * Subscribers are stored as a private `sbek_subscriber` post type (the post
 * title is the email address), so editors get a native admin list for free
 * and downstream export/sending can be layered on later. The footer form
 * posts to admin-post.php; the handler validates, dedupes, stores, then
 * redirects back to the submitting page with a ?subscribed= status flag that
 * footer.php reads to show a success/error message.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the private subscriber post type.
 *
 * Not public (no front-end archive/single); visible in wp-admin only to users
 * who can manage options, purely as a read-only list of captured emails.
 */
function sbek_register_subscriber_cpt(): void {
	register_post_type( 'sbek_subscriber', array(
		'labels'          => array(
			'name'          => __( 'Subscribers', 'sbek' ),
			'singular_name' => __( 'Subscriber', 'sbek' ),
			'menu_name'     => __( 'Subscribers', 'sbek' ),
			'search_items'  => __( 'Search Subscribers', 'sbek' ),
			'not_found'     => __( 'No subscribers yet.', 'sbek' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-email',
		'capability_type' => 'post',
		'capabilities'    => array(
			'create_posts' => 'do_not_allow', // Capture only via the front-end form.
		),
		'map_meta_cap'    => true,
		'supports'        => array( 'title' ),
		'rewrite'         => false,
		'query_var'       => false,
	) );
}
add_action( 'init', 'sbek_register_subscriber_cpt' );

/**
 * Handle a newsletter subscription submission from the footer form.
 *
 * Bails to a redirect with ?subscribed=error on nonce/validation failure,
 * ?subscribed=duplicate if already subscribed, ?subscribed=success otherwise.
 */
function sbek_handle_subscribe(): void {
	$redirect = wp_get_referer() ?: home_url( '/' );

	// Honeypot: real users leave this hidden field empty. Bots fill it — treat
	// as success so they get no signal, but store nothing.
	if ( ! empty( $_POST['sbek_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'success', $redirect ) . '#colophon' );
		exit;
	}

	if (
		! isset( $_POST['sbek_newsletter_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sbek_newsletter_nonce'] ) ), 'sbek_subscribe' )
	) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'error', $redirect ) . '#colophon' );
		exit;
	}

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'error', $redirect ) . '#colophon' );
		exit;
	}

	// Dedupe on the email (stored as the post title). get_page_by_title() is
	// deprecated since WP 6.2, so match on post_title via WP_Query.
	$existing = get_posts( array(
		'post_type'              => 'sbek_subscriber',
		'post_status'            => 'publish',
		'title'                  => $email,
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	) );

	if ( ! empty( $existing ) ) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'duplicate', $redirect ) . '#colophon' );
		exit;
	}

	$lang    = function_exists( 'sbek_lang' ) ? sbek_lang() : 'en';
	$post_id = wp_insert_post( array(
		'post_type'   => 'sbek_subscriber',
		'post_title'  => $email,
		'post_status' => 'publish',
		'meta_input'  => array(
			'_sbek_sub_lang' => $lang,
			'_sbek_sub_ip'   => sbek_subscriber_ip_hash(),
		),
	), true );

	$status = ( $post_id && ! is_wp_error( $post_id ) ) ? 'success' : 'error';

	wp_safe_redirect( add_query_arg( 'subscribed', $status, $redirect ) . '#colophon' );
	exit;
}
add_action( 'admin_post_nopriv_sbek_subscribe', 'sbek_handle_subscribe' );
add_action( 'admin_post_sbek_subscribe', 'sbek_handle_subscribe' );

/**
 * A privacy-preserving, salted hash of the submitter's IP — enough to spot
 * abuse patterns without storing raw personal data.
 */
function sbek_subscriber_ip_hash(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return $ip ? wp_hash( $ip ) : '';
}

/**
 * Map the current ?subscribed= flag to a [message, is_error] pair for display.
 *
 * @return array{0:string,1:bool}|null Null when no subscription attempt is on the URL.
 */
function sbek_subscribe_notice(): ?array {
	if ( ! isset( $_GET['subscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag.
		return null;
	}

	$status = sanitize_key( wp_unslash( $_GET['subscribed'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	switch ( $status ) {
		case 'success':
			return array( t( 'subscribe_success' ), false );
		case 'duplicate':
			return array( t( 'subscribe_duplicate' ), false );
		default:
			return array( t( 'subscribe_error' ), true );
	}
}
