<?php
/**
 * Sabay Clone functions and definitions
 */

if ( ! function_exists( 'sabay_clone_setup' ) ) :
    function sabay_clone_setup() {
        load_theme_textdomain( 'sabay-clone', get_template_directory() . '/languages' );

        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
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
            'menu-1'    => esc_html__( 'Primary (English)', 'sabay-clone' ),
            'menu-1-jp' => esc_html__( 'Primary (Japanese)', 'sabay-clone' ),
        ) );
    }
endif;
add_action( 'after_setup_theme', 'sabay_clone_setup' );

/**
 * Multilingual (i18n) — must load first so sabay_lang() is available
 */
require get_template_directory() . '/inc/i18n.php';

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
 * Mega Menu Walker
 */
require get_template_directory() . '/inc/frontend/class-mega-menu-walker.php';

/**
 * Performance & Caching
 */
require get_template_directory() . '/inc/frontend/performance.php';

/**
 * SEO
 */
require get_template_directory() . '/inc/frontend/seo.php';

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
function sabay_clone_scripts() {
    // Google Fonts for Khmer
    wp_enqueue_style( 'sabay-clone-fonts', 'https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@400;700&display=swap', array(), null );
    
    // Main Tailwind CSS
    wp_enqueue_style( 'sabay-clone-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0' );

    // Load More JS
    wp_enqueue_script( 'sabay-load-more', get_template_directory_uri() . '/assets/js/load-more.js', array(), '1.0.0', true );
    wp_localize_script( 'sabay-load-more', 'sabay_ajax', array(
        'ajax_url'       => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'sabay_load_more_nonce' ),
        'lang'           => sabay_lang(),
        'loading_text'   => t( 'loading' ),
        'no_more_text'   => t( 'no_more_posts' ),
        'load_more_text' => t( 'load_more' ),
    ) );

    // Motion Effects JS
    wp_enqueue_script( 'sabay-motion', get_template_directory_uri() . '/assets/js/motion.js', array(), '1.0.0', true );

    // Threaded comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'sabay_clone_scripts' );

/**
 * AJAX Load More Handler
 */
function sabay_ajax_load_more() {
    check_ajax_referer( 'sabay_load_more_nonce', 'nonce' );

    $paged = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 2;
    $lang  = isset( $_POST['lang'] ) ? sanitize_key( $_POST['lang'] ) : SABAY_DEFAULT_LANGUAGE;

    if ( ! in_array( $lang, SABAY_LANGUAGES, true ) ) {
        $lang = SABAY_DEFAULT_LANGUAGE;
    }

    // Set global so sabay_localize_url() works correctly inside templates
    $GLOBALS['sabay_lang'] = $lang;

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
add_action( 'wp_ajax_sabay_load_more', 'sabay_ajax_load_more' );
add_action( 'wp_ajax_nopriv_sabay_load_more', 'sabay_ajax_load_more' );

