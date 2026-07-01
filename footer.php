<footer id="colophon" class="site-footer bg-gray-950 text-white pt-16 pb-8 mt-16 border-t-4 border-sbek-red">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
            <!-- Brand & About -->
            <div class="space-y-6">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-3xl font-bold text-sbek-red uppercase tracking-tighter">
                    Sbek Clone
                </a>
                <p class="text-gray-400 text-sm leading-relaxed">
                    The leading digital news source in Cambodia, providing the latest in Entertainment, Technology, Life, and Sport since 2026.
                </p>
                <?php
                $sbek_socials = function_exists( 'sbek_site_setting' ) ? sbek_site_setting( 'socials', [] ) : [];
                if ( ! empty( $sbek_socials ) ) :
                    ?>
                    <div class="flex space-x-4">
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
                                class="w-10 h-10 bg-gray-800 text-white rounded-full flex items-center justify-center hover:bg-sbek-red transition-colors">
                                <?php echo sbek_icon( sbek_social_icon_name( $sbek_platform ), [ 'width' => 20, 'height' => 20 ] ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Categories -->
            <div>
                <h3 class="text-lg font-bold mb-6 border-l-4 border-sbek-red pl-3 uppercase"><?php echo esc_html( t( 'categories' ) ); ?></h3>
                <ul class="space-y-3 text-gray-400 text-sm">
                    <?php
                    $footer_cats = get_categories( array('number' => 6) );
                    foreach ( $footer_cats as $cat ) :
                    ?>
                        <li><a href="<?php echo esc_url( sbek_localize_url( get_category_link( $cat->term_id ) ) ); ?>" class="hover:text-sbek-red hover:pl-2 transition-all duration-300 flex items-center">
                            <span class="mr-2">&rsaquo;</span> <?php echo esc_html( sbek_term_name( $cat ) ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Useful Links -->
            <div>
                <h3 class="text-lg font-bold mb-6 border-l-4 border-sbek-red pl-3 uppercase"><?php echo esc_html( t( 'quick_links' ) ); ?></h3>
                <ul class="space-y-3 text-gray-400 text-sm">
                    <li><a href="#" class="hover:text-sbek-red transition-colors"><?php echo esc_html( t( 'about_us' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sbek-red transition-colors"><?php echo esc_html( t( 'advertise' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sbek-red transition-colors"><?php echo esc_html( t( 'privacy_policy' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sbek-red transition-colors"><?php echo esc_html( t( 'terms_of_use' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sbek-red transition-colors"><?php echo esc_html( t( 'contact_us' ) ); ?></a></li>
                </ul>
            </div>

            <!-- Contact/Newsletter -->
            <div>
                <h3 class="text-lg font-bold mb-6 border-l-4 border-sbek-red pl-3 uppercase"><?php echo esc_html( t( 'newsletter' ) ); ?></h3>
                <p class="text-gray-400 text-sm mb-4"><?php echo esc_html( t( 'newsletter_desc' ) ); ?></p>
                <form class="flex">
                    <input type="email" placeholder="<?php echo esc_attr( t( 'email_address' ) ); ?>" class="bg-gray-800 border-none rounded-l px-4 py-2 w-full text-sm focus:ring-1 focus:ring-sbek-red">
                    <button class="bg-sbek-red hover:bg-red-700 px-4 py-2 rounded-r transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-gray-500 text-xs">
            <p>&copy; <?php echo date('Y'); ?> Sbek Clone. Built for speed and performance.</p>
            <div class="mt-4 md:mt-0">
                <?php echo sbek_language_switcher(); ?>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-8 right-8 bg-sbek-red text-white w-12 h-12 rounded-full shadow-2xl flex items-center justify-center opacity-0 translate-y-10 transition-all duration-500 hover:bg-red-700 z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
    </button>
</footer>

<?php wp_footer(); ?>
</body>
</html>