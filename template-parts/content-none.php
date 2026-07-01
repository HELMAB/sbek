<section class="mx-auto max-w-lg py-20 text-center">
    <p class="font-display text-6xl font-extrabold text-gray-200">404</p>
    <h2 class="mt-4 font-display text-2xl font-extrabold text-ink"><?php echo esc_html( t( 'no_posts_found' ) ); ?></h2>
    <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="mt-6 inline-flex items-center gap-1.5 rounded-full bg-ink px-6 py-3 font-bold text-white transition-colors hover:bg-sbek-red">
        <?php echo esc_html( t( 'back_to_home' ) ); ?>
        <?php echo sbek_icon( 'arrow-right', [ 'class' => 'h-4 w-4' ] ); ?>
    </a>
</section>
