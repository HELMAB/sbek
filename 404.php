<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-16 text-center">
    <p class="text-sbek-red text-6xl font-bold mb-4">404</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo esc_html( t( 'not_found_title' ) ); ?></h1>
    <p class="text-gray-500 mb-8"><?php echo esc_html( t( 'not_found_desc' ) ); ?></p>

    <div class="max-w-md mx-auto mb-8">
        <?php get_search_form(); ?>
    </div>

    <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="inline-block bg-sbek-red text-white font-bold py-3 px-8 rounded-full hover:bg-gray-900 transition-colors duration-300">
        <?php echo esc_html( t( 'back_to_home' ) ); ?>
    </a>

    <?php
    $sbek_404_popular = sbek_get_cached_query( array(
        'posts_per_page' => 4,
        'meta_key'       => '_sbek_trending_score',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ), 'sbek_404_popular_' . sbek_lang(), 3600 );

    if ( $sbek_404_popular->have_posts() ) :
        ?>
        <div class="mt-16 text-left">
            <h2 class="bg-gray-900 text-white px-4 py-1 text-lg font-bold uppercase inline-block mb-6"><?php echo esc_html( t( 'popular_now' ) ); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
