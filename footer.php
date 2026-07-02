<?php
$sbek_footer_desc    = function_exists( 'sbek_site_setting' ) ? sbek_site_setting( 'description', '' ) : '';
$sbek_footer_desc    = $sbek_footer_desc ?: t( 'footer_about' );
$sbek_socials        = function_exists( 'sbek_site_setting' ) ? sbek_site_setting( 'socials', [] ) : [];
$sbek_phones         = function_exists( 'sbek_site_setting' ) ? sbek_site_setting( 'phones', [] ) : [];
$sbek_footer_menu    = sbek_is_default_lang() ? 'footer-menu' : 'footer-menu-' . sbek_lang();
$sbek_has_footer_nav = has_nav_menu( $sbek_footer_menu );
$sbek_fallback_links = $sbek_has_footer_nav ? [] : sbek_footer_fallback_links();
$sbek_show_links_col = $sbek_has_footer_nav || ! empty( $sbek_fallback_links );
$sbek_sub_notice     = function_exists( 'sbek_subscribe_notice' ) ? sbek_subscribe_notice() : null;
?>
<footer id="colophon" class="site-footer relative mt-16 overflow-hidden border-t-4 border-sbek-red bg-gray-950 text-white">
    <h2 class="sr-only"><?php echo esc_html( t( 'footer_nav_label' ) ); ?></h2>

    <!-- Faint hatch texture for depth on the flat dark ground. -->
    <div class="sbek-footer-texture pointer-events-none absolute inset-0 opacity-[0.04]" aria-hidden="true"></div>

    <!-- Full-width back-to-top divider (replaces the fixed-button margin hack). -->
    <a href="#masthead" class="group relative flex items-center justify-center gap-2 border-b border-gray-800 py-4 text-xs font-bold uppercase tracking-widest text-gray-400 transition-colors hover:text-sbek-red">
        <?php echo sbek_icon( 'arrow-up', [ 'class' => 'h-4 w-4 transition-transform duration-200 group-hover:-translate-y-0.5' ] ); ?>
        <?php echo esc_html( t( 'back_to_top' ) ); ?>
    </a>

    <div class="relative container mx-auto px-4 pt-14 pb-8">
        <div class="mb-12 grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-12 lg:gap-12">
            <!-- Brand & About -->
            <div class="space-y-6 lg:col-span-4">
                <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="block font-display text-5xl font-black uppercase leading-none tracking-tighter text-white transition-colors hover:text-sbek-red sm:text-6xl">
                    <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                </a>
                <p class="max-w-sm text-sm leading-relaxed text-gray-400">
                    <?php echo esc_html( $sbek_footer_desc ); ?>
                </p>
                <?php if ( ! empty( $sbek_socials ) ) : ?>
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
            <div class="lg:col-span-2">
                <h3 class="mb-6 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                    <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'categories' ) ); ?>
                </h3>
                <ul class="space-y-3 text-sm text-gray-400">
                    <?php foreach ( sbek_footer_categories() as $cat ) : ?>
                        <li><a href="<?php echo esc_url( sbek_localize_url( get_category_link( $cat->term_id ) ) ); ?>" class="group flex items-center transition-colors hover:text-sbek-red">
                            <span class="mr-2 text-sbek-red transition-transform duration-200 group-hover:translate-x-0.5">&rsaquo;</span><?php echo esc_html( sbek_term_name( $cat ) ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Site links (editor-managed footer menu, with a graceful fallback) -->
            <?php if ( $sbek_show_links_col ) : ?>
            <div class="lg:col-span-2">
                <h3 class="mb-6 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                    <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'quick_links' ) ); ?>
                </h3>
                <?php if ( $sbek_has_footer_nav ) : ?>
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => $sbek_footer_menu,
                        'container'      => false,
                        'menu_class'     => 'sbek-footer-menu space-y-3 text-sm text-gray-400',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                <?php else : ?>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <?php foreach ( $sbek_fallback_links as $sbek_link ) : ?>
                            <li><a href="<?php echo esc_url( $sbek_link['url'] ); ?>" class="transition-colors hover:text-sbek-red"><?php echo esc_html( $sbek_link['label'] ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Contact + Newsletter -->
            <div class="space-y-8 lg:col-span-4">
                <?php if ( ! empty( $sbek_phones ) ) : ?>
                    <div>
                        <h3 class="mb-4 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                            <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'get_in_touch' ) ); ?>
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <?php foreach ( $sbek_phones as $sbek_phone ) :
                                if ( empty( $sbek_phone['number'] ) ) {
                                    continue;
                                }
                                ?>
                                <li>
                                    <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $sbek_phone['number'] ) ); ?>" class="group flex items-center gap-2.5 transition-colors hover:text-sbek-red">
                                        <?php echo sbek_icon( 'phone', [ 'class' => 'h-4 w-4 shrink-0 text-sbek-red' ] ); ?>
                                        <span>
                                            <?php if ( ! empty( $sbek_phone['label'] ) ) : ?>
                                                <span class="mr-1.5 text-xs uppercase tracking-wider text-gray-500"><?php echo esc_html( $sbek_phone['label'] ); ?></span>
                                            <?php endif; ?>
                                            <?php echo esc_html( $sbek_phone['number'] ); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div>
                    <h3 class="mb-4 flex items-center gap-2.5 font-display text-lg font-extrabold uppercase tracking-tight">
                        <span class="inline-block h-5 w-1.5 shrink-0 bg-sbek-red"></span><?php echo esc_html( t( 'newsletter' ) ); ?>
                    </h3>
                    <p class="mb-4 text-sm text-gray-400"><?php echo esc_html( t( 'newsletter_desc' ) ); ?></p>

                    <?php if ( $sbek_sub_notice ) : ?>
                        <p class="mb-4 rounded-sm px-3 py-2 text-sm <?php echo $sbek_sub_notice[1] ? 'bg-sbek-red/15 text-sbek-red' : 'bg-emerald-500/15 text-emerald-400'; ?>" role="status">
                            <?php echo esc_html( $sbek_sub_notice[0] ); ?>
                        </p>
                    <?php endif; ?>

                    <form class="flex" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                        <input type="hidden" name="action" value="sbek_subscribe">
                        <?php wp_nonce_field( 'sbek_subscribe', 'sbek_newsletter_nonce' ); ?>
                        <!-- Honeypot: hidden from users, tempting to bots. -->
                        <p class="hidden" aria-hidden="true">
                            <label>Website<input type="text" name="sbek_website" tabindex="-1" autocomplete="off"></label>
                        </p>
                        <label for="sbek-newsletter-email" class="sr-only"><?php echo esc_html( t( 'email_address' ) ); ?></label>
                        <input type="email" id="sbek-newsletter-email" name="email" required placeholder="<?php echo esc_attr( t( 'email_address' ) ); ?>" class="w-full rounded-l-sm border-none bg-gray-800 px-4 py-2.5 text-sm text-white placeholder:text-gray-500 focus:ring-1 focus:ring-sbek-red focus:outline-none">
                        <button type="submit" aria-label="<?php echo esc_attr( t( 'subscribe' ) ); ?>" class="flex items-center justify-center rounded-r-sm bg-sbek-red px-4 py-2.5 text-white transition-colors hover:bg-sbek-red/85">
                            <?php echo sbek_icon( 'mail', [ 'width' => 18, 'height' => 18 ] ); ?>
                        </button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500"><?php echo esc_html( t( 'newsletter_privacy' ) ); ?></p>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-800 pt-8 text-xs text-gray-500 md:flex-row">
            <p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php echo esc_html( t( 'footer_tagline' ) ); ?></p>
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
