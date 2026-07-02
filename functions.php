<?php
/**
 * Sbek functions and definitions
 */

if ( ! function_exists( 'sbek_setup' ) ) :
    function sbek_setup() {
        load_theme_textdomain( 'sbek', get_template_directory() . '/languages' );

        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );

        // Capped hero rendition for single posts — the `full` size ships the
        // original upload, which on a news site can be a multi-MB photo.
        // Proportional (no crop) so editorial images keep their framing.
        // Older uploads need a thumbnail regeneration to pick this size up;
        // until then WordPress falls back to the nearest available size.
        add_image_size( 'sbek-hero', 1600, 0, false );
        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support( 'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ) );

        register_nav_menus( array(
            'menu-1'    => esc_html__( 'Primary (English)', 'sbek' ),
            'menu-1-jp' => esc_html__( 'Primary (Japanese)', 'sbek' ),
        ) );
    }
endif;
add_action( 'after_setup_theme', 'sbek_setup' );

function sbek_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Sbek Sidebar', 'sbek' ),
        'id'            => 'sbek-sidebar',
        'description'   => esc_html__( 'Displayed on single post pages, below the Trending panel.', 'sbek' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s bg-white p-6 rounded-sm shadow-xs mb-8">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="text-lg font-bold border-b-2 border-sbek-red pb-2 mb-4 uppercase">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'sbek_widgets_init' );

/**
 * Multilingual (i18n) — must load first so sbek_lang() is available
 */
require get_template_directory() . '/inc/i18n.php';

/**
 * Security hardening — shared by admin and frontend
 */
require get_template_directory() . '/inc/security.php';

/**
 * Multilingual Admin UI
 */
require get_template_directory() . '/inc/admin/i18n-admin.php';

/**
 * Inline SVG icons (Lucide) — shared by admin screens and frontend templates
 */
require get_template_directory() . '/inc/icons.php';

/**
 * Custom Fields
 */
require get_template_directory() . '/inc/admin/custom-fields.php';

/**
 * Mega Menu Walker (desktop) + Mobile drawer walker
 */
require get_template_directory() . '/inc/frontend/class-mega-menu-walker.php';
require get_template_directory() . '/inc/frontend/class-mobile-menu-walker.php';

/**
 * Reusable front-end components (ad slots, section headings)
 */
require get_template_directory() . '/inc/frontend/components.php';

/**
 * Single-article enhancements (heading anchors, table of contents)
 */
require get_template_directory() . '/inc/frontend/article.php';

/**
 * Performance & Caching
 */
require get_template_directory() . '/inc/frontend/performance.php';

/**
 * SEO
 */
require get_template_directory() . '/inc/frontend/seo.php';

/**
 * Search enhancements (category facet)
 */
require get_template_directory() . '/inc/frontend/search.php';

/**
 * Comments
 */
require get_template_directory() . '/inc/frontend/comments.php';

/**
 * Site Settings admin page
 */
require get_template_directory() . '/inc/admin/site-settings.php';

/**
 * Third-party integrations (GA4, GTM, custom scripts)
 */
require get_template_directory() . '/inc/frontend/integrations.php';

/**
 * Admin dashboard setup widget
 */
require get_template_directory() . '/inc/admin/dashboard-widget.php';

/**
 * Branded wp-login.php
 */
require get_template_directory() . '/inc/admin/login.php';

/**
 * Enqueue scripts and styles.
 */
function sbek_scripts() {
    // Google Fonts: Khmer body (Kantumruy Pro / Battambang) + Libre Franklin display headlines.
    wp_enqueue_style( 'sbek-fonts', 'https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@400;500;600;700&family=Libre+Franklin:wght@400;500;600;700;800;900&display=swap', array(), null );
    
    // Main Tailwind CSS
    wp_enqueue_style( 'sbek-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0' );

    // Load More JS
    wp_enqueue_script( 'sbek-load-more', get_template_directory_uri() . '/assets/js/load-more.js', array(), '1.0.0', true );
    wp_localize_script( 'sbek-load-more', 'sbek_ajax', array(
        'ajax_url'       => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'sbek_load_more_nonce' ),
        'lang'           => sbek_lang(),
        'loading_text'   => t( 'loading' ),
        'no_more_text'   => t( 'no_more_posts' ),
        'load_more_text' => t( 'load_more' ),
    ) );

    // Motion Effects JS
    wp_enqueue_script( 'sbek-motion', get_template_directory_uri() . '/assets/js/motion.js', array(), '1.0.0', true );

    // Mobile navigation drawer
    wp_enqueue_script( 'sbek-mobile-nav', get_template_directory_uri() . '/assets/js/mobile-nav.js', array(), '1.0.0', true );

    // Threaded comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'sbek_scripts' );

/**
 * AJAX Load More Handler
 */
function sbek_ajax_load_more() {
    check_ajax_referer( 'sbek_load_more_nonce', 'nonce' );

    $paged = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 2;
    $lang  = isset( $_POST['lang'] ) ? sanitize_key( $_POST['lang'] ) : SBEK_DEFAULT_LANGUAGE;

    if ( ! in_array( $lang, SBEK_LANGUAGES, true ) ) {
        $lang = SBEK_DEFAULT_LANGUAGE;
    }

    // Set global so sbek_localize_url() works correctly inside templates
    $GLOBALS['sbek_lang'] = $lang;

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 4,
        'paged'          => $paged,
        'tax_query'      => array(
            array(
                'taxonomy' => 'language',
                'field'    => 'slug',
                'terms'    => array( $lang ),
            ),
        ),
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) : $query->the_post();
            get_template_part( 'template-parts/content-grid' );
        endwhile;
    endif;

    wp_reset_postdata();
    die();
}
add_action( 'wp_ajax_sbek_load_more', 'sbek_ajax_load_more' );
add_action( 'wp_ajax_nopriv_sbek_load_more', 'sbek_ajax_load_more' );

