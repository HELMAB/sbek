<?php
/**
 * Sabay Clone functions and definitions
 */

if ( ! function_exists( 'sabay_clone_setup' ) ) :
    function sabay_clone_setup() {
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
            'menu-1' => esc_html__( 'Primary', 'sabay-clone' ),
        ) );
    }
endif;
add_action( 'after_setup_theme', 'sabay_clone_setup' );

/**
 * Custom Fields
 */
require get_template_directory() . '/inc/custom-fields.php';

/**
 * Mega Menu Walker
 */
require get_template_directory() . '/inc/class-mega-menu-walker.php';

/**
 * Performance & Caching
 */
require get_template_directory() . '/inc/performance.php';

/**
 * SEO
 */
require get_template_directory() . '/inc/seo.php';

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
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'sabay_load_more_nonce' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'sabay_clone_scripts' );

/**
 * AJAX Load More Handler
 */
function sabay_ajax_load_more() {
    check_ajax_referer( 'sabay_load_more_nonce', 'nonce' );

    $paged = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 2;
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 4, // Match your grid count
        'paged'          => $paged
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

