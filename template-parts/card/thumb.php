<?php
/**
 * News card: zoom-on-hover thumbnail followed by the headline.
 *
 * Args:
 *   post        WP_Post (required)
 *   class       Extra classes on the link.
 *   size        Image size (default sbek-card).
 *   sizes       `sizes` attribute for the image.
 *   thumb_class Extra classes on the image frame (e.g. fixed heights).
 *   title_class Modifier classes for the headline.
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_post = $args['post'] ?? null;
if ( ! $sbek_post instanceof WP_Post ) {
	return;
}
?>
<a href="<?php echo esc_url( get_permalink( $sbek_post ) ); ?>" class="news-thumb <?php echo esc_attr( $args['class'] ?? '' ); ?>">
	<?php
	echo sbek_get_thumb( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		$sbek_post,
		array(
			'size'  => $args['size'] ?? 'sbek-card',
			'sizes' => $args['sizes'] ?? '(max-width: 767px) 150px, 180px',
			'class' => $args['thumb_class'] ?? '',
		)
	);
	?>
	<h3 class="news-thumb__title <?php echo esc_attr( $args['title_class'] ?? '' ); ?>"><?php echo esc_html( get_the_title( $sbek_post ) ); ?></h3>
</a>
