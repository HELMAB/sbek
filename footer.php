<footer id="colophon" class="site-footer mt-16 border-t-4 border-sbek-red bg-gray-950 pt-16 pb-8 text-white">
    <div class="container mx-auto px-4">
        <div class="mb-12 grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-4">
            <!-- Brand & About -->
            <div class="space-y-6">
                <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="inline-block font-display text-3xl font-extrabold uppercase tracking-tight text-sbek-red">
                    Sbek
                </a>
                <p class="text-sm leading-relaxed text-gray-400">
                    <?php echo esc_html( t( 'footer_about' ) ); ?>
                </p>
                <?php
                $sbek_socials = function_exists( 'sbek_site_setting' ) ? sbek_site_setting( 'socials', [] ) : [];
                if ( ! empty( $sbek_socials ) ) :
                    ?>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ( $sbek_socials as $sbek_social ) :
                            if ( empty( $sbek_social['url'] ) ) {
                                continue;
                            }
                            $sbek_platform = $sbek_social['platform'] ?? '';
                            ?>
                            <a href="<?php echo esc_url( $sbek_social['url'] ); ?>"
                                target="_blank" rel="noopener noreferrer"
                                aria-label="<?php echo esc_attr( $sbek_platform ); ?>"
                                title="<?php echo esc_attr( $sbek_platform ); ?>"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 text-white transition-colors hover:bg-sbek-red">
                                <?php echo sbek_icon( sbek_social_icon_name( $sbek_platform ), [ 'width' => 20, 'height' => 20 ] ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Categories -->
            <div>
                <h3 class="mb-6 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                    <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'categories' ) ); ?>
                </h3>
                <ul class="space-y-3 text-sm text-gray-400">
                    <?php
                    $footer_cats = get_categories( array( 'number' => 6, 'parent' => 0 ) );
                    foreach ( $footer_cats as $cat ) :
                    ?>
                        <li><a href="<?php echo esc_url( sbek_localize_url( get_category_link( $cat->term_id ) ) ); ?>" class="group flex items-center transition-colors hover:text-sbek-red">
                            <span class="mr-2 text-sbek-red transition-transform duration-200 group-hover:translate-x-0.5">&rsaquo;</span><?php echo esc_html( sbek_term_name( $cat ) ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Useful Links -->
            <div>
                <h3 class="mb-6 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                    <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'quick_links' ) ); ?>
                </h3>
                <ul class="space-y-3 text-sm text-gray-400">
                    <li><a href="#" class="transition-colors hover:text-sbek-red"><?php echo esc_html( t( 'about_us' ) ); ?></a></li>
                    <li><a href="#" class="transition-colors hover:text-sbek-red"><?php echo esc_html( t( 'advertise' ) ); ?></a></li>
                    <li><a href="#" class="transition-colors hover:text-sbek-red"><?php echo esc_html( t( 'privacy_policy' ) ); ?></a></li>
                    <li><a href="#" class="transition-colors hover:text-sbek-red"><?php echo esc_html( t( 'terms_of_use' ) ); ?></a></li>
                    <li><a href="#" class="transition-colors hover:text-sbek-red"><?php echo esc_html( t( 'contact_us' ) ); ?></a></li>
                </ul>
            </div>

            <!-- Contact/Newsletter -->
            <div>
                <h3 class="mb-6 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                    <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'newsletter' ) ); ?>
                </h3>
                <p class="mb-4 text-sm text-gray-400"><?php echo esc_html( t( 'newsletter_desc' ) ); ?></p>
                <form class="flex" action="#" method="post">
                    <label for="sbek-newsletter-email" class="sr-only"><?php echo esc_html( t( 'email_address' ) ); ?></label>
                    <input type="email" id="sbek-newsletter-email" name="email" placeholder="<?php echo esc_attr( t( 'email_address' ) ); ?>" class="w-full rounded-l-sm border-none bg-gray-800 px-4 py-2.5 text-sm text-white placeholder:text-gray-500 focus:ring-1 focus:ring-sbek-red focus:outline-none">
                    <button type="submit" aria-label="<?php echo esc_attr( t( 'subscribe' ) ); ?>" class="flex items-center justify-center rounded-r-sm bg-sbek-red px-4 py-2.5 text-white transition-colors hover:bg-sbek-red/85">
                        <?php echo sbek_icon( 'mail', [ 'width' => 18, 'height' => 18 ] ); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-800 pt-8 text-xs text-gray-500 md:flex-row lg:pr-20">
            <p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Sbek. <?php echo esc_html( t( 'footer_tagline' ) ); ?></p>
            <div>
                <?php echo sbek_language_switcher(); ?>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" type="button" aria-label="<?php echo esc_attr( t( 'back_to_top' ) ); ?>" class="fixed right-6 bottom-6 z-50 flex h-12 w-12 translate-y-10 items-center justify-center rounded-full bg-sbek-red text-white opacity-0 shadow-2xl transition-all duration-500 hover:bg-sbek-red/85">
        <?php echo sbek_icon( 'arrow-up', [ 'width' => 22, 'height' => 22 ] ); ?>
    </button>
</footer>

<?php wp_footer(); ?>
</body>
</html>