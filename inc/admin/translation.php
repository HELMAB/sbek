<?php
/**
 * "Translation" meta box: pairs a post with its counterpart in the other
 * language via `_sbek_translation_id` (stored on both posts). Drives hreflang
 * tags and the header language switcher.
 *
 * Integrity rules enforced on save: same post type, different language, not
 * itself, editable by the current user; re-linking or unlinking also clears
 * the previous partner's back-reference so pairs never go one-sided.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

function sbek_register_translation_meta(): void {
	foreach ( array( 'post', 'page' ) as $post_type ) {
		register_post_meta(
			$post_type,
			'_sbek_translation_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => static fn( $allowed, $key, $post_id ): bool => current_user_can( 'edit_post', $post_id ),
				'show_in_rest'      => false,
			)
		);
	}
}
add_action( 'init', 'sbek_register_translation_meta' );

function sbek_add_translation_meta_box(): void {
	add_meta_box( 'sbek_translation', __( 'Translation', 'sbek' ), 'sbek_render_translation_meta_box', array( 'post', 'page' ), 'side', 'default' );
}
add_action( 'add_meta_boxes', 'sbek_add_translation_meta_box' );

function sbek_render_translation_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'sbek_save_translation', 'sbek_translation_nonce' );

	$linked_id = (int) get_post_meta( $post->ID, '_sbek_translation_id', true );
	$own_lang  = sbek_post_language( $post->ID ) ?: SBEK_DEFAULT_LANGUAGE;
	$others    = array_diff( array_keys( sbek_languages() ), array( $own_lang ) );

	// Recent posts in the other language(s), plus the current link.
	$candidates = get_posts(
		array(
			'post_type'          => $post->post_type,
			'post_status'        => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page'     => 100,
			'post__not_in'       => array( $post->ID ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'no_found_rows'      => true,
			'sbek_all_languages' => true,
			'tax_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'language',
					'field'    => 'slug',
					'terms'    => array_values( $others ),
				),
			),
		)
	);

	if ( $linked_id && ! in_array( $linked_id, wp_list_pluck( $candidates, 'ID' ), true ) ) {
		$linked = get_post( $linked_id );
		if ( $linked ) {
			array_unshift( $candidates, $linked );
		}
	}
	?>
	<p>
		<label for="sbek_translation_id" class="screen-reader-text"><?php esc_html_e( 'Translated version', 'sbek' ); ?></label>
		<select id="sbek_translation_id" name="sbek_translation_id" class="widefat">
			<option value="0"><?php esc_html_e( '— Not translated —', 'sbek' ); ?></option>
			<?php foreach ( $candidates as $candidate ) : ?>
				<option value="<?php echo esc_attr( (string) $candidate->ID ); ?>" <?php selected( $linked_id, $candidate->ID ); ?>>
					<?php echo esc_html( sprintf( '[%s] %s (#%d)', strtoupper( sbek_post_language( $candidate->ID ) ?: '?' ), get_the_title( $candidate ) ?: __( '(no title)', 'sbek' ), $candidate->ID ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="description"><?php esc_html_e( 'Pick the same story in the other language. The link is saved on both posts.', 'sbek' ); ?></p>
	<?php
}

/**
 * Point $post_id at $target (0 = unlink), keeping both sides consistent.
 */
function sbek_set_translation( int $post_id, int $target ): void {
	$previous = (int) get_post_meta( $post_id, '_sbek_translation_id', true );

	if ( $previous && $previous !== $target && (int) get_post_meta( $previous, '_sbek_translation_id', true ) === $post_id ) {
		delete_post_meta( $previous, '_sbek_translation_id' );
	}

	if ( ! $target ) {
		delete_post_meta( $post_id, '_sbek_translation_id' );
		return;
	}

	// The target may already be paired with a third post: release it.
	$targets_previous = (int) get_post_meta( $target, '_sbek_translation_id', true );
	if ( $targets_previous && $targets_previous !== $post_id ) {
		delete_post_meta( $targets_previous, '_sbek_translation_id' );
	}

	update_post_meta( $post_id, '_sbek_translation_id', $target );
	update_post_meta( $target, '_sbek_translation_id', $post_id );
}

/**
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function sbek_save_translation_meta( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! isset( $_POST['sbek_translation_id'] ) ) {
		return;
	}

	$nonce = isset( $_POST['sbek_translation_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sbek_translation_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sbek_save_translation' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$target = absint( $_POST['sbek_translation_id'] );

	if ( $target ) {
		$target_post = get_post( $target );
		$valid       = $target_post
			&& $target !== $post_id
			&& $target_post->post_type === $post->post_type
			&& current_user_can( 'edit_post', $target )
			&& sbek_post_language( $target ) !== ( sbek_post_language( $post_id ) ?: SBEK_DEFAULT_LANGUAGE );

		if ( ! $valid ) {
			return; // Leave the existing link untouched rather than storing garbage.
		}
	}

	sbek_set_translation( $post_id, $target );
}
add_action( 'save_post', 'sbek_save_translation_meta', 30, 2 );
