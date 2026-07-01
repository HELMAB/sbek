<?php
/**
 * Reusable presentational components (front end).
 */

defined( 'ABSPATH' ) || exit;

/**
 * A labeled, reserved advertising slot.
 *
 * Holds its space gracefully whether or not a network fills it, so ad units
 * dropped in later don't reflow the layout. Purely presentational — real ad
 * markup would be injected inside the returned container.
 *
 * @param string $variant One of: 'leaderboard', 'in-feed', 'rectangle'.
 * @return string
 */
function sbek_ad_slot( string $variant = 'leaderboard' ): string {
	$sizes = array(
		'leaderboard' => 'h-24 md:h-28',
		'in-feed'     => 'h-24 md:h-32',
		'rectangle'   => 'h-[250px]',
	);

	$height = $sizes[ $variant ] ?? $sizes['leaderboard'];

	return '<div class="flex ' . esc_attr( $height ) . ' w-full items-center justify-center rounded-sm border border-dashed border-gray-300 bg-gray-100/70">'
		. '<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">' . esc_html( t( 'advertisement' ) ) . '</span>'
		. '</div>';
}

/**
 * Estimated reading time in whole minutes (~200 words/min).
 *
 * Note: word counting is space-based, so Khmer/Japanese (which don't space
 * between words) will read low — acceptable for a rough "min read" badge.
 *
 * @param int|null $post_id Defaults to the current post.
 * @return int Minutes, minimum 1.
 */
function sbek_reading_time( ?int $post_id = null ): int {
	$content = get_post_field( 'post_content', $post_id ?: get_the_ID() );
	$words   = str_word_count( wp_strip_all_tags( (string) $content ) );

	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * A section heading bar: a red tick, the section name in the display face,
 * and an optional "View all" link. Used to open homepage section modules.
 *
 * @param string $title    Heading text (already localized).
 * @param string $view_url Optional "view all" URL (already localized/escaped-safe).
 * @return string
 */
function sbek_section_heading( string $title, string $view_url = '' ): string {
	$html  = '<div class="mb-5 flex items-baseline justify-between border-b-2 border-ink pb-2">';
	$html .= '<h2 class="flex items-center gap-2.5 font-display text-xl font-extrabold uppercase tracking-tight text-ink">';
	$html .= '<span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span>' . esc_html( $title );
	$html .= '</h2>';

	if ( $view_url ) {
		$html .= '<a href="' . esc_url( $view_url ) . '" class="group inline-flex items-center gap-1 text-sm font-bold text-sbek-red">'
			. esc_html( t( 'view_all' ) )
			. sbek_icon( 'arrow-right', [ 'class' => 'h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5' ] )
			. '</a>';
	}

	$html .= '</div>';

	return $html;
}
