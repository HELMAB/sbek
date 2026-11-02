<?php
/**
 * Homepage category block: colored tab header, a lead story + 8-card grid,
 * and a "next" arrow paging through older stories (pages 2+ are shipped as
 * inert <template>s — no extra requests, no images loaded until shown).
 * Mobile: one swipe row ending in a "more" chevron.
 *
 * Args:
 *   term   WP_Term (category)
 *   posts  WP_Post[] (9 per page, up to 3 pages)
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

$sbek_term  = $args['term'] ?? null;
$sbek_posts = (array) ( $args['posts'] ?? array() );

if ( ! $sbek_term instanceof WP_Term || ! $sbek_posts ) {
	return;
}

$sbek_color = sbek_term_color( $sbek_term );
$sbek_url   = get_term_link( $sbek_term );
$sbek_url   = is_wp_error( $sbek_url ) ? '' : $sbek_url;
$sbek_pages = array_chunk( $sbek_posts, 9 );

// A last page with fewer than 3 stories looks broken; fold it away.
if ( count( $sbek_pages ) > 1 && count( end( $sbek_pages ) ) < 3 ) {
	array_pop( $sbek_pages );
}

/**
 * Render one page: lead + up to 8 grid cards.
 *
 * @param WP_Post[] $page Posts on this page.
 */
$sbek_render_page = static function ( array $page ): void {
	$lead = array_shift( $page );
	?>
	<div class="cat-page">
		<?php
		get_template_part(
			'template-parts/card/thumb',
			null,
			array(
				'post'        => $lead,
				'class'       => 'cat-page__lead',
				'size'        => 'sbek-wide',
				'sizes'       => '350px',
				'thumb_class' => 'thumb--lead',
				'title_class' => 'news-thumb__title--lead',
			)
		);
		?>
		<div class="card-grid card-grid--cat">
			<?php
			foreach ( $page as $post ) {
				get_template_part( 'template-parts/card/thumb', null, array( 'post' => $post ) );
			}
			?>
		</div>
	</div>
	<?php
};
?>
<section class="home-block" style="<?php echo esc_attr( sbek_color_style( $sbek_color ) ); ?>" aria-label="<?php echo esc_attr( $sbek_term->name ); ?>">
	<?php sbek_section_header( $sbek_term->name, $sbek_color, $sbek_url ); ?>
	<div class="home-block__body home-block__body--paged">
		<div class="pager" data-sbek-pager data-page-label="<?php /* translators: 1: current page, 2: number of pages. */ echo esc_attr__( 'Page %1$s of %2$s', 'sbek' ); ?>">
			<div class="pager__view" data-pager-view>
				<?php $sbek_render_page( $sbek_pages[0] ); ?>
			</div>
			<?php foreach ( array_slice( $sbek_pages, 1 ) as $sbek_page ) : ?>
				<template data-pager-page><?php $sbek_render_page( $sbek_page ); ?></template>
			<?php endforeach; ?>
			<?php if ( count( $sbek_pages ) > 1 ) : ?>
				<button type="button" class="pager__prev pager__prev--side" data-pager-prev>
					<?php echo sbek_icon( 'chevron-left', array( 'width' => 30, 'height' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span class="screen-reader-text">
						<?php
						/* translators: %s: category name. */
						echo esc_html( sprintf( __( 'Newer stories from %s', 'sbek' ), $sbek_term->name ) );
						?>
					</span>
				</button>
				<button type="button" class="pager__next pager__next--side" data-pager-next>
					<?php echo sbek_icon( 'chevron-right', array( 'width' => 30, 'height' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span class="screen-reader-text">
						<?php
						/* translators: %s: category name. */
						echo esc_html( sprintf( __( 'More from %s', 'sbek' ), $sbek_term->name ) );
						?>
					</span>
				</button>
				<p class="pager__count" aria-hidden="true" data-pager-count>1 / <?php echo esc_html( (string) count( $sbek_pages ) ); ?></p>
				<p class="screen-reader-text" role="status" data-pager-status></p>
			<?php endif; ?>
		</div>

		<div class="swipe-row" data-sbek-swipe>
			<div class="swipe-row__track">
				<?php
				foreach ( $sbek_pages[0] as $sbek_post ) {
					get_template_part( 'template-parts/card/swipe', null, array( 'post' => $sbek_post ) );
				}
				?>
				<?php if ( $sbek_url ) : ?>
					<a href="<?php echo esc_url( $sbek_url ); ?>" class="swipe-row__more">
						<?php echo sbek_icon( 'chevron-right', array( 'width' => 30, 'height' => 30 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span class="screen-reader-text"><?php echo esc_html( $sbek_term->name ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
