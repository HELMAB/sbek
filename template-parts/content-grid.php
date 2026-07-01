<?php
/**
 * Standard grid card.
 *
 * Rendered in homepage/archive grids and appended verbatim by the "Load More"
 * AJAX handler, so it must stand alone (reads its own post meta, no reliance
 * on loop-external variables).
 */
$is_sponsored = get_post_meta( get_the_ID(), '_sbek_is_sponsored', true );
$video_url    = get_post_meta( get_the_ID(), '_sbek_video_url', true );
$categories   = get_the_category();
$permalink    = sbek_localize_url( get_permalink() );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'scroll-reveal group flex flex-col overflow-hidden rounded-sm border border-gray-200 bg-white transition-shadow duration-200 hover:shadow-md ' . ( $is_sponsored ? 'border-t-2 border-t-amber-500' : '' ) ); ?>>
    <a href="<?php echo esc_url( $permalink ); ?>" class="relative block aspect-video overflow-hidden bg-gray-100" tabindex="-1" aria-hidden="true">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'medium', [ 'class' => 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105', 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
        <?php else : ?>
            <?php echo sbek_thumb_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <?php endif; ?>

        <?php if ( $video_url ) : ?>
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-black/50 pl-1 backdrop-blur-xs transition-colors group-hover:bg-sbek-red/80">
                    <svg class="h-6 w-6 fill-current text-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>
        <?php endif; ?>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <?php if ( $is_sponsored ) : ?>
            <span class="mb-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-600"><?php echo esc_html( t( 'sponsored' ) ); ?></span>
        <?php elseif ( ! empty( $categories ) ) : ?>
            <a href="<?php echo esc_url( sbek_localize_url( get_category_link( $categories[0]->term_id ) ) ); ?>" class="mb-1.5 text-[11px] font-bold uppercase tracking-wider text-sbek-red hover:underline"><?php echo esc_html( sbek_term_name( $categories[0] ) ); ?></a>
        <?php endif; ?>

        <h3 class="font-display text-lg font-bold leading-snug text-ink">
            <a href="<?php echo esc_url( $permalink ); ?>" class="transition-colors group-hover:text-sbek-red"><?php the_title(); ?></a>
        </h3>

        <div class="mt-auto flex items-center gap-2 pt-3 text-xs text-gray-500">
            <span><?php echo esc_html( get_the_date() ); ?></span>
            <span aria-hidden="true">•</span>
            <span><?php echo esc_html( t( 'time_ago', human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ) ); ?></span>
        </div>
    </div>
</article>
