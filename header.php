<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-50 font-khmer opacity-0 animate-fade-in'); ?>>
<?php wp_body_open(); ?>

<a class="skip-link sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-100 focus:bg-white focus:text-sbek-red focus:px-4 focus:py-2 focus:rounded-sm focus:shadow-lg" href="#primary"><?php echo esc_html( t( 'skip_to_content' ) ); ?></a>

<!-- Dateline bar: scrolls away with the page; the masthead below stays sticky. -->
<div class="bg-gray-950 text-gray-300">
    <div class="container mx-auto flex h-8 items-center justify-between px-4 text-xs">
        <span class="font-semibold tracking-wide"><?php echo esc_html( date_i18n( 'l, j F Y' ) ); ?></span>
        <span class="inline-flex items-center gap-1.5 font-bold text-white">
            <span class="h-1.5 w-1.5 rounded-full bg-sbek-red"></span>
            <?php echo esc_html( strtoupper( sbek_lang() ) ) . ' ' . esc_html( t( 'edition' ) ); ?>
        </span>
    </div>
</div>

<header id="masthead" class="site-header sticky top-0 z-50 bg-white">
    <div class="container mx-auto h-20 px-4 flex justify-between items-stretch relative">
        <div class="site-branding flex items-center gap-1">
            <button type="button" class="mobile-nav-open md:hidden -ml-2 flex h-11 w-11 shrink-0 items-center justify-center text-ink" aria-controls="mobile-nav" aria-expanded="false">
                <span class="sr-only"><?php echo esc_attr( t( 'open_menu' ) ); ?></span>
                <?php echo sbek_icon( 'menu', [ 'class' => 'h-6 w-6' ] ); ?>
            </button>
            <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="inline-flex items-center font-display text-sbek-red text-2xl sm:text-3xl font-extrabold uppercase tracking-tight">
                <?php
                $sbek_logo = function_exists( 'sbek_site_setting_image_url' ) ? sbek_site_setting_image_url( 'logo', 'medium' ) : '';
                if ( $sbek_logo ) :
                    ?>
                    <img src="<?php echo esc_url( $sbek_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="h-10 w-auto">
                <?php else : ?>
                    <?php bloginfo( 'name' ); ?>
                <?php endif; ?>
            </a>
        </div>
        <!--
            items-stretch above (instead of items-center) + h-full on nav/li/a below
            makes each top-level link span the header row's FULL height, not just
            its own text — otherwise there's a dead strip between the link and the
            dropdown where the mouse loses :hover mid-travel and can never reach
            (let alone click) the mega-menu panel.
        -->
        <nav id="site-navigation" class="main-navigation hidden md:flex h-full items-center gap-x-8" aria-label="<?php echo esc_attr( t( 'primary_navigation' ) ); ?>">
            <?php
            $menu_location = sbek_is_default_lang() ? 'menu-1' : 'menu-1-' . sbek_lang();
            wp_nav_menu( array(
                'theme_location' => $menu_location,
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'menu_class'     => 'flex h-full items-stretch gap-x-8',
                'walker'         => new Sbek_Mega_Menu_Walker(),
                'fallback_cb'    => false,
            ) );
            ?>
        </nav>
        <div class="ml-4 flex items-center">
            <?php echo sbek_language_switcher(); ?>
        </div>
    </div>

    <!-- Breaking News Ticker -->
    <?php
    $breaking = sbek_get_cached_query( array(
        'posts_per_page' => 5,
        'meta_key'       => '_sbek_is_breaking',
        'meta_value'     => 1,
    ), 'sbek_breaking_news_' . sbek_lang(), 900 );

    if ( ! $breaking->have_posts() ) {
        $breaking = sbek_get_cached_query( array( 'posts_per_page' => 5 ), 'sbek_breaking_news_fallback_' . sbek_lang(), 900 );
    }

    // Build the item run once, then render it twice so the marquee loops with
    // no empty gap (paired with the -50% keyframe in input.css).
    $sbek_ticker = '';
    if ( $breaking->have_posts() ) :
        while ( $breaking->have_posts() ) : $breaking->the_post();
            $sbek_ticker .= '<a href="' . esc_url( sbek_localize_url( get_permalink() ) ) . '" class="mr-8 whitespace-nowrap text-sm font-medium hover:text-sbek-red">&bull; ' . esc_html( get_the_title() ) . '</a>';
        endwhile;
        wp_reset_postdata();
    endif;

    if ( $sbek_ticker ) :
    ?>
    <div class="overflow-hidden border-t border-b border-gray-200 bg-gray-100 py-1">
        <div class="container mx-auto flex items-center px-4">
            <span class="mr-4 shrink-0 whitespace-nowrap rounded-sm bg-sbek-red px-2 py-0.5 text-[10px] font-bold uppercase text-white"><?php echo esc_html( t( 'breaking_news' ) ); ?></span>
            <div class="grow overflow-hidden">
                <div class="flex w-max animate-marquee">
                    <div class="flex shrink-0"><?php echo $sbek_ticker; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                    <div class="flex shrink-0" aria-hidden="true"><?php echo $sbek_ticker; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</header>

<!-- Mobile navigation drawer (md:hidden). Behavior in assets/js/mobile-nav.js. -->
<div id="mobile-nav" class="mobile-nav fixed inset-0 z-[60] md:hidden" aria-hidden="true">
    <div class="mobile-nav-backdrop absolute inset-0 bg-black/50"></div>
    <div class="mobile-nav-panel absolute inset-y-0 left-0 flex w-[85%] max-w-sm flex-col bg-white shadow-2xl" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( t( 'primary_navigation' ) ); ?>">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5">
            <span class="font-display text-lg font-extrabold uppercase tracking-tight text-sbek-red"><?php echo esc_html( t( 'menu_label' ) ); ?></span>
            <button type="button" class="mobile-nav-close -mr-2 flex h-11 w-11 items-center justify-center text-gray-500 hover:text-sbek-red">
                <span class="sr-only"><?php echo esc_attr( t( 'close_menu' ) ); ?></span>
                <?php echo sbek_icon( 'x-close', [ 'class' => 'h-6 w-6' ] ); ?>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto" aria-label="<?php echo esc_attr( t( 'primary_navigation' ) ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location' => $menu_location,
                'menu_id'        => 'mobile-menu',
                'container'      => false,
                'menu_class'     => 'flex flex-col',
                'walker'         => new Sbek_Mobile_Menu_Walker(),
                'fallback_cb'    => false,
            ) );
            ?>
        </nav>
        <div class="shrink-0 border-t border-gray-200 px-5 py-4">
            <?php echo sbek_language_switcher(); ?>
        </div>
    </div>
</div>
