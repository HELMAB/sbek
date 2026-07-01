<?php
/**
 * Homepage section module: a category's lead story plus a headline list.
 *
 * Expects $args['category'] — a WP_Term (category). Renders nothing if the
 * category has no posts in the current language.
 */
$sbek_cat = $args['category'] ?? null;
if ( ! $sbek_cat instanceof WP_Term ) {
	return;
}

$sbek_section = new WP_Query( array(
	'cat'            => $sbek_cat->term_id,
	'posts_per_page' => 5,
	'ignore_sticky_posts' => true,
) );

if ( ! $sbek_section->have_posts() ) {
	return;
}

$sbek_cat_url = sbek_localize_url( get_category_link( $sbek_cat->term_id ) );
?>
<section class="scroll-reveal container mx-auto px-4 py-6">
    <?php echo sbek_section_heading( sbek_term_name( $sbek_cat ), $sbek_cat_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="grid grid-cols-12 gap-x-8 gap-y-6">
        <?php
        $sbek_section->the_post();
        $sbek_lead_video = get_post_meta( get_the_ID(), '_sbek_video_url', true );
        ?>
        <!-- Lead story -->
        <article class="col-span-12 group lg:col-span-7">
            <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="relative mb-4 block aspect-video overflow-hidden rounded-sm bg-gray-100">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'large', [ 'class' => 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105', 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
                <?php else : ?>
                    <?php echo sbek_thumb_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                <?php endif; ?>
                <?php if ( $sbek_lead_video ) : ?>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-black/50 pl-1 backdrop-blur-xs transition-colors group-hover:bg-sbek-red/80">
                            <svg class="h-7 w-7 fill-current text-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                <?php endif; ?>
            </a>
            <h3 class="font-display text-2xl font-extrabold leading-tight text-ink lg:text-3xl">
                <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="transition-colors group-hover:text-sbek-red"><?php the_title(); ?></a>
            </h3>
            <p class="mt-2 line-clamp-2 text-[15px] leading-relaxed text-gray-600"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
            <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                <span><?php echo esc_html( get_the_date() ); ?></span>
                <span aria-hidden="true">•</span>
                <span><?php echo esc_html( t( 'time_ago', human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ) ); ?></span>
            </div>
        </article>

        <!-- Secondary headline list -->
        <div class="col-span-12 divide-y divide-gray-200 lg:col-span-5 lg:border-l lg:border-gray-200 lg:pl-8">
            <?php while ( $sbek_section->have_posts() ) : $sbek_section->the_post(); ?>
                <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="group flex gap-4 py-4 first:pt-0">
                    <div class="min-w-0 flex-1">
                        <h4 class="font-display text-base font-bold leading-snug text-ink transition-colors group-hover:text-sbek-red"><?php the_title(); ?></h4>
                        <span class="mt-1 block text-xs text-gray-500"><?php echo esc_html( t( 'time_ago', human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ) ); ?></span>
                    </div>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="relative aspect-video w-24 shrink-0 overflow-hidden rounded-sm bg-gray-100">
                            <?php the_post_thumbnail( 'thumbnail', [ 'class' => 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105', 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php
wp_reset_postdata();
