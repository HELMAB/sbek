<?php
/**
 * Archive / search feed item. One markup for both views: the list view shows
 * thumbnail + headline + date + excerpt in a row, the grid view (CSS only)
 * shows thumbnail + headline. `data-feed-item` is what infinite scroll
 * collects from the next page.
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
?>
<article class="feed-item" data-feed-item>
	<a href="<?php echo esc_url( get_permalink( $sbek_post ) ); ?>" class="feed-item__link">
		<div class="feed-item__media">
			<?php
			echo sbek_get_thumb( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
				$sbek_post,
				array(
					'size'  => 'sbek-card',
					'sizes' => '(max-width: 767px) 100vw, (max-width: 991px) 50vw, 250px',
				)
			);
			?>
		</div>
		<div class="feed-item__body">
			<h2 class="feed-item__title"><?php echo esc_html( get_the_title( $sbek_post ) ); ?></h2>
			<p class="feed-item__meta">
				<?php echo sbek_icon( 'clock', array( 'width' => 12, 'height' => 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $sbek_post ) ); ?>"><?php echo esc_html( sbek_post_date( $sbek_post ) ); ?></time>
			</p>
			<p class="feed-item__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $sbek_post ) ) ); ?></p>
		</div>
	</a>
</article>
