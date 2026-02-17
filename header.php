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

<header id="masthead" class="site-header bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="site-branding">
            <a href="<?php echo esc_url( sabay_localize_url( home_url( '/' ) ) ); ?>" class="text-sabay-red text-3xl font-bold uppercase">
                Sabay Clone
            </a>
        </div>
        <nav id="site-navigation" class="main-navigation hidden md:flex space-x-6 font-medium h-full">
            <?php
            $menu_location = sabay_is_default_lang() ? 'menu-1' : 'menu-1-' . sabay_lang();
            wp_nav_menu( array(
                'theme_location' => $menu_location,
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'menu_class'     => 'flex space-x-6 h-full items-center',
                'walker'         => new Sabay_Mega_Menu_Walker(),
                'fallback_cb'    => false,
            ) );
            ?>
        </nav>
        <div class="ml-4">
            <?php echo sabay_language_switcher(); ?>
        </div>
    </div>
    
    <!-- Breaking News Ticker -->
    <div class="bg-gray-100 border-t border-b border-gray-200 py-1 overflow-hidden">
        <div class="container mx-auto px-4 flex items-center">
            <span class="bg-sabay-red text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase mr-4 whitespace-nowrap flex-shrink-0"><?php echo esc_html( t( 'breaking_news' ) ); ?></span>
            <div class="flex-grow overflow-hidden whitespace-nowrap">
                <div class="inline-block animate-marquee hover:pause whitespace-nowrap">
                    <?php
                    $breaking = sabay_get_cached_query( array( 'posts_per_page' => 5 ), 'sabay_breaking_news_' . sabay_lang(), 900 );
                    if ( $breaking->have_posts() ) : while ( $breaking->have_posts() ) : $breaking->the_post();
                    ?>
                        <a href="<?php echo esc_url( sabay_localize_url( get_permalink() ) ); ?>" class="text-sm font-medium mr-8 hover:text-sabay-red inline-block">• <?php the_title(); ?></a>
                    <?php endwhile; wp_reset_postdata(); endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
