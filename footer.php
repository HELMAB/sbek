<footer id="colophon" class="site-footer bg-gray-950 text-white pt-16 pb-8 mt-16 border-t-4 border-sabay-red">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
            <!-- Brand & About -->
            <div class="space-y-6">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-3xl font-bold text-sabay-red uppercase tracking-tighter">
                    Sabay Clone
                </a>
                <p class="text-gray-400 text-sm leading-relaxed">
                    The leading digital news source in Cambodia, providing the latest in Entertainment, Technology, Life, and Sport since 2026.
                </p>
                <div class="flex space-x-4">
                    <!-- Facebook -->
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors group">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                    </a>
                    <!-- Telegram -->
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-sky-500 transition-colors group">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M20.665 3.717l-17.73 6.837c-1.213.486-1.203 1.163-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701v3.164c.31 0 .447-.142.621-.311l1.488-1.445 3.093 2.284c.57.315.98.153 1.121-.527l2.031-9.57c.208-.83-.313-1.206-.861-.96z"/></svg>
                    </a>
                    <!-- TikTok -->
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors group">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.9-.32-1.98-.23-2.81.3-.64.4-.98 1.13-1.03 1.88-.12.82.26 1.67.96 2.1.51.35 1.15.44 1.76.39.81-.03 1.57-.45 2.01-1.13.44-.65.59-1.45.59-2.24l.01-11.49z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Categories -->
            <div>
                <h3 class="text-lg font-bold mb-6 border-l-4 border-sabay-red pl-3 uppercase"><?php echo esc_html( t( 'categories' ) ); ?></h3>
                <ul class="space-y-3 text-gray-400 text-sm">
                    <?php
                    $footer_cats = get_categories( array('number' => 6) );
                    foreach ( $footer_cats as $cat ) :
                    ?>
                        <li><a href="<?php echo esc_url( sabay_localize_url( get_category_link( $cat->term_id ) ) ); ?>" class="hover:text-sabay-red hover:pl-2 transition-all duration-300 flex items-center">
                            <span class="mr-2">&rsaquo;</span> <?php echo esc_html( sabay_term_name( $cat ) ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Useful Links -->
            <div>
                <h3 class="text-lg font-bold mb-6 border-l-4 border-sabay-red pl-3 uppercase"><?php echo esc_html( t( 'quick_links' ) ); ?></h3>
                <ul class="space-y-3 text-gray-400 text-sm">
                    <li><a href="#" class="hover:text-sabay-red transition-colors"><?php echo esc_html( t( 'about_us' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sabay-red transition-colors"><?php echo esc_html( t( 'advertise' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sabay-red transition-colors"><?php echo esc_html( t( 'privacy_policy' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sabay-red transition-colors"><?php echo esc_html( t( 'terms_of_use' ) ); ?></a></li>
                    <li><a href="#" class="hover:text-sabay-red transition-colors"><?php echo esc_html( t( 'contact_us' ) ); ?></a></li>
                </ul>
            </div>

            <!-- Contact/Newsletter -->
            <div>
                <h3 class="text-lg font-bold mb-6 border-l-4 border-sabay-red pl-3 uppercase"><?php echo esc_html( t( 'newsletter' ) ); ?></h3>
                <p class="text-gray-400 text-sm mb-4"><?php echo esc_html( t( 'newsletter_desc' ) ); ?></p>
                <form class="flex">
                    <input type="email" placeholder="<?php echo esc_attr( t( 'email_address' ) ); ?>" class="bg-gray-800 border-none rounded-l px-4 py-2 w-full text-sm focus:ring-1 focus:ring-sabay-red">
                    <button class="bg-sabay-red hover:bg-red-700 px-4 py-2 rounded-r transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-gray-500 text-xs">
            <p>&copy; <?php echo date('Y'); ?> Sabay Clone. Built for speed and performance.</p>
            <div class="mt-4 md:mt-0">
                <?php echo sabay_language_switcher(); ?>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-8 right-8 bg-sabay-red text-white w-12 h-12 rounded-full shadow-2xl flex items-center justify-center opacity-0 translate-y-10 transition-all duration-500 hover:bg-red-700 z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
    </button>
</footer>

<?php wp_footer(); ?>
</body>
</html>