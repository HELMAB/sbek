<?php
/**
 * Homepage hero. One set of tiles serves both layouts: a 2×2 grid (7fr/5fr
 * columns) from 768px, a single-slide auto-advancing carousel below that —
 * so each image is downloaded once, whatever the viewport.
 *
 * Args:
 *   posts  WP_Post[] (up to 4)
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_posts = array_slice( (array) ( $args['posts'] ?? array() ), 0, 4 );
if ( ! $sbek_posts ) {
	return;
}

$sbek_has_ads = is_active_sidebar( 'sbek-ad-hero' );
?>
<section id="banner" class="banner container<?php echo $sbek_has_ads ? ' banner--ads' : ''; ?>" aria-label="<?php esc_attr_e( 'Top stories', 'sbek' ); ?>">
	<div class="banner__main" data-sbek-carousel aria-roledescription="carousel">
		<div class="banner__track" data-carousel-track aria-live="polite">
			<?php
			foreach ( $sbek_posts as $sbek_i => $sbek_post ) :
				$sbek_wide = in_array( $sbek_i, array( 0, 2 ), true );
				get_template_part(
					'template-parts/card/hero',
					null,
					array(
						'post'  => $sbek_post,
						'class' => 'banner__item' . ( $sbek_wide ? ' banner__item--wide' : '' ),
						'sizes' => $sbek_wide
							? '(max-width: 767px) 100vw, (max-width: 991px) 260px, 490px'
							: '(max-width: 767px) 100vw, (max-width: 991px) 190px, 350px',
						'lcp'   => 0 === $sbek_i,
						'eager' => $sbek_i < 2,
					)
				);
			endforeach;
			?>
		</div>
		<?php if ( count( $sbek_posts ) > 1 ) : ?>
			<button type="button" class="banner__nav banner__nav--prev" data-carousel-prev>
				<?php echo sbek_icon( 'chevron-left', array( 'width' => 22, 'height' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Previous', 'sbek' ); ?></span>
			</button>
			<button type="button" class="banner__nav banner__nav--next" data-carousel-next>
				<?php echo sbek_icon( 'chevron-right', array( 'width' => 22, 'height' => 22 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Next', 'sbek' ); ?></span>
			</button>
		<?php endif; ?>
	</div>
	<?php sbek_ad_area( 'sbek-ad-hero', 'banner__ads' ); ?>
</section>
