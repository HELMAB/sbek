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
		<div class="banner__track" id="sbek-banner-track" data-carousel-track aria-live="off">
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
			<div class="banner__controls">
				<button type="button" class="banner__pause" aria-controls="sbek-banner-track" data-carousel-pause hidden
					data-label-pause="<?php esc_attr_e( 'Stop automatic slide show', 'sbek' ); ?>"
					data-label-play="<?php esc_attr_e( 'Start automatic slide show', 'sbek' ); ?>">
					<?php echo sbek_icon( 'pause', array( 'width' => 14, 'height' => 14, 'class' => 'banner__pause-icon' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<?php echo sbek_icon( 'play', array( 'width' => 14, 'height' => 14, 'class' => 'banner__play-icon' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span class="screen-reader-text" data-carousel-pause-label><?php esc_html_e( 'Stop automatic slide show', 'sbek' ); ?></span>
				</button>
				<div class="banner__dots" role="group" aria-label="<?php esc_attr_e( 'Choose a story', 'sbek' ); ?>">
					<?php foreach ( $sbek_posts as $sbek_i => $sbek_post ) : ?>
						<button type="button" class="banner__dot" data-carousel-dot="<?php echo esc_attr( (string) $sbek_i ); ?>"<?php echo 0 === $sbek_i ? ' aria-current="true"' : ''; ?>>
							<span class="screen-reader-text">
								<?php
								/* translators: 1: story number, 2: number of stories. */
								echo esc_html( sprintf( __( 'Story %1$d of %2$d', 'sbek' ), $sbek_i + 1, count( $sbek_posts ) ) );
								?>
							</span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php sbek_ad_area( 'sbek-ad-hero', 'banner__ads' ); ?>
</section>
