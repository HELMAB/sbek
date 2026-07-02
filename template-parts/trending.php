<?php
/**
 * Trending rail panel (numbered list), shared by front-page.php and single.php.
 *
 * Args:
 * - 'limit' (int) How many posts to list. Default 5.
 *
 * Renders nothing when there are no posts, so callers don't get an empty box.
 */

$sbek_trending_limit = isset( $args['limit'] ) ? (int) $args['limit'] : 5;
$sbek_trending       = sbek_get_trending_posts( $sbek_trending_limit );

if ( ! $sbek_trending->have_posts() ) {
    return;
}

$sbek_rank = 0;
?>
<div class="rounded-sm border border-gray-200 bg-white">
    <h2 class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 font-display text-lg font-extrabold uppercase tracking-tight text-ink">
        <span class="inline-block h-4 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'trending' ) ); ?>
    </h2>
    <ol class="divide-y divide-gray-200">
        <?php while ( $sbek_trending->have_posts() ) : $sbek_trending->the_post(); $sbek_rank++; ?>
            <li>
                <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="group flex gap-3 px-4 py-3">
                    <span class="w-6 shrink-0 font-display text-2xl font-extrabold leading-none text-gray-300 transition-colors group-hover:text-sbek-red"><?php echo esc_html( $sbek_rank ); ?></span>
                    <span class="font-display text-[15px] font-bold leading-snug text-ink transition-colors group-hover:text-sbek-red"><?php the_title(); ?></span>
                </a>
            </li>
        <?php endwhile; wp_reset_postdata(); ?>
    </ol>
</div>
