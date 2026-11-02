<?php
/**
 * Sidebar list row: 40×40 thumbnail + bold headline.
 *
 * Args:
 *   post  WP_Post (required)
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_post = $args['post'] ?? null;
if ( ! $sbek_post instanceof WP_Post ) {
	return;
}

$sbek_url = get_permalink( $sbek_post );
?>
<li class="media-row">
	<a href="<?php echo esc_url( $sbek_url ); ?>" class="media-row__thumb" tabindex="-1" aria-hidden="true">
		<?php
		echo sbek_get_post_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
			$sbek_post,
			'thumbnail',
			array(
				'sizes'   => '40px',
				'loading' => 'lazy',
			)
		);
		?>
	</a>
	<a href="<?php echo esc_url( $sbek_url ); ?>" class="media-row__title"><?php echo esc_html( get_the_title( $sbek_post ) ); ?></a>
</li>
