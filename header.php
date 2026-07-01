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

<a class="skip-link sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:bg-white focus:text-sbek-red focus:px-4 focus:py-2 focus:rounded focus:shadow-lg" href="#primary"><?php echo esc_html( t( 'skip_to_content' ) ); ?></a>

<header id="masthead" class="site-header bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="site-branding">
            <a href="<?php echo esc_url( sbek_localize_url( home_url( '/' ) ) ); ?>" class="inline-flex items-center text-sbek-red text-3xl font-bold uppercase">
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
        <nav id="site-navigation" class="main-navigation hidden md:flex space-x-6 font-medium h-full" aria-label="<?php echo esc_attr( t( 'primary_navigation' ) ); ?>">
            <?php
            $menu_location = sbek_is_default_lang() ? 'menu-1' : 'menu-1-' . sbek_lang();
            wp_nav_menu( array(
                'theme_location' => $menu_location,
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'menu_class'     => 'flex space-x-6 h-full items-center',
                'walker'         => new Sbek_Mega_Menu_Walker(),
                'fallback_cb'    => false,
            ) );
            ?>
        </nav>
        <div class="ml-4">
            <?php echo sbek_language_switcher(); ?>
        </div>
    </div>
    
    <!-- Breaking News Ticker -->
    <div class="bg-gray-100 border-t border-b border-gray-200 py-1 overflow-hidden">
        <div class="container mx-auto px-4 flex items-center">
            <span class="bg-sbek-red text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase mr-4 whitespace-nowrap flex-shrink-0"><?php echo esc_html( t( 'breaking_news' ) ); ?></span>
            <div class="flex-grow overflow-hidden whitespace-nowrap">
                <div class="inline-block animate-marquee hover:pause whitespace-nowrap">
                    <?php
                    $breaking = sbek_get_cached_query( array(
                        'posts_per_page' => 5,
                        'meta_key'       => '_sbek_is_breaking',
                        'meta_value'     => 1,
                    ), 'sbek_breaking_news_' . sbek_lang(), 900 );

                    if ( ! $breaking->have_posts() ) {
                        $breaking = sbek_get_cached_query( array( 'posts_per_page' => 5 ), 'sbek_breaking_news_fallback_' . sbek_lang(), 900 );
                    }

                    if ( $breaking->have_posts() ) : while ( $breaking->have_posts() ) : $breaking->the_post();
                    ?>
                        <a href="<?php echo esc_url( sbek_localize_url( get_permalink() ) ); ?>" class="text-sm font-medium mr-8 hover:text-sbek-red inline-block">• <?php the_title(); ?></a>
                    <?php endwhile; wp_reset_postdata(); endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
