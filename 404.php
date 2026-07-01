<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-16">
    <div class="mx-auto max-w-lg text-center">
        <p class="font-display text-7xl font-extrabold leading-none text-gray-200 sm:text-8xl">404</p>
        <h1 class="mt-4 font-display text-2xl font-extrabold text-ink sm:text-3xl"><?php echo esc_html( t( 'not_found_title' ) ); ?></h1>
        <p class="mt-3 text-gray-500"><?php echo esc_html( t( 'not_found_desc' ) ); ?></p>

        <div class="mx-auto mt-8 max-w-md">
            <?php get_search_form(); ?>
        </div>

        <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="mt-6 inline-flex items-center gap-1.5 rounded-full bg-ink px-6 py-3 font-bold text-white transition-colors hover:bg-sbek-red">
            <?php echo esc_html( t( 'back_to_home' ) ); ?>
            <?php echo sbek_icon( 'arrow-right', [ 'class' => 'h-4 w-4' ] ); ?>
        </a>
    </div>

    <?php
    $sbek_404_popular = sbek_get_cached_query( array(
        'posts_per_page' => 4,
        'meta_key'       => '_sbek_trending_score',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ), 'sbek_404_popular_' . sbek_lang(), 3600 );

    if ( $sbek_404_popular->have_posts() ) :
        ?>
        <div class="mx-auto mt-16 max-w-6xl">
            <?php echo sbek_section_heading( t( 'popular_now' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <?php
                while ( $sbek_404_popular->have_posts() ) :
                    $sbek_404_popular->the_post();
                    get_template_part( 'template-parts/content-grid' );
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
