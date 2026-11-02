<?php
/**
 * "News details" meta box on posts: trending score, video URL, source, and
 * the sponsored / breaking flags.
 *
 * Meta is registered with register_post_meta() so it carries a type,
 * sanitizer and auth callback wherever it is written (meta box, REST, CLI).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string,array{type:string,sanitize:callable}>
 */
function sbek_news_meta_fields(): array {
	return array(
		'_sbek_trending_score' => array(
			'type'     => 'integer',
			'sanitize' => static fn( $value ): int => max( 0, min( 100, (int) $value ) ),
		),
		'_sbek_video_url'      => array(
			'type'     => 'string',
			'sanitize' => 'esc_url_raw',
		),
		'_sbek_source_name'    => array(
			'type'     => 'string',
			'sanitize' => 'sanitize_text_field',
		),
		'_sbek_source_url'     => array(
			'type'     => 'string',
			'sanitize' => 'esc_url_raw',
		),
		'_sbek_is_sponsored'   => array(
			'type'     => 'boolean',
			'sanitize' => static fn( $value ): int => $value ? 1 : 0,
		),
		'_sbek_is_breaking'    => array(
			'type'     => 'boolean',
			'sanitize' => static fn( $value ): int => $value ? 1 : 0,
		),
	);
}

function sbek_register_news_meta(): void {
	foreach ( sbek_news_meta_fields() as $key => $field ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'              => $field['type'],
				'single'            => true,
				'sanitize_callback' => $field['sanitize'],
				'auth_callback'     => static fn( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', $post_id ),
				'show_in_rest'      => false,
			)
		);
	}
}
add_action( 'init', 'sbek_register_news_meta' );

function sbek_add_news_meta_box(): void {
	add_meta_box( 'sbek_news_meta', __( 'News details', 'sbek' ), 'sbek_render_news_meta_box', 'post', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'sbek_add_news_meta_box' );

function sbek_render_news_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'sbek_save_news_meta', 'sbek_news_meta_nonce' );

	$score     = get_post_meta( $post->ID, '_sbek_trending_score', true );
	$video     = get_post_meta( $post->ID, '_sbek_video_url', true );
	$source    = get_post_meta( $post->ID, '_sbek_source_name', true );
	$source_to = get_post_meta( $post->ID, '_sbek_source_url', true );
	$sponsored = (bool) get_post_meta( $post->ID, '_sbek_is_sponsored', true );
	$breaking  = (bool) get_post_meta( $post->ID, '_sbek_is_breaking', true );
	?>
	<p>
		<label for="sbek_trending_score"><strong><?php esc_html_e( 'Trending score (0–100)', 'sbek' ); ?></strong></label>
		<input type="number" min="0" max="100" step="1" id="sbek_trending_score" name="sbek_trending_score" value="<?php echo esc_attr( (string) $score ); ?>" class="widefat" placeholder="0">
		<span class="description"><?php esc_html_e( 'Higher scores rank first in "Popular articles".', 'sbek' ); ?></span>
	</p>
	<p>
		<label for="sbek_video_url"><strong><?php esc_html_e( 'Video URL', 'sbek' ); ?></strong></label>
		<input type="url" id="sbek_video_url" name="sbek_video_url" value="<?php echo esc_attr( (string) $video ); ?>" class="widefat" placeholder="https://www.youtube.com/watch?v=…">
		<span class="description"><?php esc_html_e( 'Embedded above the article. Set the post format to "Video" to list it in the homepage video block.', 'sbek' ); ?></span>
	</p>
	<p>
		<label for="sbek_source_name"><strong><?php esc_html_e( 'Source', 'sbek' ); ?></strong></label>
		<input type="text" id="sbek_source_name" name="sbek_source_name" value="<?php echo esc_attr( (string) $source ); ?>" class="widefat">
	</p>
	<p>
		<label for="sbek_source_url"><strong><?php esc_html_e( 'Source URL', 'sbek' ); ?></strong></label>
		<input type="url" id="sbek_source_url" name="sbek_source_url" value="<?php echo esc_attr( (string) $source_to ); ?>" class="widefat">
	</p>
	<p>
		<label><input type="checkbox" name="sbek_is_sponsored" value="1" <?php checked( $sponsored ); ?>> <?php esc_html_e( 'Sponsored / advertorial', 'sbek' ); ?></label><br>
		<label><input type="checkbox" name="sbek_is_breaking" value="1" <?php checked( $breaking ); ?>> <?php esc_html_e( 'Breaking news (header ticker)', 'sbek' ); ?></label>
	</p>
	<?php
}

/**
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function sbek_save_news_meta( int $post_id, WP_Post $post ): void {
	if ( 'post' !== $post->post_type || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['sbek_news_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sbek_news_meta_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sbek_save_news_meta' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( sbek_news_meta_fields() as $key => $field ) {
		$input = ltrim( $key, '_' );

		if ( 'boolean' === $field['type'] ) {
			$value = ! empty( $_POST[ $input ] ); // Absent checkbox = off.
		} else {
			$value = isset( $_POST[ $input ] ) ? trim( (string) wp_unslash( $_POST[ $input ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the registered callback in update_post_meta().
		}

		if ( '' === $value || false === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value ); // Runs the registered sanitize_callback.
		}
	}
}
add_action( 'save_post', 'sbek_save_news_meta', 10, 2 );
