<?php
/**
 * Hero tile (homepage banner grid / mobile carousel slide): full-bleed image,
 * dark gradient, headline + date, category badge.
 *
 * Args:
 *   post   WP_Post (required)
 *   class  Extra classes on the tile.
 *   sizes  `sizes` attribute for the image.
 *   lcp    true for the Largest-Contentful-Paint candidate (high priority).
 *   eager  Load eagerly (above the fold). Default true.
 *   date   Show the date line. Default true.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_post = $args['post'] ?? null;
if ( ! $sbek_post instanceof WP_Post ) {
	return;
}

$sbek_img_attrs = array(
	'sizes'   => $args['sizes'] ?? '100vw',
	'loading' => ( $args['eager'] ?? true ) ? 'eager' : 'lazy',
);
if ( ! empty( $args['lcp'] ) ) {
	$sbek_img_attrs['fetchpriority'] = 'high';
}
?>
<a href="<?php echo esc_url( get_permalink( $sbek_post ) ); ?>" class="hero-item <?php echo esc_attr( $args['class'] ?? '' ); ?>">
	<?php echo sbek_get_post_image( $sbek_post, 'sbek-wide', $sbek_img_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
	<span class="hero-item__shade" aria-hidden="true"></span>
	<span class="hero-item__text">
		<?php echo sbek_get_sponsored_label( $sbek_post, 'sponsored-label--on-image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
		<span class="hero-item__title"><?php echo esc_html( get_the_title( $sbek_post ) ); ?></span>
		<?php if ( $args['date'] ?? true ) : ?>
			<span class="hero-item__date"><?php echo esc_html( sbek_post_date( $sbek_post ) ); ?></span>
		<?php endif; ?>
	</span>
	<?php echo sbek_get_category_badge( $sbek_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
</a>
