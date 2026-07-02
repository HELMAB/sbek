<?php get_header(); ?>

<main id="primary" class="site-main pb-12">

    <!-- Top leaderboard ad -->
    <div class="container mx-auto px-4 pt-6">
        <?php echo sbek_ad_slot( 'leaderboard' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
    </div>

    <!-- Top stories: lead hero + secondaries, with a Trending rail -->
    <section class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-12 gap-x-8 gap-y-8">
            <div class="col-span-12 lg:col-span-8">
                <?php
                $sbek_hero = new WP_Query( array( 'posts_per_page' => 1, 'ignore_sticky_posts' => true ) );
                if ( $sbek_hero->have_posts() ) : while ( $sbek_hero->have_posts() ) : $sbek_hero->the_post();
                    $sbek_hero_cats = get_the_category();
                ?>
                    <article class="group relative mb-6 overflow-hidden rounded-sm bg-gray-900">
                        <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="block">
                            <div class="relative aspect-[16/10] overflow-hidden sm:aspect-[16/9] lg:aspect-[16/8]">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'large', [ 'class' => 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105', 'loading' => 'eager', 'fetchpriority' => 'high' ] ); ?>
                                <?php else : ?>
                                    <?php echo sbek_thumb_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/25 to-transparent"></div>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                <span class="mb-3 inline-block bg-sbek-red px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white"><?php echo esc_html( t( 'featured' ) ); ?></span>
                                <h2 class="font-display text-2xl font-extrabold leading-tight text-white sm:text-3xl lg:text-4xl">
                                    <span class="transition-colors group-hover:text-sbek-red/90"><?php the_title(); ?></span>
                                </h2>
                                <p class="mt-3 hidden max-w-2xl text-sm leading-relaxed text-gray-200 sm:line-clamp-2"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
                            </div>
                        </a>
                    </article>
                <?php endwhile; wp_reset_postdata(); endif; ?>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <?php
                    $sbek_secondary = new WP_Query( array( 'posts_per_page' => 3, 'offset' => 1, 'ignore_sticky_posts' => true ) );
                    if ( $sbek_secondary->have_posts() ) : while ( $sbek_secondary->have_posts() ) : $sbek_secondary->the_post();
                        get_template_part( 'template-parts/content-grid' );
                    endwhile; wp_reset_postdata(); endif;
                    ?>
                </div>
            </div>

            <aside class="col-span-12 space-y-6 lg:col-span-4">
                <?php get_template_part( 'template-parts/trending', null, array( 'limit' => 6 ) ); ?>

                <?php echo sbek_ad_slot( 'rectangle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </aside>
        </div>
    </section>

    <!-- Category section modules (top-level sections with posts) -->
    <?php
    // Ordered by term_id (their creation order), which mirrors the primary
    // nav order (World, Politics, Business, Technology, Sports, …) so the
    // homepage modules lead with the same sections as the menu.
    $sbek_sections = get_terms( array(
        'taxonomy'   => 'category',
        'parent'     => 0,
        'hide_empty' => true,
        'exclude'    => array( (int) get_option( 'default_category' ) ),
        'number'     => 5,
        'orderby'    => 'term_id',
        'order'      => 'ASC',
    ) );

    $sbek_module_i = 0;
    if ( ! is_wp_error( $sbek_sections ) ) :
        foreach ( $sbek_sections as $sbek_section_cat ) :
            get_template_part( 'template-parts/section-module', null, array( 'category' => $sbek_section_cat ) );
            $sbek_module_i++;

            // Drop an in-feed ad partway down the section stack.
            if ( 2 === $sbek_module_i ) :
                ?>
                <div class="container mx-auto px-4 py-2">
                    <?php echo sbek_ad_slot( 'in-feed' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>
                <?php
            endif;
        endforeach;
    endif;
    ?>

    <!-- Latest News (infinite scroll). Grid size (4) must match the AJAX handler's per-page. -->
    <section class="container mx-auto px-4 py-6">
        <?php echo sbek_section_heading( t( 'latest_news' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <div id="news-grid-container" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            $sbek_latest = new WP_Query( array( 'posts_per_page' => 4, 'paged' => 1, 'ignore_sticky_posts' => true ) );
            if ( $sbek_latest->have_posts() ) : while ( $sbek_latest->have_posts() ) : $sbek_latest->the_post();
                get_template_part( 'template-parts/content-grid' );
            endwhile; wp_reset_postdata(); endif;
            ?>
        </div>

        <div class="mt-10 text-center">
            <button id="load-more-posts" class="rounded-full bg-ink px-8 py-3 font-bold text-white transition-colors duration-200 hover:bg-sbek-red">
                <?php echo esc_html( t( 'load_more' ) ); ?>
            </button>
        </div>
    </section>

</main>

<?php get_footer(); ?>
