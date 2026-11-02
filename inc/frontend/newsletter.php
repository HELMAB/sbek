<?php
/**
 * Newsletter subscriptions — self-contained, no third-party service.
 *
 * Subscribers are stored as a private `sbek_subscriber` post type (title =
 * email address) so editors get a native admin list. The footer form posts
 * to admin-post.php; the handler checks the honeypot, nonce, a per-IP
 * throttle and the email, dedupes, stores, then redirects back with a
 * ?subscribed= status flag that the form reads to show a message.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

const SBEK_SUBSCRIBE_MAX_PER_HOUR = 5;

function sbek_register_subscriber_cpt(): void {
	register_post_type(
		'sbek_subscriber',
		array(
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
			'capability_type' => array( 'sbek_subscriber', 'sbek_subscribers' ),
			'capabilities'    => array(
				// Personal data: only administrators see or delete the list.
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'publish_posts'          => 'do_not_allow',
				'create_posts'           => 'do_not_allow', // Capture only via the front-end form.
			),
			'map_meta_cap'    => true,
			'supports'        => array( 'title' ),
			'rewrite'         => false,
			'query_var'       => false,
		)
	);
}
add_action( 'init', 'sbek_register_subscriber_cpt' );

/**
 * Redirect back to the submitting page with a status flag.
 */
function sbek_subscribe_redirect( string $status ): void {
	$referer = wp_get_referer(); // Validated against allowed hosts by core.
	$target  = $referer ? $referer : home_url( '/' );

	wp_safe_redirect( add_query_arg( 'subscribed', $status, remove_query_arg( 'subscribed', $target ) ) . '#newsletter' );
	exit;
}

function sbek_handle_subscribe(): void {
	// Honeypot: bots fill the hidden field. Pretend success, store nothing.
	if ( ! empty( $_POST['sbek_website'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below; bail-out only.
		sbek_subscribe_redirect( 'success' );
	}

	$nonce = isset( $_POST['sbek_newsletter_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sbek_newsletter_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sbek_subscribe' ) ) {
		sbek_subscribe_redirect( 'error' );
	}

	$ip_hash      = sbek_subscriber_ip_hash();
	$throttle_key = 'sbek_sub_rate_' . md5( $ip_hash );
	$attempts     = (int) get_transient( $throttle_key );
	if ( $ip_hash && $attempts >= SBEK_SUBSCRIBE_MAX_PER_HOUR ) {
		sbek_subscribe_redirect( 'error' );
	}
	if ( $ip_hash ) {
		set_transient( $throttle_key, $attempts + 1, HOUR_IN_SECONDS );
	}

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		sbek_subscribe_redirect( 'error' );
	}

	$lang = sanitize_key( wp_unslash( $_POST['lang'] ?? '' ) );
	if ( ! isset( sbek_languages()[ $lang ] ) ) {
		$lang = SBEK_DEFAULT_LANGUAGE;
	}

	$existing = get_posts(
		array(
			'post_type'              => 'sbek_subscriber',
			'post_status'            => 'any',
			'title'                  => $email,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( $existing ) {
		sbek_subscribe_redirect( 'duplicate' );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'sbek_subscriber',
			'post_title'  => $email,
			'post_status' => 'private',
			'meta_input'  => array(
				'_sbek_sub_lang' => $lang,
				'_sbek_sub_ip'   => $ip_hash,
			),
		),
		true
	);

	sbek_subscribe_redirect( is_wp_error( $post_id ) ? 'error' : 'success' );
}
add_action( 'admin_post_nopriv_sbek_subscribe', 'sbek_handle_subscribe' );
add_action( 'admin_post_sbek_subscribe', 'sbek_handle_subscribe' );

/**
 * Salted hash of the submitter's IP — enough to throttle and spot abuse
 * without storing raw personal data.
 */
function sbek_subscriber_ip_hash(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? wp_hash( $ip ) : '';
}

/**
 * Message for the current ?subscribed= flag, or null when there is none.
 *
 * @return array{message:string,error:bool}|null
 */
function sbek_subscribe_notice(): ?array {
	if ( ! isset( $_GET['subscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag.
		return null;
	}

	switch ( sanitize_key( wp_unslash( $_GET['subscribed'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		case 'success':
			return array(
				'message' => __( 'Thanks! You are subscribed.', 'sbek' ),
				'error'   => false,
			);
		case 'duplicate':
			return array(
				'message' => __( 'This email address is already subscribed.', 'sbek' ),
				'error'   => false,
			);
		default:
			return array(
				'message' => __( 'Sorry, that did not work. Please check the address and try again.', 'sbek' ),
				'error'   => true,
			);
	}
}

// ─── Admin: CSV export ─────────────────────────────────────────────────────────

const SBEK_EXPORT_SUBSCRIBERS_ACTION = 'sbek_export_subscribers';

/**
 * Nonce-protected download link for the subscriber CSV.
 */
function sbek_subscribers_export_url(): string {
	return wp_nonce_url( admin_url( 'admin-post.php?action=' . SBEK_EXPORT_SUBSCRIBERS_ACTION ), SBEK_EXPORT_SUBSCRIBERS_ACTION );
}

/**
 * Stream every subscriber as CSV (email, language, date). Administrators only.
 */
function sbek_export_subscribers(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'sbek' ), 403 );
	}

	check_admin_referer( SBEK_EXPORT_SUBSCRIBERS_ACTION );

	$ids = get_posts(
		array(
			'post_type'      => 'sbek_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="subscribers-' . wp_date( 'Y-m-d' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so spreadsheet apps detect the encoding.
	fputcsv( $out, array( 'email', 'language', 'subscribed' ) );

	// A leading = + - @ would be run as a formula by spreadsheet apps.
	$cell = static fn( string $value ): string => preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;

	foreach ( $ids as $id ) {
		fputcsv(
			$out,
			array(
				$cell( get_the_title( $id ) ),
				$cell( (string) get_post_meta( $id, '_sbek_sub_lang', true ) ),
				get_the_date( 'Y-m-d H:i', $id ),
			)
		);
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_' . SBEK_EXPORT_SUBSCRIBERS_ACTION, 'sbek_export_subscribers' );

/**
 * "Export CSV" button above the Subscribers list.
 *
 * @param string $which top | bottom.
 */
function sbek_subscribers_export_button( string $which ): void {
	if ( 'top' !== $which || 'sbek_subscriber' !== get_current_screen()?->post_type || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	printf(
		'<div class="alignleft actions"><a class="button" href="%s">%s</a></div>',
		esc_url( sbek_subscribers_export_url() ),
		esc_html__( 'Export CSV', 'sbek' )
	);
}
add_action( 'manage_posts_extra_tablenav', 'sbek_subscribers_export_button' );
