<?php get_header(); ?>

<main id="primary" class="site-main container mx-auto px-4 py-24 text-center">
    <p class="text-sabay-red text-6xl font-bold mb-4">404</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo esc_html( t( 'not_found_title' ) ); ?></h1>
    <p class="text-gray-500 mb-8"><?php echo esc_html( t( 'not_found_desc' ) ); ?></p>
    <a href="<?php echo esc_url( sabay_localize_url( home_url( '/' ) ) ); ?>" class="inline-block bg-sabay-red text-white font-bold py-3 px-8 rounded-full hover:bg-gray-900 transition-colors duration-300">
        <?php echo esc_html( t( 'back_to_home' ) ); ?>
    </a>
</main>

<?php get_footer(); ?>
